<?php
/**
 * Mysql
 *
 * @package ko\data
 * @author zhangchu
 */

/**
 * Mysql操作类实现
 */
class Ko_Data_Mysql
{
	const MAX_RECONN = 3;
	
	private static $s_AInstance = array();

	private $_hLink;
	private $_hResult;
	
	private $_sHost;
	private $_sUser;
	private $_sPasswd;
	private $_sDbName;

	public function __construct($sHost, $sUser, $sPasswd, $sDbName)
	{
		$this->_sHost = $sHost;
		$this->_sUser = $sUser;
		$this->_sPasswd = $sPasswd;
		$this->_sDbName = $sDbName;
		$this->_vConnect();
	}

	public function __destruct()
	{
		$this->_bFreeResult();
		$this->_vClose();
	}

	/**
	 * @return Ko_Data_Mysql
	 */
	public static function OInstance($sHost, $sUser, $sPasswd, $sDbName)
	{
		if (empty(self::$s_AInstance[$sHost.':'.$sDbName]))
		{
			self::$s_AInstance[$sHost.':'.$sDbName] = array(
				'link' => new self($sHost, $sUser, $sPasswd, $sDbName),
				'user' => $sUser,
				'pass' => $sPasswd,
				);
		}
		assert($sUser == self::$s_AInstance[$sHost.':'.$sDbName]['user']);
		assert($sPasswd == self::$s_AInstance[$sHost.':'.$sDbName]['pass']);
		return self::$s_AInstance[$sHost.':'.$sDbName]['link'];
	}

	/**
	 * @return string
	 */
	public static function SEscape($sIn)
	{
		return @mysql_escape_string($sIn);
	}

	/**
	 * @return int
	 */
	public function iAffectedRows()
	{
		return mysql_affected_rows($this->_hLink);
	}

	/**
	 * @return int
	 */
	public function iErrno()
	{
		return mysql_errno($this->_hLink);
	}

	/**
	 * @return string
	 */
	public function sError()
	{
		return mysql_error($this->_hLink);
	}

	/**
	 * @return array
	 */
	public function aFetchArray($iType = MYSQL_BOTH)
	{
		if (is_null($this->_hResult) || is_bool($this->_hResult))
		{
			return false;
		}
		return mysql_fetch_array($this->_hResult, $iType);
	}

	/**
	 * @return array
	 */
	public function aFetchAssoc()
	{
		if (is_null($this->_hResult) || is_bool($this->_hResult))
		{
			return false;
		}
		return mysql_fetch_assoc($this->_hResult);
	}

	/**
	 * @return array
	 */
	public function aFetchRow()
	{
		if (is_null($this->_hResult) || is_bool($this->_hResult))
		{
			return false;
		}
		return mysql_fetch_row($this->_hResult);
	}

	/**
	 * @return int
	 */
	public function iInsertId()
	{
		return mysql_insert_id($this->_hLink);
	}

	/**
	 * @return int
	 */
	public function iNumRows()
	{
		if (is_null($this->_hResult) || is_bool($this->_hResult))
		{
			return 0;
		}
		return mysql_num_rows($this->_hResult);
	}

	/**
	 * @return bool
	 */
	public function bQuery($sSql)
	{
		return $this->_bQuery($sSql, 0);
	}

	/**
	 * @return bool
	 */
	public function bSelectDb($sDbName)
	{
		return mysql_select_db($sDbName, $this->_hLink);
	}
	
	private function _bQuery($sSql, $iReconnect)
	{
		$this->_bFreeResult();
		$this->_hResult = mysql_query($sSql, $this->_hLink);
		if (false === $this->_hResult && self::MAX_RECONN > $iReconnect)
		{
			$errno = $this->iErrno();
			if (2006 === $errno || false === $errno)
			{	//MySQL server has gone away
				$this->_vClose();
				$this->_vConnect();
				return $this->_bQuery($sSql, $iReconnect + 1);
			}
		}
		return $this->_hResult !== false;
	}

	private function _bFreeResult()
	{
		if (is_null($this->_hResult) || is_bool($this->_hResult))
		{
			return true;
		}
		return mysql_free_result($this->_hResult);
	}
	
	private function _vConnect()
	{
		$this->_hLink = mysql_connect($this->_sHost, $this->_sUser, $this->_sPasswd, true);
		assert($this->_hLink!==false);

		mysql_set_charset('binary', $this->_hLink);

		$bRet = $this->bSelectDb($this->_sDbName);
		assert($bRet);
	}
	
	private function _vClose()
	{
		mysql_close($this->_hLink);
	}
}
