<?php
/**
 * Config
 *
 * @package ko\dao
 * @author zhangchu
 */

/**
 * 单表类型(db_single)的适配器
 */
class Ko_Dao_Config extends Ko_Dao_DBHandler
{
	public function __construct($sTable, $vKeyField='', $sIdKey='', $sDBAgentName='', $sMCacheName='', $iMCacheTime=3600)
	{
		$this->_oDB = new Ko_Dao_DB($sTable, '', $vKeyField, $sIdKey, $sDBAgentName, $sMCacheName, $iMCacheTime);
	}

	/**
	 * @param Ko_Tool_SQL|Ko_Tool_MONGO|array $oOption
	 * @return int
	 */
	public function iUpdateByCond($oOption, $aUpdate, $aChange=array())
	{
		return $this->_oDB->iUpdateByCond(1, $oOption, $aUpdate, $aChange);
	}

	/**
	 * @param Ko_Tool_SQL|Ko_Tool_MONGO|array $oOption
	 * @return int
	 */
	public function iDeleteByCond($oOption)
	{
		return $this->_oDB->iDeleteByCond(1, $oOption);
	}

	/**
	 * 配置 key 才可用
	 *
	 * @return array
	 */
	public function aGetListByKeys($aKey, $sKeyField = '')
	{
		return $this->_oDB->aGetListByKeys(1, $aKey, $sKeyField);
	}

	/**
	 * @param Ko_Tool_SQL|Ko_Tool_MONGO|array $oOption
	 * @return array
	 */
	public function aGetList($oOption, $iCacheTime=0)
	{
		return $this->_oDB->aGetList(1, $oOption, $iCacheTime);
	}
}

?>