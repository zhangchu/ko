<?php
/**
 * Module
 *
 * @package ko\tool
 * @author zhangchu
 */

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
		$sScript = Ko_Web_Request::SScriptFilename();
		if ('/' != $sScript[0] && ':/' != substr($sScript, 1, 2))
		{
			$sPath = realpath(dirname($sScript));
			$sScript = $sPath.'/'.$sScript;
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
	 * eg3:
	 *     $classname = 'APPS\user\MRest_test',  return 'APPS\user\Rest'
	 * eg4:
	 *     $classname = 'APPS\user\MApi',  return 'APPS\user\'
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
	 * eg2:
	 *     $sModuleName = 'O2O_UsEr',  return 'O2o_User'
	 * eg3:
	 *     $sModuleName = 'APPS\user\ReSt',  return 'APPS\user\Rest'
	 * </pre>
	 *
	 * @return string
	 */
	public static function SGetRegularModuleName($sModuleName)
	{
		$pos = strrpos($sModuleName, '\\');
		if (false !== $pos)
		{
			$ns = substr($sModuleName, 0, $pos + 1);
			$mname = substr($sModuleName, $pos + 1);
			if (0 == strlen($mname))
			{
				return $ns;
			}
			return $ns.self::_SGetRegularModuleName($mname);
		}
		else
		{
			return self::_SGetRegularModuleName($sModuleName);
		}
	}

	/**
	 * 获取模块的根模块名
	 *
	 * <pre>
	 * eg1:
	 *     $sModuleName = 'O2o_User_AddCreditApi',  return 'O2o'
	 * eg2:
	 *     $sModuleName = 'APPS\user\Rest_test',  return 'APPS\user\'
	 * </pre>
	 *
	 * @return string
	 */
	public static function SGetRootModuleName($sModuleName)
	{
		$pos = strrpos($sModuleName, '\\');
		if (false !== $pos)
		{
			return substr($sModuleName, 0, $pos + 1);
		}
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
	 * eg3:
	 *     $sVarName = 'APPS\user\Rest_test'  return array('APPS\user\Rest', 'test');
	 * eg3:
	 *     $sVarName = 'APPS\user\Rest'  return array('APPS\user\', 'Rest');
	 * </pre>
	 *
	 * @return array
	 */
	public static function AGetSubModule($sVarName)
	{
		$pos = strrpos($sVarName, '\\');
		if (false !== $pos)
		{
			$ns = substr($sVarName, 0, $pos + 1);
			$vname = substr($sVarName, $pos + 1);
			list($m, $f) = self::_AGetSubModule($vname);
			return array($ns.$m, $f);
		}
		return self::_AGetSubModule($sVarName);
	}

	private static function _SGetRegularModuleName($sModuleName)
	{
		$sModuleName = strtolower($sModuleName);
		$aList = explode('_', $sModuleName);
		foreach ($aList as $k => $v)
		{
			$aList[$k] = ucfirst($v);
		}
		return implode('_', $aList);
	}

	private static function _AGetSubModule($sVarName)
	{
		$aList = explode('_', $sVarName);
		$sName = array_pop($aList);
		return array(implode('_', $aList), $sName);
	}

	private static function _SGetClassModuleName($sClassName)
	{
		$pos = strrpos($sClassName, '\\');
		if (false !== $pos)
		{
			$cname = substr($sClassName, $pos + 1);
			if (substr($cname, 0, 1) !== 'M')
			{
				return '';
			}
			$ns = substr($sClassName, 0, $pos + 1);
			$pos = strrpos($cname, '_');
			if (false === $pos)
			{
				return $ns;
			}
			return $ns.substr($cname, 1, $pos - 1);
		}
		else
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
}
