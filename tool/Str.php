<?php
/**
 * Str
 *
 * @package ko
 * @subpackage tool
 * @author zhangchu
 */

//include_once('../ko.class.php');

/**
 * 字符编码的一些函数接口
 */
interface IKo_Tool_Str
{
	/**
	 * 从 GB18030 转化为 UTF-8 编码
	 */
	public static function VConvert2UTF8(&$aIn);
	/**
	 * 从 GB18030 转化为 UTF-8 编码
	 *
	 * @return string
	 */
	public static function SConvert2UTF8($sIn);

	/**
	 * 从 UTF-8 转化为 GB18030 编码
	 */
	public static function VConvert2GB18030(&$aIn);
	/**
	 * 从 UTF-8 转化为 GB18030 编码
	 *
	 * @return string
	 */
	public static function SConvert2GB18030($sIn);

	/**
	 * 过滤掉不符合 utf-8/gb18030 规范的字符
	 */
	public static function VFilterErrorCode(&$aIn, $sCharset = '');
	/**
	 * 过滤掉不符合 utf-8/gb18030 规范的字符
	 *
	 * @return string
	 */
	public static function SFilterErrorCode($sIn, $sCharset = '');
	/**
	 * 过滤掉不符合 utf-8/gb18030 规范的字符
	 *
	 * @return string
	 */
	public static function SFilterErrorCode_UTF8($sIn);
	/**
	 * 过滤掉不符合 utf-8/gb18030 规范的字符
	 *
	 * @return string
	 */
	public static function SFilterErrorCode_GB18030($sIn);

	/**
	 * 截取字符串，保证返回数据字节数不超过 $iMaxLength
	 *
	 * @return string
	 */
	public static function SSubStr($sIn, $iMaxLength, $sExt = '', $sCharset = '');
	/**
	 * 截取字符串，保证返回数据字节数不超过 $iMaxLength
	 *
	 * @return string
	 */
	public static function SSubStr_UTF8($sIn, $iMaxLength, $sExt = '');
	/**
	 * 截取字符串，保证返回数据字节数不超过 $iMaxLength
	 *
	 * @return string
	 */
	public static function SSubStr_GB18030($sIn, $iMaxLength, $sExt = '');

	/**
	 * 截取字符串，保证返回数据页面显示长度不超过 $iShowLength，按照单字节字符占1位，多字节占2位计算
	 *
	 * @return string
	 */
	public static function SShowStr($sIn, $iShowLength, $sExt = '', $sCharset = '');
	/**
	 * 截取字符串，保证返回数据页面显示长度不超过 $iShowLength，按照单字节字符占1位，多字节占2位计算
	 *
	 * @return string
	 */
	public static function SShowStr_UTF8($sIn, $iShowLength, $sExt = '');
	/**
	 * 截取字符串，保证返回数据页面显示长度不超过 $iShowLength，按照单字节字符占1位，多字节占2位计算
	 *
	 * @return string
	 */
	public static function SShowStr_GB18030($sIn, $iShowLength, $sExt = '');

	/**
	 * 将字符串拆分到数组
	 *
	 * @return array
	 */
	public static function AStr2Arr($sIn, $sCharset = '');
	/**
	 * 将字符串拆分到数组
	 *
	 * @return array
	 */
	public static function AStr2Arr_UTF8($sIn);
	/**
	 * 将字符串拆分到数组
	 *
	 * @return array
	 */
	public static function AStr2Arr_GB18030($sIn);
}

/**
 * 字符编码的一些函数实现
 */
class Ko_Tool_Str implements IKo_Tool_Str
{
	private static $s_aGB = array('gb18030', 'gb2312', 'gbk');

	public static function VConvert2UTF8(&$aIn)
	{
		if (is_array($aIn))
		{
			foreach ($aIn as $k => $v)
			{
				if (is_array($v))
				{
					self::VConvert2UTF8($aIn[$k]);
				}
				else if (is_string($v))
				{
					$aIn[$k] = self::SConvert2UTF8($v);
				}
			}
		}
	}

	/**
	 * @return string
	 */
	public static function SConvert2UTF8($sIn)
	{
		$sRet = iconv('GB18030', 'UTF-8//IGNORE', $sIn);
		if (false === $sRet)
		{
			$sRet = iconv('GB18030', 'UTF-8//IGNORE', self::SFilterErrorCode($sIn, 'GB18030'));
		}
		return $sRet;
	}

