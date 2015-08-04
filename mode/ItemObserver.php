<?php
/**
 * ItemObserver
 *
 * @package ko\mode
 * @author zhangchu
 */

/**
 * 数据修改后的观察者接口
 */
interface IKo_Mode_ItemObserver
{
	public function vOnInsert($oDao, $sHintId, $aData, $vAdmin);
	public function vOnUpdate($oDao, $sHintId, $aUpdate, $aChange, $vAdmin);
	public function vOnDelete($oDao, $sHintId, $vAdmin);
}
