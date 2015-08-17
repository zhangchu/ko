<?php
/**
 * 使用说明
 *
 * <b>简介</b>
 * <pre>
 *   实现一个通用的用户身份验证系统
 * </pre>
 *
 * <b>数据库例表</b>
 * <pre>
 *   一个自增长值用来生成用户id
 *   insert into idgenerator (kind, last_id) values('komodeusertest', 1);
 * </pre>
 * <pre>
 *   CREATE TABLE s_zhangchu_username(
 *     username varchar(128) not null default '',
 *     src varchar(32) not null default '',
 *     uid bigint UNSIGNED not null default 0,
 *     unique (username, src)
 *   )ENGINE=InnoDB DEFAULT CHARSET=latin1;
 *   CREATE TABLE s_zhangchu_bindlog(
 *     uid bigint UNSIGNED not null default 0,
 *     username varchar(128) not null default '',
 *     src varchar(32) not null default '',
 *     bind tinyint UNSIGNED not null default 0,			-- 0/1 解除/绑定
 *     ctime timestamp NOT NULL default 0,
 *     index (uid)
 *   )ENGINE=InnoDB DEFAULT CHARSET=latin1;
 *   CREATE TABLE s_zhangchu_hashpass_0(
 *     uid bigint UNSIGNED not null default 0,
 *     salt varchar(64) not null default '',
 *     hash varchar(64) not null default '',
 *     unique (uid)
 *   )ENGINE=InnoDB DEFAULT CHARSET=latin1;
 *   CREATE TABLE s_zhangchu_password_0(
 *     uid bigint UNSIGNED not null default 0,
 *     salt varchar(64) not null default '',
 *     passwd varchar(128) not null default '',
 *     unique (uid)
 *   )ENGINE=InnoDB DEFAULT CHARSET=latin1;
 *   CREATE TABLE s_zhangchu_varsalt_0(
 *     uid bigint UNSIGNED not null default 0,
 *     salt varchar(64) not null default '',
 *     oldsalt varchar(64) not null default '',
 *     mtime timestamp NOT NULL default 0,
 *     unique (uid)
 *   )ENGINE=InnoDB DEFAULT CHARSET=latin1;
 *   CREATE TABLE s_zhangchu_tmpsalt_0(
 *     uid bigint UNSIGNED not null default 0,
 *     salt varchar(64) not null default '',
 *     ctime timestamp NOT NULL default 0,
 *     unique (uid, salt),
 *     key (ctime)
 *   )ENGINE=InnoDB DEFAULT CHARSET=latin1;
 *   CREATE TABLE s_zhangchu_cookie_0(
 *     uid bigint UNSIGNED not null default 0,
 *     series varchar(64) not null default '',
 *     token varchar(64) not null default '',
 *     mtime timestamp NOT NULL default 0,
 *     unique (uid, series),
 *     key (mtime)
 *   )ENGINE=InnoDB DEFAULT CHARSET=latin1;
 * </pre>
 *
 * <b>_aConf 配置</b>
 * @see Ko_Mode_User::$_aConf
 *
 * @package ko\mode
 * @author zhangchu
 */

/**
 * 通用的用户身份验证系统实现
 */
class Ko_Mode_User extends Ko_Busi_Api
{
	/**
	 * 配置数组
	 *
	 * <pre>
	 * array(
	 *   'idgen' => 生成 UID 的 Dao，可选，如果没有这个值，用户ID由应用生成或者使用 username 表的自增长字段
	 *   'username' => 用户名查找 UID 表
	 *   'bindlog' => 用户名和 UID 绑定关系的 log 表，可选
	 *   'hashpass' => 加密密码表，可选
	 *   'password' => 明文密码表，可选
	 *   'varsalt' => 用于生成 session_token 的 salt 表
	 *   'tmpsalt' => 使用 hash 密码登录使用的临时 salt 表，可选，对于过期的数据，应定期清理
	 *   'persistent' => 长期登陆的 token 表，要求db_split类型，可选，对于过期的数据，应定期清理
	 *   'session_timeout' => session_token 过期时间，可选
	 *   'persistent_timeout' => persistent_token 过期时间，可选
	 *   'persistent_strict' => 严格模式下，一个persistent_token只能验证一次，可选
	 * )
	 * </pre>
	 *
	 * @var array
	 */
	protected $_aConf = array();

