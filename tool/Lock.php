<?php
/**
 * Lock
 *
 * @package ko\tool
 * @author zhangchu
 */

class Ko_Tool_Lock
{
	private static $s_aLockHandle = array();

	/**
	 * 获取写锁，独占锁，不阻塞
	 *
	 * @return boolean
	 */
	public static function BGetExLock($sName)
	{
		return self::_BGetLock($sName, LOCK_EX | LOCK_NB);
	}

	/**
	 * 获取写锁，独占锁，阻塞
	 *
	 * @return boolean
	 */
	public static function BWaitExLock($sName)
	{
		return self::_BGetLock($sName, LOCK_EX);
	}
	
	/**
	 * 释放锁
	 */
	public static function VReleaseLock($sName)
	{
		if (isset(self::$s_aLockHandle[$sName]))
		{
			fclose(self::$s_aLockHandle[$sName]);
		}
	}
	
	private static function _BGetLock($sName, $iOperation)
	{
		$lockfp = fopen($sName.'.lock', 'w');
		if (!$lockfp)
		{
			return false;
		}
		if (!flock($lockfp, $iOperation))
		{
			return false;
		}
		self::$s_aLockHandle[$sName] = $lockfp;
		return true;
	}
}
