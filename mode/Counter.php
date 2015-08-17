<?php
/**
 * 使用说明
 *
 * <b>简介</b>
 * <pre>
 *   使用一个db_single/db_one/db_split类型的表来进行计数持久化存储，使用 memcache 作为计数器的缓冲
 * </pre>
 *
 * <b>数据库例表</b>
 * <pre>
 *   CREATE TABLE s_zhangchu_counter(
 *     action varchar(64) not null default '',
 *     times int not null default 0,
 *     mtime timestamp NOT NULL default 0,
 *     unique(action)
 *   )ENGINE=InnoDB DEFAULT CHARSET=latin1;
 *   or
 *   CREATE TABLE s_zhangchu_counterex_0(
 *     uid int not null default 0,
 *     action varchar(64) not null default '',
 *     times int not null default 0,
 *     mtime timestamp NOT NULL default 0,
 *     unique(uid, action)
 *   )ENGINE=InnoDB DEFAULT CHARSET=latin1;
 * </pre>
 *
 * <b>_aConf 配置</b>
 * @see Ko_Mode_Counter::$_aConf
 *
 * @package ko\mode
 * @author zhangchu
 */

/**
 * 计数器
 */
class Ko_Mode_Counter extends Ko_Busi_Api
{
	const DEFAULT_STEP = 100;
	const DEFAULT_SECOND = 3600;

	/**
	 * 配置数组
	 *
	 * <pre>
	 * array(
	 *   'db' => 数据库 Dao 名称
	 *   'mc' => 使用的 memcache Dao 名称
	 *   'forbidmc' => 是否禁止 MC 做缓冲，缺省 false，当每个计数量都不大时，可以设置为 true
	 *   'step' => memcache 中的数据超过这个值，自动同步到数据库，缺省为 DEFAULT_STEP
	 *   'second' => 超过这个秒数的条目，自动同步memcache中的值到数据库，缺省为 DEFAULT_SECOND
	 * )
	 * </pre>
	 *
	 * @var array
	 */
	protected $_aConf = array();

	private $_bForbidMC;
	private $_iStep;
	private $_iSecond;

	public function __construct()
	{
		$this->_bForbidMC = isset($this->_aConf['forbidmc']) ? $this->_aConf['forbidmc'] : false;
		$this->_iStep = strlen($this->_aConf['step']) ? intval($this->_aConf['step']) : self::DEFAULT_STEP;
		$this->_iSecond = strlen($this->_aConf['second']) ? intval($this->_aConf['second']) : self::DEFAULT_SECOND;
	}

	/**
	 * 增加计数
	 *
	 * 增加计数值，为了承担较大规模的并发计数，使用先将数据增加记录在 MC 中。
	 * 只有 MC 里面的值达到或超过 step 值，才进行同步到数据库。
	 * 但是，同步到数据库是分为三步，查询 MC 的值 --> 增加数据库值 --> 减少 MC 的值
	 * 在并发情况下，由于 MC 中的值无法减为负值，可能会导致第三步无法按预期执行，导致计数超过实际的值
	 * 为了尽量减少上述情况发生的概率，可以在允许的情况下尽量提高 step / value 的大小
	 *
	 * @return int
	 */
	public function iIncrement($vKey, $iValue = 1, $iStep = 0)
	{
		if ($this->_bForbidMC)
		{
			$this->_vIncrDb($vKey, $iValue);
			return 0;
		}
		
		if ($iStep <= 0)
		{
			$iStep = $this->_iStep;
		}
		
		$mcDao = $this->_aConf['mc'].'Dao';
		$key = $this->_sGetMCKey($vKey);
		$iRet = $this->$mcDao->iIncrementEx($key, $iValue);
		$iMod = $iRet % $iStep;
		if ($iMod < $iValue)
		{	//如果增加的值跨越临界值 step 的倍数，同步 MC 值到 DB
			$this->_iMC2DB($vKey);
		}
		return $iRet;
	}

