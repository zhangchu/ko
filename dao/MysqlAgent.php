<?php
/**
 * MysqlAgent
 *
 * @package ko\dao
 * @author zhangchu
 */

/**
 * 创建对象的实现
 */
class Ko_Dao_MysqlAgent
{
	public static function OInstance($sKind)
	{
		switch (KO_DB_ENGINE)
		{
		case 'kproxy':
			return Ko_Dao_MysqlK::OInstance($sKind);
		case 'mysql':
			return Ko_Dao_Mysql::OInstance($sKind);
		default:
			assert(0);
		}
	}
}

?>