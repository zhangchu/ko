<?php
/**
 * Attr
 *
 * @package ko\html
 * @author zhangchu
 */

//include_once('../ko.class.php');

class Ko_Html_Attr implements IKo_Html_Item
{
	private $_sName = '';
	private $_sValue = '';

	public function sGetName()
	{
		return $this->_sName;
	}

	public function vSetName($sName)
	{
		$this->_sName = $sName;
	}

	public function sGetValue()
	{
		return $this->_sValue;
	}

	public function vSetValue($sValue)
	{
		$this->_sValue = $sValue;
	}

	public function bParse($oHtmlStr)
	{
		assert($oHtmlStr instanceof Ko_Html_Str);

		if ($oHtmlStr->bIsEndTag())
		{
			return false;
		}

		$this->_sName = $oHtmlStr->sGetNameStr();
		$oHtmlStr->vLTrim();
		if ('=' === $oHtmlStr->sChar())
		{
			$oHtmlStr->vNext();
			$this->_sValue = htmlspecialchars_decode($oHtmlStr->sGetQuoteStr());
		}
		else
		{
			$this->_sValue = '';
		}
		return '' !== $this->_sName && '/' !== $this->_sName;
	}

	public function sHtml()
	{
		if ('' === $this->_sName)
		{
			return '';
		}
		if ('' === $this->_sValue)
		{
			return $this->_sName;
		}
		return $this->_sName.'="'.htmlspecialchars($this->_sValue).'"';
	}
}

/*

$node = new Ko_Html_Node;
$str = new Ko_Html_Str(" \t href=\"a\\\"\\bc\"></a>");
$attr = new Ko_Html_Attr($node);
$ret = $attr->bParse($str);
var_dump($ret);

$ret = $attr->sHtml();
var_dump($ret);

*/
?>