	public static function VConvert2GB18030(&$aIn)
	{
		if (is_array($aIn))
		{
			foreach ($aIn as $k => $v)
			{
				if (is_array($v))
				{
					self::VConvert2GB18030($aIn[$k]);
				}
				else if (is_string($v))
				{
					$aIn[$k] = self::SConvert2GB18030($v);
				}
			}
		}
	}

	/**
	 * @return string
	 */
	public static function SConvert2GB18030($sIn)
	{
		$sRet = iconv('UTF-8', 'GB18030//IGNORE', $sIn);
		if (false === $sRet)
		{
			$sRet = iconv('UTF-8', 'GB18030//IGNORE', self::SFilterErrorCode($sIn, 'UTF-8'));
		}
		return $sRet;
	}

	public static function VFilterErrorCode(&$aIn, $sCharset = '')
	{
		if (is_array($aIn))
		{
			foreach ($aIn as $k => $v)
			{
				if (is_array($v))
				{
					self::VFilterErrorCode($aIn[$k], $sCharset);
				}
				else if (is_string($v))
				{
					$aIn[$k] = self::SFilterErrorCode($v, $sCharset);
				}
			}
		}
	}

	/**
	 * @return string
	 */
	public static function SFilterErrorCode($sIn, $sCharset = '')
	{
		$fn = 'SFilterErrorCode_'.self::_SConvertCharset($sCharset);
		return self::$fn($sIn);
	}

	/**
	 * @return string
	 */
	public static function SFilterErrorCode_UTF8($sIn)
	{
		$aOut = array(
			'start' => -1,
			'del' => array(),
			);
		self::_VCheckStr_UTF8($sIn, '_VFilterErrorCode_OnChar', '_VFilterErrorCode_OnFail', '_VFilterErrorCode_OnComplete', $aOut);
		return self::_SFilterStr($sIn, $aOut['del']);
	}

	/**
	 * @return string
	 */
	public static function SFilterErrorCode_GB18030($sIn)
	{
		$aOut = array(
			'start' => -1,
			'del' => array(),
			);
		self::_VCheckStr_GB18030($sIn, '_VFilterErrorCode_OnChar', '_VFilterErrorCode_OnFail', '_VFilterErrorCode_OnComplete', $aOut);
		return self::_SFilterStr($sIn, $aOut['del']);
	}

	/**
	 * @return string
	 */
	public static function SSubStr($sIn, $iMaxLength, $sExt = '', $sCharset = '')
	{
		$fn = 'SSubStr_'.self::_SConvertCharset($sCharset);
		return self::$fn($sIn, $iMaxLength, $sExt);
	}

	/**
	 * @return string
	 */
	public static function SSubStr_UTF8($sIn, $iMaxLength, $sExt = '')
	{
		return self::_SSubStr($sIn, $iMaxLength, $sExt, 'SFilterErrorCode_UTF8');
	}

	/**
	 * @return string
	 */
	public static function SSubStr_GB18030($sIn, $iMaxLength, $sExt = '')
	{
		return self::_SSubStr($sIn, $iMaxLength, $sExt, 'SFilterErrorCode_GB18030');
	}

	/**
	 * @return string
	 */
	public static function SShowStr($sIn, $iShowLength, $sExt = '', $sCharset = '')
	{
		$fn = 'SShowStr_'.self::_SConvertCharset($sCharset);
		return self::$fn($sIn, $iShowLength, $sExt);
	}

	/**
	 * @return string
	 */
	public static function SShowStr_UTF8($sIn, $iShowLength, $sExt = '')
	{
		return self::_SShowStr($sIn, $iShowLength, $sExt, 'AStr2Arr_UTF8', 6);
	}

	/**
	 * @return string
	 */
	public static function SShowStr_GB18030($sIn, $iShowLength, $sExt = '')
	{
		return self::_SShowStr($sIn, $iShowLength, $sExt, 'AStr2Arr_GB18030', 4);
	}

	/**
	 * @return array
	 */
	public static function AStr2Arr($sIn, $sCharset = '')
	{
		$fn = 'AStr2Arr_'.self::_SConvertCharset($sCharset);
		return self::$fn($sIn);
	}

	/**
	 * @return array
	 */
	public static function AStr2Arr_UTF8($sIn)
	{
		$aOut = array();
		self::_VCheckStr_UTF8($sIn, '_VStr2Arr_OnChar', '_VStr2Arr_OnFail', '_VStr2Arr_OnComplete', $aOut);
		return $aOut;
	}

	/**
	 * @return array
	 */
	public static function AStr2Arr_GB18030($sIn)
	{
		$aOut = array();
		self::_VCheckStr_GB18030($sIn, '_VStr2Arr_OnChar', '_VStr2Arr_OnFail', '_VStr2Arr_OnComplete', $aOut);
		return $aOut;
	}

