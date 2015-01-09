<?php
/**
 * Text
 *
 * @package ko\Mode\Content
 * @author zhangchu
 */

class Ko_Mode_Content_Text implements IKo_Mode_Content_Base
{
	public static function S2Valid($sIn, $iMaxLength)
	{
		return Ko_Tool_Str::SSubStr_UTF8(Ko_Tool_Str::SForce2UTF8($sIn), $iMaxLength);
	}
	
	public static function S2Text($sIn, $iMaxLength = 0, $sExt = '')
	{
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
			$sIn = Ko_Tool_Str::SSubStr_UTF8($sIn, $iMaxLength);
		}
		$sIn = Ko_View_Escape::VEscapeHtml($sIn);
		if ($iMaxLength)
		{
			$sIn = Ko_Html_WebParse::sParse($sIn, $iMaxLength, 'UTF-8');
		}
		return $sIn;
	}
}
