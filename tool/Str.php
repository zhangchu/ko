<?php
/**
 * Str
 *
 * @package ko\tool
 * @author zhangchu
 */

//include_once('../ko.class.php');

/**
 * 字符编码的一些函数实现
 */
class Ko_Tool_Str
{
	private static $s_aGB = array('gb18030', 'gb2312', 'gbk');
	
	/**
	 * 无论输入是utf-8还是gb18030，都返回utf8
	 * 由于要进行字符检查，在已知输入的字符集的时候，应该使用 SConvert2UTF8
	 *
	 * @return string
	 */
	public static function SForce2UTF8($sIn)
	{
		return self::BIsUtf8($sIn)
			? $sIn
			: self::SConvert2UTF8($sIn);
	}

	/**
	 * 无论输入是utf-8还是gb18030，都返回utf8
	 */
	public static function VForce2UTF8(&$aIn)
	{
		if (is_array($aIn))
		{
			foreach ($aIn as $k => $v)
			{
				if (is_array($v))
				{
					self::VForce2UTF8($aIn[$k]);
				}
				else if (is_string($v))
				{
					$aIn[$k] = self::SForce2UTF8($v);
				}
			}
		}
	}
	
	/**
	 * 无论输入是utf-8还是gb18030，都返回gb18030
	 * 由于要进行字符检查，在已知输入的字符集的时候，应该使用 SConvert2GB18030
	 *
	 * @return string
	 */
	public static function SForce2GB18030($sIn)
	{
		return self::BIsUtf8($sIn)
			? self::SConvert2GB18030($sIn)
			: self::SFilterErrorCode($sIn, 'GB18030');
	}

	/**
	 * 无论输入是utf-8还是gb18030，都返回gb18030
	 */
	public static function VForce2GB18030(&$aIn)
	{
		if (is_array($aIn))
		{
			foreach ($aIn as $k => $v)
			{
				if (is_array($v))
				{
					self::VForce2GB18030($aIn[$k]);
				}
				else if (is_string($v))
				{
					$aIn[$k] = self::SForce2GB18030($v);
				}
			}
		}
	}
	
	/**
	 * 判断字符串是utf-8编码还是gb18030编码
	 *
	 * @return boolean
	 */
	public static function BIsUtf8($sIn)
	{
		$onlyAscii = true;
		$onlyUtf8 = false;
		$iLen = strlen($sIn);
		for ($i=0; $i<$iLen; $i++)
		{
			$c0 = ord($sIn[$i]);
			if ($c0 < 0x80)
			{
				continue;
			}
			else if (self::_BCheckMultiByte_UTF8($sIn, $i, $c0, $iLen, $j))
			{
				$i += $j - 1;
				$onlyAscii = false;
				if ($j > 2)
				{
					$onlyUtf8 = true;
				}
				continue;
			}
			return false;
		}

		if ($onlyAscii)
		{
			return false;
		}
		if ($onlyUtf8)
		{
			return true;
		}
		
		for ($i=0; $i<$iLen; $i++)
		{
			$c0 = ord($sIn[$i]);
			if ($c0 >= 0x80)
			{
				$j = 2;
				if (self::_BCheckSecondByte_GB18030($sIn, $i, $iLen, $j))
				{
					if ($j != 2)
					{
						return false;
					}
					$i += $j - 1;
					continue;
				}
				return true;
			}
		}
		return false;
	}
	
	/**
	 * 判断字符串是否是纯ASCII编码
	 *
	 * @return boolean
	 */
	public static function BIsASCII($sIn)
	{
		$iLen = strlen($sIn);
		for($i=0; $i<$iLen; $i++)
		{
			$c0 = ord($sIn[$i]);
			if ($c0 >= 0x80)
			{
				return false;
			}
		}
		return true;
	}
	
	/**
	 * 从 GB18030 转化为 UTF-8 编码
	 */
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
	 * 从 GB18030 转化为 UTF-8 编码
	 *
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

	/**
	 * 从 UTF-8 转化为 GB18030 编码
	 */
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
	 * 从 UTF-8 转化为 GB18030 编码
	 *
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

	/**
	 * 过滤掉不符合 utf-8/gb18030 规范的字符
	 */
	public static function VFilterErrorCode(&$aIn, $sCharset = '', $sMode = '')
	{
		if (is_array($aIn))
		{
			foreach ($aIn as $k => $v)
			{
				if (is_array($v))
				{
					self::VFilterErrorCode($aIn[$k], $sCharset, $sMode);
				}
				else if (is_string($v))
				{
					$aIn[$k] = self::SFilterErrorCode($v, $sCharset, $sMode);
				}
			}
		}
	}

