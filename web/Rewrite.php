<?php
/**
 * Rewrite
 *
 * @package ko\web
 * @author jiangjw & zhangchu
 */

class Ko_Web_Rewrite
{
    private static $s_aRules = array();
    
    public static function VLoadCacheFile($sConfFilename, $sCacheFilename)
    {
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
        if (!@preg_match($matchedPattern, $uri, $match))
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
        self::_VResetEnv($location);
        return array($location, $httpCode);
    }
    
    private static function _VResetEnv($location)
    {
        list($sn, $qs) = explode('?', $location, 2);
        parse_str($qs, $arr);
        $GLOBALS['_GET'] = $_GET = $arr;
        $GLOBALS['_REQUEST'] = $_REQUEST = $_REQUEST + $arr;
        $GLOBALS['_SERVER']['QUERY_STRING'] = $_SERVER['QUERY_STRING'] =
        $GLOBALS['_ENV']['QUERY_STRING'] = $_ENV['QUERY_STRING'] = $qs;
        
        if (false !== ($pos = strpos($sn, '.php/')))
        {
            $pi = substr($sn, $pos + 4);
            $sn = substr($sn, 0, $pos + 4);
        }
        else
        {
            $pi = '';
        }
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
