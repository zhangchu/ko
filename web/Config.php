<?php
/**
 * Config
 *
 * @package ko/Web
 * @author zhangchu
 */

class Ko_Web_Config
{
	private static $s_sConfFilename = '';
	private static $s_sCacheFilename = '';
	
	private static $s_sAppName = '';
	private static $s_sDocumentRoot = '';
	private static $s_sRewriteConf = '';
	private static $s_sRewriteCache = '';
	
	public static function VSetConf($sConfFilename, $sCacheFilename = '')
	{
		self::$s_sConfFilename = $sConfFilename;
		self::$s_sCacheFilename = $sCacheFilename;
	}
	
	public static function VLoad()
	{
		if ('' === self::$s_sConfFilename)
		{
			return;
		}
		if ('' === self::$s_sCacheFilename)
		{
			self::ALoadFile(self::$s_sCacheFilename);
		}
		else
		{
			self::VLoadCacheFile(self::$s_sConfFilename, self::$s_sCacheFilename);
		}
	}
	
	public static function VLoadCacheFile($sConfFilename, $sCacheFilename)
	{
		if (!is_file($sConfFilename))
		{
			return;
		}
		$cacheDir = dirname($sCacheFilename);
		if (!is_dir($cacheDir))
		{
			mkdir($cacheDir, 0777, true);
			if (!is_dir($cacheDir))
			{
				self::ALoadFile($sConfFilename);
				return;
			}
		}
		if (!is_file($sCacheFilename) || filemtime($sConfFilename) > filemtime($sCacheFilename))
		{
			$config = self::ALoadFile($sConfFilename);
			$script = "<?php\nKo_Web_Config::VLoadConfig("
				.Ko_Tool_Stringify::SConvArray($config)
				.");\n";
			file_put_contents($sCacheFilename, $script);
		}
		else
		{
			require_once($sCacheFilename);
		}
	}
	
	public static function ALoadFile($sFilename)
	{
		$config = parse_ini_file($sFilename, true);
		self::VLoadConfig($config);
		return $config;
	}
	
	public static function VLoadConfig($aConfig)
	{
		$host = Ko_Web_Request::SServerName();
		if (isset($aConfig['global'][$host]))
		{
			self::$s_sAppName = $aConfig['global'][$host];
			if (isset($aConfig['app_'.self::$s_sAppName]))
			{
				self::$s_sDocumentRoot = $aConfig['app_'.self::$s_sAppName]['documentroot'];
				self::$s_sRewriteConf = $aConfig['app_'.self::$s_sAppName]['rewriteconf'];
				self::$s_sRewriteCache = $aConfig['app_'.self::$s_sAppName]['rewritecache'];
			}
		}
	}
	
	public static function SGetAppName()
	{
		return self::$s_sAppName;
	}
	
	public static function SGetDocumentRoot()
	{
		return self::$s_sDocumentRoot;
	}
	
	public static function SGetRewriteConf()
	{
		return self::$s_sRewriteConf;
	}
	
	public static function SGetRewriteCache()
	{
		return self::$s_sRewriteCache;
	}
}
