<?php
/**
 * StrParser
 *
 * @package ko\tool
 * @author zhangchu
 */

/**
 * 字符串分析基类实现
 */
class Ko_Tool_StrParser
{
	/**
	 * @var string
	 */
	protected $_sHtml = '';
	/**
	 * @var int
	 */
	protected $_iOffset = 0;

	public function __construct($sHtml)
	{
		$this->_sHtml = strval($sHtml);
	}

	/**
	 * 判断当前是否空白字符
	 *
	 * @return bool
	 */
	public function bIsBlank($iOffset = 0)
	{
		$sChar = $this->sChar($iOffset);
		return ' ' === $sChar || "\t" === $sChar
			|| "\n" === $sChar || "\r" === $sChar
			|| "\0" === $sChar || "\x0B" === $sChar;
	}

	/**
	 * 查询当前字符
	 *
	 * @return string
	 */
	public function sChar($iOffset = 0)
	{
		return $this->_sHtml[$this->_iOffset + $iOffset];
	}

	/**
	 * 定位到下一个字符
	 */
	public function vNext($iOffset = 1)
	{
		$this->_iOffset += $iOffset;
	}

	/**
	 * 定位到指定的字符串下一个开始位置
	 */
	public function vFind($sNeedle)
	{
		$pos = stripos($this->_sHtml, $sNeedle, $this->_iOffset);
		if ($pos === false)
		{
			$this->_iOffset = strlen($this->_sHtml);
		}
		else
		{
			$this->_iOffset = $pos;
		}
	}

	/**
	 * 定位到下一个非空白字符
	 */
	public function vLTrim()
	{
		while (!$this->bEnd() && $this->bIsBlank())
		{
			$this->vNext();
		}
	}

	/**
	 * 是否已经到了字符串末尾
	 *
	 * @return bool
	 */
	public function bEnd($iOffset = 0)
	{
		return !isset($this->_sHtml[$this->_iOffset + $iOffset]);
	}

	/**
	 * 从当前位置获取一个字符串，直到结尾或者回调函数返回 true
	 *
	 * @return string
	 */
	public function sGetStr($fnExitFunc, $aPara)
	{
		$start = $this->_iOffset;
		while (!$this->bEnd())
		{
			if (call_user_func_array($fnExitFunc, $aPara))
			{
				break;
			}
			$this->vNext();
		}
		$end = $this->_iOffset;
		return substr($this->_sHtml, $start, $end - $start);
	}

	/**
	 * @return string
	 */
	protected function _sGetNameStr($sCharlist = '')
	{
		$this->vLTrim();

		return strtolower($this->sGetStr(array($this, 'bGetNameStr_Exit'), array($sCharlist)));
	}
	/**
	 * @return bool
	 */
	public function bGetNameStr_Exit($sCharlist)
	{
		return $this->bIsBlank() || '=' === $this->sChar() || false !== strpos($sCharlist, $this->sChar());
	}

	/**
	 * @return string
	 */
	protected function _sGetQuoteStr($sCharlist = '')
	{
		$this->vLTrim();

		$bIsQuoteStart = false;
		if ('"' == $this->sChar())
		{
			$this->vNext();
			$sEndQuote = '"';
			$bIsQuoteStart = true;
		}
		else if ('\'' == $this->sChar())
		{
			$this->vNext();
			$sEndQuote = '\'';
			$bIsQuoteStart = true;
		}

		if ($bIsQuoteStart)
		{
			$sRet = stripslashes($this->sGetStr(array($this, 'bGetQuoteStr_IsQuoteStart_Exit'), array($sEndQuote)));
			$this->vNext();
		}
		else
		{
			$sRet = $this->sGetStr(array($this, 'bGetQuoteStr_IsNotQuoteStart_Exit'), array($sCharlist));
		}
		return $sRet;
	}
	/**
	 * @return bool
	 */
	public function bGetQuoteStr_IsQuoteStart_Exit($sEndQuote)
	{
		if ($sEndQuote === $this->sChar())
		{
			return true;
		}
		if ('\\' === $this->sChar() && ('"' === $this->sChar(1) || '\'' === $this->sChar(1) || '\\' === $this->sChar(1)))
		{
			$this->vNext();
		}
		return false;
	}
	/**
	 * @return bool
	 */
	public function bGetQuoteStr_IsNotQuoteStart_Exit($sCharlist)
	{
		return $this->bIsBlank() || false !== strpos($sCharlist, $this->sChar());
	}
}

?>