<?php
/**
 * 使用说明
 *
 * <b>简介</b>
 * <pre>
 *   实现 OAuth 2.0 -- http://tools.ietf.org/html/rfc6749
 * </pre>
 *
 * <b>数据库例表</b>
 * <pre>
 *   client 部分的表
 *   CREATE TABLE z_test_oauth2_usertoken_0(
 *     uid bigint unsigned not null default 0,
 *     src varchar(32) not null default '',
 *     token varchar(128) not null default '',
 *     token_type varchar(128) not null default '',
 *     expires_in int unsigned not null default 0,
 *     refresh_token varchar(128) not null default '',
 *     scope varchar(128) not null default '',
 *     ctime timestamp NOT NULL default 0,
 *     unique (uid, src, token)
 *   )ENGINE=InnoDB DEFAULT CHARSET=utf8;
 *   CREATE TABLE z_test_oauth2_usertoken_last_0(
 *     uid bigint unsigned not null default 0,
 *     src varchar(32) not null default '',
 *     token varchar(128) not null default '',
 *     unique (uid, src)
 *   )ENGINE=InnoDB DEFAULT CHARSET=utf8;
 * </pre>
 *
 * <b>_aConf 配置</b>
 * @see Ko_Mode_OAuth2Client::$_aConf
 *
 * @package ko\mode
 * @author zhangchu
 */

/**
 * OAuth 2.0 实现
 */
class Ko_Mode_OAuth2Client extends Ko_Mode_OAuthClientBase
{
	/**
	 * 配置数组
	 *
	 * <pre>
	 * array(
	 *   'usertoken' => 用户 access_token 表
	 *   'lasttoken' => 用户最新 access_token 表
	 *   'srclist' => array(
	 *     'xxx' => array(
	 *       'client_id' => 
	 *       'client_secret' => 
	 *       'authorize_uri' => 
	 *       'token_uri' => 
	 *       'redirect_uri' =>
	 *       'request_method' => 
	 *     ),
	 *     ...
	 *   ),
	 * )
	 * </pre>
	 *
	 * @var array
	 */
	protected $_aConf = array();

	/**
	 * 入口接口 / 回调接口
	 *
	 * @return array|exit
	 */
	public function vMain($sSrc, $fnGetToken)
	{
		assert(isset($this->_aConf['srclist'][$sSrc]));

		$this->aGetPara();
		if (0 === strlen($this->_aReq['code']))
		{
			$authorizeUri = Ko_Mode_OAuth2Server::SGetAuthorizeUri($this->_aConf['srclist'][$sSrc]['authorize_uri'],
				$this->_aConf['srclist'][$sSrc]['client_id'],
				$this->_aConf['srclist'][$sSrc]['redirect_uri']);
			Ko_Web_Response::VSetRedirect($authorizeUri);
			Ko_Web_Response::VSend();
			exit;
		}

		$uri = Ko_Mode_OAuth2Server::SGetAccessTokenUri($this->_aConf['srclist'][$sSrc]['request_method'],
			$this->_aConf['srclist'][$sSrc]['token_uri'],
			$this->_aConf['srclist'][$sSrc]['client_id'],
			$this->_aConf['srclist'][$sSrc]['client_secret'],
			$this->_aConf['srclist'][$sSrc]['redirect_uri'],
			$this->_aReq['code']);
		$response = call_user_func($fnGetToken, $uri);
		return self::AParseToken($response);
	}

	/**
	 * 分析 token
	 * 
	 * @return array
	 */
	public static function AParseToken($sResponse)
	{
		$arr = json_decode($sResponse, true);
		if (strlen($arr['access_token']))
		{
			return $arr;
		}
		return array();
	}

	/**
	 * 用户绑定 token
	 * 
	 * @return boolean
	 */
	public function bSaveUserToken($sSrc, $iUid, $aTokeninfo)
	{
		return $this->_bSaveUserToken($sSrc, $iUid, $aTokeninfo, 'access_token', array('token_type', 'expires_in', 'refresh_token', 'scope'));
	}
}

?>