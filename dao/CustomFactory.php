<?php
/**
 * CustomFactory
 *
 * @package ko\dao
 * @author zhangchu
 */

/**
 * 创建自定义类型的工厂类接口
 */
interface IKo_Dao_CustomFactory
{
	/**
	 * 创建自定义类型的 Dao 对象
	 *
	 * @param array $aConfig Dao.php 中的配置信息
	 * @param array $aParam 扩展信息，保留
	 * @return object
	 */
	public static function OCreateDao($aConfig, $aParam);
}

?>