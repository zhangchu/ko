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
 *   server 部分的表
 *   CREATE TABLE s_ko_oauth2_client_0(
 *     cid int unsigned not null default 0,
 *     public tinyint not null default 0,
 *     passcred tinyint not null default 0,          -- 是否可以使用 password 许可方式获得 access token
 *     clientcred tinyint not null default 0,        -- 是否可以使用 client_credentials 许可方式获得 access token
 *     redirect_uris text,                           -- 使用空格分隔多个 uri
 *     unique (cid)
 *   )ENGINE=InnoDB DEFAULT CHARSET=utf8;
 *   CREATE TABLE s_ko_oauth2_code_0(
 *     cid int unsigned not null default 0,
 *     code varchar(128) not null default '',
 *     redirect_uri varchar(512) not null default '',
 *     uid bigint unsigned not null default 0,
 *     scope text,
 *     ctime timestamp NOT NULL default 0,
 *     unique (cid, code)
 *   )ENGINE=InnoDB DEFAULT CHARSET=utf8;
 *   CREATE TABLE s_ko_oauth2_token(
 *     token varchar(128) not null default '',
 *     cid int unsigned not null default 0,
 *     uid bigint unsigned not null default 0,
 *     scope text,
 *     ctime timestamp NOT NULL default 0,
 *     unique (token),
 *     index(uid, cid)
 *   )ENGINE=InnoDB DEFAULT CHARSET=utf8;
 *   CREATE TABLE s_ko_oauth2_refreshtoken(
 *     token varchar(128) not null default '',
 *     cid int unsigned not null default 0,
 *     uid bigint unsigned not null default 0,
 *     scope text,
 *     ctime timestamp NOT NULL default 0,
 *     unique (token)
 *   )ENGINE=InnoDB DEFAULT CHARSET=utf8;
 * </pre>
 *
 * <b>_aConf 配置</b>
 * @see Ko_Mode_OAuth2Server::$_aConf
 *
 * @package ko\mode
 * @author zhangchu
 */

/**
 * OAuth 2.0 实现
 */
class Ko_Mode_OAuth2Server extends Ko_Mode_OAuthServerBase
{
	/**
	 * 配置数组
	 *
	 * <pre>
	 * array(
	 *   'client' => client 信息表
	 *   'code' => 授权码表，对于过期的数据，应定期清理
	 *   'tokenApi' => access token 表，对于过期的数据，应定期清理
	 *   'refreshtokenApi' => refresh token 表，对于过期的数据，应定期清理
	 *   'token_type' => 通常使用一个 URI 作为 access token 类型
	 *   'code_timeout' => 可选，授权码过期时间
	 *   'token_timeout' => 可选，access token 过期时间，为 0 表示不过期
	 *   'refreshtoken_timeout' => 可选，refresh token 过期时间，为 0 表示不过期
	 * )
	 * </pre>
	 *
	 * @var array
	 */
	protected $_aConf = array();

	const DEFAULT_CODE_TIMEOUT				= 600;
	const DEFAULT_TOKEN_TIMEOUT				= 86400;
	const DEFAULT_REFRESHTOKEN_TIMEOUT		= 2592000;	// 30 x 86400

	protected $_sClientId = '';		//auth 接口是传入的 client_id，token接口是验证通过的 client_id

	/**
	 * client 信息管理：注册/设置信息
	 */
	public function vSetClientInfo($iCid, $bPublic, $iPasscred, $iClientcred, $sRedirectUris)
	{
		//http://tools.ietf.org/html/rfc6749#section-2.1
		$iPublic = $bPublic ? 1 : 0;
		$sRedirectUris = $this->_sNormalizeUri($sRedirectUris);
		$aData = array(
			'cid' => $iCid,
			'public' => $iPublic,
			'passcred' => $iPasscred,
			'clientcred' => $iClientcred,
			'redirect_uris' => $sRedirectUris,
			);
		$aUpdate = array(
			'public' => $iPublic,
			'passcred' => $iPasscred,
			'clientcred' => $iClientcred,
			'redirect_uris' => $sRedirectUris,
			);
		$clientDao = $this->_aConf['client'].'Dao';
		$this->$clientDao->aInsert($aData, $aUpdate);
	}