	const TMPSALT_TIMEOUT				= 300;
	const DEFAULT_SESSION_TIMEOUT		= 28800;	// 8 x 3600
	const DEFAULT_PERSISTENT_TIMEOUT	= 2592000;	// 30 x 86400
	const DEFAULT_PERSISTENT_STRICT     = true;

	const E_REGISTER_ALREADY			= 1;		// 用户名已经注册
	const E_REGISTER_UNKNOWN			= 9;		// 不可控的异常情况才会报此错误
	const E_RECALL_USER					= 11;		// 用户名未注册
	const E_LOGIN_USER					= 21;		// 用户名未注册
	const E_LOGIN_PASS					= 22;		// 密码错误
	const E_LOGIN_SALT					= 23;		// 随机串不存在或过期，正常情况较少可能发生这种错误，重复提交数据请求可能导致这个错误
	const E_CHANGEPASS_OLDPASS			= 31;		// 修改密码时旧密码不正确
	const E_SESSION_ERROR				= 41;		// session_token 错误，可能是过期太久，或者修改了密码
	const E_SESSION_EXPIRE				= 42;		// session_token 过期
	const E_PERSISTENT_ERROR			= 51;		// persistent_token 错误，可能是修改了密码
	const E_PERSISTENT_SERIES			= 52;		// persistent_token 在服务器不存在，可能是过期太久
	const E_PERSISTENT_EXPIRE			= 53;		// persistent_token 过期
	const E_PERSISTENT_TOKEN			= 54;		// persistent_token 已经被验证过了，可能是 cookie 失窃，或者重复提交了数据请求，这个时候会清除用户所有的 persistent_token

	/**
	 * 获取一个新的用户ID，必须配置了 idgen 属性，或者用户ID使用 username 表的自增长字段
	 *
	 * @return int
	 */
	public function iGetNewUserId()
	{
		if (isset($this->_aConf['idgen']))
		{
			$idgenDao = $this->_aConf['idgen'].'Dao';
			return $this->$idgenDao->sNewId();
		}
		return 0;
	}

	/**
	 * 判断用户名是否注册
	 *
	 * @return int 未注册返回0，否则返回 uid
	 */
	public function iIsRegister($sUsername, $sSrc = '')
	{
		$usernameDao = $this->_aConf['username'].'Dao';
		$info = $this->$usernameDao->aGet(array('username' => $sUsername, 'src' => $sSrc));
		return empty($info) ? 0 : $info['uid'];
	}

	/**
	 * 注册用户，必须配置了 idgen 属性，或者用户ID使用 username 表的自增长字段
	 *
	 * @return int 注册失败返回0，否则返回 uid
	 */
	public function iRegister($sUsername, $sPassword, &$iErrno)
	{
		assert(strlen($sPassword));
		return $this->_iRegister($sUsername, '', $sPassword, $iErrno);
	}
	
	/**
	 * 注册外站用户，必须配置了 idgen 属性，或者用户ID使用 username 表的自增长字段
	 *
	 * @return int 注册失败返回0，否则返回 uid
	 */
	public function iRegisterExternal($sUsername, $sSrc, &$iErrno)
	{
		assert(strlen($sSrc));
		return $this->_iRegister($sUsername, $sSrc, $this->_sGetNewSalt(), $iErrno);
	}

	/**
	 * 注册用户，用户ID由应用生成
	 *
	 * @return boolean 返回注册是否成功
	 */
	public function bRegisterUid($iUid, $sUsername, $sPassword, &$iErrno)
	{
		assert(strlen($sPassword));
		return $this->_bRegisterUid($iUid, $sUsername, '', $sPassword, $iErrno);
	}

