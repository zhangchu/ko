<?php
/**
 * Table
 *
 * @package ko\dao
 * @author zhangchu
 */

/**
 * 数据表操作类接口
 */
interface IKo_Dao_Table
{
	/**
	 * @return int
	 */
	public function iTableCount();
	/**
	 * @return Ko_Data_Mysql
	 */
	public function oConnectDB($no, $sTag = 'slave');
	/**
	 * @return string
	 */
	public function sGetRealTableName($no);
	public function vDoFetchSelect($sSql, $fnCallback, $sTag = 'slave');
}
