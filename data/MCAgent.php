<?php
/**
 * MCAgent
 *
 * @package ko\data
 * @author zhangchu
 */

/**
 * 封装创建 memcache 的实现
 */
class Ko_Data_MCAgent
{
	public static function OInstance($sName = '', $sExinfo = '')
	{
		switch (KO_MC_ENGINE)
		{
		case 'kproxy':
			return Ko_Data_MCache::OInstance($sName, $sExinfo);
		case 'memcache':
			return Ko_Data_MemCache::OInstance($sName, $sExinfo);
		case 'saemc':
			return Ko_Data_MCSae::OInstance($sName, $sExinfo);
		default:
			assert(0);
		}
	}
}

?>