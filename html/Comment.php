<?php
/**
 * Comment
 *
 * @package ko
 * @subpackage html
 * @author zhangchu
 */

//include_once('../ko.class.php');

interface IKo_Html_Comment
{
	public function sGetComment();
	public function vSetComment($sComment);
}

class Ko_Html_Comment implements IKo_Html_Comment, IKo_Html_Item
{
	private $_sComment = '';

	public function sGetComment()
	{
		return $this->_sComment;
	}

	public function vSetComment($sComment)
	{
		$this->_sComment = $sComment;
	}

	public function bParse($oHtmlStr)
	{
		assert($oHtmlStr instanceof Ko_Html_Str);

		$this->_sComment = $oHtmlStr->sGetCommentStr();
		return '' !== $this->_sComment;
	}

	public function sHtml()
	{
		return '<!--'.$this->_sComment.'-->';
	}
}

/*

$str = new Ko_Html_Str("<!-- 123 -->abasd");

$text = new Ko_Html_Comment;
$ret = $text->bParse($str);
var_dump($ret);

$ret = $text->sHtml();
var_dump($ret);

*/
?>