	/**
	 * 用户授权管理：撤销 refresh token 授权
	 */
	public function vRevokeRefreshToken($iUid, $iCid, $sToken)
	{
		$refreshtokenApi = $this->_aConf['refreshtokenApi'];
		$oOption = new Ko_Tool_SQL;
		$oOption->oWhere('uid = ? and cid = ?', $iUid, $iCid);
		$this->$refreshtokenApi->iDelete($sToken, $oOption);
	}

	/**
	 * 授权请求接口
	 *
	 * @return boolean|exit 返回 true 输出用户确认页，返回 false 输出错误提示页
	 */
	public function vMain_Auth($iUid = 0, $bAgree = false, $sScope = '')
	{
		$this->_sClientId = $this->_aReq['client_id'];
		if (!$this->_bCheckAuth($sRedirectUri))
		{
			return false;
		}
		if ($iUid && 'POST' === Ko_Web_Request::SRequestMethod())
		{
			$this->_vAuthClient($iUid, $bAgree, $sScope, $sRedirectUri);
		}
		return true;
	}

	/**
	 * token 接口
	 *
	 * @return exit
	 */
	public function vMain_Token($fnCheckClient_Callback, $fnCheckUser_Callback = null)
	{
		if (!$this->_bCheckClient($fnCheckClient_Callback))
		{
			$this->_vTokenError('invalid_client');
		}
		switch ($this->_aReq['grant_type'])
		{
		case 'authorization_code':
			list($uid, $scope) = $this->_aCheckCode();
			$token = $this->_sGenToken($uid, $scope);
			$refresh_token = $this->_sGenRefreshToken($uid, $scope);
			break;
		case 'password':
			list($uid, $scope) = $this->_aCheckPasscred($fnCheckUser_Callback);
			$token = $this->_sGenToken($uid, $scope);
			$refresh_token = $this->_sGenRefreshToken($uid, $scope);
			break;
		case 'client_credentials':
			list($uid, $scope) = $this->_aCheckClientcred();
			$token = $this->_sGenToken($uid, $scope);
			$refresh_token = '';
			break;
		case 'refresh_token':
			list($uid, $scope, $scope_originally) = $this->_aCheckRefreshToken();
			$token = $this->_sGenToken($uid, $scope);
			$refresh_token = $this->_sGenRefreshToken($uid, $scope_originally);
			break;
		default:
			$this->_vTokenError('invalid_request');
			break;
		}
		$this->_vTokenSucc($token, $refresh_token, $scope);
	}

	/**
	 * client请求用户数据：对请求进行权限验证，如果验证错误，直接输出 400/401 头并退出程序
	 *
	 * @return array 返回授权信息 array($cid, uid, scope)
	 */
	public function aCheckAuthReq()
	{
		if (!isset($this->_aReq['access_token']))
		{
			$auth = Ko_Web_Request::SHttpAuthorization();
			list($bearer, $access_token) = explode(' ', $auth);
			if ('Bearer' === $bearer && strlen($access_token))
			{
				$this->_aReq['access_token'] = $access_token;
			}
			else
			{
				$this->_vTokenError('empty_accesstoken');
			}
		}
		$tokenApi = $this->_aConf['tokenApi'];
		$info = $this->$tokenApi->aGet($this->_aReq['access_token']);
		if (empty($info))
		{
			$this->_vTokenError('invalid_accesstoken');
		}
		$tt = $this->_iGetTokenTimeout();
		if ($tt && ($tt < abs(time() - strtotime($info['ctime']))))
		{
			$this->_vTokenError('expire_accesstoken');
		}
		return array($info['cid'], $info['uid'], $info['scope']);
	}

