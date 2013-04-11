<?php
/**
 * LCAgent
 *
 * @package ko
 * @subpackage data
 * @author zhangchu
 */

/**
 * 封装创建 localcache 的接口
 */
interface IKo_Data_LCAgent
{
	public static function OInstance();
}

/**
 * 封装创建 localcache 的实现
 */
class Ko_Data_LCAgent implements IKo_Data_LCAgent
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