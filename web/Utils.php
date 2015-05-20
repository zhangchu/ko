<?php
/**
 * Utils
 *
 * @package ko\web
 * @author zhangchu
 */

class Ko_Web_Utils
{
	/**
	 * 判断是否是ajax请求
	 *
	 * @return boolean
	 */
	public static function BIsAjax()
	{
		return 'XMLHttpRequest' === Ko_Web_Request::SHttpXRequestedWith();
	}
	
	/**
	 * 获取url中的域名关键词
	 * 如：http://www.sina.com.cn/xxx 返回 sina
	 *     http://www.baidu.com/xxx 返回 baidu
	 *
	 * @param string $sUrl
	 * @return string
	 */
	public static function SGetDomainTag($sUrl)
	{
		$urlinfo = parse_url($sUrl);
		return Ko_Tool_Domain::SGetTag($urlinfo['host']);
	}
	
	/**
	 * 根据 uri 获取 script_name, query_string, path_info
	 *
	 * @return array($sn, $qs, $pi)
	 */
	public static function AParseUri($sUri)
	{
		list($sn, $qs) = explode('?', $sUri, 2);
		if (false !== ($pos = strpos($sn, '.php/')))
		{
			$pi = substr($sn, $pos + 4);
			$sn = substr($sn, 0, $pos + 4);
		}
		else
		{
			if ('/' === substr($sn, -1))
			{
				$sn .= 'index.php';
			}
			$pi = '';
		}
		return array($sn, $qs, $pi);
	}
	
	/**
	 * 根据 uri 重新设置一些相关的环境变量
	 */
	public static function VResetEnv($sUri)
	{
		list($sn, $qs, $pi) = self::AParseUri($sUri);
		
		parse_str($qs, $arr);
		$GLOBALS['_GET'] = $_GET = $arr;
		$GLOBALS['_REQUEST'] = $_REQUEST = $_REQUEST + $arr;
		$GLOBALS['_SERVER']['QUERY_STRING'] = $_SERVER['QUERY_STRING'] =
		$GLOBALS['_ENV']['QUERY_STRING'] = $_ENV['QUERY_STRING'] = $qs;

		$GLOBALS['_SERVER']['PHP_SELF'] = $_SERVER['PHP_SELF'] =
		$GLOBALS['_SERVER']['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] =
		$GLOBALS['_ENV']['PHP_SELF'] = $_ENV['PHP_SELF'] =
		$GLOBALS['_ENV']['SCRIPT_NAME'] = $_ENV['SCRIPT_NAME'] = $sn;
		
		$GLOBALS['_SERVER']['SCRIPT_FILENAME'] = $_SERVER['SCRIPT_FILENAME'] =
		$GLOBALS['_ENV']['SCRIPT_FILENAME'] = $_ENV['SCRIPT_FILENAME'] =
			Ko_Web_Request::SDocumentRoot().$sn;
		
		$GLOBALS['_SERVER']['PATH_INFO'] = $_SERVER['PATH_INFO'] =
		$GLOBALS['_ENV']['PATH_INFO'] = $_ENV['PATH_INFO'] = $pi;
	}
}
