<?php
/**
 * XIData
 *
 * @package ko
 * @subpackage mode
 * @author zhangchu
 */

/**
 * 自定义数据接口
 */
interface IKo_Mode_XIData
{
	public function aGetList($aReq, $oOption);
	public function vAfterGetList(&$aList);
}

/**
 * 数据接口的缺省实现
 */
class Ko_Mode_XIData extends Ko_Busi_Api implements IKo_Mode_XIData
{
	public function aGetList($aReq, $oOption)
	{
		return array();
	}
	
	public function vAfterGetList(&$aList)
	{
	}
}
