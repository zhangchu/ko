<?php
/**
 * Str
 *
 * @package ko\view
 * @author zhangchu
 */

class Ko_View_Str extends Ko_Tool_StrParser
{
	/**
	 * 解析 {固定前缀}{注册模块名}_{函数名}_{参数1}_{参数2}_..._{参数N}
	 *
	 * @return array
	 */
	public static function AParseAutoStr($sItem)
	{
		$items = explode('_', $sItem);
		$sRegName = array_shift($items);
		$sFuncName = array_shift($items);
		return array(substr($sRegName, strlen(KO_VIEW_AUTOTAG)), $sFuncName, $items);
	}

	/**
	 * 拼凑一个自动分析串
	 *
	 * @return string
	 */
	public static function SAssembleAutoStr($sRegName, $sFuncName, $aViewPara)
	{
		array_unshift($aViewPara, KO_VIEW_AUTOTAG.$sRegName, $sFuncName);
		return rtrim(implode('_', $aViewPara), 'L');
	}

	/**
	 * 获取模版文件的全路经
	 *
	 * @return string
	 */
	public static function SGetAbsoluteFile($sFile, $sTemplateDir)
	{
		if (0 == strncasecmp($sFile, 'file:', 5) && '/' == $sFile[5])
		{
			return substr($sFile, 5);
		}
		return $sTemplateDir.'/'.$sFile;
	}

	/**
	 * 分析模版中都使用了哪些自动分析的变量，并转换成数组
	 *
	 * @return array
	 */
	public function aParseArr($sStart, $sEnd, $sTemplateDir, &$aFilelist)
	{
		return $this->_aVar2Arr($this->_aParseVar($sStart, $sEnd, $sTemplateDir, $aFilelist));
	}

	/**
	 * 获取 KO_VIEW_AUTOTAG 开头的模版标签
	 *
	 * @return string
	 */
	public function sGetAutoStr()
	{
		$this->vFind('$'.KO_VIEW_AUTOTAG);
		$this->vNext(1);
		return $this->sGetStr(array($this, 'bGetAutoStr_Exit'), array());
	}
	/**
	 * @return bool
	 */
	public function bGetAutoStr_Exit()
	{
		return $this->bIsBlank() || '@' === $this->sChar() || '[' === $this->sChar() || ']' === $this->sChar() || '-' === $this->sChar() || ')' === $this->sChar();
	}

	/**
	 * 获取 $sStart 开头，$sEnd 结束的中间的串
	 *
	 * @return string
	 */
	public function sGetBlockStr($sStart, $sEnd)
	{
		$this->vFind($sStart);
		$this->vNext(strlen($sStart));
		return $this->sGetStr(array($this, 'bGetBlockStr_Exit'), array($sEnd));
	}
	/**
	 * @return bool
	 */
	public function bGetBlockStr_Exit($sEnd)
	{
		$iLen = strlen($sEnd);
		for ($i=0; $i<$iLen; $i++)
		{
			if (strcasecmp($this->sChar($i), $sEnd[$i]))
			{
				return false;
			}
		}
		return true;
	}

	private function _aVar2Arr($aVar)
	{
		$arr = array();
		foreach ($aVar as $var)
		{
			$items = explode('.', $var);
			$ref = &$arr;
			foreach ($items as $k => $item)
			{
				if (!isset($ref[$item]))
				{
					$ref[$item] = array();
				}
				$ref = &$ref[$item];
			}
		}
		return $arr;
	}

	private function _aParseVar($sStart, $sEnd, $sTemplateDir, &$aFilelist)
	{
		$arr = array();
		while (!$this->bEnd())
		{
			$block = $this->sGetBlockStr($sStart, $sEnd);
			if (strlen($block))
			{
				if (0 == strncasecmp($block, 'include', 7))
				{
					$objblock = new Ko_View_Str(substr($block, 7));
					$filename = $objblock->_sGetFile($sTemplateDir);
					if (is_file($filename))
					{
						$aFilelist[] = $filename;
						$str = file_get_contents($filename);
						$objfile = new Ko_View_Str($str);
						$arr = array_merge($arr, $objfile->_aParseVar($sStart, $sEnd, $sTemplateDir, $aFilelist));
					}
				}
				else
				{
					$objblock = new Ko_View_Str($block);
					while (!$objblock->bEnd())
					{
						$str = $objblock->sGetAutoStr();
						if (strlen($str))
						{
							$arr[] = $str;
						}
					}
				}
			}
		}
		return array_unique($arr);
	}

	private function _sGetFile($sTemplateDir)
	{
		while (!$this->bEnd())
		{
			list($sName, $sValue) = $this->_aGetAttr();
			if ('file' == $sName)
			{
				return self::SGetAbsoluteFile($sValue, $sTemplateDir);
			}
			if ('' == $sValue)
			{
				$iLen = strlen($sName);
				if ($iLen > 2 && (('"' == $sName[0] && '"' == $sName[$iLen-1]) || ('\'' == $sName[0] && '\'' == $sName[$iLen-1])))
				{
					return self::SGetAbsoluteFile(substr($sName, 1, -1), $sTemplateDir);
				}
			}
		}
		return '';
	}

	private function _aGetAttr()
	{
		$sName = $this->_sGetNameStr();
		$this->vLTrim();
		if ('=' === $this->sChar())
		{
			$this->vNext();
			$sValue = $this->_sGetQuoteStr();
		}
		else
		{
			$sValue = '';
		}
		return array($sName, $sValue);
	}
}

?>