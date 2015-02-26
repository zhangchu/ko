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
	private static $s_sConfFilename = '';
	private static $s_sCacheFilename = '';
	
	private static $s_aRules = array();
	
	private static $s_sRewrited = '';
	private static $s_iHttpCode = 0;
	
	public static function VSetConf($sConfFilename, $sCacheFilename)
	{
		self::$s_sConfFilename = $sConfFilename;
		self::$s_sCacheFilename = $sCacheFilename;
	}
	
	public static function VHandle()
	{
		if ('' === self::$s_sConfFilename)
		{
			return;
		}
		if ('' === self::$s_sCacheFilename)
		{
			self::VLoadFile(self::$s_sConfFilename);
		}
		else
		{
			self::VLoadCacheFile(self::$s_sConfFilename, self::$s_sCacheFilename);
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
	
	private static function _AGet($sRequestUri)
	{
		list($path, $query) = explode('?', $sRequestUri, 2);
		$paths = explode('/', $path);
		$paths = array_values(array_diff($paths, array('')));
		
		$keys = array();
		$matched = self::_SMatchPath($paths, self::$s_aRules, $keys);
		if (null === $matched)
		{
			return array($sRequestUri, 0);
		}
		$keys = array_reverse($keys);
		list($location, $httpCode) = explode(' ', $matched, 2);
		
		$matchedPattern = '/^\/'.implode('\/', $keys).'/i';
		$uri = '/'.implode('/', $paths);
		if (!@preg_match($matchedPattern, $uri, $match))
		{
			return array($sRequestUri, 0);
		}
		$location = @preg_replace($matchedPattern, $location, $match[0]);
		if (false === $location)
		{
			return array($sRequestUri, 0);
		}
		$httpCode = intval($httpCode);
		
		if (isset($query))
		{
			if (false === strpos($location, '?'))
			{
				$location .= '?'.$query;
			}
			else
			{
				$location .= '&'.$query;
			}
		}
		return array($location, $httpCode);
	}
	
	private static function _SMatchPath($paths, $rules, &$aKeys)
	{
		$path = array_shift($paths);
		if (null === $path)
		{
			if (isset($rules['$']))
			{
				return $rules['$'];
			}
			return isset($rules['*']) ? $rules['*'] : null;
		}
		
		if (isset($rules[$path]) && @preg_match ('/'.$path.'/', $path))
		{
			$matched = self::_SMatchPath($paths, $rules[$path], $aKeys);
			if (null !== $matched)
			{
				$aKeys[] = $path;
				return $matched;
			}
		}
		foreach ($rules as $pattern => $subrules)
		{
			if ('$' === $pattern || '*' === $pattern)
			{
				continue;
			}
			if (@preg_match('/^'.$pattern.'$/i', $path))
			{
				$matched = self::_SMatchPath($paths, $subrules, $aKeys);
				if (null !== $matched)
				{
					$aKeys[] = $pattern;
					return $matched;
				}
			}
		}
		return isset($rules['*']) ? $rules['*'] : null;
	}
}
