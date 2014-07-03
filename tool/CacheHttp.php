<?php
/**
 * CacheHttp
 *
 * @package ko\tool
 * @author zhangchu
 */

class Ko_Tool_CacheHttp
{
	public static function VClear($sCacheDir, $sUrl)
	{
		$md5 = md5($sUrl);
		$filename = $sCacheDir.'/'.substr($md5, 0, 2).'/'.substr($md5, 2, 2).'/'.$md5;
		if (is_file($filename))
		{
			unlink($filename);
		}
	}
	
	public static function VSet($sCacheDir, $sUrl, $sContent)
	{
		$md5 = md5($sUrl);
		$dir = $sCacheDir.'/'.substr($md5, 0, 2);
		if (!is_dir($dir))
		{
			mkdir($dir);
		}
		$dir .= '/'.substr($md5, 2, 2);
		if (!is_dir($dir))
		{
			mkdir($dir);
		}
		$filename = $dir.'/'.$md5;
		file_put_contents($filename, $sContent);
	}
	
	public static function SGet($sCacheDir, $sUrl)
	{
		$md5 = md5($sUrl);
		$filename = $sCacheDir.'/'.substr($md5, 0, 2).'/'.substr($md5, 2, 2).'/'.$md5;
		if (!is_file($filename))
		{
			return false;
		}
		return file_get_contents($filename);
	}
}
