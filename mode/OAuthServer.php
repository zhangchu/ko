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
 *   server 部分的表
 *   CREATE TABLE s_ko_oauth_client_0(
 *     cid int unsigned not null default 0,
 *     secret varchar(128) not null default '',
 *     unique (cid)
 *   )ENGINE=InnoDB DEFAULT CHARSET=utf8;
 *   CREATE TABLE s_ko_oauth_temptoken(
 *     token varchar(128) not null default '',
 *     secret varchar(128) not null default '',
 *     cid int unsigned not null default 0,
 *     callback varchar(512) not null default '',
 *     verifier varchar(128) not null default '',
 *     uid bigint unsigned not null default 0,
 *     scope text,
 *     ctime timestamp NOT NULL default 0,
 *     atime timestamp NOT NULL default 0,
 *     unique (token)
 *   )ENGINE=InnoDB DEFAULT CHARSET=utf8;
 *   CREATE TABLE s_ko_oauth_token(
 *     token varchar(128) not null default '',
 *     cid int unsigned not null default 0,
 *     secret varchar(128) not null default '',
 *     uid bigint unsigned not null default 0,
 *     scope text,
 *     ctime timestamp NOT NULL default 0,
 *     unique (token),
 *     index (uid, cid)
 *   )ENGINE=InnoDB DEFAULT CHARSET=utf8;
 *   CREATE TABLE s_ko_oauth_nonce_0(
 *     cid int unsigned not null default 0,
 *     otime timestamp NOT NULL default 0,
 *     nonce varchar(128) not null default '',
 *     ctime timestamp NOT NULL default 0,
 *     unique (cid, otime, nonce)
 *   )ENGINE=InnoDB DEFAULT CHARSET=utf8;
 * </pre>
 *
 * <b>_aConf 配置</b>
 * @see Ko_Mode_OAuthServer::$_aConf
 *
 * @package ko\mode
 * @author zhangchu
 */

/**
 * OAuth 1.0 实现
 */
class Ko_Mode_OAuthServer extends Ko_Mode_OAuthServerBase
{
	/**
	 * 配置数组
	 *
	 * <pre>
	 * array(
	 *   'client' => client 秘钥表
	 *   'temptoken' => temptoken 表，对于过期的数据，应定期清理
	 *   'tokenApi' => token 表，对于过期的数据，应定期清理
	 *   'nonce' => nonce 校验表，可选，对于过期的数据，应定期清理
	 *   'baseuri' => 通过计算获得的 base uri 可能会受代理等因素影响，所以直接根据 API 文档进行配置实现更为简单
	 *   'client_timeout' => client 时钟和服务器时钟的最大误差，可选，为 0 不校验时间戳
	 *   'temptoken_timeout' => temptoken 过期时间，可选
	 *   'token_timeout' => token 过期时间，可选，为 0 表示不过期
	 * )
	 * </pre>
	 *
	 * @var array
	 */
	protected $_aConf = array();

	const DEFAULT_CLIENT_TIMEOUT			= 1800;
	const DEFAULT_TEMPTOKEN_TIMEOUT			= 1800;
	const DEFAULT_TOKEN_TIMEOUT				= 86400;

	private $_sBaseuri;

	/**
	 * client 信息管理：注册/设置信息
	 */
	public function vSetClientInfo($iCid, &$sSecret)
	{
		$sSecret = Ko_Tool_OAuth::SGenKey();
		$aData = array(
			'cid' => $iCid,
			'secret' => $sSecret,
			);
		$aUpdate = array(
			'secret' => $sSecret,
			);
		$clientDao = $this->_aConf['client'].'Dao';
		$this->$clientDao->aInsert($aData, $aUpdate);
	}

	/**
	 * 用户授权
	 *
	 * @return boolean|string|exit 返回 true 输出用户确认页，返回 false 输出错误提示页，其他返回 verifier 表示应该显示在页面上，让用户通知 client 授权完成
	 */
	public function vMain_Auth(&$iCid, $iUid = 0, $sScope = '')
	{
		$iCid = $this->_iCheckTempToken($sCallback);
		if (!$iCid)
		{
			return false;
		}
		if ($iUid && 'POST' === Ko_Web_Request::SRequestMethod())
		{
			return $this->_vAuthClient($iUid, $sScope, $sCallback);
		}
		return true;
	}