	/**
	 * 过滤掉不符合 utf-8/gb18030 规范的字符
	 *
	 * @return string
	 */
	public static function SFilterErrorCode($sIn, $sCharset = '', $sMode = '')
	{
		$charset = self::_SConvertCharset($sCharset);
		if ('UTF8' === $charset)
		{
			$sMode = strtolower($sMode);
			switch ($sMode)
			{
				case 'strict':
				case 'xml':
					$charset = ucfirst($charset);
					break;
				default:
					break;
			}
		}
		$fn = 'SFilterErrorCode_'.$charset;
		return self::$fn($sIn);
	}

	/**
	 * 过滤掉不符合 xml 规范的字符，并且过滤掉专用区内的字符(PUA) 0xE000-0xF8FF, 0xF0000-0xFFFFD和0x100000-0x10FFFD
	 *
	 * @return string
	 */
	public static function SFilterErrorCode_Strict($sIn)
	{
		$aOut = array(
			'start' => -1,
			'del' => array(),
		);
		self::_VCheckStr_Strict($sIn, '_VFilterErrorCode_OnChar', '_VFilterErrorCode_OnFail', '_VFilterErrorCode_OnComplete', $aOut);
		return self::_SFilterStr($sIn, $aOut['del']);
	}

	/**
	 * 过滤掉不符合 xml 规范的字符
	 *
	 * @return string
	 */
	public static function SFilterErrorCode_Xml($sIn)
	{
		$aOut = array(
			'start' => -1,
			'del' => array(),
			);
		self::_VCheckStr_Xml($sIn, '_VFilterErrorCode_OnChar', '_VFilterErrorCode_OnFail', '_VFilterErrorCode_OnComplete', $aOut);
		return self::_SFilterStr($sIn, $aOut['del']);
	}

	/**
	 * 过滤掉不符合 utf-8/gb18030 规范的字符
	 *
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
	 * 过滤掉不符合 utf-8/gb18030 规范的字符
	 *
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
	 * 截取字符串，保证返回数据字节数不超过 $iMaxLength
	 *
	 * @return string
	 */
	public static function SSubStr($sIn, $iMaxLength, $sExt = '', $sCharset = '')
	{
		$fn = 'SSubStr_'.self::_SConvertCharset($sCharset);
		return self::$fn($sIn, $iMaxLength, $sExt);
	}

	/**
	 * 截取字符串，保证返回数据字节数不超过 $iMaxLength
	 *
	 * @return string
	 */
	public static function SSubStr_UTF8($sIn, $iMaxLength, $sExt = '')
	{
		return self::_SSubStr($sIn, $iMaxLength, $sExt, 'SFilterErrorCode_UTF8');
	}

	/**
	 * 截取字符串，保证返回数据字节数不超过 $iMaxLength
	 *
	 * @return string
	 */
	public static function SSubStr_GB18030($sIn, $iMaxLength, $sExt = '')
	{
		return self::_SSubStr($sIn, $iMaxLength, $sExt, 'SFilterErrorCode_GB18030');
	}

	/**
	 * 截取字符串，保证返回数据页面显示长度不超过 $iShowLength，按照单字节字符占1位，多字节占2位计算
	 *
	 * @return string
	 */
	public static function SShowStr($sIn, $iShowLength, $sExt = '', $sCharset = '')
	{
		$fn = 'SShowStr_'.self::_SConvertCharset($sCharset);
		return self::$fn($sIn, $iShowLength, $sExt);
	}

	/**
	 * 截取字符串，保证返回数据页面显示长度不超过 $iShowLength，按照单字节字符占1位，多字节占2位计算
	 *
	 * @return string
	 */
	public static function SShowStr_UTF8($sIn, $iShowLength, $sExt = '')
	{
		return self::_SShowStr($sIn, $iShowLength, $sExt, 'AStr2Arr_UTF8', 6);
	}

	/**
	 * 截取字符串，保证返回数据页面显示长度不超过 $iShowLength，按照单字节字符占1位，多字节占2位计算
	 *
	 * @return string
	 */
	public static function SShowStr_GB18030($sIn, $iShowLength, $sExt = '')
	{
		return self::_SShowStr($sIn, $iShowLength, $sExt, 'AStr2Arr_GB18030', 4);
	}

