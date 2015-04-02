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

	private static $s_sRewrited = '';
	private static $s_iHttpCode = 0;

	public static function VHandle()
	{
		$confFilename = Ko_Web_Config::SGetRewriteConf();
		if ('' !== $confFilename)
		{
			$cacheFilename = Ko_Web_Config::SGetRewriteCache();
			if ('' === $cacheFilename)
			{
				self::VLoadFile($confFilename);
			}
			else
			{
				self::VLoadCacheFile($confFilename, $cacheFilename);
			}
		}
		list($rewrited, $httpcode) = self::AGet();
		if ($httpcode)
		{
			Ko_Web_Response::VSetRedirect($rewrited);
			Ko_Web_Response::VSetHttpCode($httpcode);
			Ko_Web_Response::VSend();
			exit;
		}
		Ko_Web_Utils::VResetEnv($rewrited);
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
				self::VLoadFile($sConfFilename);
				return;
			}
		}
		if (!is_file($sCacheFilename) || filemtime($sConfFilename) > filemtime($sCacheFilename))
		{
			self::VLoadFile($sConfFilename);
			$script = "<?php\nKo_Web_Rewrite::VLoadRules("
				.Ko_Tool_Stringify::SConvArray(self::$s_aRules)
				.");\n";
			file_put_contents($sCacheFilename, $script);
		}
		else
		{
			require_once($sCacheFilename);
		}
	}

	public static function VLoadFile($sFilename)
	{
		$content = file_get_contents($sFilename);
		if (false === $content)
		{
			return array();
		}
		return self::VLoadConf($content);
	}

	public static function VLoadConf($sContent)
	{
		$rules = Ko_Web_RewriteParser::AProcess($sContent);
		return self::VLoadRules($rules);
	}

	public static function VLoadRules($aRules)
	{
		self::$s_aRules = $aRules;
	}

	public static function AGet()
	{
		if ('' === self::$s_sRewrited)
		{
			$uri = Ko_Web_Request::SRequestUri();
			list(self::$s_sRewrited, self::$s_iHttpCode) = self::_AGet($uri);
		}
		return array(self::$s_sRewrited, self::$s_iHttpCode);
	}

	private static function _AGet($sUri)
	{
		list($path, $query) = explode('?', $sUri, 2);

		$paths = self::_ASplitPath($path);
		$keys = array();
		if (is_null($matched = self::_VMatchPath($paths, self::$s_aRules, $keys)))
		{
			return array($sUri, 0);
		}
		$keys = array_reverse($keys);
		list($location, $httpCode) = explode(' ', $matched, 2);

		$slashmismatch = false;
		$keylen = count($keys);
		$pathlen = count($paths);
		if ($keylen === $pathlen + 1 && '' === $keys[$keylen - 1])
		{	//规则: /a/b/   URI: /a/b
			$slashmismatch = true;
			$paths[] = '';
		}
		elseif ($keylen + 1 === $pathlen && '' === $paths[$pathlen - 1])
		{	//规则: /a/b   URI: /a/b/
			$slashmismatch = true;
			array_pop($paths);
		}

		if ($slashmismatch && 'GET' === Ko_Web_Request::SRequestMethod())
		{
			$location = '/'.implode('/', $paths);
			$httpCode = 301;
		}
		else
		{
			$matchedPattern = '/^\/'.implode('\/', $keys).'/i';
			$uri = '/'.implode('/', $paths);
			if (!@preg_match($matchedPattern, $uri, $match) ||
				false === ($location = @preg_replace($matchedPattern, $location, $match[0])))
			{
				return array($sUri, 0);
			}
		}

		if (isset($query))
		{
			$location .= (false === strpos($location, '?')) ? '?' : '&';
			$location .= $query;
		}
		return array($location, intval($httpCode));
	}

	private static function _VMatchPath($aPath, $aRule, &$aKeys)
	{
		$path = array_shift($aPath);
		if (null === $path)
		{
			if (isset($aRule['$']))
			{	// 规则: /a/b$    URI: /a/b
				return $aRule['$'];
			}
			if (isset($aRule['']['$']))
			{	// 规则: /a/b/$    URI: /a/b
				$aKeys[] = '';
				return $aRule['']['$'];
			}
			if (isset($aRule['*']))
			{	// 规则: /a/b    URI: /a/b
				return $aRule['*'];
			}
			if (isset($aRule['']['*']))
			{	// 规则: /a/b/    URI: /a/b
				$aKeys[] = '';
				return $aRule['']['*'];
			}
			return null;
		}
		
		if (isset($aRule[$path]) && @preg_match('/'.$path.'/', $path))
		{	//精确匹配
			$matched = self::_VMatchPath($aPath, $aRule[$path], $aKeys);
			if (null !== $matched)
			{
				$aKeys[] = $path;
				return $matched;
			}
		}
		foreach ($aRule as $pattern => $subrules)
		{
			if ('$' === $pattern || '*' === $pattern)
			{
				continue;
			}
			if (@preg_match('/^'.$pattern.'$/i', $path))
			{	//正则匹配
				$matched = self::_VMatchPath($aPath, $subrules, $aKeys);
				if (null !== $matched)
				{
					$aKeys[] = $pattern;
					return $matched;
				}
			}
		}
		
		if (isset($aRule['']['*']))
		{	// 规则: /a/b/    URI: /a/b/c    path: c
			$aKeys[] = '';
			return $aRule['']['*'];
		}
		if (isset($aRule['*']))
		{	// 规则: /a/b    URI: /a/b/c    path: c
			return $aRule['*'];
		}
		return null;
	}

	private static function _ASplitPath($sPath)
	{
		$paths = array_diff(explode('/', $sPath), array(''));
		if ('/' === substr($sPath, -1))
		{
			$paths[] = '';
		}
		return array_values($paths);
	}
}
