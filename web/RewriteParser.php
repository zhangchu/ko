<?php
/**
 * RewriteParser
 *
 * @package ko\web
 * @author jiangjw & zhangchu
 */

/**
 * 简单 rewrite 规则分析器
 *
 * example:
 *   /sh/s.html /group/search.php
 *   /s/(.*).html /login/s.php?t=$1
 *   /travel-photos-albums/mafengwo/(\d+)/(\d+).html /photo/$1_$2.html 301
 *   /zs/ /hotel/ 301
 */
class Ko_Web_RewriteParser
{
	public static function AProcess($sText)
	{
		$rules = array();
		foreach (self::_aText2Array($sText) as $pattern => $rewrited)
		{
			Ko_Tool_Array::VOffsetSet($rules, $pattern, $rewrited, '/');
		}
		return $rules;
	}

	private static function _AText2Array($sText)
	{
		$arr = array();
		foreach (explode("\n", $sText) as $line)
		{
			list($line, $comment) = explode('#', $line, 2);
			$line = trim($line);
			if (0 === strlen($line))
			{
				continue;
			}
			list($pattern, $rewrited) = explode (' ', $line, 2);
			$pattern = ltrim($pattern, '^');
			$pattern = ltrim($pattern, '/');
			if ('$' === substr($pattern, -1))
			{
				$pattern = substr($pattern, 0, -1);
				$tag = '$';
			}
			else if ('*' === substr($pattern, -1))
			{
				$pattern = substr($pattern, 0, -1);
				$tag = '*';
			}
			else
			{
				$tag = '*';
			}
			$pattern .= '/'.$tag;
			$arr[$pattern] = self::_SNormalizeRule($rewrited);
		}
		return $arr;
	}

	private static function _SNormalizeRule($sUri)
	{
		list($location, $httpCode) = explode(' ', trim($sUri), 2);
		if ('/' !== substr($location, 0, 1))
		{
			$location = '/'.$location;
		}
		$httpCode = trim($httpCode);
		if ('301' !== $httpCode && '302' !== $httpCode)
		{
			return $location;
		}
		return $location.' '.$httpCode;
	}
}
