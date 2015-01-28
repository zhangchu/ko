<?php
/**
 * Safe
 *
 * @package ko\app
 * @author zhangchu
 */

/**
 * 较为安全的Web应用程序的基类实现
 */
class Ko_App_Safe extends Ko_App_Base
{
	/**
	 * 通过设置允许的请求方法来保证基本的安全
	 *
	 * @param string $sAllowMethod 可以设置为 'POST', 'GET', 'GET,POST'
	 */
	public function __construct($sAllowMethod = 'POST')
	{
		if (!self::BCheckRequestMethod($sAllowMethod))
		{
			exit;
		}
	}
	
	public static function BCheckRequestMethod($sAllowMethod = 'POST')
	{
		$method = Ko_Web_Request::SRequestMethod();
		return false !== strpos($sAllowMethod, $method);
	}
}
