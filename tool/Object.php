<?php
/**
 * Object
 *
 * @package ko
 * @subpackage tool
 * @author zhangchu
 */

//include_once('../ko.class.php');

/**
 * 对象自动创建接口
 */
interface IKo_Tool_Object
{
	/**
	 * 创建对象，只能创建同模块下的
	 *
	 * @return object
	 */
	public static function OCreateInThisModule($vClass, $sName);
	/**
	 * 创建对象，可以创建同模块下的，也可以创建子模块下的
	 *
	 * @return object
	 */
	public static function OCreate($vClass, $sName);
	/**
	 * 创建对象，可以创建根模块下的任意对象
	 *
	 * @return object
	 */
	public static function OCreateFromRoot($vClass, $sName);
	/**
	 * 创建对象，可以创建 $sFromModule 下的任意对象
	 *
	 * @return object
	 */
	public static function OCreateFromModule($sFromModule, $sName);
}

/**
 * 对象自动创建实现
 */
class Ko_Tool_Object implements IKo_Tool_Object
{
	/**
	 * @return object
	 */
	public static function OCreateInThisModule($vClass, $sName)
	{
		// 获取模块名
		$sThisModule = Ko_Tool_Module::SGetObjectModuleName($vClass);

		// 禁止创建子模块的对象
		list($sSubModuleName, $sFile) = Ko_Tool_Module::AGetSubModule($sName);
		assert('' === $sSubModuleName);

		// 构造类名
		$sClassName = 'K'.$sThisModule.'_'.$sFile;

		// 创建对象
		return Ko_Tool_Singleton::OInstance($sClassName);
	}

	/**
	 * @return object
	 */
	public static function OCreate($vClass, $sName)
	{
		// 获取模块名
		$sThisModule = Ko_Tool_Module::SGetObjectModuleName($vClass);

		return self::OCreateFromModule($sThisModule, $sName);
	}

	/**
	 * @return object
	 */
	public static function OCreateFromRoot($vClass, $sName)
	{
		// 获取模块名
		$sThisModule = Ko_Tool_Module::SGetObjectModuleName($vClass);

		// 获取根模块名
		$sRootModule = Ko_Tool_Module::SGetRootModuleName($sThisModule);

		return self::OCreateFromModule($sRootModule, $sName);
	}

	/**
	 * @return object
	 */
	public static function OCreateFromModule($sFromModule, $sName)
	{
		// 分析子模块名和文件名
		list($sModule, $sFile) = Ko_Tool_Module::AGetSubModule($sName);

		// 构造类名
		if($sModule !== '')
		{
			$sModule = Ko_Tool_Module::SGetRegularModuleName($sModule);
			$sClassName = 'K'.$sFromModule.'_'.$sModule.'_'.$sFile;
		}
		else
		{
			$sClassName = 'K'.$sFromModule.'_'.$sFile;
		}

		// 创建对象
		return Ko_Tool_Singleton::OInstance($sClassName);
	}
}

/*

class KA_C1
{
}

class KA_C2
{
}

class KA_B_C1
{
}

class KA_B_C2
{
}

$a1 = new KA_C1;
$a2 = new KA_C2;
$b1 = new KA_B_C1;
$b2 = new KA_B_C2;

$o = Ko_Tool_Object::OCreate($a1, 'C2');
var_dump($o);
$o = Ko_Tool_Object::OCreate($a1, 'b_C1');
var_dump($o);
$o = Ko_Tool_Object::OCreateFromRoot($a1, 'b_C1');
var_dump($o);
$o = Ko_Tool_Object::OCreateFromRoot($b1, 'C1');
var_dump($o);
$o = Ko_Tool_Object::OCreateInThisModule($a1, 'C2');
var_dump($o);
$o = Ko_Tool_Object::OCreateInThisModule($b1, 'C2');
var_dump($o);
$o = Ko_Tool_Object::OCreateInThisModule($a1, 'b_C2');
var_dump($o);

*/
?>