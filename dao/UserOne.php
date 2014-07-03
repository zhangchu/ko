<?php
/**
 * UserOne
 *
 * @package ko\dao
 * @author zhangchu
 */

/**
 * 分表字段就是唯一字段类型表(db_one)的适配器接口
 */
interface IKo_Dao_UserOne
{
	/**
	 * useuo 为真 才可用
	 *
	 * @return array
	 */
	public function aGetListByKeys($aKey, $sKeyField = '');
	/**
	 * useuo 为真 才可用
	 *
	 * @return array
	 */
	public function aGetDetails($oObjs, $sSplitField = '', $bRetmap = true);
}

/**
 * 分表字段就是唯一字段类型表(db_one)的适配器实现
 */
class Ko_Dao_UserOne extends Ko_Dao_DBHandler implements IKo_Dao_UserOne
{
	public function __construct($sTable, $sSplitField, $sIdKey='', $sDBAgentName='', $sMCacheName='', $iMCacheTime=3600, $bUseUO = false, $aUoFields = array(), $sUoName = '')
	{
		$this->_oDB = new Ko_Dao_DB($sTable, $sSplitField, '', $sIdKey, $sDBAgentName, $sMCacheName, $iMCacheTime, $bUseUO, $aUoFields, $sUoName);
	}
	
	/**
	 * @return array
	 */
	public function aGetListByKeys($aKey, $sKeyField = '')
	{
		return $this->_oDB->aGetDetails($aKey, $sKeyField);
	}

	/**
	 * @return array
	 */
	public function aGetDetails($oObjs, $sSplitField = '', $bRetmap = true)
	{
		return $this->_oDB->aGetDetails($oObjs, $sSplitField, '', $bRetmap);
	}
}

?>