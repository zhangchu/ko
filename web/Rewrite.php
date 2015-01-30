<?php
/**
 * Rewrite
 *
 * @package ko\web
 * @author: jiangjw (joy.jingwei@gmail.com)
 */

class Ko_Web_Rewrite
{
    private $_aRules = array();

    public function vLoadFile($sFilename)
    {
        $content = file_get_contents($sFilename);
        if (false === $content)
        {
            return array();
        }
        return $this->vLoadConf($content);
    }
    
    public function vLoadConf($sContent)
    {
        $rules = Ko_Web_RewriteParser::AProcess($sContent);
        return $this->vLoadRules($rules);
    }
    
    public function vLoadRules($aRules)
    {
        $this->_aRules = $aRules;
    }
    
    public function aGet($sRequestUri)
    {
        list($path, $query) = explode('?', $sRequestUri, 2);
        $paths = explode('/', $path);
        $paths = array_values(array_diff($paths, array('')));
        
        $keys = array();
        $matched = $this->_sMatchPath($paths, $this->_aRules, $keys);
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
    
    private function _sMatchPath($paths, $rules, &$aKeys)
    {
        $path = array_shift($paths);
        if (null === $path)
        {
            if (isset($rules['$']))
            {
                return $rules['$'];
            }
            else if (isset($rules['*']))
            {
                return $rules['*'];
            }
            return null;
        }
        
        if (isset($rules[$path]) && @preg_match ('/'.$path.'/', $path))
        {
            $matched = $this->_sMatchPath($paths, $rules[$path], $aKeys);
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
                $matched = $this->_sMatchPath($paths, $subrules, $aKeys);
                if (null !== $matched)
                {
                    $aKeys[] = $pattern;
                    return $matched;
                }
            }
        }
        if (isset($rules['*']))
        {
            return $rules['*'];
        }
        return null;
    }
}
