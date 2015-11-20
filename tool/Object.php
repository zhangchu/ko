<?php
/**
 * Object
 *
 * @package ko\tool
 * @author zhangchu
 */

//include_once('../ko.class.php');

/**
 * 对象自动创建实现
 */
class Ko_Tool_Object
{
	/**
	 * 创建对象，只能创建同模块下的
	 *
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
		$pos = strrpos($sThisModule, '\\');
		if (false !== $pos)
		{
			if ('\\' === substr($sThisModule, -1))
			{
				$sClassName = substr($sThisModule, 0, $pos + 1).'M'.$sFile;
			}
			else
			{
				$sClassName = substr($sThisModule, 0, $pos + 1).'M'.substr($sThisModule, $pos + 1).'_'.$sFile;
			}
		}
		else
		{
			$sClassName = 'K'.$sThisModule.'_'.$sFile;
		}

		// 创建对象
		return Ko_Tool_Singleton::OInstance($sClassName);
	}

	/**
	 * 创建对象，可以创建同模块下的，也可以创建子模块下的
	 *
	 * @return object
	 */
	public static function OCreate($vClass, $sName)
	{
		// 获取模块名
		$sThisModule = Ko_Tool_Module::SGetObjectModuleName($vClass);

		return self::OCreateFromModule($sThisModule, $sName);
	}

	/**
	 * 创建对象，可以创建根模块下的任意对象
	 *
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
	 * 创建对象，可以创建 $sFromModule 下的任意对象
	 *
	 * @return object
	 */
	public static function OCreateFromModule($sFromModule, $sName)
	{
		// 分析子模块名和文件名
		list($sModule, $sFile) = Ko_Tool_Module::AGetSubModule($sName);

		// 构造类名
		$pos = strrpos($sFromModule, '\\');
		if (false !== $pos)
		{
			if($sModule !== '')
			{
				$sModule = Ko_Tool_Module::SGetRegularModuleName($sModule);
				if ('\\' === substr($sFromModule, -1))
				{
					$sClassName = substr($sFromModule, 0, $pos + 1).'M'.$sModule.'_'.$sFile;
				}
				else
				{
					$sClassName = substr($sFromModule, 0, $pos + 1).'M'.substr($sFromModule, $pos + 1).'_'.$sModule.'_'.$sFile;
				}
			}
			else
			{
				if ('\\' === substr($sFromModule, -1))
				{
					$sClassName = substr($sFromModule, 0, $pos + 1).'M'.$sFile;
				}
				else
				{
					$sClassName = substr($sFromModule, 0, $pos + 1).'M'.substr($sFromModule, $pos + 1).'_'.$sFile;
				}
			}
		}
		else
		{
			if($sModule !== '')
			{
				$sModule = Ko_Tool_Module::SGetRegularModuleName($sModule);
				$sClassName = 'K'.$sFromModule.'_'.$sModule.'_'.$sFile;
			}
			else
			{
				$sClassName = 'K'.$sFromModule.'_'.$sFile;
			}
		}

		// 创建对象
		return Ko_Tool_Singleton::OInstance($sClassName);
	}
}
