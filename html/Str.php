<?php
/**
 * Str
 *
 * @package ko\html
 * @author zhangchu
 */

//include_once('../ko.class.php');

class Ko_Html_Str extends Ko_Tool_StrParser
{
	/**
	 * 获取属性值，使用引号引起来的字符串，也可能没有使用引号，使用空白字符或者 '>' 结束
	 */
	public function sGetQuoteStr()
	{
		return $this->_sGetQuoteStr('>');
	}

	/**
	 * 获取属性名称字符串，使用空白字符或者 '>' '=' 结束
	 */
	public function sGetNameStr()
	{
		return $this->_sGetNameStr('>');
	}

	/**
	 * 获取文本字符串，使用 '<' 结束
	 */
	public function sGetTextStr()
	{
		return $this->sGetStr(array($this, 'bGetTextStr_Exit'), array());
	}
	public function bGetTextStr_Exit()
	{
		return '<' === $this->sChar() && !$this->bIsBlank(1) && !$this->bEnd(1) && '>' !== $this->sChar(1);
	}

	/**
	 * 获取tag字符串，使用 '<' 开始，使用空白字符或者 '>' 结束
	 */
	public function sGetTagStr()
	{
		assert('<' === $this->sChar());
		$this->vNext();
		return strtolower($this->sGetStr(array($this, 'bGetTagStr_Exit'), array()));
	}
	public function bGetTagStr_Exit()
	{
		return $this->bIsBlank() || $this->bIsEndTag();
	}

	/**
	 * 获取脚本文本
	 */
	public function sGetScriptStr()
	{
		$start = $this->_iOffset;
		$this->vFind('</script');
		$end = $this->_iOffset;
		$this->vFind('>');
		$this->vNext();
		return substr($this->_sHtml, $start, $end - $start);
	}

	/**
	 * 获取注释文本
	 */
	public function sGetCommentStr()
	{
		assert($this->bIsCommentStart());
		$this->vNext(4);
		$start = $this->_iOffset;
		$this->vFind('-->');
		$end = $this->_iOffset;
		$this->vNext(3);
		return substr($this->_sHtml, $start, $end - $start);
	}

	/**
	 * 当前是否是注释代码
	 */
	public function bIsCommentStart()
	{
		return '<' === $this->sChar() && '!' === $this->sChar(1) && '-' === $this->sChar(2) && '-' === $this->sChar(3);
	}

	/**
	 * 当前是否是 tag 完成标记 '/>' 或 '>'
	 */
	public function bIsEndTag()
	{
		$this->vLTrim();
		return ('/' === $this->sChar() && '>' === $this->sChar(1)) || '>' === $this->sChar();
	}
}

/*

$str = new Ko_Html_Str(" \t href=\"a\\\"\\bc\"></a>");
var_dump($str);
$ret = $str->sGetNameStr();
var_dump($ret);
$str->vNext();
$ret = $str->sGetQuoteStr();
var_dump($ret);

$str = new Ko_Html_Str("ansdas <a></a>");
var_dump($str);
$ret = $str->sGetTextStr();
var_dump($ret);

$str = new Ko_Html_Str("<a href=></a>");
var_dump($str);
$ret = $str->sGetTagStr();
var_dump($ret);

$str = new Ko_Html_Str("</a>");
var_dump($str);
$ret = $str->sGetTagStr();
var_dump($ret);

$str = new Ko_Html_Str("<!-- 123 -->abasd");
var_dump($str);
$ret = $str->bIsCommentStart();
var_dump($ret);
$ret = $str->sGetCommentStr();
var_dump($ret);
$ret = $str->bIsCommentStart();
var_dump($ret);

*/
?>