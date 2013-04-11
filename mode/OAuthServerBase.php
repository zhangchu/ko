<?php
/**
 * OAuthServerBase
 *
 * @package ko
 * @subpackage mode
 * @author zhangchu
 */

interface IKo_Mode_OAuthServerBase
{
	/**
	 * client 信息管理：获取注册信息
	 *
	 * @return array
	 */
	public function aGetClientInfo($iCid);

	/**
	 * 用户授权管理：撤销 access token 授权
	 */
	public function vRevokeToken($iUid, $iCid, $sToken);

	/**
	 * 视图驱动接口
	 */
	public function vAuto_auth($oSmarty, $sKey, $aTmpl, $aRegPara, $aViewPara);
}

class Ko_Mode_OAuthServerBase extends Ko_Mode_OAuthBase implements IKo_Mode_OAuthServerBase
{
	/**
	 * @return array
	 */
	public function aGetClientInfo($iCid)
	{
		$clientDao = $this->_aConf['client'].'Dao';
		return $this->$clientDao->aGet($iCid);
	}

	public function vRevokeToken($iUid, $iCid, $sToken)
	{
		$tokenApi = $this->_aConf['tokenApi'];
		$oOption = new Ko_Tool_SQL;
		$oOption->oWhere('uid = ? and cid = ?', $iUid, $iCid);
		$this->$tokenApi->iDelete($sToken, $oOption);
	}

	public function vAuto_auth($oSmarty, $sKey, $aTmpl, $aRegPara, $aViewPara)
	{
		$oSmarty->vAssignHtml($sKey, $this->_aReq);
	}

	protected function _sGenKey()
	{
		return md5(uniqid('', true));
	}
}

?>