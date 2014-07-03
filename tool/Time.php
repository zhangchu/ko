<?php
/**
 * Time
 *
 * @package ko\tool
 * @author zhangchu
 */

class Ko_Tool_Time
{
	/**
	 * 检查是否为Crontab运行时间
	 *
	 * @return boolean
	 */
	public static function BCheckTime($iWeek, $iHour, $iMinute, $aCron)
	{
		return self::_BCheckTimeArr('week', $iWeek, $aCron)
			&& self::_BCheckTimeArr('hour', $iHour, $aCron)
			&& self::_BCheckTimeArr('minute', $iMinute, $aCron);
	}

	private static function _BCheckTimeArr($sUnit, $iUnit, $aCron)
	{
		if (isset($aCron[$sUnit]))
		{
			if (is_array($aCron[$sUnit]))
			{
				if (!in_array($iUnit, $aCron[$sUnit]))
				{
					return false;
				}
			}
			else
			{
				if ($aCron[$sUnit] != $iUnit)
				{
					return false;
				}
			}
		}
		return true;
	}
}
