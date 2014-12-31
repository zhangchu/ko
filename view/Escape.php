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
	 * 封装 addslashes 和 _SForbidScript 编码
	 *
	 * @return string
	 */
	public static function SAddSlashes($sInput)
	{
		return addslashes(self::_SForbidScript($sInput));
	}

	/**
	 * 封装 addslashes 和 _SForbidScript 和 htmlspecialchars 编码
	 *
	 * @return string
	 */
	public static function SAddSlashesHtml($sInput)
	{
		return self::SAddSlashes(htmlspecialchars($sInput));
	}

	/**
	 * 封装 nl2br 和 htmlspecialchars 编码
	 *
	 * @return string
	 */
	public static function SMultiline($sInput)
	{
		return nl2br(htmlspecialchars($sInput));
	}
	
	/**
	 * JSON_ENCODE
	 *
	 * @return string
	 */
	public static function SEscapeJson($vValue)
	{
		return json_encode($vValue);
	}
	
	/**
	 * 将HTML作为普通文本设置到编辑器中，或 html文本 编辑器编辑
	 *
	 * @return string
	 */
	public static function SEscapeEditor($sValue, $sTextType='html')
	{
		if($sTextType == 'plain')
  		{
			$sValue = str_replace(array('&quot;', '&lt;', '&gt;', '&amp;'), array('"', '<', '>', '&'),
						str_replace(array('<br />', '<br/>'), array('', ''), $sValue));
		}
		return str_replace(array("\n", "\r"), array('\\n', ''), self::SAddSlashes($sValue));
	}

	/**
	 * 单行文本 input编辑/显示 或 多行文本 textarea编辑
	 */
	public static function VEscapeHtml($vValue, $aExclude=array())
	{
		return self::_VEscape('', $vValue, 'htmlspecialchars', $aExclude);
	}
	
	/**
	 * 单行文本 简单的作为JS变量
	 */
	public static function VEscapeSlashes($vValue, $aExclude=array())
	{
		return self::_VEscape('', $vValue, array('self', 'SAddSlashes'), $aExclude);
	}
	
	/**
	 * 单行文本 作为JS变量，并最终输出到页面显示
	 */
	public static function VEscapeSlashesHtml($vValue, $aExclude=array())
	{
		return self::_VEscape('', $vValue, array('self', 'SAddSlashesHtml'), $aExclude);
	}
	
	/**
	 * 多行文本 显示
	 */
	public static function VEscapeMultiline($vValue, $aExclude=array())
	{
		return self::_VEscape('', $vValue, array('self', 'SMultiline'), $aExclude);
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

	private static function _SForbidScript($sText)
	{
		$sText = str_replace("\r", '', $sText);
		return preg_replace('/script/i', ' script ', $sText);
	}
}
