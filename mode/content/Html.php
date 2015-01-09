<?php
/**
 * Html
 *
 * @package ko\Mode\Content
 * @author zhangchu
 */

class Ko_Mode_Content_Html implements IKo_Mode_Content_Base
{
	public static function S2Valid($sIn, $iMaxLength)
	{
		return Ko_Html_WebParse::sParse(Ko_Tool_Str::SForce2UTF8($sIn), $iMaxLength, 'UTF-8');
	}
	
	public static function S2Text($sIn, $iMaxLength = 0, $sExt = '')
	{
		$sIn = strip_tags($sIn);
		$sIn = str_replace('&nbsp;', ' ', $sIn);
		$sIn = htmlspecialchars_decode($sIn);
		$sIn = Ko_Html_Utils::SDeleteUselessBlank($sIn);
		if ($iMaxLength)
		{
			$sIn = Ko_Tool_Str::SSubStr_UTF8($sIn, $iMaxLength, $sExt);
		}
		return $sIn;
	}
	
	public static function S2Html($sIn, $iMaxLength = 0)
	{
		if ($iMaxLength)
		{
			$sIn = self::S2Text($sIn, $iMaxLength, '...');
			$sIn = Ko_View_Escape::VEscapeHtml($sIn);
			$sIn = Ko_Html_WebParse::sParse($sIn, $iMaxLength, 'UTF-8');
		}
		return $sIn;
	}
}
