<?php
/**
 * MysqlAgent
 *
 * @package ko\dao
 * @author zhangchu
 */

/**
 * 创建对象的接口
 */
interface IKo_Dao_MysqlAgent
{
	public static function OInstance($sKind, $bSlave = false);
}

/**
 * 创建对象的实现
 */
class Ko_Dao_MysqlAgent implements IKo_Dao_MysqlAgent
{
	public static function OInstance($sKind, $bSlave = false)
	{
		switch (KO_DB_ENGINE)
		{
		case 'kproxy':
			return Ko_Dao_MysqlK::OInstance($sKind, $bSlave);
		case 'mysql':
			return Ko_Dao_Mysql::OInstance($sKind, $bSlave);
		default:
			assert(0);
		}
	}
}

?>