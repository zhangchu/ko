<?php
/**
 * LimitBase
 *
 * @package ko\mode
 * @author zhangchu
 */

class Ko_Mode_LimitBase extends Ko_Busi_Api
{
	/**
	 * @return int
	 */
	public static function IGetStartTime($sCtime, $bAlign, $iSecond, $iOffset)
	{
		if ($bAlign)
		{
			$start = strtotime($sCtime) - $iOffset;
			return floor($start / $iSecond) * $iSecond + $iOffset;
		}
		return strtotime($sCtime);
	}
	
	/**
	 * @return bool
	 */
	public function bPreCheck($vObj, $sAction, $iSecond, $iMaxTimes, $iTimes = 1, $bAlign = false, $iOffset = 0)
	{
		assert($iTimes > 0 && $iMaxTimes >= $iTimes);
		return $iTimes <= $this->iPreCheck($vObj, $sAction, $iSecond, $iMaxTimes, $bAlign, $iOffset);
	}

	/**
	 * @return int
	 */
	public function iPreCheck($vObj, $sAction, $iSecond, $iMaxTimes, $bAlign = false, $iOffset = 0)
	{
		assert($iSecond >= 0 && $iMaxTimes > 0);

		$info = $this->_aGet($vObj, $sAction);
		if (!empty($info))
		{
			if (0 == $iSecond)
			{
				return $iMaxTimes - $info['times'];
			}
			$start = self::IGetStartTime($info['ctime'], $bAlign, $iSecond, $iOffset);
			if ($start + $iSecond > time())
			{
				return $iMaxTimes - $info['times'];
			}
		}
		return $iMaxTimes;
	}

	/**
	 * @return bool
	 */
	public function bCheck($vObj, $sAction, $iSecond, $iMaxTimes, $iTimes = 1, $bAlign = false, $iOffset = 0)
	{
		assert($iSecond >= 0 && $iTimes > 0 && $iMaxTimes >= $iTimes);
		return $this->_bCheck($vObj, $sAction, $iSecond, $iMaxTimes, $iTimes, $bAlign, $iOffset);
	}

	public function vFree($vObj, $sAction)
	{
		$this->_iDelete($vObj, $sAction);
	}

	/**
	 * @return bool
	 */
	public function bPreCheckMinute($vObj, $sAction, $iMinute, $iMaxTimes, $iTimes = 1, $iOffset = 0)
	{
		return $this->bPreCheck($vObj, $sAction, $iMinute * 60, $iMaxTimes, $iTimes, true, $iOffset);
	}

	/**
	 * @return int
	 */
	public function iPreCheckMinute($vObj, $sAction, $iMinute, $iMaxTimes, $iOffset = 0)
	{
		return $this->iPreCheck($vObj, $sAction, $iMinute * 60, $iMaxTimes, true, $iOffset);
	}

	/**
	 * @return bool
	 */
	public function bCheckMinute($vObj, $sAction, $iMinute, $iMaxTimes, $iTimes = 1, $iOffset = 0)
	{
		return $this->bCheck($vObj, $sAction, $iMinute * 60, $iMaxTimes, $iTimes, true, $iOffset);
	}

	/**
	 * @return bool
	 */
	public function bPreCheckHour($vObj, $sAction, $iHour, $iMaxTimes, $iTimes = 1, $iOffset = 0)
	{
		return $this->bPreCheck($vObj, $sAction, $iHour * 3600, $iMaxTimes, $iTimes, true, $iOffset);
	}

	/**
	 * @return int
	 */
	public function iPreCheckHour($vObj, $sAction, $iHour, $iMaxTimes, $iOffset = 0)
	{
		return $this->iPreCheck($vObj, $sAction, $iHour * 3600, $iMaxTimes, true, $iOffset);
	}

	/**
	 * @return bool
	 */
	public function bCheckHour($vObj, $sAction, $iHour, $iMaxTimes, $iTimes = 1, $iOffset = 0)
	{
		return $this->bCheck($vObj, $sAction, $iHour * 3600, $iMaxTimes, $iTimes, true, $iOffset);
	}

	/**
	 * @return bool
	 */
	public function bPreCheckDay($vObj, $sAction, $iDay, $iMaxTimes, $iTimes = 1, $iOffset = 57600)
	{
		return $this->bPreCheck($vObj, $sAction, $iDay * 86400, $iMaxTimes, $iTimes, true, $iOffset);
	}

	/**
	 * @return int
	 */
	public function iPreCheckDay($vObj, $sAction, $iDay, $iMaxTimes, $iOffset = 57600)
	{
		return $this->iPreCheck($vObj, $sAction, $iDay * 86400, $iMaxTimes, true, $iOffset);
	}

	/**
	 * 缺省零点对齐
	 *
	 * @return bool
	 */
	public function bCheckDay($vObj, $sAction, $iDay, $iMaxTimes, $iTimes = 1, $iOffset = 57600)
	{
		return $this->bCheck($vObj, $sAction, $iDay * 86400, $iMaxTimes, $iTimes, true, $iOffset);
	}

	/**
	 * @return bool
	 */
	public function bPreCheckWeek($vObj, $sAction, $iWeek, $iMaxTimes, $iTimes = 1, $iOffset = 316800)
	{
		return $this->bPreCheck($vObj, $sAction, $iWeek * 604800, $iMaxTimes, $iTimes, true, $iOffset);
	}

	/**
	 * @return int
	 */
	public function iPreCheckWeek($vObj, $sAction, $iWeek, $iMaxTimes, $iOffset = 316800)
	{
		return $this->iPreCheck($vObj, $sAction, $iWeek * 604800, $iMaxTimes, true, $iOffset);
	}

	/**
	 * 缺省周一零点对齐
	 *
	 * @return bool
	 */
	public function bCheckWeek($vObj, $sAction, $iWeek, $iMaxTimes, $iTimes = 1, $iOffset = 316800)
	{
		return $this->bCheck($vObj, $sAction, $iWeek * 604800, $iMaxTimes, $iTimes, true, $iOffset);
	}

	protected function _bCheck($vObj, $sAction, $iSecond, $iMaxTimes, $iTimes, $bAlign, $iOffset)
	{
		assert(0);
	}
	
	protected function _aGet($vObj, $sAction)
	{
		assert(0);
	}
	
	protected function _iDelete($vObj, $sAction)
	{
		assert(0);
	}
}
