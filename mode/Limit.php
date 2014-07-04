<?php
/**
 * 使用说明
 *
 * <b>简介</b>
 * <pre>
 *   使用 mysql/redis 引擎限制某对象在某段时间发生某事若干次
 * </pre>
 *
 * <b>_aConf 配置</b>
 * @see Ko_Mode_Limit::$_aConf
 *
 * @package ko\mode
 * @author zhangchu
 */

/**
 * 限制某对象在某段时间发生某事若干次
 */
class Ko_Mode_Limit extends Ko_Busi_Api
{
	/**
	 * 配置数组
	 *
	 * <pre>
	 * array(
	 *   'engine' => 使用的存储引擎 mysql/redis，缺省 mysql
	 *   'db' => 数据库 Dao 名称，mysql 有效
	 *   'redis' => redis Dao 名称，redis 有效
	 *   'hashkey' => hash 数据的 key，redis 有效
	 * )
	 * </pre>
	 *
	 * @var array
	 */
	protected $_aConf = array();
	
	private $_oEngine;

	public function __construct()
	{
		if ('redis' === $this->_aConf['engine'])
		{
			$dao = $this->_aConf['redis'].'Dao';
			$hashkey = 'koli:'.Ko_Tool_Module::SGetObjectModuleName($this).':'.$this->_aConf['hashkey'];
			$this->_oEngine = new Ko_Mode_LimitRedis($this->$dao, $hashkey);
		}
		else
		{
			$dao = $this->_aConf['db'].'Dao';
			$this->_oEngine = new Ko_Mode_LimitMysql($this->$dao);
		}
	}

	/**
	 * @return bool
	 */
	public function bPreCheck($vObj, $sAction, $iSecond, $iMaxTimes, $iTimes = 1, $bAlign = false, $iOffset = 0)
	{
		return $this->_oEngine->bPreCheck($vObj, $sAction, $iSecond, $iMaxTimes, $iTimes, $bAlign, $iOffset);
	}

	/**
	 * @return int
	 */
	public function iPreCheck($vObj, $sAction, $iSecond, $iMaxTimes, $bAlign = false, $iOffset = 0)
	{
		return $this->_oEngine->iPreCheck($vObj, $sAction, $iSecond, $iMaxTimes, $bAlign, $iOffset);
	}

	/**
	 * @return bool
	 */
	public function bCheck($vObj, $sAction, $iSecond, $iMaxTimes, $iTimes = 1, $bAlign = false, $iOffset = 0)
	{
		return $this->_oEngine->bCheck($vObj, $sAction, $iSecond, $iMaxTimes, $iTimes, $bAlign, $iOffset);
	}

	public function vFree($vObj, $sAction)
	{
		$this->_oEngine->vFree($vObj, $sAction);
	}

	/**
	 * @return bool
	 */
	public function bPreCheckMinute($vObj, $sAction, $iMinute, $iMaxTimes, $iTimes = 1, $iOffset = 0)
	{
		return $this->_oEngine->bPreCheckMinute($vObj, $sAction, $iMinute, $iMaxTimes, $iTimes, $iOffset);
	}

	/**
	 * @return int
	 */
	public function iPreCheckMinute($vObj, $sAction, $iMinute, $iMaxTimes, $iOffset = 0)
	{
		return $this->_oEngine->iPreCheckMinute($vObj, $sAction, $iMinute, $iMaxTimes, $iOffset);
	}

	/**
	 * @return bool
	 */
	public function bCheckMinute($vObj, $sAction, $iMinute, $iMaxTimes, $iTimes = 1, $iOffset = 0)
	{
		return $this->_oEngine->bCheckMinute($vObj, $sAction, $iMinute, $iMaxTimes, $iTimes, $iOffset);
	}

	/**
	 * @return bool
	 */
	public function bPreCheckHour($vObj, $sAction, $iHour, $iMaxTimes, $iTimes = 1, $iOffset = 0)
	{
		return $this->_oEngine->bPreCheckHour($vObj, $sAction, $iHour, $iMaxTimes, $iTimes, $iOffset);
	}

	/**
	 * @return int
	 */
	public function iPreCheckHour($vObj, $sAction, $iHour, $iMaxTimes, $iOffset = 0)
	{
		return $this->_oEngine->iPreCheckHour($vObj, $sAction, $iHour, $iMaxTimes, $iOffset);
	}

	/**
	 * @return bool
	 */
	public function bCheckHour($vObj, $sAction, $iHour, $iMaxTimes, $iTimes = 1, $iOffset = 0)
	{
		return $this->_oEngine->bCheckHour($vObj, $sAction, $iHour, $iMaxTimes, $iTimes, $iOffset);
	}

	/**
	 * @return bool
	 */
	public function bPreCheckDay($vObj, $sAction, $iDay, $iMaxTimes, $iTimes = 1, $iOffset = 57600)
	{
		return $this->_oEngine->bPreCheckDay($vObj, $sAction, $iDay, $iMaxTimes, $iTimes, $iOffset);
	}

	/**
	 * @return int
	 */
	public function iPreCheckDay($vObj, $sAction, $iDay, $iMaxTimes, $iOffset = 57600)
	{
		return $this->_oEngine->iPreCheckDay($vObj, $sAction, $iDay, $iMaxTimes, $iOffset);
	}

	/**
	 * 缺省零点对齐
	 *
	 * @return bool
	 */
	public function bCheckDay($vObj, $sAction, $iDay, $iMaxTimes, $iTimes = 1, $iOffset = 57600)
	{
		return $this->_oEngine->bCheckDay($vObj, $sAction, $iDay, $iMaxTimes, $iTimes, $iOffset);
	}

	/**
	 * @return bool
	 */
	public function bPreCheckWeek($vObj, $sAction, $iWeek, $iMaxTimes, $iTimes = 1, $iOffset = 316800)
	{
		return $this->_oEngine->bPreCheckWeek($vObj, $sAction, $iWeek, $iMaxTimes, $iTimes, $iOffset);
	}

	/**
	 * @return int
	 */
	public function iPreCheckWeek($vObj, $sAction, $iWeek, $iMaxTimes, $iOffset = 316800)
	{
		return $this->_oEngine->iPreCheckWeek($vObj, $sAction, $iWeek, $iMaxTimes, $iOffset);
	}

	/**
	 * 缺省周一零点对齐
	 *
	 * @return bool
	 */
	public function bCheckWeek($vObj, $sAction, $iWeek, $iMaxTimes, $iTimes = 1, $iOffset = 316800)
	{
		return $this->_oEngine->bCheckWeek($vObj, $sAction, $iWeek, $iMaxTimes, $iTimes, $iOffset);
	}
}
