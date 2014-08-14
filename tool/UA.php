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
	 * еп╤оUser-Agentйг╥ЯйгеюЁФ
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
