<?php
/**
 * Filter
 *
 * @package ko\html
 * @author zhangchu
 */

//include_once('../ko.class.php');

interface IKo_Html_Filter
{
	/**
	 * 返回 true 应该被过滤
	 */
	public function bFilterTag($oNode);
	/**
	 * 返回 true 应该被过滤
	 */
	public function bFilterStyle($oNode, $oStyle);
	/**
	 * 返回 true 应该被过滤
	 */
	public function bFilterAttr($oNode, $oAttr);
	/**
	 * 返回 true 应该被过滤
	 */
	public function bFilterChild($oNode, $oChild);
	/**
	 * 返回额外需要添加的属性
	 */
	public function sGetExtraAttr($oNode);
	/**
	 * 对输出的代码进行最后的处理
	 */
	public function sFilterHtml($sHtml);
}

?>