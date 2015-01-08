<?php
/**
 * Utils
 *
 * @package ko\html
 * @author zhangchu
 */

class Ko_Html_Utils
{
	/**
	 * 去除文本中无用的空白字符
	 *
	 * @return string
	 */
	public static function SDeleteUselessBlank($sText)
	{
		return preg_replace('/[\s\x0b\0]+/', ' ', $sText);
	}
	
	/**
	 * 将文本进行标红处理，并截取摘要
	 * 传入的数据是文本格式，返回的数据是 html 格式
	 * 只支持UTF-8编码的文本，同时，外部需要进行 mb_internal_encoding('UTF-8') 设置
	 *
	 * @param array $aWord 要标红的词列表
	 * @return string
	 */
	public static function SHighlight($sText, $aWord, $iLength = 0, $sExt = '...', $sBefore = '<font color="red">', $sAfter = '</font>')
	{
		$sText = self::SDeleteUselessBlank($sText);
		$minpos = self::_IGetHighlightMinPos($sText, $aWord);
		$sSubText = self::_SGetHighlightSubStr($sText, $iLength, $sExt, $minpos);
		return self::_SHighlight($sSubText, $aWord, $sBefore, $sAfter);
	}
	
	/**
	 * 将数组转换为 html
	 *
	 * @return string
	 */
	public static function SArr2html($aData, $iDepth = 0)
	{
		if (is_array($aData))
		{
			$space = '';
			for ($i=0; $i<$iDepth; ++$i)
			{
				$space .= ' &nbsp;';
			}
			$html = 'Array (<br />'."\n";
			foreach ($aData as $k => $v)
			{
				$html .= ' &nbsp;'.$space.'['.htmlspecialchars($k).'] => '.self::SArr2html($v, $iDepth+1).'<br />'."\n";
			}
			$html .= $space.')';
		}
		else
		{
			$html = htmlspecialchars($aData);
		}
		return $html;
	}
	
	/**
	 * 返回时间的一个缩短显示格式
	 *
	 * @return string
	 */
	public static function SGetShortTime($sTime)
	{
		$t = strtotime($sTime);
		$showtime = date('Y-m-d H:i:s', $t);
		$now = time();
		$interval = $now - $t;
		if ($interval > 90 * 86400)
		{
			return '<a title="'.htmlspecialchars($sTime).'">'.htmlspecialchars(substr($showtime, 0, 10)).'</a>';
		}
		else if ($interval > 12 * 3600)
		{
			return '<a title="'.htmlspecialchars($sTime).'">'.htmlspecialchars(substr($showtime, 5, 11)).'</a>';
		}
		return '<a title="'.htmlspecialchars($sTime).'">'.htmlspecialchars(substr($showtime, 11, 8)).'</a>';
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
	
	private static function _BIsAlreadyHighlight($start, $end, &$posarr)
	{
		$delarr = array();
		foreach ($posarr as $k => $v)
		{
			if ($start <= $k && $v <= $end)
			{
				$delarr[] = $k;
			}
			else
			{
				if ($k <= $start && $start < $v)
				{
					return true;
				}
				if ($k < $end && $end <= $v)
				{
					return true;
				}
			}
		}
		foreach ($delarr as $k)
		{
			unset($posarr[$k]);
		}
		return false;
	}
	
	private static function _SHighlight($sText, $aWord, $sBefore, $sAfter)
	{
		$posarr = array();
		$textlen = mb_strlen($sText);
		foreach ($aWord as $word)
		{
			$wlen = mb_strlen($word);
			$offset = 0;
			while ($offset < $textlen)
			{
				$pos = mb_stripos($sText, $word, $offset);
				if (false === $pos)
				{
					break;
				}
				$offset = $pos + $wlen;
				if (self::_BIsAlreadyHighlight($pos, $offset, $posarr))
				{
					continue;
				}
				$posarr[$pos] = $offset;
			}
		}
		krsort($posarr, SORT_NUMERIC);
		$sHtml = '';
		foreach ($posarr as $k => $v)
		{
			$sHtml = $sBefore.htmlspecialchars(mb_substr($sText, $k, $v - $k)).$sAfter.htmlspecialchars(mb_substr($sText, $v)).$sHtml;
			$sText = mb_substr($sText, 0, $k);
		}
		$sHtml = htmlspecialchars($sText).$sHtml;
		return $sHtml;
	}
}
