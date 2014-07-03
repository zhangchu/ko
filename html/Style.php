<?php
/**
 * Style
 *
 * @package ko\html
 * @author zhangchu
 */

//include_once('../ko.class.php');

class Ko_Html_Style
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
}

/*

$style = new Ko_Html_Style;

$style->vSetName('color');
$ret = $style->sGetName();
var_dump($ret);

$style->vSetValue('red');
$ret = $style->sGetValue();
var_dump($ret);

*/
?>