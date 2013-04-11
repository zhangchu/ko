<?php
/**
 * DBHelp
 *
 * @package ko
 * @subpackage dao
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
	 * @return string
	 */
	public function sGetAutoIdField();
	/**
	 * @return string
	 */
	public function sGetIdKey();
	/**
	 * @return int 返回用来计算分表的ID，在使用字符串字段分表时用来查询某个串对应的 ID
	 */
	public function iGetHintId($vHintId);
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