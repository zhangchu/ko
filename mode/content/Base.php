<?php
/**
 * Base
 *
 * @package ko\Mode\Content
 * @author zhangchu
 */

interface IKo_Mode_Content_Base
{
	public static function S2Valid($sIn, $iMaxLength);
	public static function S2Text($sIn, $iMaxLength = 0, $sExt = '');
	public static function S2Html($sIn, $iMaxLength = 0);
}