	private static function _SConvertCharset($sCharset)
	{
		if (in_array(strtolower($sCharset), self::$s_aGB))
		{
			return 'GB18030';
		}
		return 'UTF8';
	}

	private static function _VStr2Arr_OnChar($sIn, $iStart, $iLen, &$aOut)
	{
		$aOut[] = substr($sIn, $iStart, $iLen);
	}

	private static function _VStr2Arr_OnFail($sIn, $iStart, &$aOut)
	{
	}

	private static function _VStr2Arr_OnComplete($sIn, $iLen, &$aOut)
	{
	}

	private static function _VFilterErrorCode_OnChar($sIn, $iStart, $iLen, &$aOut)
	{
		if ($aOut['start'] >= 0)
		{
			$aOut['del'][] = $aOut['start'];
			$aOut['del'][] = $iStart;
			$aOut['start'] = -1;
		}
	}

	private static function _VFilterErrorCode_OnFail($sIn, $iStart, &$aOut)
	{
		if ($aOut['start'] < 0)
		{
			$aOut['start'] = $iStart;
		}
	}

	private static function _VFilterErrorCode_OnComplete($sIn, $iLen, &$aOut)
	{
		if ($aOut['start'] >= 0)
		{
			$aOut['del'][] = $aOut['start'];
			$aOut['del'][] = $iLen;
			$aOut['start'] = -1;
		}
	}

	private static function _VCheckStr_UTF8($sIn, $fnChar, $fnFail, $fnComplete, &$aOut)
	{
		$iLen = strlen($sIn);
		for($i=0; $i<$iLen; $i++)
		{
			$c0 = ord($sIn[$i]);
			if ($c0 < 0x80)
			{
				self::$fnChar($sIn, $i, 1, $aOut);
				continue;
			}
			if (0xC0 <= $c0 && $c0 <= 0xFD)
			{
				if ($c0 >= 0xFC)       $j = 6;
				else if ($c0 >= 0xF8)  $j = 5;
				else if ($c0 >= 0xF0)  $j = 4;
				else if ($c0 >= 0xE0)  $j = 3;
				else                   $j = 2;
				if ($i + $j <= $iLen)
				{
					for ($k=1; $k<$j; $k++)
					{
						$ck = ord($sIn[$i + $k]);
						if (0x80 <= $ck && $ck <= 0xBF)
						{
							continue;
						}
						break;
					}
					if ($k === $j)
					{
						self::$fnChar($sIn, $i, $j, $aOut);
						$i += $j - 1;
						continue;
					}
				}
			}
			self::$fnFail($sIn, $i, $aOut);
		}
		self::$fnComplete($sIn, $iLen, $aOut);
	}

	private static function _VCheckStr_GB18030($sIn, $fnChar, $fnFail, $fnComplete, &$aOut)
	{
		$iLen = strlen($sIn);
		for($i=0; $i<$iLen; $i++)
		{
			$c0 = ord($sIn[$i]);
			if ($c0 < 0x80)
			{
				self::$fnChar($sIn, $i, 1, $aOut);
				continue;
			}
			if (0x81 <= $c0 && $c0 <= 0xFE)
			{
				$j = 2;
				if ($i + $j <= $iLen)
				{
					$c1 = ord($sIn[$i+1]);
					if ((0x40 <= $c1 && $c1 <= 0x7E) || (0x80 <= $c1 && $c1 <= 0xFE))
					{
						self::$fnChar($sIn, $i, 2, $aOut);
						$i ++;
						continue;
					}
					if (0x30 <= $c1 && $c1 <= 0x39)
					{
						$j = 4;
						if ($i + $j <= $iLen)
						{
							$c2 = ord($sIn[$i+2]);
							$c3 = ord($sIn[$i+3]);
							if (0x80 <= $c2 && $c2 <= 0xFE && 0x30 <= $c3 && $c3 <= 0x39)
							{
								self::$fnChar($sIn, $i, 4, $aOut);
								$i += 3;
								continue;
							}
						}
					}
				}
			}
			self::$fnFail($sIn, $i, $aOut);
		}
		self::$fnComplete($sIn, $iLen, $aOut);
	}

	private static function _SSubStr($sIn, $iMaxLength, $sExt, $fnFilterFunc)
	{
		$iLen = strlen($sIn);
		if ($iLen <= $iMaxLength)
		{
			return $sIn;
		}
		$iExtLen = strlen($sExt);
		assert($iExtLen < $iMaxLength);
		$sOut = substr($sIn, 0, $iMaxLength - $iExtLen);
		$sOut = self::$fnFilterFunc($sOut);
		return $sOut.$sExt;
	}

