<?php
/**
 * ItemHelp
 *
 * @package ko\mode
 * @author zhangchu
 */

/**
 * 观察目标辅助接口
 */
interface IKo_Mode_ItemHelp
{
	/**
	 * 绑定观察者
	 */
	public function vAttach($oObserver);
	/**
	 * 将唯一键转换为一个字符串
	 *
	 * @return string
	 */
	public function sGetHintId($vKey);
}

?>