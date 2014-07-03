<?php
/**
 * 使用说明
 *
 * <b>简介</b>
 * <pre>
 *   使用 mysql 引擎限制某对象在某段时间发生某事若干次
 * </pre>
 *
 * <b>数据库例表 - 单表</b>
 * <pre>
 *   CREATE TABLE s_zhangchu_limit(
 *     name varchar(64) not null default '',
 *     action varchar(64) not null default '',
 *     times int not null default 0,
 *     ctime timestamp NOT NULL default 0,
 *     unique(name, action)
 *   )ENGINE=InnoDB DEFAULT CHARSET=latin1;
 * </pre>
 *
 * <b>数据库例表 - 分表</b>
 * <pre>
 *   CREATE TABLE s_zhangchu_limit2_0(
 *     uid int not null default 0,
 *     action varchar(64) not null default '',
 *     times int not null default 0,
 *     ctime timestamp NOT NULL default 0,
 *     unique(uid, action)
 *  )ENGINE=InnoDB DEFAULT CHARSET=latin1;
 * </pre>
 *
 * @package ko\mode
 * @author zhangchu
 */

/**
 * 限制某对象在某段时间发生某事若干次实现
 */
class Ko_Mode_LimitMysql extends Ko_Mode_LimitBase
{
	private $_oDbDao;

	public function __construct($oDb)
	{
		$this->_oDbDao = $oDb;
		$indexField = $this->_oDbDao->aGetIndexField();
		assert(2 == count($indexField));
	}

	protected function _bCheck($vObj, $sAction, $iSecond, $iMaxTimes, $iTimes, $bAlign, $iOffset)
	{
		$info = $this->_aGet($vObj, $sAction);
		if (empty($info))
		{
			return $this->_bAdd($vObj, $sAction, $iMaxTimes, $iTimes);
		}

		if (0 == $iSecond)
		{
			return $this->_bUpdate($vObj, $sAction, $iMaxTimes, $iTimes);
		}

		$start = parent::IGetStartTime($info['ctime'], $bAlign, $iSecond, $iOffset);
		if ($start + $iSecond > time())
		{
			return $this->_bUpdate($vObj, $sAction, $iMaxTimes, $iTimes);
		}
		return $this->_bReset($vObj, $sAction, $iMaxTimes, $iTimes, $info['ctime']);
	}

	protected function _aGet($vObj, $sAction)
	{
		$indexField = $this->_oDbDao->aGetIndexField();
		$arr = array(
			$indexField[0] => $vObj,
			$indexField[1] => $sAction,
			);
		return $this->_oDbDao->aGet($arr);
	}

	protected function _iDelete($vObj, $sAction)
	{
		$indexField = $this->_oDbDao->aGetIndexField();
		$arr = array(
			$indexField[0] => $vObj,
			$indexField[1] => $sAction,
			);
		return $this->_oDbDao->iDelete($arr);
	}

	private function _bAdd($vObj, $sAction, $iMaxTimes, $iTimes)
	{
		try
		{
			$this->_iInsert($vObj, $sAction, $iTimes);
			return true;
		}
		catch(Exception $ex)
		{
		}
		return $this->_bUpdate($vObj, $sAction, $iMaxTimes, $iTimes);
	}

	private function _bReset($vObj, $sAction, $iMaxTimes, $iTimes, $sCtime)
	{
		$update = array(
			'times' => $iTimes,
			'ctime' => date('Y-m-d H:i:s'),
			);
		$oOption = new Ko_Tool_SQL;
		$oOption->oWhere('ctime = ?', $sCtime);
		if ($this->_iUpdate($vObj, $sAction, $update, array(), $oOption))
		{
			return true;
		}
		return $this->_bUpdate($vObj, $sAction, $iMaxTimes, $iTimes);
	}

	private function _bUpdate($vObj, $sAction, $iMaxTimes, $iTimes)
	{
		$change = array(
			'times' => $iTimes,
			);
		$oOption = new Ko_Tool_SQL;
		$oOption->oWhere('times <= ?', $iMaxTimes - $iTimes);
		if ($this->_iUpdate($vObj, $sAction, array(), $change, $oOption))
		{
			return true;
		}
		return false;
	}

	private function _iInsert($vObj, $sAction, $iTimes)
	{
		$indexField = $this->_oDbDao->aGetIndexField();
		$data = array(
			$indexField[0] => $vObj,
			$indexField[1] => $sAction,
			'times' => $iTimes,
			'ctime' => date('Y-m-d H:i:s'),
			);
		return $this->_oDbDao->aInsert($data);
	}

	private function _iUpdate($vObj, $sAction, $aUpdate, $aChange, $oOption)
	{
		$indexField = $this->_oDbDao->aGetIndexField();
		$arr = array(
			$indexField[0] => $vObj,
			$indexField[1] => $sAction,
			);
		return $this->_oDbDao->iUpdate($arr, $aUpdate, $aChange, $oOption);
	}
}