	/**
	 * client Api: 获取跳转到用户授权的 uri
	 *
	 * @return string
	 */
	public static function SGetAuthorizeUri($sUri, $sKey, $sCallback = '', $sScope = '', $sState = '', $bImplicit = false)
	{
		//http://tools.ietf.org/html/rfc6749#section-4.1.1
		//http://tools.ietf.org/html/rfc6749#section-4.2.1
		$uri = $sUri.'?response_type='.($bImplicit ? 'token' : 'code').'&client_id='.urlencode($sKey);
		if (0 !== strlen($sCallback))
		{
			$uri .= '&redirect_uri='.urlencode($sCallback);
		}
		if (0 !== strlen($sScope))
		{
			$uri .= '&scope='.urlencode($sScope);
		}
		if (0 !== strlen($sState))
		{
			$uri .= '&state='.urlencode($sState);
		}
		return $uri;
	}
	
	/**
	 * client Api: 获取 access token 的接口 uri
	 *
	 * @return string
	 */
	public static function SGetAccessTokenUri($sMethod, $sUri, $sKey, $sSecret, $sCallback, $sCode)
	{
		//http://tools.ietf.org/html/rfc6749#section-4.1.3
		$uri = ('GET' === $sMethod) ? $sUri.'?' : '';
		$uri .= 'grant_type=authorization_code&code='.urlencode($sCode);
		if (0 !== strlen($sCallback))
		{
			$uri .= '&redirect_uri='.urlencode($sCallback);
		}
		if (0 !== strlen($sKey))
		{
			$uri .= '&client_id='.urlencode($sKey);
			if (0 !== strlen($sSecret))
			{
				$uri .= '&client_secret='.urlencode($sSecret);
			}
		}
		return $uri;
	}

	/**
	 * client Api: 获取 access token 的接口 uri
	 *
	 * @return string
	 */
	public static function SGetAccessTokenUri_Passcred($sMethod, $sUri, $sKey, $sSecret, $sUsername, $sPassword, $sScope = '')
	{
		//http://tools.ietf.org/html/rfc6749#section-4.3.2
		$uri = ('GET' === $sMethod) ? $sUri.'?' : '';
		$uri .= 'grant_type=password&username='.urlencode($sUsername).'&password='.urlencode($sPassword);
		if (0 !== strlen($sScope))
		{
			$uri .= '&scope='.urlencode($sScope);
		}
		if (0 !== strlen($sKey))
		{
			$uri .= '&client_id='.urlencode($sKey);
			if (0 !== strlen($sSecret))
			{
				$uri .= '&client_secret='.urlencode($sSecret);
			}
		}
		return $uri;
	}

	/**
	 * client Api: 获取 access token 的接口 uri
	 *
	 * @return string
	 */
	public static function SGetAccessTokenUri_Clientcred($sMethod, $sUri, $sKey, $sSecret, $sScope = '')
	{
		//http://tools.ietf.org/html/rfc6749#section-4.4.2
		$uri = ('GET' === $sMethod) ? $sUri.'?' : '';
		$uri .= 'grant_type=client_credentials';
		if (0 !== strlen($sScope))
		{
			$uri .= '&scope='.urlencode($sScope);
		}
		if (0 !== strlen($sKey))
		{
			$uri .= '&client_id='.urlencode($sKey);
			if (0 !== strlen($sSecret))
			{
				$uri .= '&client_secret='.urlencode($sSecret);
			}
		}
		return $uri;
	}

	/**
	 * client Api: 获取 access token 的接口 uri
	 *
	 * @return string
	 */
	public static function SGetAccessTokenUri_Refresh($sMethod, $sUri, $sKey, $sSecret, $sRefreshToken, $sScope = '')
	{
		//http://tools.ietf.org/html/rfc6749#section-6
		$uri = ('GET' === $sMethod) ? $sUri.'?' : '';
		$uri .= 'grant_type=refresh_token&refresh_token='.urlencode($sRefreshToken);
		if (0 !== strlen($sScope))
		{
			$uri .= '&scope='.urlencode($sScope);
		}
		if (0 !== strlen($sKey))
		{
			$uri .= '&client_id='.urlencode($sKey);
			if (0 !== strlen($sSecret))
			{
				$uri .= '&client_secret='.urlencode($sSecret);
			}
		}
		return $uri;
	}
	
