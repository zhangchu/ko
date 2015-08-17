<?php
/**
 * DBSplit
 *
 * @package ko\dao
 * @author zhangchu
 */

/**
 * 分表类型(db_split)的适配器
 */
class Ko_Dao_DBSplit extends Ko_Dao_DBHandler
{
	public function __construct($sTable, $vKeyField, $sIdKey='', $sDBAgentName='', $sMCacheName = '', $iMCacheTime = 3600, $bUseUO = false, $aUoFields = array(), $sUoName = '')
	{
		$this->_oDB = new Ko_Dao_DB($sTable, $vKeyField, $sIdKey, $sDBAgentName, $sMCacheName, $iMCacheTime, $bUseUO, $aUoFields, $sUoName);
	}

	/**
	 * @param Ko_Tool_SQL|Ko_Tool_MONGO|array $oOption
	 * @return int 返回 affectedrows
	 */
	public function iUpdateByCond($vHintId, $oOption, $aUpdate, $aChange=array())
	{
		return $this->_oDB->iUpdateByCond($vHintId, $oOption, $aUpdate, $aChange);
	}

	/**
	 * @param Ko_Tool_SQL|Ko_Tool_MONGO|array $oOption
	 * @return int 返回 affectedrows
	 */
	public function iDeleteByCond($vHintId, $oOption)
	{
		return $this->_oDB->iDeleteByCond($vHintId, $oOption);
	}

	/**
	 * 配置 key 才可用
	 *
	 * @return array 根据 _aKeyField 查询多条数据
	 */
	public function aGetListByKeys($vHintId, $aKey, $sKeyField = '')
	{
		return $this->_oDB->aGetListByKeys($vHintId, $aKey, $sKeyField);
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
	 * @return array 根据 Option 查询
	 */
	public function aGetList($vHintId, $oOption, $iCacheTime=0)
	{
		return $this->_oDB->aGetList($vHintId, $oOption, $iCacheTime);
	}
}

?>