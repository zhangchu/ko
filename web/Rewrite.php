<?php
/**
 * Rewrite
 *
 * @package ko\web
 * @author: jiangjw (joy.jingwei@gmail.com)
 */

class Ko_Web_Rewrite
{
    private static $s_aRules = array();
	
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
    
    public static function AGet($sRequestUri)
    {
        list($path, $query) = explode('?', $sRequestUri, 2);
        $paths = explode('/', $path);
        $paths = array_values(array_diff($paths, array('')));
        
        $keys = array();
        $matched = self::_SMatchPath($paths, self::$s_aRules, $keys);
        if (null === $matched)
        {
            return array(null, 0);
        }
        $keys = array_reverse($keys);
        list($location, $httpCode) = explode(' ', $matched, 2);
        
        $matchedPattern = '/^\/'.implode('\/', $keys).'/i';
        $uri = '/'.implode('/', $paths);
        if (!preg_match($matchedPattern, $uri, $match))
        {
            return array(null, 0);
        }
        $location = @preg_replace($matchedPattern, $location, $match[0]);
        if (false === $location)
        {
            return array(null, 0);
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
