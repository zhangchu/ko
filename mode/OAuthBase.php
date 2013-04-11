<?php
/**
 * OAuthBase
 *
 * @package ko
 * @subpackage mode
 * @author zhangchu
 */

interface IKo_Mode_OAuthBase
{
	/**
	 * 临时授权 / 用户授权 / token 接口 / 回调接口
	 *
	 * @return array
	 */
	public function aGetPara($aReqType = array());
}

class Ko_Mode_OAuthBase extends Ko_Busi_Api implements IKo_Mode_OAuthBase
{
	protected $_aReqType = array();
	protected $_aReq = array();

	/**
	 * @return array
	 */
	public function aGetPara($aReqType = array())
	{
		$types = array_merge($this->_aReqType, $aReqType);
		if ('GET' === getenv('REQUEST_METHOD'))
		{
			return $this->_aReq = Ko_Tool_Input::ACleanAllGet($types, 'UTF-8');
		}
		return $this->_aReq = Ko_Tool_Input::ACleanAllPost($types, 'UTF-8');
	}
}

?>