<?php
/**
 * OAuthBase
 *
 * @package ko\mode
 * @author zhangchu
 */

class Ko_Mode_OAuthBase extends Ko_Busi_Api
{
	protected $_aReq = array();

	/**
	 * 临时授权 / 用户授权 / token 接口 / 回调接口
	 *
	 * @return array
	 */
	public function aGetPara()
	{
		if ('GET' === Ko_Web_Request::SRequestMethod())
		{
			return $this->_aReq = Ko_Web_Request::AGet(false, 'UTF-8');
		}
		return $this->_aReq = Ko_Web_Request::APost(false, 'UTF-8');
	}
}
