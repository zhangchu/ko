<?php
/**
 * Lock
 *
 * @package ko\tool
 * @author zhangchu
 */

interface IKo_Tool_Lock
{
	/**
	 * 获取写锁，独占锁，不阻塞
	 *
	 * @return boolean
	 */
	public static function BGetExLock($sName);
}

class Ko_Tool_Lock implements IKo_Tool_Lock
{
	private static $s_aLockHandle = array();

	/**
	 * @return boolean
	 */
	public static function BGetExLock($sName)
	{
		$lockfp = fopen($sName.'.lock', 'w');
		if (!$lockfp)
		{
			return false;
		}
		if (!flock($lockfp, LOCK_EX | LOCK_NB))
		{
			return false;
		}
		self::$s_aLockHandle[$sName] = $lockfp;
		return true;
	}
}
