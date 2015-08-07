<?php
/**
 * UserOne
 *
 * @package ko\dao
 * @author zhangchu
 */

/**
 * 分表字段就是唯一字段类型表(db_one)的适配器
 */
class Ko_Dao_UserOne extends Ko_Dao_DBHandler
{
	public function __construct($sTable, $sIdKey='', $sDBAgentName='', $sMCacheName='', $iMCacheTime=3600, $bUseUO = false, $aUoFields = array(), $sUoName = '')
	{
		$this->_oDB = new Ko_Dao_DB($sTable, '', $sIdKey, $sDBAgentName, $sMCacheName, $iMCacheTime, $bUseUO, $aUoFields, $sUoName);
	}
	
	/**
	 * useuo 为真 才可用
	 *
	 * @return array
	 */
	public function aGetListByKeys($aKey, $sKeyField = '')
	{
		return $this->_oDB->aGetDetails($aKey, $sKeyField);
	}

	/**
	 * useuo 为真 才可用
	 *
	 * @return array
	 */
	public function aGetDetails($oObjs, $sSplitField = '', $bRetmap = true)
	{
		return $this->_oDB->aGetDetails($oObjs, $sSplitField, '', $bRetmap);
	}
}

?>