<?php
/**
 * Event
 *
 * @brief: 给对象绑定事件
 *
 * @package ko\Web
 * @author: zhangchu
 */

class Ko_Web_Event
{
	private static $s_aEventObj = array();
	
	public static function On($group, $name, $callback = null)
	{
		$obj = self::_OGetEventObj($group);
		$obj->oOn($name, $callback);
	}
	
	public static function Once($group, $name, $callback = null)
	{
		$obj = self::_OGetEventObj($group);
		$obj->oOnce($name, $callback);
	}
	
	public static function Off($group, $name = null, $callback = null)
	{
		$obj = self::_OGetEventObj($group);
		$obj->oOff($name, $callback);
	}
	
	public static function Trigger($group, $name)
	{
		$obj = self::_OGetEventObj($group);
		$args = func_get_args();
		array_shift($args);
		call_user_func_array(array($obj, 'oTrigger'), $args);
	}
	
	private static function _OGetEventObj($group)
	{
		if (!isset(self::$s_aEventObj[$group]))
		{
			self::$s_aEventObj[$group] = new Ko_Mode_Event;
		}
		return self::$s_aEventObj[$group];
	}
}
