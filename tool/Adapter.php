<?php
/**
 * Adapter
 *
 * @package ko\tool
 * @author zhangchu
 */

class Ko_Tool_Adapter
{
	const SPLIT = '#a#d#a#p#t#e#r#';
	const SPLIT_LEN = 15;
	
	private static $s_aConvFunc = array();

	/**
	 * 设置自定义类型的处理函数，处理函数要批量处理数据
	 * 如：
	 *   function batchconv_userlogo($datalist)
	 *   {
	 *     $newlist = array();
	 *     foreach ($datalist as $k => $v)
	 *     {
	 *       $data = $v[0];
	 *       $para = $v[1];
	 *       $newlist[$k] = getnewdatafunc($data, $para);
	 *     }
	 *     return $newlist;
	 *   }
	 */
	public static function VOn($sType, $fnConv)
	{
		self::$s_aConvFunc[$sType] = $fnConv;
	}
	
	/**
	 * 将数据按照指定的规则来进行适配
	 * rule支持的基本数据类型：
	 *   boolean, integer, float, string, list, hash
	 *   使用 any 来表示任意类型
	 * 如：
	 *   Ko_Tool_Adapter::VConv($data, 'boolean');
	 *   Ko_Tool_Adapter::VConv($data, 'string');
	 *   Ko_Tool_Adapter::VConv($data, 'userlogo');               //自定义类型，需要调用 VOn 来添加处理函数
	 *   Ko_Tool_Adapter::VConv($data, array('list', 'string'));  //列表中全部数据转为字符串
	 *   Ko_Tool_Adapter::VConv($data, array('hash', array(
	 *     'email' => 'string',
	 *     'isverify' => 'boolean',
	 *     'education' => array('list', array('hash', array(
	 *       'stime' => 'string',
	 *       'etime' => 'string',
	 *       'college' => 'string',
	 *     ))),
	 *     'userinfo' => 'userbasicinfo',                         //自定义类型
	 *     'logo16' => array('userlogo', '16'),                   //带参数的自定义类型
	 *     'userdata' => 'any',
	 *   )));
	 */
	public static function VConv($vData, $vRule)
	{
		$aBatchData = array();
		self::_VConv($vData, $vRule, $aBatchData, self::SPLIT);
		foreach ($aBatchData as $sType => $data)
		{
			if (isset(self::$s_aConvFunc[$sType]))
			{
				$ret = call_user_func(self::$s_aConvFunc[$sType], $data);
				foreach ($ret as $k => $v)
				{
					if (self::SPLIT === $k)
					{
						$vData = $v;
					}
					else
					{
						Ko_Tool_Array::VOffsetSet($vData, substr($k, self::SPLIT_LEN, -self::SPLIT_LEN), $v, self::SPLIT);
					}
				}
			}
		}
		return $vData;
	}

	private static function _VConv(&$vData, $vRule, &$aBatchData, $sBatchKey)
	{
		list($sType, $vChildRule) = self::_AParseRule($vRule);
		switch ($sType)
		{
			case 'any':
				break;
			case 'bool':
			case 'boolean':
				$vData = (boolean)$vData;
				break;
			case 'int':
			case 'integer':
				$vData = (integer)$vData;
				break;
			case 'double':
			case 'float':
				$vData = (float)$vData;
				break;
			case 'str':
			case 'string':
				$vData = (string)$vData;
				break;
			case 'array':
			case 'list':
				self::_VList($vData, $vChildRule, $aBatchData, $sBatchKey);
				break;
			case 'object':
			case 'hash':
				self::_VHash($vData, $vChildRule, $aBatchData, $sBatchKey);
				break;
			default:
				$aBatchData[$sType][$sBatchKey] = array($vData, $vChildRule);
				break;
		}
	}

	private static function _AParseRule($vRule)
	{
		if (is_array($vRule))
		{
			if (isset($vRule['type']))
			{
				$sType = $vRule['type'];
				switch ($sType)
				{
					case 'array':
					case 'list':
						$vChildRule = isset($vRule['items']) ? $vRule['items'] : $vRule['elements'];
						break;
					case 'object':
					case 'hash':
						$vChildRule = isset($vRule['properties']) ? $vRule['properties'] : $vRule['members'];
						break;
					default:
						$vChildRule = isset($vRule['paras']) ? $vRule['paras'] : null;
						break;
				}
			}
			else
			{
				$sType = $vRule[0];
				$vChildRule = $vRule[1];
			}
		}
		else
		{
			$sType = $vRule;
			$vChildRule = null;
		}
		return array($sType, $vChildRule);
	}

	private static function _VHash(&$vData, $vRule, &$aBatchData, $sBatchKey)
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
				self::_VConv($v, $vRule[$k], $aBatchData, $sBatchKey.$k.self::SPLIT);
			}
		}
		unset($v);
	}
	
	private static function _VList(&$vData, $vRule, &$aBatchData, $sBatchKey)
	{
		if (!is_array($vData))
		{
			$vData = array();
		}
		foreach ($vData as $k => &$v)
		{
			self::_VConv($v, $vRule, $aBatchData, $sBatchKey.$k.self::SPLIT);
		}
		unset($v);
	}
}
