<?php
/**
 * UA
 *
 * @package ko\tool
 * @author zhangchu
 */

class Ko_Tool_UA
{
	private static $s_aSpiderKeys = array('spider', 'bot', 'slurp');
	private static $s_aPhone = array('phone', 'android', 'symbian', 'smartphone', 'midp', 'wap', 'ipod');
	private static $s_aPad = array('ipad', 'xoom');

	/**
	 * 判断User-Agent是否是爬虫
	 *
	 * @return boolean
	 */
	public static function BIsSpider($sUa)
	{
		$ua = strtolower($sUa);
		foreach (self::$s_aSpiderKeys as $key)
		{
			if (false !== strpos($ua, $key))
			{
				return true;
			}
		}
		return false;
	}
	
	/**
	 * 判断User-Agent是否非法
	 *
	 * @return boolean
	 */
	public static function BIsInvalid($sUa)
	{
		if ('' === $sUa)
		{
			return true;
		}
		return false;
	}

	/**
	 * 判断User-Agent是否为移动设备，包括手机和pad
	 *
	 * @return boolean
	 */
	public static function BIsMobile($sUa)
	{
		return self::BIsPhone($sUa) || self::BIsPad($sUa);
	}
	
	/**
	 * 判断User-Agent是否是手机
	 *
	 * @return boolean
	 */
	public static function BIsPhone($sUa)
	{
		$ua = strtolower($sUa);
		foreach (self::$s_aPhone as $key)
		{
			if (false !== strpos($ua, $key))
			{
				return true;
			}
		}
		return false;
	}
	
	/**
	 * 判断User-Agent是否是pad
	 *
	 * @return boolean
	 */
	public static function BIsPad($sUa)
	{
		$ua = strtolower($sUa);
		foreach (self::$s_aPad as $key)
		{
			if (false !== strpos($ua, $key))
			{
				return true;
			}
		}
		return false;
	}
}
