<?php
/**
 * ItemBefore
 *
 * @package ko\mode
 * @author zhangchu
 */

/**
 * 数据修改前的观察者接口
 */
interface IKo_Mode_ItemBefore
{
	public function vBeforeInsert($oDao, $aData, $aUpdate, $aChange, $vAdmin);
	public function vBeforeUpdate($oDao, $vKey, $aUpdate, $aChange, $oOption, $vAdmin);
	public function vBeforeDelete($oDao, $vKey, $oOption, $vAdmin);
}
