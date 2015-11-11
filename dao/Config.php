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
		$this->_oDB = new Ko_Dao_DB($sTable, $vKeyField, $sIdKey, $sDBAgentName, $sMCacheName, $iMCacheTime);
	}

	/**
	 * @return array
	 */
	public function aInsertMulti($aData, $oOption = null)
	{
		return $this->_oDB->aInsertMulti($aData, $oOption);
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
	 * 根据数据库表唯一键（一个字段或两个字段）进行数据获取，对于分表，需要使用UO支持
	 *
	 * @return array 查询多条数据
	 */
	public function aGetDetails($oObjs, $sSplitField = '', $sKeyField = '', $bRetmap = true)
	{
		return $this->_oDB->aGetDetails($oObjs, $sSplitField, $sKeyField, $bRetmap);
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