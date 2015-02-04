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
 * 2. 非 .php 结尾的
 *    /path/abc/index => /path/abc.php 并执行注册为 index 的函数
 */
class Ko_Web_Route
{
	const ERR_FILE = 1;
	const ERR_FUNC = 2;
	const ERR_METHOD = 3;
	
	private static $s_aRoute = array();
	
	private static $s_aError = array(
		self::ERR_FILE => 'File Not Found',
		self::ERR_FUNC => 'Func Not Supported',
		self::ERR_METHOD => 'Method Not Supported',
	);
	
	private static $s_iErrno = 0;
	private static $s_sFile = '';
	private static $s_sFunc = '';
	private static $s_sMethod = '';
	
	public static function IDispatch($scriptFilename, $requestMethod)
	{
		self::$s_sFile = $scriptFilename;
		self::$s_sFunc = '';
		self::$s_sMethod = $requestMethod;
		if ('.php' === substr(self::$s_sFile, -4))
		{
			if (!is_file(self::$s_sFile))
			{
				return self::$s_iErrno = self::ERR_FILE;
			}
			self::_VRequireFile(self::$s_sFile);
		}
		else
		{
			self::$s_sFunc = basename(self::$s_sFile);
			$dirname = dirname(self::$s_sFile).'.php';
			if (!is_file($dirname))
			{
				return self::$s_iErrno = self::ERR_FILE;
			}
			self::_VRequireFile($dirname);
			if (!isset(self::$s_aRoute[self::$s_sFunc]))
			{
				return self::$s_iErrno = self::ERR_FUNC;
			}
			if (!isset(self::$s_aRoute[self::$s_sFunc][self::$s_sMethod]))
			{
				return self::$s_iErrno = self::ERR_METHOD;
			}
			call_user_func(self::$s_aRoute[self::$s_sFunc][self::$s_sMethod]);
		}
		return self::$s_iErrno = 0;
	}
	
	public static function AGetDispatchInfo()
	{
		return array(self::$s_sFile, self::$s_sFunc);
	}
	
	public static function V404()
	{
		$error = "File: ".self::$s_sFile."\n"
			."Func: ".self::$s_sFunc."\n"
			."Method: ".self::$s_sMethod."\n"
			."Errno: ".self::$s_iErrno."\n"
			."Error: ".self::$s_aError[self::$s_iErrno]."\n";
		$render = new Ko_View_Render_TEXT;
		$render->oSetData('error', $error);
		
		Ko_Web_Response::VSetHttpCode(404);
		Ko_Web_Response::VSetContentType('text/plain');
		Ko_Web_Response::VAppendBody($render);
		Ko_Web_Response::VSend();
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