	/**
	 * 注册外站用户，用户ID由应用生成
	 *
	 * @return boolean 返回注册是否成功
	 */
	public function bRegisterUidExternal($iUid, $sUsername, $sSrc, &$iErrno)
	{
		assert(strlen($sSrc));
		return $this->_bRegisterUid($iUid, $sUsername, $sSrc, $this->_sGetNewSalt(), $iErrno);
	}

	/**
	 * 将用户名和用户ID绑定，这样允许一个用户使用多个账号（一个密码）登录
	 *
	 * @return boolean 返回绑定是否成功
	 */
	public function bBindUsername($iUid, $sUsername, $sSrc = '')
	{
		assert(0 != $iUid);
		return $this->_bInsertUsername($sUsername, $sSrc, $iUid);
	}
	
	/**
	 * 解除用户名和用户ID的绑定
	 *
	 * @return boolean 返回解除绑定是否成功
	 */
	public function bUnbindUsername($iUid, $sUsername, $sSrc = '')
	{
		assert(0 != $iUid);
		$usernameDao = $this->_aConf['username'].'Dao';
		$oOption = new Ko_Tool_SQL;
		$oOption->oWhere('uid = ?', $iUid);
		$ret = $this->$usernameDao->iDelete(array('username' => $sUsername, 'src' => $sSrc), $oOption);
		if ($ret)
		{
			if (isset($this->_aConf['bindlog']))
			{
				$bindlogDao = $this->_aConf['bindlog'].'Dao';
				$aData = array(
					'uid' => $iUid,
					'username' => $sUsername,
					'src' => $sSrc,
					'bind' => 0,
					'ctime' => date('Y-m-d H:i:s'),
				);
				$this->$bindlogDao->aInsert($aData);
			}
			return true;
		}
		return false;
	}
	
	/**
	 * 找回密码
	 *
	 * @param string sPassword 返回密码
	 * @return int 未注册返回0，否则返回 uid
	 */
	public function iRecallPassword($sUsername, &$sPassword, &$iErrno)
	{
		assert(isset($this->_aConf['password']));

		$iUid = $this->iIsRegister($sUsername);
		if (!$iUid)
		{
			$iErrno = self::E_RECALL_USER;
			return 0;
		}
		$passwordDao = $this->_aConf['password'].'Dao';
		$info = $this->$passwordDao->aGet($iUid);
		$sPassword = $info['passwd'];
		return $iUid;
	}

	/**
	 * 使用明文密码进行登录验证
	 *
	 * @return int 登录失败返回0，否则返回 uid
	 */
	public function iLogin($sUsername, $sPassword, &$iErrno)
	{
		$iUid = $this->iIsRegister($sUsername);
		if (!$iUid)
		{
			$iErrno = self::E_LOGIN_USER;
			return 0;
		}
		if (!$this->bCheckPassword($iUid, $sPassword))
		{
			$iErrno = self::E_LOGIN_PASS;
			return 0;
		}
		return $iUid;
	}

	/**
	 * 使用hash密码进行登录验证的第一步，获取临时随机串
	 *
	 * @return string 返回一个临时随机串，空串表示用户不存在
	 */
	public function sLogin_GetTmpSalt($sUsername)
	{
		$iUid = $this->iIsRegister($sUsername);
		if (!$iUid)
		{
			return '';
		}
		return $this->sCheckPassword_GetTmpSalt($iUid);
	}

	/**
	 * 使用hash密码进行登录验证的第二步，校验hash密码
	 *
	 * @return int 登录失败返回0，否则返回 uid
	 */
	public function iLogin_CheckHashPass($sUsername, $sTmpSalt, $sHashpass, &$iErrno)
	{
		$iUid = $this->iIsRegister($sUsername);
		if (!$iUid)
		{
			$iErrno = self::E_LOGIN_USER;
			return 0;
		}
		if (!$this->bCheckPassword_CheckHashPass($iUid, $sTmpSalt, $sHashpass, $iErrno))
		{
			return 0;
		}
		return $iUid;
	}