	/**
	 * client请求token
	 *
	 * @return exit
	 */
	public function vMain_Token($sBaseuri = '', $fnXAuth_CheckUser_Callback = null)
	{
		$this->_vCheckParas($sBaseuri, true);

		if (isset($this->_aReq['oauth_token']))
		{
			$authinfo = $this->_vCheckVerifier();
			if (false === $authinfo || empty($authinfo[0]))
			{
				header('HTTP/1.0 401 Unauthorized');
				exit;
			}
			list($token, $secret) = $this->aGetUserTokenDirect($this->_aReq['oauth_consumer_key'], $authinfo[0], $authinfo[1]);
			$sBody = 'oauth_token='.urlencode($token).'&oauth_token_secret='.urlencode($secret);
		}
		else
		{
			if (isset($this->_aReq['x_auth_mode']))
			{
				$authinfo = call_user_func_array($fnXAuth_CheckUser_Callback, array($this->_aReq['x_auth_username'], $this->_aReq['x_auth_password']));
				if (false === $authinfo || empty($authinfo[0]))
				{
					header('HTTP/1.0 401 Unauthorized');
					exit;
				}
				list($token, $secret) = $this->aGetUserTokenDirect($this->_aReq['oauth_consumer_key'], $authinfo[0], $authinfo[1]);
				$tt = $this->_iGetTokenTimeout();
				$sBody = 'oauth_token='.urlencode($token).'&oauth_token_secret='.urlencode($secret).'&x_auth_expires='.($tt ? ($tt + time()) : 0);
			}
			else
			{
				list($token, $secret) = $this->_aGetTemporaryCredentials();
				$sBody = 'oauth_token='.urlencode($token).'&oauth_token_secret='.urlencode($secret).'&oauth_callback_confirmed=true';
			}
		}

		header('Content-Type: application/x-www-form-urlencoded');
		echo $sBody;
		exit;
	}

	/**
	 * client请求用户数据：对请求进行权限验证，如果验证错误，直接输出 400/401 头并退出程序
	 *
	 * @return array|exit 返回授权信息 array(uid, scope)
	 */
	public function aCheckAuthReq($sBaseuri = '')
	{
		$this->_vCheckParas($sBaseuri, false);

		$tokenApi = $this->_aConf['tokenApi'];
		$info = $this->$tokenApi->aGet($this->_aReq['oauth_token']);
		assert(strval($info['cid']) === $this->_aReq['oauth_consumer_key']);
		return array($info['uid'], $info['scope']);
	}

	/**
	 * 直接获取用户的授权信息
	 *
	 * @return array 返回用户授权信息 array(token, secret)
	 */
	public function aGetUserTokenDirect($iCid, $iUid, $sScope = '')
	{
		$tokenApi = $this->_aConf['tokenApi'];
		$oOption = new Ko_Tool_SQL;
		$tokenInfo = $this->$tokenApi->aGetList($oOption->oWhere('uid = ? and cid = ?', $iUid, $iCid)->oLimit(1));
		if (!empty($tokenInfo))
		{
			$aUpdate = array(
				'scope' => $sScope,
				'ctime' => date('Y-m-d H:i:s'),
			);
			$this->$tokenApi->iUpdate($tokenInfo[0], $aUpdate);
			return array($tokenInfo[0]['token'], $tokenInfo[0]['secret']);
		}

		$sToken = Ko_Tool_OAuth::SGenKey();
		$sSecret = Ko_Tool_OAuth::SGenKey();
		$aData = array(
			'cid' => $iCid,
			'token' => $sToken,
			'secret' => $sSecret,
			'uid' => $iUid,
			'scope' => $sScope,
			'ctime' => date('Y-m-d H:i:s'),
			);
		$this->$tokenApi->aInsert($aData);
		return array($sToken, $sSecret);
	}

	/**
	 * client Api: 获取 temp token 的接口 uri
	 *
	 * @return string
	 */
	public static function SGetRequestTokenUri($sMethod, $sUri, $sKey, $sSecret, $sCallback)
	{
		$aReq = array(
			'oauth_consumer_key' => $sKey,
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_timestamp' => time(),
			'oauth_nonce' => mt_rand(),
			'oauth_callback' => $sCallback,
			'oauth_version' => '1.0',
			);
		return self::_SGetTokenUri($sMethod, $sUri, $aReq, $sSecret, '');
	}

	/**
	 * client Api: 获取跳转到用户授权的 uri
	 *
	 * @return string
	 */
	public static function SGetAuthorizeUri($sUri, $sRequestToken)
	{
		return $sUri.'?oauth_token='.urlencode($sRequestToken);
	}

