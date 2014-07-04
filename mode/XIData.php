<?php
/**
 * XIData
 *
 * @package ko\mode
 * @author zhangchu
 */

/**
 * 数据接口的缺省实现
 */
class Ko_Mode_XIData extends Ko_Busi_Api
{
	public function aGetList($aReq, $oOption)
	{
		return array();
	}
	
	public function vAfterGetList(&$aList)
	{
	}
}