	/**
	 * 使用明文密码进行验证
	 *
	 * @return boolean 返回密码是否正确
	 */
	public function bCheckPassword($iUid, $sPassword)
	{
		if (isset($this->_aConf['hashpass']))
		{
			$hashpassDao = $this->_aConf['hashpass'].'Dao';
			$info = $this->$hashpassDao->aGet($iUid);
			return $info['hash'] == $this->_sGetHashpass($info['salt'], $sPassword);
		}
		if (isset($this->_aConf['password']))
		{
			$passwordDao = $this->_aConf['password'].'Dao';
			$info = $this->$passwordDao->aGet($iUid);
			return $info['passwd'] == $sPassword;
		}
		assert(0);
	}

	/**
	 * 使用hash密码进行验证的第一步，获取临时随机串
	 *
	 * @return string 返回一个临时随机串，空串表示用户不存在
	 */
	public function sCheckPassword_GetTmpSalt($iUid)
	{
		assert(isset($this->_aConf['password']) && isset($this->_aConf['tmpsalt']));

		$sSalt = $this->_sGetNewSalt();
		$this->_vInsertTmpSalt($iUid, $sSalt);
		return $sSalt;
	}

	/**
	 * 使用hash密码进行验证的第二步，校验hash密码
	 *
	 * @return boolean 登录失败返回0，否则返回 uid
	 */
	public function bCheckPassword_CheckHashPass($iUid, $sTmpSalt, $sHashpass, &$iErrno)
	{
		assert(isset($this->_aConf['password']) && isset($this->_aConf['tmpsalt']));

		if (!$this->_bCheckTmpSalt($iUid, $sTmpSalt))
		{
			$iErrno = self::E_LOGIN_SALT;
			return false;
		}
		$passwordDao = $this->_aConf['password'].'Dao';
		$info = $this->$passwordDao->aGet($iUid);
		if ($sHashpass != $this->_sGetHashpass($sTmpSalt, $info['passwd']))
		{
			$iErrno = self::E_LOGIN_PASS;
			return false;
		}
		return true;
	}

	/**
	 * 修改密码，需要验证旧密码
	 *
	 * @return boolean 返回是否成功修改密码
	 */
	public function bChangePassword($iUid, $sOldPass, $sNewPass, &$iErrno)
	{
		if (!$this->bCheckPassword($iUid, $sOldPass))
		{
			$iErrno = self::E_CHANGEPASS_OLDPASS;
			return false;
		}
		$this->vResetPassword($iUid, $sNewPass);
		return true;
	}

	/**
	 * 重置密码
	 */
	public function vResetPassword($iUid, $sNewPass)
	{
		assert(strlen($sNewPass));
		if (isset($this->_aConf['hashpass']))
		{
			$this->_vUpdateHashpass($iUid, $sNewPass);
		}
		if (isset($this->_aConf['password']))
		{
			$this->_vUpdatePassword($iUid, $sNewPass);
		}
		if (isset($this->_aConf['varsalt']))
		{
			// 修改密码后应该重置 varsalt，这会导致当前的 session_token 全部失效
			$sSalt = $this->_sGetNewSalt();
			$this->_vUpdateVarSalt($iUid, $sSalt, $sSalt);
		}
	}

	/**
	 * 获取 session_token
	 *
	 * @return string 返回 session_token
	 */
	public function sGetSessionToken($iUid, $sExinfo)
	{
		return $this->_sGenSessionToken($iUid, time(), $sExinfo);
	}

	/**
	 * 验证 session_token
	 *
	 * @param string sExinfo 返回扩展信息
	 * @return int 失败返回 0，成功返回 uid
	 */
	public function iCheckSessionToken($sSessionToken, &$sExinfo, &$iErrno)
	{
		list($hash, $uid, $sessiontime, $sExinfo) = explode('_', $sSessionToken, 4);
		if (!$this->_bCheckSessionHash($hash, $uid, $sessiontime, $sExinfo))
		{
			$iErrno = self::E_SESSION_ERROR;
			return 0;
		}
		if ($this->_iGetSessionTimeout() < abs(time() - $sessiontime))
		{
			$iErrno = self::E_SESSION_EXPIRE;
			return 0;
		}
		return $uid;
	}