	/**
	 * client Api: 获取 access token 的接口 uri
	 *
	 * @return string
	 */
	public static function SGetAccessTokenUri($sMethod, $sUri, $sKey, $sSecret, $sRequestToken, $sRequestSecret, $sVerifier)
	{
		$aReq = array(
			'oauth_consumer_key' => $sKey,
			'oauth_token' => $sRequestToken,
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_timestamp' => time(),
			'oauth_nonce' => mt_rand(),
			'oauth_verifier' => $sVerifier,
			'oauth_version' => '1.0',
			);
		return self::_SGetTokenUri($sMethod, $sUri, $aReq, $sSecret, $sRequestSecret);
	}
	
	/**
	 * client Api: 使用 XAuth 获取 access token 的接口 uri
	 *
	 * @return string
	 */
	public static function SGetXAuthTokenUri($sMethod, $sUri, $sKey, $sSecret, $sUsername, $sPassword)
	{
		$aReq = array(
			'x_auth_mode' => 'client_auth',
			'x_auth_username' => $sUsername,
			'x_auth_password' => $sPassword,
			'oauth_consumer_key' => $sKey,
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_timestamp' => time(),
			'oauth_nonce' => mt_rand(),
			'oauth_version' => '1.0',
			);
		return self::_SGetTokenUri($sMethod, $sUri, $aReq, $sSecret, '');
	}
	
	/**
	 * client Api: 获取 api 的接口 uri
	 *
	 * @return string
	 */
	public static function SGetApiUri($sMethod, $sUri, $sKey, $sSecret, $sAccessToken, $sAccessSecret, $aPara)
	{
		$aReq = array(
			'oauth_consumer_key' => $sKey,
			'oauth_token' => $sAccessToken,
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_timestamp' => time(),
			'oauth_nonce' => mt_rand(),
			'oauth_version' => '1.0',
			);
		$aReq = array_merge($aPara, $aReq);
		return self::_SGetTokenUri($sMethod, $sUri, $aReq, $sSecret, $sAccessSecret);
	}

	private function _iCheckTempToken(&$sCallback)
	{
		if (!isset($this->_aReq['oauth_token']))
		{
			return 0;
		}
		$temptokenDao = $this->_aConf['temptoken'].'Dao';
		$info = $this->$temptokenDao->aGet($this->_aReq['oauth_token']);
		if (empty($info) || ($this->_iGetTempTokenTimeout() < abs(time() - strtotime($info['ctime']))))
		{
			return 0;
		}
		$sCallback = $info['callback'];
		return $info['cid'];
	}

	private function _vAuthClient($iUid, $sScope, $sCallback)
	{
		$sVerifier = Ko_Tool_OAuth::SGenKey();
		$aUpdate = array(
			'verifier' => $sVerifier,
			'uid' => $iUid,
			'scope' => $sScope,
			'atime' => date('Y-m-d H:i:s'),
			);
		$temptokenDao = $this->_aConf['temptoken'].'Dao';
		$this->$temptokenDao->iUpdate($this->_aReq['oauth_token'], $aUpdate);

		if (strlen($sCallback))
		{
			header('Location: '.$sCallback.((false === strpos($sCallback, '?')) ? '?' : '&').'oauth_token='.urlencode($this->_aReq['oauth_token']).'&oauth_verifier='.urlencode($sVerifier));
			exit;
		}

		return $sVerifier;
	}

	private function _vCheckParas($sBaseuri, $bTempToken)
	{
		$this->_sBaseuri = $sBaseuri;
		if (!Ko_Tool_OAuth::BCheckParas($this->_aReq, $bTempToken))
		{
			header('HTTP/1.0 400 Bad Request');
			exit;
		}
		list($sClientSecret, $sTokenSecret) = $this->_aGetSecret($bTempToken);
		if (empty($sClientSecret)
			|| (isset($this->_aReq['oauth_token']) && empty($sTokenSecret))
			|| !$this->_bCheckSignature($sClientSecret, $sTokenSecret)
			|| !$this->_bCheckNonce())
		{
			header('HTTP/1.0 401 Unauthorized');
			exit;
		}
	}

