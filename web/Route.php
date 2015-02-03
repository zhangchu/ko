<?php
/**
 * Route
 *
 * @package ko\web
 * @author: jiangjw & zhangchu
 */

/**
 * 根据 SCRIPT_FILENAME & REQUEST_METHOD 来决定路由结果
 *
 * 1. .php 结尾的直接路由
 *    /path/abc/xyz.php
 * 2. / 结尾的补充 index.php 后直接路由
 *    /path/abc/ => /path/abc/index.php
 * 3. 其他种类
 *    /path/abc/index => /path/abc.php 并执行注册为 index 的函数
 */
class Ko_Web_Route
{
	const ERR_FILE = 1;
	const ERR_FUNC = 2;
	const ERR_METHOD = 3;
	
	private static $s_aRoute = array();
	
	public static function IDispatch($scriptFilename, $requestMethod)
	{
		if ('/' === substr($scriptFilename, -1))
		{
			$scriptFilename .= 'index.php';
		}
		if ('.php' === substr($scriptFilename, -4))
		{
			if (!is_file($scriptFilename))
			{
				return self::ERR_FILE;
			}
			self::_VRequireFile($scriptFilename);
		}
		else
		{
			$dirname = dirname($scriptFilename).'.php';
			if (!is_file($dirname))
			{
				return self::ERR_FILE;
			}
			self::_VRequireFile($dirname);
			$basename = basename($scriptFilename);
			if (!isset(self::$s_aRoute[$basename]))
			{
				return self::ERR_FUNC;
			}
			if (!isset(self::$s_aRoute[$basename][$requestMethod]))
			{
				return self::ERR_METHOD;
			}
			call_user_func(self::$s_aRoute[$basename][$requestMethod]);
		}
		return 0;
	}
	
	public static function VGet($sName, $fnRoute)
	{
		self::_VRegisterRoute('GET', $sName, $fnRoute);
	}

	public static function VPost($sName, $fnRoute)
	{
		self::_VRegisterRoute('POST', $sName, $fnRoute);
	}
	
	public static function VPut($sName, $fnRoute)
	{
		self::_VRegisterRoute('PUT', $sName, $fnRoute);
	}
	
	public static function VDelete($sName, $fnRoute)
	{
		self::_VRegisterRoute('DELETE', $sName, $fnRoute);
	}
	
	private static function _VRegisterRoute($sMethod, $sName, $fnRoute)
	{
		self::$s_aRoute[$sName][$sMethod] = $fnRoute;
	}
	
	private static function _VRequireFile($sFilename)
	{
		$cwd = getcwd();
		chdir(dirname($sFilename));
		require_once($sFilename);
		chdir($cwd);
	}
}
