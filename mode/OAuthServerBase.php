<?php
/**
 * OAuthServerBase
 *
 * @package ko\mode
 * @author zhangchu
 */

class Ko_Mode_OAuthServerBase extends Ko_Mode_OAuthBase
{
	/**
	 * client 信息管理：获取注册信息
	 *
	 * @return array
	 */
	public function aGetClientInfo($iCid)
	{
		$clientDao = $this->_aConf['client'].'Dao';
		return $this->$clientDao->aGet($iCid);
	}

	/**
	 * 用户授权管理：撤销 access token 授权
	 */
	public function vRevokeToken($iUid, $iCid, $sToken)
	{
		$tokenApi = $this->_aConf['tokenApi'];
		$oOption = new Ko_Tool_SQL;
		$oOption->oWhere('uid = ? and cid = ?', $iUid, $iCid);
		$this->$tokenApi->iDelete($sToken, $oOption);
	}

	/**
	 * 视图驱动接口
	 */
	public function vAuto_auth($oSmarty, $sKey, $aTmpl, $aRegPara, $aViewPara)
	{
		$oSmarty->vAssignHtml($sKey, $this->_aReq);
	}
}

?>