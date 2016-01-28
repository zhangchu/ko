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
 * 1. .php 结尾的不在内部进行路由，交给外部路由
 *    /path/abc/xyz.php
 * 2. 其他文件如果存在，直接输出文件
 *    /path/abc/xyz.txt
 * 3. 如果目录存在，直接跳转到目录
 *    /path/abc/xyz => /path/abc/xyz/
 * 4. 其他
 *    /path/abc/xyz => /path/abc.php 并执行注册为 xyz 的函数
 *    如果 /path/abc.php 不存在，或者里面的 xyz 函数不存在
 *    /path/abc/xyz => /path/abc/xyz.php 并执行注册为 index 的函数
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
	
	public static function IDispatch(&$phpFilename)
	{
		$scriptFilename = Ko_Web_Request::SScriptFilename();
		$requestMethod = Ko_Web_Request::SRequestMethod(true);
		return self::_IDispatch($scriptFilename, $requestMethod, $phpFilename);
	}

	public static function VCallIndex()
	{
		if (!isset(self::$s_aRoute['index']))
		{
			return;
		}
		$requestMethod = Ko_Web_Request::SRequestMethod(true);
		if (!isset(self::$s_aRoute['index'][$requestMethod]))
		{
			return;
		}
		call_user_func(self::$s_aRoute['index'][$requestMethod]);
	}
	
	private static function _IDispatch($scriptFilename, $requestMethod, &$phpFilename)
	{
		self::$s_sFile = $scriptFilename;
		self::$s_sFunc = '';
		self::$s_sMethod = $requestMethod;
		$phpFilename = '';
		if ('.php' === substr(self::$s_sFile, -4))
		{
			if (!is_file(self::$s_sFile))
			{
				return self::$s_iErrno = self::ERR_FILE;
			}
			$phpFilename = self::$s_sFile;
		}
		else if (is_file(self::$s_sFile))
		{
			$render = new Ko_View_Render_FILE;
			$render->oSetData('filename', self::$s_sFile);
			Ko_Web_Response::VSend($render);
		}
		else if (is_dir(self::$s_sFile))
		{
			list($rewrite, ) = Ko_Web_Rewrite::AGet();
			list($path, $query) = explode('?', $rewrite, 2);
			if (isset($query))
			{
				$query = '?'.$query;
			}
			Ko_Web_Response::VSetRedirect($path.'/'.$query);
			Ko_Web_Response::VSend();
		}
		else
		{
			$pathinfo = pathinfo(self::$s_sFile);
			self::$s_sFunc = $pathinfo['basename'];
			self::$s_sFile = $pathinfo['dirname'].'.php';
			if (self::_IWebRoute())
			{
				self::$s_sFile = $pathinfo['dirname'].'/'.$pathinfo['basename'].'.php';
				self::$s_sFunc = 'index';
				return self::_IWebRoute();
			}
		}
		return self::$s_iErrno = 0;
	}
	
	public static function AGetDispatchInfo()
	{
		return array(self::$s_sFile, self::$s_sFunc);
	}
	
	public static function V404()
	{
		$error = 'File: '.self::$s_sFile."\n"
			.'Func: '.self::$s_sFunc."\n"
			.'Method: '.self::$s_sMethod."\n"
			.'Errno: '.self::$s_iErrno."\n"
			.'Error: '.self::$s_aError[self::$s_iErrno];
		$render = new Ko_View_Render_TEXT;
		$render->oSetData('error', $error);
		
		Ko_Web_Response::VSetHttpCode(404);
		Ko_Web_Response::VSend($render);
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

	private static function _IWebRoute()
	{
		if (!is_file(self::$s_sFile))
		{
			return self::$s_iErrno = self::ERR_FILE;
		}
		self::_VRequireFile(self::$s_sFile);
		if (!isset(self::$s_aRoute[self::$s_sFunc]))
		{
			return self::$s_iErrno = self::ERR_FUNC;
		}
		if (!isset(self::$s_aRoute[self::$s_sFunc][self::$s_sMethod]))
		{
			return self::$s_iErrno = self::ERR_METHOD;
		}
		call_user_func(self::$s_aRoute[self::$s_sFunc][self::$s_sMethod]);
		return self::$s_iErrno = 0;
	}
}
