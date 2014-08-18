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
		$ua = strtolower(substr($sUa, 0, 4));
		if ('wget' === $ua || 'curl' === $ua)
		{
			return true;
		}
		return false;
	}
}