	private static function _SShowStr($sIn, $iShowLength, $sExt, $fnS2A, $iMaxLen)
	{
		$iLen = strlen($sIn);
		if ($iLen <= $iShowLength)
		{
			return $sIn;
		}
		$iExtLen = strlen($sExt);
		assert($iExtLen < $iShowLength);
		$sOut = substr($sIn, 0, ($iShowLength + 1) * $iMaxLen);
		$aStr = self::$fnS2A($sOut);
		$iCount = count($aStr);
		$iLen = 0;
		$iCut = $iCount;
		for ($i=0; $i<$iCount; $i++)
		{
			$iLen += (strlen($aStr[$i]) <= 1) ? 1 : 2;
			if ($iLen + $iExtLen > $iShowLength)
			{
				$iCut = min($iCut, $i);
			}
			if ($iLen > $iShowLength)
			{
				break;
			}
		}
		if ($iLen <= $iShowLength)
		{
			return $sIn;
		}
		$aStr = array_slice($aStr, 0, $iCut);
		return implode('', $aStr).$sExt;
	}

	private static function _SFilterStr($sIn, $aDel)
	{
		if (empty($aDel))
		{
			return $sIn;
		}
		$sOut = '';
		$iOffset = 0;
		$iLen = count($aDel);
		assert(0 === $iLen % 2);
		for ($i=0; $i<$iLen; $i+=2)
		{
			$sOut .= substr($sIn, $iOffset, $aDel[$i] - $iOffset);
			$iOffset = $aDel[$i+1];
		}
		$sOut .= substr($sIn, $iOffset);
		return $sOut;
	}
}

/*

$i = $argv[1];
$step = $argv[2];

$s = '中a文1';
$a = Ko_Tool_Str::AStr2Arr_UTF8($s);
var_dump($a);
$ret = Ko_Tool_Str::SShowStr_UTF8($s, $i);
echo strlen($ret).' '.$ret.' '.iconv("utf-8", "gb18030", $ret)."\n";
$ret = Ko_Tool_Str::SShowStr_UTF8($s, $i, '.');
echo strlen($ret).' '.$ret.' '.iconv("utf-8", "gb18030", $ret)."\n";
$ret = Ko_Tool_Str::SSubStr_UTF8($s, $i);
echo strlen($ret).' '.$ret.' '.iconv("utf-8", "gb18030", $ret)."\n";
$ret = Ko_Tool_Str::SSubStr_UTF8($s, $i, '.');
echo strlen($ret).' '.$ret.' '.iconv("utf-8", "gb18030", $ret)."\n";
$s = substr($s, 0, $i).substr($s, $i + $step);
$ret = Ko_Tool_Str::SFilterErrorCode_UTF8($s);
echo strlen($ret).' '.$ret.' '.iconv("utf-8", "gb18030", $ret)."\n";

$s = iconv("utf-8", "gb18030", '中a文1');
$a = Ko_Tool_Str::AStr2Arr_GB18030($s);
var_dump($a);
$ret = Ko_Tool_Str::SShowStr_GB18030($s, $i);
echo strlen($ret).' '.$ret."\n";
$ret = Ko_Tool_Str::SShowStr_GB18030($s, $i, '.');
echo strlen($ret).' '.$ret."\n";
$ret = Ko_Tool_Str::SSubStr_GB18030($s, $i);
echo strlen($ret).' '.$ret."\n";
$ret = Ko_Tool_Str::SSubStr_GB18030($s, $i, '.');
echo strlen($ret).' '.$ret."\n";
$s = substr($s, 0, $i).substr($s, $i + $step);
$ret = Ko_Tool_Str::SFilterErrorCode_GB18030($s);
echo strlen($ret).' '.$ret."\n";

$aIn = array(
	'中文', array('语文', '试验'),
);
Ko_Tool_Str::VConvert2GB18030($aIn);
var_dump($aIn);
Ko_Tool_Str::VConvert2UTF8($aIn);
var_dump($aIn);

$sIn = '中文测试';
$sIn = Ko_Tool_Str::SConvert2GB18030($sIn);
var_dump($sIn);
$sIn = substr($sIn, 0, 7);
$sOut = iconv('GB18030', 'UTF-8//IGNORE', $sIn);
var_dump($sOut);
$sIn = Ko_Tool_Str::SConvert2UTF8($sIn);
var_dump($sIn);

*/
?>