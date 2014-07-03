<?php
/**
 * Http
 *
 * @package ko\tool
 * @author zhangchu
 */

class Ko_Tool_Http
{
	private static $s_sCacheDir;
	
	public static function vSetCacheDir($sDir)
	{
		self::$s_sCacheDir = $sDir;
	}
	
	public static function sGet($sUrl, $sMock = 'ff')
	{
		if (strlen(self::$s_sCacheDir))
		{
			$content = Ko_Tool_CacheHttp::SGet(self::$s_sCacheDir, $sUrl);
			if (false !== $content)
			{
				return $content;
			}
		}
		$func = 'sGet_Mock_'.$sMock;
		$content = self::$func($sUrl);
		if (false !== $content && strlen(self::$s_sCacheDir))
		{
			Ko_Tool_CacheHttp::VSet(self::$s_sCacheDir, $sUrl, $content);
		}
		return $content;
	}
	
	public static function vClear($sUrl)
	{
		Ko_Tool_CacheHttp::VClear(self::$s_sCacheDir, $sUrl);
	}
	
	public static function sGet_Mock_ff($sUrl)
	{
		$header = array();
		$header[] = 'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:18.0) Gecko/20100101 Firefox/18.0';
		$header[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
		$header[] = 'Accept-Language: zh-cn,zh;q=0.8,en-us;q=0.5,en;q=0.3';
		return self::_sGet($sUrl, $header);
	}
	
	public static function sGet_Mock_ie($sUrl)
	{
		$header = array();
		$header[] = 'User-Agent: Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)';
		$header[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
		$header[] = 'Accept-Language: zh-cn,zh;q=0.8,en-us;q=0.5,en;q=0.3';
		return self::_sGet($sUrl, $header);
	}
	
	public static function sGet_Mock_baidu($sUrl)
	{
		$header = array();
		$header[] = 'User-Agent: Baiduspider+(+http://www.baidu.com/search/spider.htm)';
		$header[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
		$header[] = 'Accept-Language: zh-cn,zh;q=0.8,en-us;q=0.5,en;q=0.3';
		return self::_sGet($sUrl, $header);
	}
	
	private static function _sGet($sUrl, $header)
	{
		$ch = curl_init($sUrl);
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		$ret = curl_exec($ch);
		curl_close($ch);
		return $ret;
	}
}
