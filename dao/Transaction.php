<?php
/**
 * Transaction
 *
 * @package ko\dao
 * @author zhangchu
 */

/**
 * 数据表事务操作类接口
 */
interface IKo_Dao_Transaction
{
	public function bBeginTransaction($vHintId = 1);
	public function bCommit();
	public function bRollBack();
	public function vForcePDO($bEnable);
}
