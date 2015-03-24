<?php
/**
 * ImgParse
 *
 * @package ko\html
 * @author zhangchu
 */

class Ko_Html_ImgParse
{
	public static function sParse($sHtml, $iMaxLength = 0, $sCharset = '')
	{
		$filter = new Ko_Html_ImgFilter;
		return Ko_Html_Parse::sParse($filter, $sHtml, $iMaxLength, $sCharset);
	}
}