	/**
	 * 获取 persistent_token
	 *
	 * @return string 返回 persistent_token
	 */
	public function sGetPersistentToken($iUid)
	{
		assert(isset($this->_aConf['persistent']));

		$sSeries = $this->_sGetNewSalt();
		$sToken = $this->_sGetNewSalt();
		$this->_vInsertPersistentToken($iUid, $sSeries, $sToken);
		return $this->_sGenPersistentToken($iUid, $sSeries, $sToken);
	}

	/**
	 * 验证 persistent_token，生成新的 persistent_token
	 *
	 * @param string sNewPersistentToken 返回新的 persistent_token
	 * @return int 失败返回 0，成功返回 uid
	 */
	public function iCheckPersistentToken($sPersistentToken, &$sNewPersistentToken, &$iErrno)
	{
		assert(isset($this->_aConf['persistent']));

		list($hash, $uid, $series, $token) = explode('_', $sPersistentToken, 4);
		if (!$this->_bCheckPersistentHash($hash, $uid, $series, $token))
		{
			$iErrno = self::E_PERSISTENT_ERROR;
			return 0;
		}
		$persistentDao = $this->_aConf['persistent'].'Dao';
		$key = array('uid' => $uid, 'series' => $series);
		$info = $this->$persistentDao->aGet($key);
		if (empty($info))
		{
			$iErrno = self::E_PERSISTENT_SERIES;
			return 0;
		}
		if ($this->_iGetPersistentTimeout() < abs(time() - strtotime($info['mtime'])))
		{
			$iErrno = self::E_PERSISTENT_EXPIRE;
			return 0;
		}
		if (!$this->_bUpdatePersistentToken($uid, $series, $token, $sNewToken))
		{
			$oOption = new Ko_Tool_SQL;
			$splitField = $this->$persistentDao->sGetSplitField();
			if (strlen($splitField))
			{
				$this->$persistentDao->iDeleteByCond($uid, $oOption->oWhere('1'));
			}
			else
			{
				$this->$persistentDao->iDeleteByCond($oOption->oWhere('uid = ?', $uid));
			}
			$iErrno = self::E_PERSISTENT_TOKEN;
			return 0;
		}
		$sNewPersistentToken = $this->_sGenPersistentToken($uid, $series, $sNewToken);
		return $uid;
	}

	/**
	 * 清理过期的 tmpsalt 数据
	 */
	public function vClearTmpSalt()
	{
		$tmpsaltDao = $this->_aConf['tmpsalt'].'Dao';
		$sql = 'select * from '.$this->$tmpsaltDao->sGetTableName().' where ctime < DATE_SUB(NOW(), INTERVAL '.(self::TMPSALT_TIMEOUT).' SECOND)';
		$this->$tmpsaltDao->vDoFetchSelect($sql, array($this, 'vClearTmpSalt_Callback'));
	}

	public function vClearTmpSalt_Callback($aInfo, $iNo)
	{
		$tmpsaltDao = $this->_aConf['tmpsalt'].'Dao';
		$key = array('uid' => $aInfo['uid'], 'salt' => $aInfo['salt']);
		$this->$tmpsaltDao->iDelete($key);
	}

	/**
	 * 清理过期的 persistent_token 数据
	 */
	public function vClearPersistentToken()
	{
		$persistentDao = $this->_aConf['persistent'].'Dao';
		$sql = 'select * from '.$this->$persistentDao->sGetTableName().' where mtime < DATE_SUB(NOW(), INTERVAL '.($this->_iGetPersistentTimeout()).' SECOND)';
		$this->$persistentDao->vDoFetchSelect($sql, array($this, 'vClearPersistentToken_Callback'));
	}

