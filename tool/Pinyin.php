<?php
/**
 * Pinyin
 *
 * @package ko\tool
 * @author zhangchu
 */

/**
 * 拼音相关的函数实现，所有输入输出按照 UTF-8 处理
 */
class Ko_Tool_Pinyin
{
	const DEFAULT_MAX_HZ2PYLEN = 10;

	const FULL = 0;
	const BRIEF = 1;
	const FULL_BRIEF = 2;
	const BRIEF_FULL = 3;
	
	private static $s_aSyllableOnset = array(				//声母
		'b', 'p', 'm', 'f', 'd', 't', 'n', 'l',
		'g', 'k', 'h', 'j', 'q', 'x',
		'zh', 'ch', 'sh', 'r', 'z', 'c', 's',
		'y', 'w');
	private static $s_aSyllableRime = array(				//韵母
		'a', 'o', 'e', 'i', 'u', 'v',
		'ai', 'ei', 'ao', 'ou', 'ia', 'ie', 'iao', 'iu', 'ua', 'uo', 'uai', 'ui', 'ue', 've', 'er',
		'an', 'en', 'in', 'un',
		'ang', 'eng', 'ing', 'ong', 'ng',
		'ian', 'uan', 'iang', 'uang', 'iong',
	);
	private static $s_aVowelTone = array(
		'a' => array('a', 0), 'ā' => array('a', 1), 'á' => array('a', 2), 'ǎ' => array('a', 3), 'à' => array('a', 4),
		'o' => array('o', 0), 'ō' => array('o', 1), 'ó' => array('o', 2), 'ǒ' => array('o', 3), 'ò' => array('o', 4),
		'e' => array('e', 0), 'ē' => array('e', 1), 'é' => array('e', 2), 'ě' => array('e', 3), 'è' => array('e', 4),
		'i' => array('i', 0), 'ī' => array('i', 1), 'í' => array('i', 2), 'ǐ' => array('i', 3), 'ì' => array('i', 4),
		'u' => array('u', 0), 'ū' => array('u', 1), 'ú' => array('u', 2), 'ǔ' => array('u', 3), 'ù' => array('u', 4),
		'ü' => array('v', 0), 'ǖ' => array('v', 1), 'ǘ' => array('v', 2), 'ǚ' => array('v', 3), 'ǜ' => array('v', 4),
	);
	private static $s_aDoubleOnset = array(
		'zh' => 'z', 'ch' => 'c', 'sh' => 's',
	);
	
	private static $s_aDBHandler = array();
	
	/**
	 * 获取字符串的拼音或拼音缩写，优先选择每个字的第一个拼音
	 *
	 * @return string
	 */
	public static function SHZ2PY($sHz, $sDbFile, $iFlag = Ko_Tool_Pinyin::FULL, $sHandler = 'cdb')
	{
		$db = self::_HGetDbHandler($sDbFile, $sHandler);
		$len = mb_strlen($sHz, 'UTF-8');
		$ret = '';
		for ($i=0; $i<$len; ++$i)
		{
			$char = mb_substr($sHz, $i, 1, 'UTF-8');
			if (false !== ($value = dba_fetch($char, $db)) && false !== ($value = unserialize($value)))
			{
				switch ($iFlag)
				{
				case self::BRIEF:
					$ret .= substr($value[0][0], 0, 1);
					break;
				default:
					$ret .= $value[0][0];
					break;
				}
			}
			else
			{
				$ret .= $char;
			}
		}
		return $ret;
	}
	
	/**
	 * 获取字符串可能的拼音列表
	 *
	 * @return array
	 */
	public static function AHZ2PY($sHz, $sDbFile, $iFlag = self::FULL, $sHandler = 'cdb', $iMaxLen = 0)
	{
		if (!$iMaxLen)
		{
			$iMaxLen = self::DEFAULT_MAX_HZ2PYLEN;
		}
		$db = self::_HGetDbHandler($sDbFile, $sHandler);
		$len = min($iMaxLen, mb_strlen($sHz, 'UTF-8'));
		$arr = array();
		for ($i=0; $i<$len; ++$i)
		{
			$char = mb_substr($sHz, $i, 1, 'UTF-8');
			if (false !== ($value = dba_fetch($char, $db)) && false !== ($value = unserialize($value)))
			{
				$arr[] = $value;
			}
			else
			{
				$arr[] = array(array($char, $char, ''));
			}
		}
		switch ($iFlag)
		{
		case self::BRIEF:
			$ret = self::_AMergePy('', $arr, $len, 0, self::BRIEF);
			break;
		case self::FULL_BRIEF:
			$ret = array_merge(self::_AMergePy('', $arr, $len, 0, self::FULL), self::_AMergePy('', $arr, $len, 0, self::BRIEF));
			break;
		case self::BRIEF_FULL:
			$ret = array_merge(self::_AMergePy('', $arr, $len, 0, self::BRIEF), self::_AMergePy('', $arr, $len, 0, self::FULL));
			break;
		default:
			$ret = self::_AMergePy('', $arr, $len, 0, self::FULL);
			break;
		}
		return array_values(array_unique($ret));
	}
	
