<?php
/**
 * 使用说明
 *
 * <b>简介</b>
 * <pre>
 *   实现 OAuth 1.0 -- http://tools.ietf.org/html/rfc5849
 *   支持 XAuth -- http://tools.ietf.org/id/draft-dehora-farrell-oauth-accesstoken-creds-02.txt
 * </pre>
 *
 * <b>数据库例表</b>
 * <pre>
 *   client 部分的表
 *   CREATE TABLE z_test_oauth_temptoken_0(
 *     token varchar(128) not null default '',
 *     src varchar(32) not null default '',
 *     oauth_token_secret varchar(128) not null default '',
 *     ctime timestamp NOT NULL default 0,
 *     unique (token, src)
 *   )ENGINE=InnoDB DEFAULT CHARSET=utf8;
 *   CREATE TABLE z_test_oauth_usertoken_0(
 *     uid bigint unsigned not null default 0,
 *     src varchar(32) not null default '',
 *     token varchar(128) not null default '',
 *     oauth_token_secret varchar(128) not null default '',
 *     ctime timestamp NOT NULL default 0,
 *     unique (uid, src, token)
 *   )ENGINE=InnoDB DEFAULT CHARSET=utf8;
 *   CREATE TABLE z_test_oauth_usertoken_last_0(
 *     uid bigint unsigned not null default 0,
 *     src varchar(32) not null default '',
 *     token varchar(128) not null default '',
 *     unique (uid, src)
 *   )ENGINE=InnoDB DEFAULT CHARSET=utf8;
 * </pre>
 *
 * <b>_aConf 配置</b>
 * @see Ko_Mode_OAuthClient::$_aConf
 *
 * @package ko\mode
 * @author zhangchu
 */

/**
 * OAuth 1.0 实现
 */
class Ko_Mode_OAuthClient extends Ko_Mode_OAuthClientBase
{
	/**
	 * 配置数组
	 *
	 * <pre>
	 * array(
	 *   'temptoken' => 临时的 token 表，对于过期的数据，应定期清理
	 *   'usertoken' => 用户 access_token 表
	 *   'lasttoken' => 用户最新 access_token 表
	 *   'srclist' => array(
	 *     'xxx' => array(
	 *       'oauth_consumer_key' => 
	 *       'oauth_consumer_secret' => 
	 *       'request_token_uri' => 
	 *       'authorize_uri' => 
	 *       'access_token_uri' => 
	 *       'oauth_callback' =>
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
	 * 保存 request token
	 * 
	 * @return array
	 */
	public function aSaveTempToken($sSrc, $sResponse)
	{
		$arr = self::AParseToken($sResponse);
		if ('true' === $arr['oauth_callback_confirmed'])
		{
			$ctime = date('Y-m-d H:i:s');
			$aData = array(
				'token' => $arr['oauth_token'],
				'src' => $sSrc,
				'oauth_token_secret' => $arr['oauth_token_secret'],
				'ctime' => $ctime,
				);
			$aUpdate = array(
				'oauth_token_secret' => $arr['oauth_token_secret'],
				'ctime' => $ctime,
				);
			$temptokenDao = $this->_aConf['temptoken'].'Dao';
			$this->$temptokenDao->aInsert($aData, $aUpdate);
			return $arr;
		}
		return array();
	}
	
	/**
	 * 查询 token 信息
	 * 
	 * @return array
	 */
	public function aGetTempToken($sSrc, $sToken)
	{
		$temptokenDao = $this->_aConf['temptoken'].'Dao';
		return $this->$temptokenDao->aGet(array('token' => $sToken, 'src' => $sSrc));
	}
	
	/**
	 * 入口接口 / 回调接口
	 *
	 * @return array|exit
	 */
	public function vMain($sSrc, $fnGetRequestToken, $fnGetAccessToken)
	{
		assert(isset($this->_aConf['srclist'][$sSrc]));

		$this->aGetPara();
		if (!isset($this->_aReq['oauth_token']) || 0 === strlen($this->_aReq['oauth_token']))
		{
			$uri = Ko_Mode_OAuthServer::SGetRequestTokenUri($this->_aConf['srclist'][$sSrc]['request_method'],
				$this->_aConf['srclist'][$sSrc]['request_token_uri'],
				$this->_aConf['srclist'][$sSrc]['oauth_consumer_key'],
				$this->_aConf['srclist'][$sSrc]['oauth_consumer_secret'],
				$this->_aConf['srclist'][$sSrc]['oauth_callback']);
			$response = call_user_func($fnGetRequestToken, $uri);
			$arr = $this->aSaveTempToken($sSrc, $response);
			$authorizeUri = Ko_Mode_OAuthServer::SGetAuthorizeUri($this->_aConf['srclist'][$sSrc]['authorize_uri'], $arr['oauth_token']);
			Ko_Web_Response::VSetRedirect($authorizeUri);
			Ko_Web_Response::VSend();
			exit;
		}

		$tokenInfo = $this->aGetTempToken($sSrc, $this->_aReq['oauth_token']);
		$uri = Ko_Mode_OAuthServer::SGetAccessTokenUri($this->_aConf['srclist'][$sSrc]['request_method'],
			$this->_aConf['srclist'][$sSrc]['access_token_uri'],
			$this->_aConf['srclist'][$sSrc]['oauth_consumer_key'],
			$this->_aConf['srclist'][$sSrc]['oauth_consumer_secret'],
			$this->_aReq['oauth_token'],
			$tokenInfo['oauth_token_secret'],
			$this->_aReq['oauth_verifier']);
		$response = call_user_func($fnGetAccessToken, $uri);
		return self::AParseToken($response);
	}
	
	/**
	 * 分析 token
	 * 
	 * @return array
	 */
	public static function AParseToken($sResponse)
	{
		parse_str($sResponse, $arr);
		if (strlen($arr['oauth_token']) && strlen($arr['oauth_token_secret']))
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
		return $this->_bSaveUserToken($sSrc, $iUid, $aTokeninfo, 'oauth_token', array('oauth_token_secret'));
	}
}

?>