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
}