	public function vClearPersistentToken_Callback($aInfo, $iNo)
	{
		$persistentDao = $this->_aConf['persistent'].'Dao';
		$key = array('uid' => $aInfo['uid'], 'series' => $aInfo['series']);
		$this->$persistentDao->iDelete($key);
	}

	private function _iRegister($sUsername, $sSrc, $sPassword, &$iErrno)
	{
		if ($this->iIsRegister($sUsername, $sSrc))
		{
			$iErrno = self::E_REGISTER_ALREADY;
			return 0;
		}
		$iUid = $this->iGetNewUserId();
		if (!$this->_bInsertUsername($sUsername, $sSrc, $iUid))
		{
			$iErrno = self::E_REGISTER_UNKNOWN;
			return 0;
		}
		$this->vResetPassword($iUid, $sPassword);
		return $iUid;
	}

	private function _bRegisterUid($iUid, $sUsername, $sSrc, $sPassword, &$iErrno)
	{
		assert(0 != $iUid);
		if ($this->iIsRegister($sUsername, $sSrc))
		{
			$iErrno = self::E_REGISTER_ALREADY;
			return false;
		}
		if (!$this->_bInsertUsername($sUsername, $sSrc, $iUid))
		{
			$iErrno = self::E_REGISTER_UNKNOWN;
			return false;
		}
		$this->vResetPassword($iUid, $sPassword);
		return true;
	}
	
	private function _bInsertUsername($sUsername, $sSrc, &$iUid)
	{
		$aData = array(
			'username' => $sUsername,
			'src' => $sSrc,
			);
		if (0 != $iUid)
		{
			$aData['uid'] = $iUid;
		}
		$usernameDao = $this->_aConf['username'].'Dao';
		try
		{
			$ret = $this->$usernameDao->iInsert($aData);
			if (0 == $iUid)
			{	// 如果 iUid 为 0 表示这个值应该从自增长字段中获取
				$iUid = $ret;
			}
			if (isset($this->_aConf['bindlog']))
			{
				$bindlogDao = $this->_aConf['bindlog'].'Dao';
				$aData = array(
					'uid' => $iUid,
					'username' => $sUsername,
					'src' => $sSrc,
					'bind' => 1,
					'ctime' => date('Y-m-d H:i:s'),
				);
				$this->$bindlogDao->aInsert($aData);
			}
		}
		catch(Exception $e)
		{
			return false;
		}
		return true;
	}

	private function _vUpdateHashpass($iUid, $sPassword)
	{
		$sSalt = $this->_sGetNewSalt();
		$sHash = $this->_sGetHashpass($sSalt, $sPassword);
		$aData = array(
			'uid' => $iUid,
			'salt' => $sSalt,
			'hash' => $sHash,
			);
		$aUpdate = array(
			'salt' => $sSalt,
			'hash' => $sHash,
			);
		$hashpassDao = $this->_aConf['hashpass'].'Dao';
		$this->$hashpassDao->aInsert($aData, $aUpdate);
	}

	private function _vUpdatePassword($iUid, $sPassword)
	{
		$sSalt = $this->_sGetNewSalt();
		$aData = array(
			'uid' => $iUid,
			'salt' => $sSalt,
			'passwd' => $sPassword,
			);
		$aUpdate = array(
			'salt' => $sSalt,
			'passwd' => $sPassword,
			);
		$passwordDao = $this->_aConf['password'].'Dao';
		$this->$passwordDao->aInsert($aData, $aUpdate);
	}

	private function _sGetUserSalt($iUid)
	{
		if (isset($this->_aConf['hashpass']))
		{
			$passDao = $this->_aConf['hashpass'].'Dao';
		}
		else if (isset($this->_aConf['password']))
		{
			$passDao = $this->_aConf['password'].'Dao';
		}
		$info = $this->$passDao->aGet($iUid);
		return $info['salt'];
	}

