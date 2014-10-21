<?php
/**
 * MysqlK
 *
 * @package ko\dao
 * @author zhangchu
 */

/**
 * 数据表分表直连操作类实现，使用了 server_setting, kind_setting, table_setting 来决定分表位置
 */
class Ko_Dao_MysqlK implements IKo_Dao_Table
{
	private $_aServerList = array();
	private $_aTableList = array();

	protected function __construct($sKind)
	{
		$oMysql = Ko_Data_Mysql::OInstance(KO_DB_HOST, KO_DB_USER, KO_DB_PASS, KO_DB_NAME);
		
		$sql = 'select * from server_setting order by master_sid, active desc, sid desc';
		$oMysql->bQuery($sql);
		while ($info = $oMysql->aFetchAssoc())
		{
			if (0 == $info['master_sid'])
			{
				$this->_aServerList[$info['sid']]['master'][] = $info;
			}
			else if ($info['active'])
			{
				$this->_aServerList[$info['master_sid']]['slave'][] = $info;
			}
			else
			{
				$this->_aServerList[$info['master_sid']]['inactive'][] = $info;
			}
		}

		$sql = 'select * from table_setting where kind = "'.Ko_Data_Mysql::SEscape($sKind).'" order by no';
		$oMysql->bQuery($sql);
		while ($info = $oMysql->aFetchAssoc())
		{
			$this->_aTableList[] = $info;
		}
		assert(!empty($this->_aTableList));
	}
	
	public static function OInstance($sKind)
	{
		return new self($sKind);
	}

	/**
	 * @return int
	 */
	public function iTableCount()
	{
		return count($this->_aTableList);
	}

	/**
	 * @return Ko_Data_Mysql
	 */
	public function oConnectDB($no, $sTag = 'slave')
	{
		$sid = $this->_aTableList[$no]['sid'];
		if (empty($this->_aServerList[$sid][$sTag]))
		{
			$sTag = empty($this->_aServerList[$sid]['slave']) ? 'master' : 'slave';
		}
		assert(!empty($this->_aServerList[$sid][$sTag]));
		return Ko_Data_Mysql::OInstance(
			$this->_aServerList[$sid][$sTag][0]['host'].':'.$this->_aServerList[$sid][$sTag][0]['port'],
			$this->_aServerList[$sid][$sTag][0]['user'],
			$this->_aServerList[$sid][$sTag][0]['passwd'],
			$this->_aTableList[$no]['db_name']);
	}

	/**
	 * @return string
	 */
	public function sGetRealTableName($no)
	{
		if (count($this->_aTableList) == 1)
		{
			return $this->_aTableList[$no]['kind'];
		}
		return $this->_aTableList[$no]['kind'].'_'.$this->_aTableList[$no]['no'];
	}

	public function vDoFetchSelect($sSql, $fnCallback, $sTag = 'slave')
	{
		$kind = $this->_aTableList[0]['kind'];
		$tcount = $this->iTableCount();
		for ($i=0; $i<$tcount; ++$i)
		{
			$oMysql = $this->oConnectDB($i, $sTag);
			$sql = str_replace($kind, $this->sGetRealTableName($i), $sSql);
			$oMysql->bQuery($sql);
			while ($info = $oMysql->aFetchAssoc())
			{
				call_user_func($fnCallback, $info, $i);
			}
		}
	}
}
