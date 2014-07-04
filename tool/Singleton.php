<?php
/**
 * Singleton
 *
 * @package ko\tool
 * @author zhangchu
 */

//include_once('../ko.class.php');

/**
 * 对象单例模式实现
 */
class Ko_Tool_Singleton
{
	private static $s_aInstance = array();

	/**
	 * @return object
	 */
	public static function OInstance($sClassName)
	{
		if (empty(self::$s_aInstance[$sClassName]))
		{
			assert(class_exists($sClassName));
			self::$s_aInstance[$sClassName] = new $sClassName();
		}
		return self::$s_aInstance[$sClassName];
	}
}

/*

$obj = new Ko_Tool_Singleton;
var_dump($obj);
$obj = Ko_Tool_Singleton::OInstance('Ko_Tool_Singleton');
var_dump($obj);
$obj = Ko_Tool_Singleton::OInstance('Ko_Tool_Singleton');
var_dump($obj);

*/

?>