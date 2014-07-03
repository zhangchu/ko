<?php
/**
 * Text
 *
 * @package ko\html
 * @author zhangchu
 */

//include_once('../ko.class.php');

class Ko_Html_Text implements IKo_Html_Item
{
	private $_sText = '';

	public function sGetText()
	{
		return $this->_sText;
	}

	public function vSetText($sText)
	{
		$this->_sText = $sText;
	}

	public function bParse($oHtmlStr)
	{
		assert($oHtmlStr instanceof Ko_Html_Str);

		$bIsBlank = $oHtmlStr->bIsBlank();
		$this->_sText = htmlspecialchars_decode(str_replace('&nbsp;', ' ', $oHtmlStr->sGetTextStr()));
		if ($bIsBlank)
		{
			$this->_sText = ' '.ltrim($this->_sText);
		}
		$bIsBlank = $oHtmlStr->bIsBlank(-1);
		if ($bIsBlank)
		{
			$this->_sText = rtrim($this->_sText).' ';
		}
		return '' !== $this->_sText;
	}

	public function sHtml()
	{
		return str_replace('  ', ' &nbsp;', htmlspecialchars($this->_sText));
	}
}

/*

$str = new Ko_Html_Str(" abcd \t <a href=\"a\\\"\\bc\"></a>");

$text = new Ko_Html_Text;
$ret = $text->bParse($str);
var_dump($ret);

$ret = $text->sHtml();
var_dump($ret);

*/
?>