	private function _bCheckAuth(&$sRedirectUri)
	{
		//http://tools.ietf.org/html/rfc6749#section-4.1.1
		//http://tools.ietf.org/html/rfc6749#section-4.2.1
		if (('code' !== $this->_aReq['response_type'] && 'token' !== $this->_aReq['response_type'])
			|| (0 === strlen($this->_sClientId))
			|| !$this->_bCheckRedirectUri($sRedirectUri))
		{
			return false;
		}
		return true;
	}

	private function _vAuthClient($iUid, $bAgree, $sScope, $sRedirectUri)
	{
		//http://tools.ietf.org/html/rfc6749#section-4.1.2
		//http://tools.ietf.org/html/rfc6749#section-4.2.2
		if (!$bAgree)
		{
			$this->_vAuthError($sRedirectUri, 'access_denied');
		}
		$scope = $this->_sNormalizeScope($sScope, '');
		if ('code' === $this->_aReq['response_type'])
		{
			$code = $this->_sGenCode($iUid, $scope);
			$url = $sRedirectUri.((false === strpos($sRedirectUri, '?')) ? '?' : '&').'code='.urlencode($code);
		}
		else
		{
			$token = $this->_sGenToken($iUid, $scope);
			$url = $sRedirectUri.'#access_token='.urlencode($token).'&token_type='.urlencode($this->_aConf['token_type']).'&expires_in='.($this->_iGetTokenTimeout()).'&scope='.urlencode($scope);
		}
		if (strlen($this->_aReq['state']))
		{
			$url .= '&state='.urlencode($this->_aReq['state']);
		}
		header('Location: '.$url);
		exit;
	}

	private function _bCheckClient($fnCheckClient_Callback)
	{
		//http://tools.ietf.org/html/rfc6749#section-2.3.1
		$client_id = urldecode(Ko_Web_Request::SPhpAuthUser());
		$client_secret = urldecode(Ko_Web_Request::SPhpAuthPw());
		if (0 === strlen($client_id) || 0 === strlen($client_secret))
		{
			$client_id = $this->_aReq['client_id'];
			$client_secret = $this->_aReq['client_secret'];
		}
		if (0 === strlen($client_id) || 0 === strlen($client_secret))
		{
			return false;
		}
		$info = $this->aGetClientInfo($client_id);
		if (empty($info))
		{
			return false;
		}
		if (call_user_func_array($fnCheckClient_Callback, array($client_id, $client_secret)))
		{
			$this->_sClientId = $client_id;
			return true;
		}
		return false;
	}

	private function _aCheckCode()
	{
		if (!isset($this->_aReq['code']))
		{
			$this->_vTokenError('invalid_request');
		}
		$codeDao = $this->_aConf['code'].'Dao';
		$key = array('cid' => $this->_sClientId, 'code' => $this->_aReq['code']);
		$info = $this->$codeDao->aGet($key);
		if (empty($info) || $this->_iGetCodeTimeout() < abs(time() - strtotime($info['ctime'])))
		{
			$this->_vTokenError('invalid_grant');
		}
		if (!$this->$codeDao->iDelete($key))
		{
			$this->_vTokenError('invalid_grant');
		}
		if (strlen($info['redirect_uri']) && $info['redirect_uri'] !== $this->_aReq['redirect_uri'])
		{
			$this->_vTokenError('invalid_request');
		}
		return array($info['uid'], $info['scope']);
	}

	private function _aCheckPasscred($fnCheckUser_Callback)
	{
		$info = $this->aGetClientInfo($this->_sClientId);
		if (empty($info) || !$info['passcred'])
		{
			$this->_vTokenError('unauthorized_client');
		}
		if (!isset($this->_aReq['username']) || !isset($this->_aReq['password']))
		{
			$this->_vTokenError('invalid_request');
		}
		$uid = call_user_func_array($fnCheckUser_Callback, array($this->_aReq['username'], $this->_aReq['password']));
		if (!$uid)
		{
			$this->_vTokenError('invalid_grant');
		}
		return array($uid, $this->_aReq['scope']);
	}
	
	private function _aCheckClientcred()
	{
		$info = $this->aGetClientInfo($this->_sClientId);
		if (empty($info) || !$info['clientcred'])
		{
			$this->_vTokenError('unauthorized_client');
		}
		return array(0, $this->_aReq['scope']);
	}
	