	/**
	 * 将字符串拆分到数组
	 *
	 * @return array
	 */
	public static function AStr2Arr($sIn, $sCharset = '')
	{
		$fn = 'AStr2Arr_'.self::_SConvertCharset($sCharset);
		return self::$fn($sIn);
	}

	/**
	 * 将字符串拆分到数组
	 *
	 * @return array
	 */
	public static function AStr2Arr_UTF8($sIn)
	{
		$aOut = array();
		self::_VCheckStr_UTF8($sIn, '_VStr2Arr_OnChar', '_VStr2Arr_OnFail', '_VStr2Arr_OnComplete', $aOut);
		return $aOut;
	}

	/**
	 * 将字符串拆分到数组
	 *
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
		if (in_array(strtolower($sCharset), self::$s_aGB, true))
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

	private static function _VCheckStr_Strict($sIn, $fnChar, $fnFail, $fnComplete, &$aOut)
	{
		$iLen = strlen($sIn);
		for($i=0; $i<$iLen; $i++)
		{
			$c0 = ord($sIn[$i]);
			if ($c0 < 0x80)
			{
				if ($c0 == 0x9 || $c0 == 0xA || $c0 == 0xD || (0x20 <= $c0 && $c0 <= 0x7E))
				{
					self::$fnChar($sIn, $i, 1, $aOut);
					continue;
				}
			}
			else if (self::_BCheckMultiByte_Strict($sIn, $i, $c0, $iLen, $j))
			{
				self::$fnChar($sIn, $i, $j, $aOut);
				$i += $j - 1;
				continue;
			}
			self::$fnFail($sIn, $i, $aOut);
		}
		self::$fnComplete($sIn, $iLen, $aOut);
	}

	private static function _BCheckMultiByte_Strict($sIn, $i, $c0, $iLen, &$j)
	{
		if (0xC0 <= $c0 && $c0 <= 0xFD)
		{
			if ($c0 >= 0xFC)       $j = 6;
			else if ($c0 >= 0xF8)  $j = 5;
			else if ($c0 >= 0xF0)  $j = 4;
			else if ($c0 >= 0xE0)  $j = 3;
			else                   $j = 2;
			if ($i + $j <= $iLen && $j <= 4)
			{
				$c1 = $c2 = $c3 = 0;
				for ($k=1; $k<$j; $k++)
				{
					$ck = 'c'.$k;
					$$ck = ord($sIn[$i + $k]);
					if (0x80 <= $$ck && $$ck <= 0xBF)
					{
						continue;
					}
					break;
				}
				if ($k === $j)
				{
					$invalid = false;
					if (2 == $j)
					{
						if ($c0 == 0xC2
							&& ((0x80 <= $c1 && $c1 <= 0x84)         // 80-84
								|| (0x86 <= $c1 && $c1 <= 0x9F)))    // 86-9F
						{
							$invalid = true;
						}
					}
					else if (3 == $j)
					{
						if (($c0 == 0xED && $c1 >= 0xA0)                     // D800-DFFF
							|| ($c0 == 0xEE) || ($c0 == 0xEF && $c1 <= 0xA3) // E000-F8FF
							|| ($c0 == 0xEF && $c1 == 0xBF && $c2 >= 0xBE))  // FFFE-FFFF
						{
							$invalid = true;
						}
					}
					else if (4 == $j)
					{
						if ($c0 > 0xF3
							|| ($c0 == 0xF3 && $c1 >= 0xB0))
						{
							$invalid = true;
						}
					}
					if (!$invalid)
					{
						return true;
					}
				}
			}
		}
		return false;
	}

	private static function _VCheckStr_Xml($sIn, $fnChar, $fnFail, $fnComplete, &$aOut)
	{
		$iLen = strlen($sIn);
		for($i=0; $i<$iLen; $i++)
		{
			$c0 = ord($sIn[$i]);
			if ($c0 < 0x80)
			{
				if ($c0 == 0x9 || $c0 == 0xA || $c0 == 0xD || (0x20 <= $c0 && $c0 <= 0x7E))
				{
					self::$fnChar($sIn, $i, 1, $aOut);
					continue;
				}
			}
			else if (self::_BCheckMultiByte_Xml($sIn, $i, $c0, $iLen, $j))
			{
				self::$fnChar($sIn, $i, $j, $aOut);
				$i += $j - 1;
				continue;
			}
			self::$fnFail($sIn, $i, $aOut);
		}
		self::$fnComplete($sIn, $iLen, $aOut);
	}
	
	private static function _BCheckMultiByte_Xml($sIn, $i, $c0, $iLen, &$j)
	{
		if (0xC0 <= $c0 && $c0 <= 0xFD)
		{
			if ($c0 >= 0xFC)       $j = 6;
			else if ($c0 >= 0xF8)  $j = 5;
			else if ($c0 >= 0xF0)  $j = 4;
			else if ($c0 >= 0xE0)  $j = 3;
			else                   $j = 2;
			if ($i + $j <= $iLen && $j <= 4)
			{
				$c1 = $c2 = $c3 = 0;
				for ($k=1; $k<$j; $k++)
				{
					$ck = 'c'.$k;
					$$ck = ord($sIn[$i + $k]);
					if (0x80 <= $$ck && $$ck <= 0xBF)
					{
						continue;
					}
					break;
				}
				if ($k === $j)
				{
					$invalid = false;
					if (2 == $j)
					{
						if ($c0 == 0xC2
							&& ((0x80 <= $c1 && $c1 <= 0x84)         // 80-84
								|| (0x86 <= $c1 && $c1 <= 0x9F)))    // 86-9F
						{
							$invalid = true;
						}
					}
					else if (3 == $j)
					{
						if (($c0 == 0xED && $c1 >= 0xA0)                     // D800-DFFF
							|| ($c0 == 0xEF && $c1 == 0xBF && $c2 >= 0xBE))  // FFFE-FFFF
						{
							$invalid = true;
						}
					}
					else if (4 == $j)
					{
						if ($c0 > 0xF4
							|| ($c0 == 0xF4 && $c1 > 0x8F))
						{
							$invalid = true;
						}
					}
					if (!$invalid)
					{
						return true;
					}
				}
			}
		}
		return false;
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
			else if (self::_BCheckMultiByte_UTF8($sIn, $i, $c0, $iLen, $j))
			{
				self::$fnChar($sIn, $i, $j, $aOut);
				$i += $j - 1;
				continue;
			}
			self::$fnFail($sIn, $i, $aOut);
		}
		self::$fnComplete($sIn, $iLen, $aOut);
	}
	
	private static function _BCheckMultiByte_UTF8($sIn, $i, $c0, $iLen, &$j)
	{
		if (0xC0 <= $c0 && $c0 <= 0xFD)
		{
			if ($c0 >= 0xFC)       $j = 6;
			else if ($c0 >= 0xF8)  $j = 5;
			else if ($c0 >= 0xF0)  $j = 4;
			else if ($c0 >= 0xE0)  $j = 3;
			else                   $j = 2;
			if ($i + $j <= $iLen)
			{
				return self::_BCheckSecondByte_UTF8($sIn, $i, $iLen, $j);
			}
		}
		return false;
	}
	
	private static function _BCheckSecondByte_UTF8($sIn, $i, $iLen, &$j)
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
			return true;
		}
		return false;
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
			else if (self::_BCheckMultiByte_GB18030($sIn, $i, $c0, $iLen, $j))
			{
				self::$fnChar($sIn, $i, $j, $aOut);
				$i += $j - 1;
				continue;
			}
			self::$fnFail($sIn, $i, $aOut);
		}
		self::$fnComplete($sIn, $iLen, $aOut);
	}
	
	private static function _BCheckMultiByte_GB18030($sIn, $i, $c0, $iLen, &$j)
	{
		if (0x81 <= $c0 && $c0 <= 0xFE)
		{
			$j = 2;
			if ($i + $j <= $iLen)
			{
				return self::_BCheckSecondByte_GB18030($sIn, $i, $iLen, $j);
			}
		}
		return false;
	}
	
	private static function _BCheckSecondByte_GB18030($sIn, $i, $iLen, &$j)
	{
		$c1 = ord($sIn[$i+1]);
		if ((0x40 <= $c1 && $c1 <= 0x7E) || (0x80 <= $c1 && $c1 <= 0xFE))
		{
			return true;
		}
		else if (0x30 <= $c1 && $c1 <= 0x39)
		{
			$j = 4;
			if ($i + $j <= $iLen)
			{
				$c2 = ord($sIn[$i+2]);
				$c3 = ord($sIn[$i+3]);
				if (0x81 <= $c2 && $c2 <= 0xFE && 0x30 <= $c3 && $c3 <= 0x39)
				{
					return true;
				}
			}
		}
		return false;
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