	private function _vInsertVarSalt($iUid, $sSalt)
	{
		$sMtime = date('Y-m-d H:i:s');
		$aData = array(
			'uid' => $iUid,
			'salt' => $sSalt,
			'oldsalt' => $sSalt,
			'mtime' => $sMtime,
			);
		$aUpdate = array(
			'salt' => $sSalt,
			'oldsalt' => $sSalt,
			'mtime' => $sMtime,
			);
		$varsaltDao = $this->_aConf['varsalt'].'Dao';
		$this->$varsaltDao->aInsert($aData, $aUpdate);
	}

	private function _vUpdateVarSalt($iUid, $sSalt, $sOldSalt)
	{
		$sMtime = date('Y-m-d H:i:s');
		$aUpdate = array(
			'salt' => $sSalt,
			'oldsalt' => $sOldSalt,
			'mtime' => $sMtime,
			);
		$varsaltDao = $this->_aConf['varsalt'].'Dao';
		$this->$varsaltDao->iUpdate($iUid, $aUpdate);
	}

	private function _vInsertTmpSalt($iUid, $sSalt)
	{
		$sCtime = date('Y-m-d H:i:s');
		$aData = array(
			'uid' => $iUid,
			'salt' => $sSalt,
			'ctime' => $sCtime,
			);
		$aUpdate = array(
			'ctime' => $sCtime,
			);
		$tmpsaltDao = $this->_aConf['tmpsalt'].'Dao';
		$this->$tmpsaltDao->aInsert($aData, $aUpdate);
	}

	private function _bCheckTmpSalt($iUid, $sSalt)
	{
		$tmpsaltDao = $this->_aConf['tmpsalt'].'Dao';
		$key = array('uid' => $iUid, 'salt' => $sSalt);
		$tmpsaltInfo = $this->$tmpsaltDao->aGet($key);
		if (empty($tmpsaltInfo) || (self::TMPSALT_TIMEOUT < abs(time() - strtotime($tmpsaltInfo['ctime']))))
		{
			return false;
		}
		return $this->$tmpsaltDao->iDelete($key) ? true : false;
	}

	private function _vInsertPersistentToken($iUid, $sSeries, $sToken)
	{
		$sMtime = date('Y-m-d H:i:s');
		$aData = array(
			'uid' => $iUid,
			'series' => $sSeries,
			'token' => $sToken,
			'mtime' => $sMtime,
			);
		$aUpdate = array(
			'token' => $sToken,
			'mtime' => $sMtime,
			);
		$persistentDao = $this->_aConf['persistent'].'Dao';
		$this->$persistentDao->aInsert($aData, $aUpdate);
	}

	private function _bUpdatePersistentToken($iUid, $sSeries, $sToken, &$sNewToken)
	{
		if ($this->_iGetPersistentStrict())
		{	// 每个 persistent_token 只能验证一次，如果重复验证可能是因为数据被重复提交，或者失窃
			$sNewToken = $this->_sGetNewSalt();
			$sMtime = date('Y-m-d H:i:s');
			$aUpdate = array(
				'token' => $sNewToken,
				'mtime' => $sMtime,
				);
			$persistentDao = $this->_aConf['persistent'].'Dao';
			$key = array('uid' => $iUid, 'series' => $sSeries);
			$oOption = new Ko_Tool_SQL;
			$oOption->oWhere('token = ?', $sToken);
			return $this->$persistentDao->iUpdate($key, $aUpdate, array(), $oOption) ? true : false;
		}
		else
		{	// 每次不更新 token，只做延期
			$sNewToken = $sToken;
			$sMtime = date('Y-m-d H:i:s');
			$aUpdate = array(
				'mtime' => $sMtime,
				);
			$persistentDao = $this->_aConf['persistent'].'Dao';
			$key = array('uid' => $iUid, 'series' => $sSeries);
			$oOption = new Ko_Tool_SQL;
			$oOption->oWhere('token = ?', $sToken);
			$this->$persistentDao->iUpdate($key, $aUpdate, array(), $oOption);
			return true;
		}
	}

