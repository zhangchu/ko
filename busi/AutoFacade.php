<?php
/**
 * AutoFacade
 *
 * @package ko\busi
 * @author zhangchu
 */

//include_once('../ko.class.php');

/**
 * 逻辑层外部接口基类，将同名Api接口转换为静态函数
 */
class Ko_Busi_AutoFacade
{
	public static function __callStatic($sName, $aArgs)
	{
		$class = get_called_class();
		if ('Facade' === substr($class, -6))
		{
			$apiClass = substr($class, 0, -6).'Api';
			$api = Ko_Tool_Singleton::OInstance($apiClass);
			return call_user_func_array(array($api, lcfirst($sName)), $aArgs);
		}
		assert(0);
	}
}

?>