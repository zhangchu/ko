<?php
/**
 * XIAuth
 *
 * @package ko\mode
 * @author zhangchu
 */

/**
 * 验证权限的接口
 */
interface IKo_Mode_XIAuth
{
	/**
	 * @return array
	 */
	public function aGetHideField($sAction, $vAdmin);
	/**
	 * @return boolean
	 */
	public function bIsActionEnable($sAction, $vAdmin);
	public function vGetListEx($vHintId, $vAdmin, $oOption);
	/**
	 * @return boolean
	 */
	public function bBeforeGet($aKey, $vAdmin, &$sError);
	/**
	 * @return boolean
	 */
	public function bBeforeInsert($aData, $vAdmin, &$sError);
	/**
	 * @return boolean
	 */
	public function bBeforeUpdate($aKey, $aData, $vAdmin, &$sError);
	/**
	 * @return boolean
	 */
	public function bBeforeDelete($aKey, $vAdmin, &$sError);
}

/**
 * 验证权限的缺省实现
 */
class Ko_Mode_XIAuth extends Ko_Busi_Api implements IKo_Mode_XIAuth
{
	/**
	 * @return array
	 */
	public function aGetHideField($sAction, $vAdmin)
	{
		return array();
	}

	/**
	 * @return boolean
	 */
	public function bIsActionEnable($sAction, $vAdmin)
	{
		return true;
	}

	public function vGetListEx($vHintId, $vAdmin, $oOption)
	{
	}

	/**
	 * @return boolean
	 */
	public function bBeforeGet($aKey, $vAdmin, &$sError)
	{
		return true;
	}

	/**
	 * @return boolean
	 */
	public function bBeforeInsert($aData, $vAdmin, &$sError)
	{
		return true;
	}

	/**
	 * @return boolean
	 */
	public function bBeforeUpdate($aKey, $aData, $vAdmin, &$sError)
	{
		return true;
	}

	/**
	 * @return boolean
	 */
	public function bBeforeDelete($aKey, $vAdmin, &$sError)
	{
		return true;
	}
}

?>