	private function _aCheckRefreshToken()
	{
		if (!isset($this->_aReq['refresh_token']))
		{
			$this->_vTokenError('invalid_request');
		}
		$refreshtokenApi = $this->_aConf['refreshtokenApi'];
		$info = $this->$refreshtokenApi->aGet($this->_aReq['refresh_token']);
		assert(strval($info['cid']) === $this->_sClientId);
		$rt = $this->_iGetRefreshTokenTimeout();
		if (empty($info) || ($rt && ($rt < abs(time() - strtotime($info['ctime'])))))
		{
			$this->_vTokenError('invalid_grant');
		}
		if (!$this->$refreshtokenApi->iDelete($this->_aReq['refresh_token']))
		{
			$this->_vTokenError('invalid_grant');
		}
		$scope = $this->_sNormalizeScope($info['scope'], $info['scope']);
		return array($info['uid'], $scope, $info['scope']);
	}

	private function _sGenCode($iUid, $sScope)
	{
		$code = Ko_Tool_OAuth::SGenKey();
		$aData = array(
			'cid' => $this->_sClientId,
			'code' => $code,
			'redirect_uri' => $this->_aReq['redirect_uri'],
			'uid' => $iUid,
			'scope' => $sScope,
			'ctime' => date('Y-m-d H:i:s'),
			);
		$codeDao = $this->_aConf['code'].'Dao';
		$this->$codeDao->aInsert($aData);
		return $code;
	}

	private function _sGenToken($iUid, $sScope)
	{
		$tokenApi = $this->_aConf['tokenApi'];
		$oOption = new Ko_Tool_SQL;
		$tokenInfo = $this->$tokenApi->aGetList($oOption->oWhere('uid = ? and cid = ?', $iUid, $this->_sClientId)->oLimit(1));
		if (!empty($tokenInfo))
		{
			$aUpdate = array(
				'scope' => $sScope,
				'ctime' => date('Y-m-d H:i:s'),
			);
			$this->$tokenApi->iUpdate($tokenInfo[0], $aUpdate);
			return $tokenInfo[0]['token'];
		}
		
		$token = Ko_Tool_OAuth::SGenKey();
		$aData = array(
			'cid' => $this->_sClientId,
			'token' => $token,
			'uid' => $iUid,
			'scope' => $sScope,
			'ctime' => date('Y-m-d H:i:s'),
			);
		$this->$tokenApi->aInsert($aData);
		return $token;
	}

	private function _sGenRefreshToken($iUid, $sScope)
	{
		$token = Ko_Tool_OAuth::SGenKey();
		$aData = array(
			'cid' => $this->_sClientId,
			'token' => $token,
			'uid' => $iUid,
			'scope' => $sScope,
			'ctime' => date('Y-m-d H:i:s'),
			);
		$refreshtokenApi = $this->_aConf['refreshtokenApi'];
		$this->$refreshtokenApi->aInsert($aData);
		return $token;
	}

	private function _vAuthError($sRedirectUri, $sError)
	{
		//http://tools.ietf.org/html/rfc6749#section-4.1.2.1
		//http://tools.ietf.org/html/rfc6749#section-4.2.2.1
		if ('code' === $this->_aReq['response_type'])
		{
			$url = $sRedirectUri.((false === strpos($sRedirectUri, '?')) ? '?' : '&').'error='.urlencode($sError);
		}
		else
		{
			$url = $sRedirectUri.'#error='.urlencode($sError);
		}
		if (strlen($this->_aReq['state']))
		{
			$url .= '&state='.urlencode($this->_aReq['state']);
		}
		header('Location: '.$url);
		exit;
	}

	private function _vTokenSucc($sAccessToken, $sRefreshToken, $sScope)
	{
		header('Content-Type: application/json;charset=UTF-8');
		header('Cache-Control: no-store');
		header('Pragma: no-cache');

		//http://tools.ietf.org/html/rfc6749#section-5.1
		$data = array(
			'access_token' => $sAccessToken,
			'token_type' => $this->_aConf['token_type'],
			'expires_in' => $this->_iGetTokenTimeout(),
			'scope' => $sScope,
			);
		if (0 !== strlen($sRefreshToken))
		{
			$data['refresh_token'] = $sRefreshToken;
		}
		echo json_encode($data);
		exit;
	}

