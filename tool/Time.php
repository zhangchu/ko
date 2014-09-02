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
	public static function BCheckTime($aCron, $iMinute, $iHour, $iWeek, $iDay)
	{
		if (!(self::_BIsEmpty('day', $aCron) && self::_BIsEmpty('week', $aCron))
			&& self::_BIsEmpty('hour', $aCron))
		{
			$aCron['hour'] = array(1);
		}
		if (!self::_BIsEmpty('hour', $aCron)
			&& self::_BIsEmpty('minute', $aCron))
		{
			$aCron['minute'] = array(1);
		}
		return self::_BCheckTimeArr('day', $iDay, $aCron)
			&& self::_BCheckTimeArr('week', $iWeek, $aCron)
			&& self::_BCheckTimeArr('hour', $iHour, $aCron)
			&& self::_BCheckTimeArr('minute', $iMinute, $aCron);
	}
	
	private static function _BIsEmpty($sUnit, $aCron)
	{
		return !isset($aCron[$sUnit]) || '' === $aCron[$sUnit] || array() === $aCron[$sUnit];
	}

	private static function _BCheckTimeArr($sUnit, $iUnit, $aCron)
	{
		if (isset($aCron[$sUnit]))
		{
			if (is_array($aCron[$sUnit]))
			{
				$arr = $aCron[$sUnit];
			}
			else
			{
				if (false !== strpos($aCron[$sUnit], ','))
				{
					$arr = explode(',', $aCron[$sUnit]);
				}
				else if (('hour' === $sUnit || 'minute' === $sUnit) && '*/' === substr($aCron[$sUnit], 0, 2))
				{
					$mod = substr($aCron[$sUnit], 2);
					$total = ('hour' === $sUnit) ? 24 : 60;
					$arr = array();
					for ($i=0; $i<$total; ++$i)
					{
						if (0 == $i % $mod)
						{
							$arr[] = $i;
						}
					}
				}
				else
				{
					$arr = array($aCron[$sUnit]);
				}
			}
			if (!in_array($iUnit, $arr))
			{
				return false;
			}
		}
		return true;
	}
}