	/**
	 * 查询计数（数据库里的值 + memcache 的值）
	 *
	 * @return int
	 */
	public function iGet($vKey, $bMC2DB = false)
	{
		if ($this->_bForbidMC)
		{
			$mcinfo = 0;
		}
		else
		{
			$key = $this->_sGetMCKey($vKey);
			$mcDao = $this->_aConf['mc'].'Dao';
			$mcinfo = intval($this->$mcDao->vGet($key));
		}
		$dbDao = $this->_aConf['db'].'Dao';
		$dbinfo = $this->$dbDao->aGet($vKey);
		if ($mcinfo && $bMC2DB)
		{
			$this->_iMC2DB($vKey);
		}
		if (empty($dbinfo))
		{
			return $mcinfo;
		}
		return $dbinfo['times'] + $mcinfo;
	}
	
	/**
	 * 批量查询
	 *
	 * @return array
	 */
	public function aGetMulti($aKey)
	{
		$keys = array();
		if ($this->_bForbidMC)
		{
			$mcinfo = array();
		}
		else
		{
			foreach ($aKey as $i => $key)
			{
				$keys[$i] = $this->_sGetMCKey($key);
			}
			$mcDao = $this->_aConf['mc'].'Dao';
			$mcinfo = $this->$mcDao->vGet($keys);
		}
		$dbDao = $this->_aConf['db'].'Dao';
		$dbinfo = $this->$dbDao->aGetDetails($aKey, '', '', false);
		$ret = array();
		foreach ($aKey as $i => $key)
		{
			$index = $this->$dbDao->aGetIndexValue($key);
			$ret[implode(':', array_map('urlencode', $index))] = $dbinfo[$i]['times'] + $mcinfo[$keys[$i]];
		}
		return $ret;
	}

	/**
	 * 扫描数据库中长期未更新的数据，进行同步
	 */
	public function vSyncAll()
	{
		if ($this->_bForbidMC)
		{
			return;
		}
		$dbDao = $this->_aConf['db'].'Dao';
		$sql = 'select * from '.$this->$dbDao->sGetTableName().' where mtime < DATE_SUB(NOW(), INTERVAL '.$this->_iSecond.' SECOND)';
		$this->$dbDao->vDoFetchSelect($sql, array($this, '_vSyncAll_Callback'));
	}

	public function _vSyncAll_Callback($aInfo, $iNo)
	{
		$dbDao = $this->_aConf['db'].'Dao';
		$mcDao = $this->_aConf['mc'].'Dao';
		$key = $this->_sGetMCKey($aInfo);

		$mcinfo = intval($this->$mcDao->vGet($key));
		if ($mcinfo)
		{
			$update = array(
				'mtime' => date('Y-m-d H:i:s'),
				);
			$change = array(
				'times' => $mcinfo,
				);
			$oOption = new Ko_Tool_SQL;
			$oOption->oWhere('mtime = ?', $aInfo['mtime']);
			if ($this->$dbDao->iUpdate($aInfo, $update, $change, $oOption))
			{
				$this->$mcDao->iDecrement($key, $mcinfo);
			}
		}
	}

	private function _iMC2DB($vKey)
	{
		$mcDao = $this->_aConf['mc'].'Dao';
		$key = $this->_sGetMCKey($vKey);

		$mcinfo = intval($this->$mcDao->vGet($key));
		if ($mcinfo)
		{
			$this->_vIncrDb($vKey, $mcinfo);
			$this->$mcDao->iDecrement($key, $mcinfo);
		}
		return $mcinfo;
	}
	
	private function _vIncrDb($vKey, $iValue)
	{
		$dbDao = $this->_aConf['db'].'Dao';
		$data = $this->$dbDao->aGetIndexValue($vKey);
		$data['times'] = $iValue;
		$data['mtime'] = date('Y-m-d H:i:s');
		$update = array(
			'mtime' => $data['mtime'],
			);
		$change = array(
			'times' => $iValue,
			);
		$this->$dbDao->aInsert($data, $update, $change);
	}

	private function _sGetMCKey($vKey)
	{
		$dbDao = $this->_aConf['db'].'Dao';
		$data = $this->$dbDao->aGetIndexValue($vKey);
		return 'koct:'.$this->$dbDao->sGetTableName().':'.implode(':', array_map('urlencode', $data));
	}
}

?>