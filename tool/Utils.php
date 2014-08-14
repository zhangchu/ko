<?php
/**
 * Utils
 *
 * @package ko\tool
 * @author zhangchu
 */

//include_once('../ko.class.php');

/**
 * 常用工具实现
 */
class Ko_Tool_Utils
{
	/**
	 * 将一个数组中的数据的某一项提出来作为一个数组
	 *
	 * @return array
	 */
	public static function AObjs2ids($vObjs, $sKey)
	{
		$ids = array();
		if (is_array($vObjs))
		{
			foreach($vObjs as $obj)
			{
				if (is_array($obj))
				{
					$ids[] = $obj[$sKey];
				}
				else if (is_object($obj))
				{
					$ids[] = $obj->$sKey;
				}
				else
				{
					$ids[] = $obj;
				}
			}
		}
		return $ids;
	}
	
	/**
	 * @static 用数组的值将数组的索引改变
	 * @abstract
	 * @param $vObjs
	 * @param $sKey
	 * @return array
	 */
	public static function AObjs2map($vObjs, $sKey, $sValue = null)
	{
		$map = array();
		if (is_array($vObjs))
		{
			foreach($vObjs as $obj)
			{
				if (is_array($obj))
				{
					$map[$obj[$sKey]] = is_null($sValue) ? $obj : $obj[$sValue];
				}
				else if (is_object($obj))
				{
					$map[$obj->$sKey] = is_null($sValue) ? $obj : $obj->$sValue;
				}
			}
		}
		return $map;
	}

	/**
	 * 数组按照某个field进行排序
	 * @param $aArr 待排数组
	 * @param $sField 按照该字段排序
	 * @param $bDesc 是否倒排（web上多数情况下desc = true, 故默认为true）
	 * @param $bPreserveKey 是否保留键值
	 * @return array
	 */
	public static function ASortByField(array $aArr, $sField, $bDesc = true, $bPreserveKey = false)
	{
		$aSortedArr = array();
		$aMapping = array();
		foreach($aArr as $sKey => $aItem)
		{
			$aMapping[$sKey] = is_array($aItem) ? $aItem[$sField] : null;
		}
		$bDesc ? arsort($aMapping) : asort($aMapping);
		foreach($aMapping as $sKey => $v)
		{
			if($bPreserveKey)
			{
				$aSortedArr[$sKey] = $aArr[$sKey];
			}
			else
			{
				$aSortedArr[] = $aArr[$sKey];
			}
		}
		return $aSortedArr;
	}
	
	/**
	 * @return array
	 */
	public static function AXml2arr($sXml, $sAttrKey = '@attributes', $sValueKey = '@value')
	{
		$oXml = simplexml_load_string($sXml, 'SimpleXMLElement', LIBXML_NOCDATA);
		if (false === $oXml)
		{
			return array();
		}
		return self::_AXml2arr($oXml, $sAttrKey, $sValueKey);
	}
	
	private static function _AXml2arr($oXml, $sAttrKey, $sValueKey)
	{
		$attr = $oXml->attributes();
		$ret = array(
			$sAttrKey => self::_AAttr2arr($attr),
			$sValueKey => (string)$oXml,
			);
		foreach ($oXml->children() as $k => $v)
		{
			$ret[$k][] = self::_AXml2arr($v, $sAttrKey, $sValueKey);
		}
		return $ret;
	}
	
	private static function _AAttr2arr($oAttr)
	{
		$ret = array();
		foreach ($oAttr as $k => $v)
		{
			$ret[$k] = (string)$v;
		}
		return $ret;
	}
}

?>
