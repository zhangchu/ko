<?php
/**
 * 使用说明
 *
 * <b>简介</b>
 * <pre>
 *   使用 redis 引擎限制某对象在某段时间发生某事若干次
 *   在临界时间点并发情况下可能会导致限制不严格的情况
 * </pre>
 *
 * @package ko\mode
 * @author zhangchu
 */

/**
 * 限制某对象在某段时间发生某事若干次实现
 */
class Ko_Mode_LimitRedis extends Ko_Mode_LimitBase
{
	private $_oRedisDao;
	private $_sHashKey = '';
	
	public function __construct($oRedis, $sHashKey)
	{
		$this->_oRedisDao = $oRedis;
		$this->_sHashKey = $sHashKey;
	}

	protected function _bCheck($vObj, $sAction, $iSecond, $iMaxTimes, $iTimes, $bAlign, $iOffset)
	{
		list($timesfield, $ctimefield) = $this->_aGetObjFields($vObj, $sAction);

		if (0 == $iSecond)
		{
			$newtimes = $this->_oRedisDao->vHIncr($this->_sHashKey, $timesfield, $iTimes);
			assert(false !== $newtimes);
			return $newtimes <= $iMaxTimes;
		}

		$now = time();
		$info = $this->_aGet($vObj, $sAction);
		if (empty($info))
		{
			$this->_oRedisDao->vHSet($this->_sHashKey, $ctimefield, $now);
			$newtimes = $this->_oRedisDao->vHIncr($this->_sHashKey, $timesfield, $iTimes);
			assert(false !== $newtimes);
			return $newtimes <= $iMaxTimes;
		}

		$start = parent::IGetStartTime($info['ctime'], $bAlign, $iSecond, $iOffset);
		if ($start + $iSecond > $now)
		{
			$newtimes = $this->_oRedisDao->vHIncr($this->_sHashKey, $timesfield, $iTimes);
			assert(false !== $newtimes);
			return $newtimes <= $iMaxTimes;
		}

		// 在临界时间点并发情况下可能会导致限制不严格的情况
		$this->_oRedisDao->vHSet($this->_sHashKey, $ctimefield, $now);
		$this->_oRedisDao->vHSet($this->_sHashKey, $timesfield, $iTimes);
		return true;
	}

	protected function _aGet($vObj, $sAction)
	{
		$aFields = $this->_aGetObjFields($vObj, $sAction);
		$ret = $this->_oRedisDao->vHMGet($this->_sHashKey, $aFields);
		if (false === $ret[$aFields[0]])
		{
			return array();
		}
		return array('times' => $ret[$aFields[0]], 'ctime' => date('Y-m-d H:i:s', $ret[$aFields[1]]));
	}
	
	protected function _iDelete($vObj, $sAction)
	{
		list($timesfield, $ctimefield) = $this->_aGetObjFields($vObj, $sAction);
		$this->_oRedisDao->vHDel($this->_sHashKey, $ctimefield);
		$this->_oRedisDao->vHDel($this->_sHashKey, $timesfield);
		return 0;
	}
	
	private function _aGetObjFields($vObj, $sAction)
	{
		return array($vObj.':'.$sAction.':c', $vObj.':'.$sAction.':t');
	}
}