	/**
	 * 将音调与拼音分开，并将带音调的符号转换为 ascii 字符
	 *
	 * @return array
	 */
	public static function ASplitTone($sPyWithTone)
	{
		$len = mb_strlen($sPyWithTone, 'UTF-8');
		$lasttone = 0;
		$arr = array();
		for ($i=0; $i<$len; ++$i)
		{
			$char = mb_substr($sPyWithTone, $i, 1, 'UTF-8');
			$ret = self::_AGetASCIIVowelTone($char);
			if (false === $ret)
			{
				$arr[] = $char;
			}
			else
			{
				$arr[] = $ret[0];
				if ($ret[1])
				{
					$lasttone = $ret[1];
				}
			}
		}
		return array(implode('', $arr), $lasttone);
	}
	
	/**
	 * 将拼音的声母和韵母分开
	 *
	 * @return array|boolean 返回 false 表示拼音错误
	 */
	public static function VSplitOnsetRime($sPy)
	{
		$onsetlen = 2;
		$onset = substr($sPy, 0, $onsetlen);
		if (!in_array($onset, self::$s_aSyllableOnset, true))
		{
			$onsetlen = 1;
			$onset = substr($sPy, 0, $onsetlen);
			if (!in_array($onset, self::$s_aSyllableOnset, true))
			{
				$onsetlen = 0;
				$onset = '';
			}
		}
		$rime = $onsetlen ? substr($sPy, $onsetlen) : $sPy;
		if (in_array($rime, self::$s_aSyllableRime, true))
		{	//有韵母
			return array($onset, $rime);
		}
		if (in_array($sPy, self::$s_aSyllableRime, true))
		{	//没声母，有韵母
			return array('', $sPy);
		}
		if (0 === strlen($rime) && 0 !== strlen($onset))
		{	//有声母，没韵母
			return array($onset, '');
		}
		return false;
	}
	
	private static function _AGetASCIIVowelTone($sVowel)
	{
		return isset(self::$s_aVowelTone[$sVowel]) ? self::$s_aVowelTone[$sVowel] : false;
	}
	
	private static function _HGetDbHandler($sDbFile, $sHandler)
	{
		if (isset(self::$s_aDBHandler[$sDbFile]))
		{
			return self::$s_aDBHandler[$sDbFile];
		}
		return self::$s_aDBHandler[$sDbFile] = dba_open($sDbFile, 'r', $sHandler);
	}
	
	private static function _AMergePy($sPrefix, $aData, $iDataLen, $iStart, $iFlag)
	{
		$aRet = array();
		if ($iDataLen)
		{
			foreach ($aData[$iStart] as $v)
			{
				if ($iFlag == self::BRIEF)
				{
					if (strlen($v[1]))
					{
						$sNextPrefix = array($sPrefix.$v[1]);
						if (isset(self::$s_aDoubleOnset[$v[1]]))
						{
							$sNextPrefix[] = $sPrefix.self::$s_aDoubleOnset[$v[1]];
						}
					}
					else
					{
						$sNextPrefix = array($sPrefix.substr($v[2], 0, 1));
					}
				}
				else
				{
					$sNextPrefix = array($sPrefix.$v[0]);
				}
				foreach ($sNextPrefix as $next)
				{
					if ($iStart == $iDataLen - 1)
					{
						$aRet[] = $next;
					}
					else
					{
						$aRet = array_merge($aRet, self::_AMergePy($next, $aData, $iDataLen, $iStart+1, $iFlag));
					}
				}
			}
		}
		return $aRet;
	}
}
