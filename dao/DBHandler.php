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
	 * @return array
	 */
	public function aGetIndexField()
	{
		return $this->_oDB->aGetIndexField();
	}

	/**
	 * @return array
	 */
	public function aGetIndexValue($vIndex)
	{
		return $this->_oDB->aGetIndexValue($vIndex);
	}

	/**
	 * @return string
	 */
	public function sGetIdKey()
	{
		return $this->_oDB->sGetIdKey();
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
	public function iInsert($aData, $aUpdate = array(), $aChange = array(), $oOption = null)
	{
		return $this->_oDB->iInsert($aData, $aUpdate, $aChange, $oOption);
	}

	/**
	 * @return array
	 */
	public function aInsert($aData, $aUpdate = array(), $aChange = array(), $oOption = null)
	{
		return $this->_oDB->aInsert($aData, $aUpdate, $aChange, $oOption);
	}

	/**
	 * @param Ko_Tool_SQL|Ko_Tool_MONGO|array $oOption
	 * @return int
	 */
	public function iUpdate($vKey, $aUpdate, $aChange=array(), $oOption=null)
	{
		return $this->_oDB->iUpdate($vKey, $vKey, $aUpdate, $aChange, $oOption);
	}

	/**
	 * @param Ko_Tool_SQL|Ko_Tool_MONGO|array $oOption
	 * @return int
	 */
	public function iDelete($vKey, $oOption=null)
	{
		return $this->_oDB->iDelete($vKey, $vKey, $oOption);
	}

	/**
	 * @return array
	 */
	public function aGet($vKey)
	{
		return $this->_oDB->aGet($vKey, $vKey);
	}

	public function vDeleteCache($vKey)
	{
		return $this->_oDB->vDeleteCache($vKey, $vKey);
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
	public function oConnectDB($no, $sTag = 'slave')
	{
		return $this->_oDB->oConnectDB($no, $sTag);
	}
	
	/**
	 * @return string
	 */
	public function sGetRealTableName($no)
	{
		return $this->_oDB->sGetRealTableName($no);
	}
	
	public function vDoFetchSelect($sSql, $fnCallback, $sTag = 'slave')
	{
		$this->_oDB->vDoFetchSelect($sSql, $fnCallback, $sTag);
	}
}
