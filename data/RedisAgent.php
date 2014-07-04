<?php
/**
 * RedisAgent
 *
 * @package ko\data
 * @author zhangchu
 */

/**
 * 封装 Redis 创建对象的实现
 */
class Ko_Data_RedisAgent
{
	public static function OInstance($sName = '', $sRedisHost = '')
	{
		if (strlen($sRedisHost) || 'redis' == KO_REDIS_ENGINE)
		{
			return Ko_Data_Redis::OInstance($sName, $sRedisHost);
		}
		return Ko_Data_RedisK::OInstance($sName, $sRedisHost);
	}
}

?>