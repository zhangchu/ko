<?php
/**
 * Config
 *
 * @package ko/Web
 * @author zhangchu
 */

if (!defined('KO_CONFIG_SITE_INI'))
{
	define('KO_CONFIG_SITE_INI', '');
}
if (!defined('KO_CONFIG_SITE_CACHE'))
{
	define('KO_CONFIG_SITE_CACHE', '');
}

/**
 * 加载web域名等配置
 */
class Ko_Web_Config
{
	private static $s_sConfFile = KO_CONFIG_SITE_INI;
	private static $s_sCacheFile = KO_CONFIG_SITE_CACHE;
	private static $s_aConfig = array();
	private static $s_aConfigCache = array();

	private $_sAppName = '';
	private $_sDocumentRoot = '';
	private $_sRewriteConf = '';
	private $_sRewriteCache = '';
	private $_sRewriteUri = '';

	public static function VSetConf($sConfFile, $sCacheFile = '')
	{
		self::$s_sConfFile = $sConfFile;
		self::$s_sCacheFile = $sCacheFile;
	}

	public static function VLoad()
	{
		if (is_file(self::$s_sConfFile)) {
			if ('' === self::$s_sCacheFile) {
				self::$s_aConfig = parse_ini_file(self::$s_sConfFile, true);
			} else {
				$cacheDir = dirname(self::$s_sCacheFile);
				if (!is_dir($cacheDir)) {
					mkdir($cacheDir, 0777, true);
					if (!is_dir($cacheDir)) {
						self::$s_aConfig = parse_ini_file(self::$s_sConfFile, true);
						return;
					}
				}
				if (!is_file(self::$s_sCacheFile) || filemtime(self::$s_sConfFile) > filemtime(self::$s_sCacheFile)) {
					self::$s_aConfig = parse_ini_file(self::$s_sConfFile, true);
					$script = "<?php\nKo_Web_Config::VLoadConfig("
						. var_export(self::$s_aConfig, true)
						. ");\n";
					file_put_contents(self::$s_sCacheFile, $script);
				} else {
					require_once(self::$s_sCacheFile);
				}
			}
		}
	}

	public static function VLoadConfig($aConfig)
	{
		self::$s_aConfig = $aConfig;
	}

	public static function SGetAppName($host = null, &$uri = null)
	{
		return self::_OGetConfig($host, $uri)->_sAppName;
	}

	public static function SGetDocumentRoot($host = null, &$uri = null)
	{
		return self::_OGetConfig($host, $uri)->_sDocumentRoot;
	}

	public static function SGetRewriteConf($host = null, &$uri = null)
	{
		return self::_OGetConfig($host, $uri)->_sRewriteConf;
	}

	public static function SGetRewriteCache($host = null, &$uri = null)
	{
		return self::_OGetConfig($host, $uri)->_sRewriteCache;
	}

	/**
	 * @return self
	 */
	private static function _OGetConfig($host, &$uri)
	{
		if (is_null($host)) {
			$host = Ko_Web_Request::SHttpHost();
		}
		if (is_null($uri)) {
			$uri = Ko_Web_Request::SRequestUri();
		}
		if (false === strpos($uri, '?')) {
			$path = $uri;
			$query = '';
		} else {
			list($path, $query) = explode('?', $uri, 2);
			$query = '?'.$query;
		}
		$key = $host.$path;
		if (!isset(self::$s_aConfigCache[$key])) {
			$path = rtrim($key, '/');
			$succ = false;
			while (false !== ($pos = strrpos($path, '/'))) {
				if ($succ = self::_BLoadConfig($key, $path, false)) {
					break;
				}
				$path = rtrim(substr($path, 0, $pos), '/');
			}
			if (!$succ) {
				self::_BLoadConfig($key, $path, true);
			}
			$rewriteuri = substr($key, strlen($path));
			if (0 === strlen($rewriteuri)) {
				$rewriteuri = '/';
			}
			self::$s_aConfigCache[$key]->_sRewriteUri = $rewriteuri.$query;
		}
		$uri = self::$s_aConfigCache[$key]->_sRewriteUri;
		return self::$s_aConfigCache[$key];
	}

	private static function _BLoadConfig($key, $path, $force)
	{
		if (!isset(self::$s_aConfig['global'][$path]) && !$force) {
			return false;
		}
		self::$s_aConfigCache[$key] = new self;
		if (isset(self::$s_aConfig['global'][$path])) {
			$appname = self::$s_aConfig['global'][$path];
			self::$s_aConfigCache[$key]->_sAppName = $appname;
			if (isset(self::$s_aConfig['app_' . $appname])) {
				self::$s_aConfigCache[$key]->_sDocumentRoot = strval(self::$s_aConfig['app_' . $appname]['documentroot']);
				self::$s_aConfigCache[$key]->_sRewriteConf = strval(self::$s_aConfig['app_' . $appname]['rewriteconf']);
				self::$s_aConfigCache[$key]->_sRewriteCache = strval(self::$s_aConfig['app_' . $appname]['rewritecache']);
			}
		}
		return true;
	}
}
