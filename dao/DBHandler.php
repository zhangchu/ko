<?php
/**
 * DBHandler
 *
 * @package ko\dao
 * @author zhangchu
 */

/**
 * Ko_Dao_DB 适配器基类
 */
class Ko_Dao_DBHandler implements IKo_Dao_DBHelp, IKo_Dao_Table
{
	/**
	 * @var Ko_Dao_DB
	 */
	protected $_oDB;

	/**
	 * @return string
	 */
	public function sGetTableName()
	{
		return $this->_oDB->sGetTableName();
	}

	/**
	 * @return string
	 */
	public function sGetSplitField()
	{
		return $this->_oDB->sGetSplitField();
	}

	/**
	 * @return array
	 */
	public function aGetKeyField()
	{
		return $this->_oDB->aGetKeyField();
	}

	/**
	 * @return string
	 */
	public function sGetAutoIdField()
	{
		return $this->_oDB->sGetAutoIdField();
	}
	
	/**
	 * @return array
	 */
	public function aGetIndexField()
	{
		return $this->_oDB->aGetIndexField();
	}

	/**
	 * @return string
	 */
	public function sGetIdKey()
	{
		return $this->_oDB->sGetIdKey();
	}

	/**
	 * @return int
	 */
	public function iGetHintId($vHintId)
	{
		return $this->_oDB->iGetHintId($vHintId);
	}

	public function vGetAttribute($sName)
	{
		return $this->_oDB->vGetAttribute($sName);
	}
	
	public function vSetAttribute($sName, $vValue)
	{
		return $this->_oDB->vSetAttribute($sName, $vValue);
	}

	/**
	 * @return int
	 */
	public function iInsert($aData, $aUpdate = array(), $aChange = array())
	{
		return $this->_oDB->iInsert($aData, $aUpdate, $aChange);
	}

	/**
	 * @return array
	 */
	public function aInsert($aData, $aUpdate = array(), $aChange = array())
	{
		return $this->_oDB->aInsert($aData, $aUpdate, $aChange);
	}

	/**
	 * @param Ko_Tool_SQL|Ko_Tool_MONGO|array $oOption
	 * @return int
	 */
	public function iUpdate($vKey, $aUpdate, $aChange=array(), $oOption=null)
	{
		return $this->_oDB->iUpdate($this->_iGetHintId($vKey), $vKey, $aUpdate, $aChange, $oOption);
	}

	/**
	 * @param Ko_Tool_SQL|Ko_Tool_MONGO|array $oOption
	 * @return int
	 */
	public function iDelete($vKey, $oOption=null)
	{
		return $this->_oDB->iDelete($this->_iGetHintId($vKey), $vKey, $oOption);
	}

	/**
	 * @return array
	 */
	public function aGet($vKey)
	{
		return $this->_oDB->aGet($this->_iGetHintId($vKey), $vKey);
	}

	public function vDeleteCache($vKey)
	{
		return $this->_oDB->vDeleteCache($this->_iGetHintId($vKey), $vKey);
	}

	/**
	 * @return int
	 */
	public function iTableCount()
	{
		return $this->_oDB->iTableCount();
	}
	
	/**
	 * @return Ko_Data_Mysql
	 */
	public function oConnectDB($no)
	{
		return $this->_oDB->oConnectDB($no);
	}
	
	/**
	 * @return string
	 */
	public function sGetRealTableName($no)
	{
		return $this->_oDB->sGetRealTableName($no);
	}
	
	public function vDoFetchSelect($sSql, $fnCallback)
	{
		$this->_oDB->vDoFetchSelect($sSql, $fnCallback);
	}
	
	/**
	 * 把数据转换为数组，用字段名做 key
	 *
	 * @return array
	 */
	public function aKeyToArray($vKey)
	{
		if (is_array($vKey))
		{
			return $this->_aArrayToKey($vKey);
		}
		$splitField = $this->sGetSplitField();
		$keyField = $this->aGetKeyField();
		$keyCount = count($keyField);
		if (strlen($splitField))
		{
			assert(0 == $keyCount);
			return array($splitField => $vKey);
		}
		assert(1 == $keyCount);
		return array($keyField[0] => $vKey);
	}

	private function _aArrayToKey($aData)
	{
		$aRet = array();
		$splitField = $this->sGetSplitField();
		if (strlen($splitField))
		{
			assert(array_key_exists($splitField, $aData));
			$aRet[$splitField] = $aData[$splitField];
		}
		$keyField = $this->aGetKeyField();
		foreach ($keyField as $field)
		{
			assert(array_key_exists($field, $aData));
			$aRet[$field] = $aData[$field];
		}
		return $aRet;
	}

	private function _iGetHintId($vKey)
	{
		$splitField = $this->sGetSplitField();
		if (strlen($splitField))
		{
			if (is_array($vKey))
			{
				assert(array_key_exists($splitField, $vKey));
				return $vKey[$splitField];
			}
			return $vKey;
		}
		return 1;
	}
}

?>