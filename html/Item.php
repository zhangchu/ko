<?php
/**
 * Item
 *
 * @package ko\html
 * @author zhangchu
 */

//include_once('../ko.class.php');

interface IKo_Html_Item
{
	/**
	 * 从 html 当前位置开始分析一个节点/属性/文本/注释
	 */
	public function bParse($oHtmlStr);

	/**
	 * 生成 html 代码
	 */
	public function sHtml();
}

?>