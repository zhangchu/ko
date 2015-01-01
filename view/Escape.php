<?php
/**
 * Escape
 *
 * @package ko\view
 * @author zhangchu
 */

class Ko_View_Escape
{
	/**
	 * 单行文本 input编辑/显示 或 多行文本 textarea编辑
	 */
	public static function VEscapeHtml($vValue, $aExclude=array())
	{
		return self::_VEscape('', $vValue, 'htmlspecialchars', $aExclude);
	}
	
	private static function _VEscape($sKey, $vInput, $fnEscape, $aExclude)
	{
		if (in_array($sKey, $aExclude, true))
		{
			return $vInput;
		}
		if (is_array($vInput))
		{
			foreach ($vInput as $k => $v)
			{
				$vInput[$k] = self::_VEscape($k, $v, $fnEscape, $aExclude);
			}
			return $vInput;
		}
		return call_user_func($fnEscape, $vInput);
	}
}
