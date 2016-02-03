<?php
/**
 * Rewrite
 *
 * @package ko\web
 * @author jiangjw & zhangchu
 */

/**
 * 根据 request_uri 进行 rewrite 操作
 */
class Ko_Web_Rewrite
{
	private static $s_aRules = array();
	private static $s_aResults = array();

	public static function VHandle()
	{
		list($rewrited, $httpcode) = self::AGet();
		if ($httpcode) {
			$approot = Ko_Web_Config::SGetAppRoot();
			if (false === ($pos = strpos($approot, '/'))) {
				Ko_Web_Response::VSetRedirect($rewrited);
			} else {
				Ko_Web_Response::VSetRedirect(substr($approot, $pos) . $rewrited);
			}
			Ko_Web_Response::VSetHttpCode($httpcode);
			Ko_Web_Response::VSend();
			exit;
		}
		Ko_Web_Utils::VResetEnv($rewrited);
	}

	public static function AGet($sUrl = '')
	{
		if (!isset(self::$s_aResults[$sUrl])) {
			if ('' === $sUrl) {
				$host = Ko_Web_Request::SHttpHost();
				$uri = Ko_Web_Request::SRequestUri();
			} else {
				$info = parse_url($sUrl);
				$host = isset($info['port']) ? $info['host'] . ':' . $info['port'] : $info['host'];
				$uri = isset($info['query']) ? $info['path'] . '?' . $info['query'] : $info['path'];
			}
			self::$s_aResults[$sUrl] = self::_AGet($host, $uri);
		}
		return self::$s_aResults[$sUrl];
	}

	public static function VLoadRules($aRules)
	{
		self::$s_aRules = $aRules;
	}

	private static function _VLoadHostRules($sHost, &$sUri)
	{
		$srcUri = $sUri;
		$confFile = Ko_Web_Config::SGetValue('rewriteconf', $sHost, $sUri);
		if (is_file($confFile)) {
			$cacheFile = Ko_Web_Config::SGetValue('rewritecache', $sHost, $srcUri);
			if ('' === $cacheFile && defined('KO_RUNTIME_DIR')) {
				$basename = basename($confFile);
				$cacheFile = KO_RUNTIME_DIR . '/' . $basename . '_rewritecache_' . md5($confFile) . '.php';
			}
			if ('' === $cacheFile) {
				self::$s_aRules = Ko_Web_RewriteParser::AProcess(file_get_contents($confFile));
			} else {
				$cacheDir = dirname($cacheFile);
				if (!is_dir($cacheDir)) {
					mkdir($cacheDir, 0777, true);
					if (!is_dir($cacheDir)) {
						self::$s_aRules = Ko_Web_RewriteParser::AProcess(file_get_contents($confFile));
						return;
					}
				}
				if (!is_file($cacheFile) || filemtime($confFile) > filemtime($cacheFile)) {
					self::$s_aRules = Ko_Web_RewriteParser::AProcess(file_get_contents($confFile));
					$script = "<?php\nKo_Web_Rewrite::VLoadRules("
						. var_export(self::$s_aRules, true)
						. ");\n";
					file_put_contents($cacheFile, $script);
				} else {
					require($cacheFile);
				}
			}
		}
	}

	private static function _AGet($sHost, $sUri)
	{
		self::_VLoadHostRules($sHost, $sUri);

		list($path, $query) = explode('?', $sUri, 2);

		$paths = self::_ASplitPath($path);
		$keys = array();
		if (is_null($matched = self::_VMatchPath($paths, self::$s_aRules, $keys))) {
			return array($sUri, 0);
		}
		$keys = array_reverse($keys);
		list($location, $httpCode) = explode(' ', $matched, 2);

		$slashmismatch = false;
		$keylen = count($keys);
		$pathlen = count($paths);
		if ($keylen === $pathlen + 1 && '' === $keys[$keylen - 1]) {    //规则: /a/b/   URI: /a/b
			$slashmismatch = true;
			$paths[] = '';
		} elseif ($keylen + 1 === $pathlen && '' === $paths[$pathlen - 1]) {    //规则: /a/b   URI: /a/b/
			$slashmismatch = true;
			array_pop($paths);
		}

		if ($slashmismatch && 'GET' === Ko_Web_Request::SRequestMethod()) {
			$location = '/' . implode('/', $paths);
			$httpCode = 301;
		} else {
			$matchedPattern = '/^\/' . implode('\/', $keys) . '/i';
			$uri = '/' . implode('/', $paths);
			if (!@preg_match($matchedPattern, $uri, $match) ||
				false === ($location = @preg_replace($matchedPattern, $location, $match[0]))
			) {
				return array($sUri, 0);
			}
		}

		if (isset($query)) {
			$location .= (false === strpos($location, '?')) ? '?' : '&';
			$location .= $query;
		}
		return array($location, intval($httpCode));
	}

	private static function _VMatchPath($aPath, $aRule, &$aKeys)
	{
		$path = array_shift($aPath);
		if (null === $path) {
			if (isset($aRule['$'])) {    // 规则: /a/b$    URI: /a/b
				return $aRule['$'];
			}
			if (isset($aRule['']['$'])) {    // 规则: /a/b/$    URI: /a/b
				$aKeys[] = '';
				return $aRule['']['$'];
			}
			if (isset($aRule['*'])) {    // 规则: /a/b    URI: /a/b
				return $aRule['*'];
			}
			if (isset($aRule['']['*'])) {    // 规则: /a/b/    URI: /a/b
				$aKeys[] = '';
				return $aRule['']['*'];
			}
			return null;
		}

		if (isset($aRule[$path]) && @preg_match('/' . $path . '/', $path)) {    //精确匹配
			$matched = self::_VMatchPath($aPath, $aRule[$path], $aKeys);
			if (null !== $matched) {
				$aKeys[] = $path;
				return $matched;
			}
		}
		foreach ($aRule as $pattern => $subrules) {
			if ('$' === $pattern || '*' === $pattern) {
				continue;
			}
			if (@preg_match('/^' . $pattern . '$/i', $path)) {    //正则匹配
				$matched = self::_VMatchPath($aPath, $subrules, $aKeys);
				if (null !== $matched) {
					$aKeys[] = $pattern;
					return $matched;
				}
			}
		}

		if (isset($aRule['']['*'])) {    // 规则: /a/b/    URI: /a/b/c    path: c
			$aKeys[] = '';
			return $aRule['']['*'];
		}
		if (isset($aRule['*'])) {    // 规则: /a/b    URI: /a/b/c    path: c
			return $aRule['*'];
		}
		return null;
	}

	private static function _ASplitPath($sPath)
	{
		$paths = array_diff(explode('/', $sPath), array(''));
		if ('/' === substr($sPath, -1)) {
			$paths[] = '';
		}
		return array_values($paths);
	}
}
