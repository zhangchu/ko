<?php
/**
 * UobjectAgent
 *
 * @package ko\data
 * @author zhangchu
 */

/**
 * 封装创建 UObject 的实现
 */
class Ko_Data_UObjectAgent
{
	public static function OInstance($sKind, $sSplitField, $sKeyField, $sUoName='')
	{
		switch (KO_DB_ENGINE)
		{
		case 'kproxy':
			return Ko_Data_UObjectMan::OInstance($sKind, $sSplitField, $sKeyField, $sUoName);
		default:
			assert(0);
		}
	}
}
