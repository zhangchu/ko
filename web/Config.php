<?php
/**
 * Config
 *
 * @package ko/Web
 * @author zhangchu
 */

/**
 * 加载web域名等配置
 */
class Ko_Web_Config
{
	private static $s_sConfFilename = '';
	private static $s_sCacheFilename = '';
	private static $s_aConfig = array();
	private static $s_aHostConfig = array();

	private $_sAppName = '';
	private $_sDocumentRoot = '';
	private $_sRewriteConf = '';
	private $_sRewriteCache = '';

	public static function VSetConf($sConfFilename, $sCacheFilename = '')
	{
		self::$s_sConfFilename = $sConfFilename;
		self::$s_sCacheFilename = $sCacheFilename;
	}

	public static function VLoad()
	{
		if ('' !== self::$s_sConfFilename) {
			if ('' === self::$s_sCacheFilename) {
				self::ALoadFile(self::$s_sCacheFilename);
			} else {
				self::VLoadCacheFile(self::$s_sConfFilename, self::$s_sCacheFilename);
			}
		}
	}

	public static function VLoadCacheFile($sConfFilename, $sCacheFilename)
	{
		if (!is_file($sConfFilename)) {
			return;
		}
		$cacheDir = dirname($sCacheFilename);
		if (!is_dir($cacheDir)) {
			mkdir($cacheDir, 0777, true);
			if (!is_dir($cacheDir)) {
				self::ALoadFile($sConfFilename);
				return;
			}
		}
		if (!is_file($sCacheFilename) || filemtime($sConfFilename) > filemtime($sCacheFilename)) {
			$config = self::ALoadFile($sConfFilename);
			$script = "<?php\nKo_Web_Config::VLoadConfig("
				. Ko_Tool_Stringify::SConvArray($config)
				. ");\n";
			file_put_contents($sCacheFilename, $script);
		} else {
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
		self::$s_aConfig = $aConfig;
	}

	public static function SGetAppName($host = null)
	{
		return self::_OGetConfig($host)->_sAppName;
	}

	public static function SGetDocumentRoot($host = null)
	{
		return self::_OGetConfig($host)->_sDocumentRoot;
	}

	public static function SGetRewriteConf($host = null)
	{
		return self::_OGetConfig($host)->_sRewriteConf;
	}

	public static function SGetRewriteCache($host = null)
	{
		return self::_OGetConfig($host)->_sRewriteCache;
	}

	/**
	 * @return self
	 */
	private static function _OGetConfig($host)
	{
		if (is_null($host)) {
			$host = Ko_Web_Request::SHttpHost();
		}
		if (!isset(self::$s_aHostConfig[$host])) {
			self::$s_aHostConfig[$host] = new self;
			if (isset(self::$s_aConfig['global'][$host])) {
				$appname = self::$s_aConfig['global'][$host];
				self::$s_aHostConfig[$host]->_sAppName = $appname;
				if (isset(self::$s_aConfig['app_' . $appname])) {
					self::$s_aHostConfig[$host]->_sDocumentRoot = strval(self::$s_aConfig['app_' . $appname]['documentroot']);
					self::$s_aHostConfig[$host]->_sRewriteConf = strval(self::$s_aConfig['app_' . $appname]['rewriteconf']);
					self::$s_aHostConfig[$host]->_sRewriteCache = strval(self::$s_aConfig['app_' . $appname]['rewritecache']);
				}
			}
		}
		return self::$s_aHostConfig[$host];
	}
}
