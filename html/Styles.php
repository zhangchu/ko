<?php
/**
 * Styles
 *
 * @package ko\html
 * @author zhangchu
 */

//include_once('../ko.class.php');

class Ko_Html_Styles
{
	private $_oParent;
	private $_aStyle = array();

	public function __construct($oParent)
	{
		assert($oParent instanceof Ko_Html_Node);

		$this->_oParent = $oParent;
	}

	/**
	 * 释放对 parent 的引用，防止内存无法释放
	 */
	public function vFreeParent()
	{
		$this->_oParent = null;
	}

	/**
	 * 从 html 中拆分 Style 列表
	 */
	public function bParse($sHtml)
	{
		$aList = explode(';', $sHtml);
		foreach ($aList as $item)
		{
			list($n, $v) = explode(':', $item);
			$n = trim($n);
			$v = trim($v);
			if ('' === $n)
			{
				continue;
			}
			$style = new Ko_Html_Style;
			$style->vSetName(strtolower($n));
			$style->vSetValue($v);
			$this->_aStyle[] = $style;
		}
		return true;
	}

	/**
	 * 生成 html 代码
	 */
	public function sHtml($aFilters)
	{
		$sHtml = '';
		foreach ($this->_aStyle as $style)
		{
			if ($this->_bFilterStyle($style, $aFilters))
			{
				continue;
			}
			$sHtml .= $style->sGetName().':'.$style->sGetValue().';';
		}
		return htmlspecialchars($sHtml);
	}

	private function _bFilterStyle($oStyle, $aFilter)
	{
		foreach ($aFilter as $oFilter)
		{
			if ($oFilter->bFilterStyle($this->_oParent, $oStyle))
			{
				return true;
			}
		}
		return false;
	}
}

/*

$node = new Ko_Html_Node;
$styles = new Ko_Html_Styles($node);

$ret = $styles->bParse('color:red;a:d;');
var_dump($ret);

$str = $styles->sHtml(array());
var_dump($str);

*/
?>