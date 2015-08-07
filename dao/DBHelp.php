<?php
/**
 * DBHelp
 *
 * @package ko\dao
 * @author zhangchu
 */

/**
 * 数据库辅助接口
 */
interface IKo_Dao_DBHelp
{
	/**
	 * @return string
	 */
	public function sGetTableName();
	/**
	 * @return string
	 */
	public function sGetSplitField();
	/**
	 * @return array
	 */
	public function aGetKeyField();
	/**
	 * @return array
	 */
	public function aGetIndexField();
	/**
	 * @return array
	 */
	public function aGetIndexValue($vIndex);
	/**
	 * @return string
	 */
	public function sGetIdKey();
	/**
	 * 查询数据库某个属性
	 */
	public function vGetAttribute($sName);
	/**
	 * 设置数据库其他属性
	 */
	public function vSetAttribute($sName, $vValue);
}

?>