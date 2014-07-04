<?php
/**
 * Module
 *
 * @package ko\tool
 * @author zhangchu
 */

//include_once('../ko.class.php');

/**
 * 命名规则函数实现
 */
class Ko_Tool_Module
{
	/**
	 * 获取脚本程序名称全路经
	 *
	 * @return string
	 */
	public static function SGetScriptFullName()
	{
		$sScript = $_SERVER['SCRIPT_FILENAME'];
		if ('/' != $sScript[0] && ':/' != substr($sScript, 1, 2))
		{
			$sScript = $_SERVER['PWD'].'/'.$_SERVER['SCRIPT_FILENAME'];
		}
		return $sScript;
	}

	/**
	 * 获取对象或者类的模块名
	 *
	 * <pre>
	 * eg1:
	 *     $classname = 'KO2o_User_AddCreditApi',  return 'O2o_User'
	 * eg2:
	 *     $classname = 'Ko_Dao_File',  return 'Ko_Dao'
	 * </pre>
	 *
	 * @return string
	 */
	public static function SGetObjectModuleName($vClass)
	{
		$sClassName = is_string($vClass) ? $vClass : get_class($vClass);
		while (1)
		{
			$sModuleName = self::_SGetClassModuleName($sClassName);
			if ('' !== $sModuleName)
			{
				break;
			}
			$sClassName = get_parent_class($sClassName);
			if (false === $sClassName)
			{
				break;
			}
		}
		return $sModuleName;
	}

	/**
	 * 将模块名规范化，每部分的首字母大写
	 *
	 * <pre>
	 * eg1:
	 *     $sModuleName = 'o2o_user',  return 'O2o_User'
	 * eg1:
	 *     $sModuleName = 'O2O_UsEr',  return 'O2o_User'
	 * </pre>
	 *
	 * @return string
	 */
	public static function SGetRegularModuleName($sModuleName)
	{
		$sModuleName = strtolower($sModuleName);
		$aList = explode('_', $sModuleName);
		foreach ($aList as $k => $v)
		{
			$aList[$k] = ucfirst($v);
		}
		return implode('_', $aList);
	}

	/**
	 * 获取模块的根模块名
	 *
	 * <pre>
	 * eg1:
	 *     $sModuleName = 'O2o_User_AddCreditApi',  return 'O2o'
	 * </pre>
	 *
	 * @return string
	 */
	public static function SGetRootModuleName($sModuleName)
	{
		$aList = explode('_', $sModuleName);
		return $aList[0];
	}

	/**
	 * 获取变量名中的子模块名和文件名
	 *
	 * <pre>
	 * eg1:
	 *     $sVarName = 'xxx_yyy_zZz',  return array('xxx_yyy', 'zZz');
	 * eg2:
	 *     $sVarName = 'zZz',  return array('', 'zZz');
	 * </pre>
	 *
	 * @return array
	 */
	public static function AGetSubModule($sVarName)
	{
		$aList = explode('_', $sVarName);
		$sName = array_pop($aList);
		return array(implode('_', $aList), $sName);
	}

	private static function _SGetClassModuleName($sClassName)
	{
		if (substr($sClassName, 0, 1) !== 'K')
		{
			return '';
		}
		$pos = strrpos($sClassName, '_');
		if (false === $pos)
		{
			return '';
		}
		if (substr($sClassName, 0, 3) === 'Ko_')
		{
			return substr($sClassName, 0, $pos);
		}
		return substr($sClassName, 1, $pos - 1);
	}
}

/*

class Ko_A_B
{
}
class KA_B_C
{
}

class A extends KA_B_C
{
}
class KB extends A
{
}

$obj = new Ko_A_B;
echo Ko_Tool_Module::SGetObjectModuleName($obj);
echo "\n";

$obj = new KA_B_C;
echo Ko_Tool_Module::SGetObjectModuleName($obj);
echo "\n";

$obj = new KB;
echo Ko_Tool_Module::SGetObjectModuleName($obj);
echo "\n";

echo Ko_Tool_Module::SGetRegularModuleName('o2o_user');
echo "\n";
echo Ko_Tool_Module::SGetRegularModuleName('o2o_UsEr');
echo "\n";

echo Ko_Tool_Module::SGetRootModuleName('O2o_User');
echo "\n";

echo Ko_Tool_Module::SGetRootModuleName('O2o');
echo "\n";

var_dump(Ko_Tool_Module::AGetSubModule("o2o_buy_cash_hkdApi"));
var_dump(Ko_Tool_Module::AGetSubModule("o2o_buy_cashFunc"));
var_dump(Ko_Tool_Module::AGetSubModule("loginApi"));

*/
?>