	private function _vTokenError($sError)
	{
		//http://tools.ietf.org/html/rfc6749#section-5.2
		if ('invalid_client' === $sError)
		{
			header('HTTP/1.1 401 Unauthorized');
		}
		else
		{
			header('HTTP/1.1 400 Bad Request');
		}
		header('Content-Type: application/json;charset=UTF-8');
		header('Cache-Control: no-store');
		header('Pragma: no-cache');

		$data = array(
			'error' => $sError,
			);
		echo json_encode($data);
		exit;
	}

	private function _bCheckRedirectUri(&$sRedirectUri)
	{
		$info = $this->aGetClientInfo($this->_sClientId);
		if (empty($info))
		{
			return false;
		}
		//http://tools.ietf.org/html/rfc6749#section-3.1.2.2
		$aUri = $this->_aParseUri($info['redirect_uris']);
		$count = count($aUri);
		if (0 === $count)
		{
			if ($info['public'] || 'token' === $this->_aReq['response_type'] || 0 === strlen($this->_aReq['redirect_uri']))
			{
				return false;
			}
			$sRedirectUri = $this->_aReq['redirect_uri'];
			return true;
		}
		if (1 === $count && 0 === strlen($this->_aReq['redirect_uri']))
		{
			$sRedirectUri = $aUri[0];
			return true;
		}
		if (0 !== strlen($this->_aReq['redirect_uri']))
		{	//http://tools.ietf.org/html/rfc6749#section-3.1.2.3
			foreach ($aUri as $uri)
			{
				if ($this->_bCompareUri($this->_aReq['redirect_uri'], $uri))
				{
					$sRedirectUri = $this->_aReq['redirect_uri'];
					return true;
				}
			}
		}
		return false;
	}

	private function _bCompareUri($sParaUrl, $sRegUri)
	{
		if ($sParaUrl === $sRegUri)
		{
			return true;
		}
		list($sParaUrl, $query) = explode('?', $sParaUrl, 2);
		return $sParaUrl === $sRegUri;
	}

	private function _aParseUri($sRedirectUri)
	{
		if (0 === strlen($sRedirectUri))
		{
			return array();
		}
		return explode(' ', $sRedirectUri);
	}

	private function _sNormalizeUri($sUris)
	{
		$valid = array();
		$arr = preg_split('/\s/', $sUris);
		foreach ($arr as $v)
		{
			if ('http://' === substr(strtolower($v), 0, 7)
				|| 'https://' === substr(strtolower($v), 0, 8))
			{
				$valid[] = $v;
			}
		}
		return implode(' ', $valid);
	}

	private function _sNormalizeScope($sScope, $sDefaultScope)
	{
		//http://tools.ietf.org/html/rfc6749#section-3.3
		if (isset($this->_aReq['scope']))
		{
			return $this->_sIntersectScope($this->_aReq['scope'], $sScope);
		}
		return $sDefaultScope;
	}

	private function _sIntersectScope($sReqScope, $sResScope)
	{
		$aReqScope = explode(' ', $sReqScope);
		$aResScope = explode(' ', $sResScope);
		$aResScope = array_intersect($aReqScope, $aResScope);
		return implode(' ', $aResScope);
	}

	private function _iGetCodeTimeout()
	{
		if (isset($this->_aConf['code_timeout']))
		{
			return $this->_aConf['code_timeout'];
		}
		return self::DEFAULT_CODE_TIMEOUT;
	}

	private function _iGetTokenTimeout()
	{
		if (isset($this->_aConf['token_timeout']))
		{
			return $this->_aConf['token_timeout'];
		}
		return self::DEFAULT_TOKEN_TIMEOUT;
	}

	private function _iGetRefreshTokenTimeout()
	{
		if (isset($this->_aConf['refreshtoken_timeout']))
		{
			return $this->_aConf['refreshtoken_timeout'];
		}
		return self::DEFAULT_REFRESHTOKEN_TIMEOUT;
	}
}

?>