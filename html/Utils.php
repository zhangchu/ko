<?php
/**
 * Utils
 *
 * @package ko
 * @subpackage html
 * @author zhangchu
 */

interface IKo_Html_Utils
{
	/**
	 * 判断html字符串的某个位置是否位于tag内部
	 *
	 * @return boolean
	 */
	public static function BIsInTag($sHtml, $iPos);
	/**
	 * 将文本进行标红处理，并截取摘要
	 * 传入的数据是文本格式，返回的数据是 html 格式
	 * 只支持UTF-8编码的文本，同时，外部需要进行 mb_internal_encoding('UTF-8') 设置
	 *
	 * @param array $aWord 要标红的词列表，应按照长度排序，最长的在前面，防止包含关系导致长词无法标红
	 * @return string
	 */
	public static function SHighlight($sText, $aWord, $iLength = 0, $sExt = '...', $sBefore = '<font color="red">', $sAfter = '</font>');
}

class Ko_Html_Utils implements IKo_Html_Utils
{
	/**
	 * @return boolean
	 */
	public static function BIsInTag($sHtml, $iPos)
	{
		$pos1 = mb_strpos($sHtml, '>', $iPos);
		if (false === $pos1)
		{
			return false;
		}
		$pos2 = mb_strpos($sHtml, '<', $iPos);
		if (false === $pos2 || $pos2 > $pos1)
		{
			return true;
		}
		return false;
	}
	
	/**
	 * @return string
	 */
	public static function SHighlight($sText, $aWord, $iLength = 0, $sExt = '...', $sBefore = '<font color="red">', $sAfter = '</font>')
	{
		$minpos = self::_IGetHighlightMinPos($sText, $aWord);
		$sSubText = self::_SGetHighlightSubStr($sText, $iLength, $sExt, $minpos);

		$sHtml = htmlspecialchars($sSubText);
		$htmllen = mb_strlen($sHtml);
		$linklen = mb_strlen($sBefore) + mb_strlen($sAfter);
		foreach ($aWord as $word)
		{
			$wlen = mb_strlen($word);
			$offset = 0;
			while ($offset < $htmllen)
			{
				$pos = mb_stripos($sHtml, $word, $offset);
				if (false === $pos)
				{
					break;
				}
				if (self::BIsInTag($sHtml, $pos) || self::_BIsInHighlight($sHtml, $pos, $sBefore, $sAfter))
				{
					$offset = $pos + $wlen;
					continue;
				}
				$sHtml = mb_substr($sHtml, 0, $pos).$sBefore.mb_substr($sHtml, $pos, $wlen).$sAfter.mb_substr($sHtml, $pos + $wlen);
				$htmllen += $linklen;
				$offset = $pos + $linklen + $wlen;
			}
		}
		return $sHtml;
	}
	
	private static function _IGetHighlightMinPos($sText, $aWord)
	{
		$minpos = false;
		foreach ($aWord as $word)
		{
			$pos = mb_stripos($sText, $word);
			if (false === $pos)
			{
				continue;
			}
			$minpos = (false === $minpos || $minpos > $pos) ? $pos : $minpos;
		}
		return intval($minpos);
	}
	
	private static function _SGetHighlightSubStr($sText, $iLength, $sExt, $iMinPos)
	{
		$clen = mb_strlen($sText);
		$iMinPos = $iMinPos - min(floor($iLength / 10), 10);		//回退若干个字符
		$iMinPos = min($iMinPos, $clen - floor($iLength / 2));	//保证末尾有足够字符
		$iMinPos = max(0, $iMinPos);								//保证大于0
		if ($iMinPos)
		{
			$sText = mb_substr($sText, $iMinPos);
			$sText = $sExt.Ko_Tool_Str::SShowStr_UTF8($sText, $iLength - strlen($sExt), $sExt);
		}
		else
		{
			$sText = Ko_Tool_Str::SShowStr_UTF8($sText, $iLength, $sExt);
		}
		return $sText;
	}
	
	private static function _BIsInHighlight($sHtml, $iPos, $sBefore, $sAfter)
	{
		$pos1 = mb_strpos($sHtml, $sAfter, $iPos);
		if (false === $pos1)
		{
			return false;
		}
		$pos2 = mb_strpos($sHtml, $sBefore, $iPos);
		if (false === $pos2 || $pos2 > $pos1)
		{
			return true;
		}
		return false;
	}
}
