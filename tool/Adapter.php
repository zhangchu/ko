<?php
/**
 * Adapter
 *
 * @package ko\tool
 * @author zhangchu
 */

class Ko_Tool_Adapter
{
	private $s_aConvFunc = array();

	/**
	 * 设置自定义类型的处理函数
	 */
	public static function VOn($sType, $fnConv)
	{
		self::$s_aConvFunc[$sType] = $fnConv;
	}
	
	/**
	 * 将数据按照指定的规则来进行适配
	 * rule支持的基本数据类型：
	 *   boolean, integer, float, string, list, hash
	 * 如：
	 *   Ko_Tool_Adapter::VConv($data, 'boolean');
	 *   Ko_Tool_Adapter::VConv($data, 'string');
	 *   Ko_Tool_Adapter::VConv($data, array('list', 'string'));  //列表中全部数据转为字符串
	 *   Ko_Tool_Adapter::VConv($data, array('hash', array(
	 *     'email' => 'string',
	 *     'isverify' => 'boolean',
	 *     'education' => array('list', array('hash', array(
	 *       'stime' => 'string',
	 *       'etime' => 'string',
	 *       'college' => 'string',
	 *     ))),
	 *     'userinfo' => 'userbasicinfo',             //自定义类型，需要调用 VOn 来添加处理函数
	 *     'logo16' => array('userlogo', '16'),         //带参数的自定义类型
	 *   )));
	 */
	public static function VConv($vData, $vRule)
	{
		if (is_array($vRule))
		{
			$sRule = $vRule[0];
			$vChildRule = $vRule[1];
		}
		else
		{
			$sRule = $vRule;
			$vChildRule = null;
		}
		switch ($sRule)
		{
		case 'bool':
		case 'boolean':
			return (boolean)$vData;
		case 'int':
		case 'integer':
			return (integer)$vData;
		case 'double':
		case 'float':
			return (float)$vData;
		case 'str':
		case 'string':
			return (string)$vData;
		case 'list':
			return self::_VList($vData, $vChildRule);
		case 'hash':
			return self::_VHash($vData, $vChildRule);
		default:
			if (isset(self::$s_aConvFunc[$sRule]))
			{
				return call_user_func(self::$s_aConvFunc[$sRule], $vData, $vChildRule);
			}
		}
		return $vData;
	}
	
	private static function _VHash($vData, $vRule)
	{
		if (!is_array($vData))
		{
			$vData = array();
		}
		if (!is_array($vRule))
		{
			$vRule = array();
		}
		foreach ($vData as $k => &$v)
		{
			if (!isset($vRule[$k]))
			{
				unset($vData[$k]);
			}
			else
			{
				$v = self::VConv($v, $vRule[$k]);
			}
		}
		unset($v);
		return $vData;
	}
	
	private static function _VList($vData, $vRule)
	{
		if (!is_array($vData))
		{
			$vData = array();
		}
		foreach ($vData as &$v)
		{
			$v = self::VConv($v, $vRule);
		}
		unset($v);
		return $vData;
	}
}