	private function _aGetSecret($bTempToken)
	{
		$info = $this->aGetClientInfo($this->_aReq['oauth_consumer_key']);
		$sClientSecret = $info['secret'];
		$sTokenSecret = '';
		if (isset($this->_aReq['oauth_token']))
		{
			if ($bTempToken)
			{
				$temptokenDao = $this->_aConf['temptoken'].'Dao';
				$info = $this->$temptokenDao->aGet($this->_aReq['oauth_token']);
				if (!empty($info) && ($this->_iGetTempTokenTimeout() >= abs(time() - strtotime($info['ctime']))))
				{
					$sTokenSecret = $info['secret'];
				}
			}
			else
			{
				$tokenApi = $this->_aConf['tokenApi'];
				$info = $this->$tokenApi->aGet($this->_aReq['oauth_token']);
				assert(strval($info['cid']) === $this->_aReq['oauth_consumer_key']);
				$tt = $this->_iGetTokenTimeout();
				if (!empty($info) && (empty($tt) || $tt >= abs(time() - strtotime($info['ctime']))))
				{
					$sTokenSecret = $info['secret'];
				}
			}
		}
		return array($sClientSecret, $sTokenSecret);
	}

	private function _bCheckSignature($sClientSecret, $sTokenSecret)
	{
		return $this->_aReq['oauth_signature'] === Ko_Tool_OAuth::SGetSignature($this->_sGetReqMethod(), $this->_sGetBaseUri(), $this->_aReq, $sClientSecret, $sTokenSecret);
	}

	private function _bCheckNonce()
	{
		$ct = $this->_iGetClientTimeout();
		if ($ct && $ct < abs(time() - $this->_aReq['oauth_timestamp']))
		{
			return false;
		}
		if (isset($this->_aConf['nonce']))
		{
			$aData = array(
				'cid' => $this->_aReq['oauth_consumer_key'],
				'otime' => date('Y-m-d H:i:s', $this->_aReq['oauth_timestamp']),
				'nonce' => $this->_aReq['oauth_nonce'],
				'ctime' => date('Y-m-d H:i:s'),
				);
			$nonceDao = $this->_aConf['nonce'].'Dao';
			try
			{
				$this->$nonceDao->aInsert($aData);
			}
			catch(Exception $e)
			{
				return false;
			}
		}
		return true;
	}

	private function _vCheckVerifier()
	{
		$temptokenDao = $this->_aConf['temptoken'].'Dao';
		$info = $this->$temptokenDao->aGet($this->_aReq['oauth_token']);
		if (0 === strlen($info['verifier']) || $this->_aReq['oauth_verifier'] !== $info['verifier'])
		{
			return false;
		}
		return $this->$temptokenDao->iDelete($this->_aReq['oauth_token']) ? array($info['uid'], $info['scope']) : false;
	}

	private function _aGetTemporaryCredentials()
	{
		$sToken = Ko_Tool_OAuth::SGenKey();
		$sSecret = Ko_Tool_OAuth::SGenKey();
		$aData = array(
			'token' => $sToken,
			'secret' => $sSecret,
			'cid' => $this->_aReq['oauth_consumer_key'],
			'callback' => $this->_aReq['oauth_callback'],
			'ctime' => date('Y-m-d H:i:s'),
			);
		$temptokenDao = $this->_aConf['temptoken'].'Dao';
		$this->$temptokenDao->aInsert($aData);
		return array($sToken, $sSecret);
	}

	private function _sGetReqMethod()
	{
		return Ko_Web_Request::SRequestMethod();
	}

	private function _sGetBaseUri()
	{
		if (!empty($this->_sBaseuri))
		{
			return $this->_sBaseuri;
		}
		return $this->_aConf['baseuri'];
	}

	private function _iGetClientTimeout()
	{
		if (isset($this->_aConf['client_timeout']))
		{
			return $this->_aConf['client_timeout'];
		}
		return self::DEFAULT_CLIENT_TIMEOUT;
	}

	private function _iGetTempTokenTimeout()
	{
		if (isset($this->_aConf['temptoken_timeout']))
		{
			return $this->_aConf['temptoken_timeout'];
		}
		return self::DEFAULT_TEMPTOKEN_TIMEOUT;
	}

	private function _iGetTokenTimeout()
	{
		if (isset($this->_aConf['token_timeout']))
		{
			return $this->_aConf['token_timeout'];
		}
		return self::DEFAULT_TOKEN_TIMEOUT;
	}
	
	private static function _SGetTokenUri($sMethod, $sUri, $aReq, $sSecret, $sRequestSecret)
	{
		$uri = ('GET' === $sMethod) ? $sUri.'?' : '';
		foreach ($aReq as $k => $v)
		{
			$uri .= $k.'='.urlencode($v).'&';
		}
		$sSignature = Ko_Tool_OAuth::SGetSignature($sMethod, $sUri, $aReq, $sSecret, $sRequestSecret);
		return $uri.'oauth_signature='.urlencode($sSignature);
	}
}

?>