	private function _sGetNewSalt()
	{
		return uniqid('', true);
	}

	private function _sGetHashpass($sSalt, $sPassword)
	{
		return md5($sSalt.'_'.$sPassword);
	}

	private function _sGenSessionHash($iUid, $iNow, $sExinfo, $sSalt)
	{
		return md5($sSalt.'_'.$iUid.'_'.$iNow.'_'.$sExinfo);
	}

	private function _sGenSessionToken($iUid, $iNow, $sExinfo)
	{
		$varsaltDao = $this->_aConf['varsalt'].'Dao';
		$info = $this->$varsaltDao->aGet($iUid);
		if (empty($info))
		{	// 第一次访问，生成 varsalt 数据
			$sSalt = $this->_sGetNewSalt();
			$this->_vInsertVarSalt($iUid, $sSalt);
		}
		else if (($this->_iGetSessionTimeout() * 2) < abs(time() - strtotime($info['mtime'])))
		{	// 过期很久，新旧 salt 都需要重置
			$sSalt = $this->_sGetNewSalt();
			$this->_vUpdateVarSalt($iUid, $sSalt, $sSalt);
		}
		else if ($this->_iGetSessionTimeout() < abs(time() - strtotime($info['mtime'])))
		{	// 过期一段时间，将旧的 salt 设置为当前 salt，并生成新的当前 salt
			$sSalt = $this->_sGetNewSalt();
			$this->_vUpdateVarSalt($iUid, $sSalt, $info['salt']);
		}
		else
		{	// 未过期
			$sSalt = $info['salt'];
		}
		return $this->_sGenSessionHash($iUid, $iNow, $sExinfo, $sSalt).'_'.$iUid.'_'.$iNow.'_'.$sExinfo;
	}

	private function _bCheckSessionHash($sHash, $iUid, $iNow, $sExinfo)
	{
		$varsaltDao = $this->_aConf['varsalt'].'Dao';
		$info = $this->$varsaltDao->aGet($iUid);
		// 如果 salt 已经过期很久，直接认为失败，不进行校验，防止 salt 失窃
		return (($this->_iGetSessionTimeout() * 2) >= abs(time() - strtotime($info['mtime'])))
			&& ($sHash == $this->_sGenSessionHash($iUid, $iNow, $sExinfo, $info['salt'])
				|| $sHash == $this->_sGenSessionHash($iUid, $iNow, $sExinfo, $info['oldsalt']));
	}

	private function _sGenPersistentHash($iUid, $sSeries, $sToken, $sSalt)
	{
		return md5($sSalt.'_'.$iUid.'_'.$sSeries.'_'.$sToken);
	}

	private function _sGenPersistentToken($iUid, $sSeries, $sToken)
	{
		$salt = $this->_sGetUserSalt($iUid);
		return $this->_sGenPersistentHash($iUid, $sSeries, $sToken, $salt).'_'.$iUid.'_'.$sSeries.'_'.$sToken;
	}

	private function _bCheckPersistentHash($sHash, $iUid, $sSeries, $sToken)
	{
		$salt = $this->_sGetUserSalt($iUid);
		return $sHash == $this->_sGenPersistentHash($iUid, $sSeries, $sToken, $salt);
	}

	private function _iGetSessionTimeout()
	{
		if (isset($this->_aConf['session_timeout']))
		{
			return $this->_aConf['session_timeout'];
		}
		return self::DEFAULT_SESSION_TIMEOUT;
	}

	private function _iGetPersistentTimeout()
	{
		if (isset($this->_aConf['persistent_timeout']))
		{
			return $this->_aConf['persistent_timeout'];
		}
		return self::DEFAULT_PERSISTENT_TIMEOUT;
	}

	private function _iGetPersistentStrict()
	{
		if (isset($this->_aConf['persistent_strict']))
		{
			return $this->_aConf['persistent_strict'];
		}
		return self::DEFAULT_PERSISTENT_STRICT;
	}
}

?>