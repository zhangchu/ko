<?php
/**
 * LCAgent
 *
 * @package ko\data
 * @author zhangchu
 */

/**
 * 封装创建 localcache 的
 */
class Ko_Data_LCAgent
{
	public static function OInstance()
	{
		switch (KO_LC_ENGINE)
		{
		case 'kproxy':
			return Ko_Data_LCache::OInstance();
		default:
			assert(0);
		}
	}
}

?>