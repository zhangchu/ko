<?php
/**
 * Stringify
 *
 * @brief: 将数据转成php程序能够识别的字符串
 *     支持   boolean int float string array null
 *     不支持 object resource
 * @example 
 *   $arr1 = array (
 *     1 => 'abc', 
 *     7 => null,
 *     56 => 2.5,
 *     3 => false,
 *     'f' => "转义的\n\"\'\f\x12\o\0",
 *     'test' => array (
 *       'c' => 'a', 
 *       'd' => array (
 *         'f' => 'g'
 *       )
 *     )
 *   );
 *   $str = Ko_Tool_Stringify::SConvArray($arr1);
 *   echo $str; // output: 
 *   array(
 *     "1" => "abc",
 *     "7" => null,
 *     "56" => 2.5,
 *     "3" => false,
 *     "f" => "转义的\n\"\\'\f\\o\000",
 *     "test" => array(
 *       "c" => "a",
 *       "d" => array(
 *         "f" => "g"
 *       )
 *     )
 *   )
 *
 * @package ko\tool
 * @author: jiangjw (joy.jingwei@gmail.com)
 */

class Ko_Tool_Stringify
{
	/**
	 * 根据数据的类型自动识别，转成对应的字符串
	 *
	 * @param int|float|boolean|string|null|array $vData 待转换的数据
	 * @param bool $bOneLine  是否需要将array的不同层级放在一行，只有当$vData为array时，该值才有效
	 * @param int $iIndentWidth 一个缩进使用的空格数量，只有当$vData为array时，该值才有效
	 * @return string
	 * @api
	 */
	public static function SConvAny($vData, $bOneLine = false, $iIndentWidth = 2)
	{
		if (is_bool($vData))
		{
			return self::SConvBoolean($vData);
		}
		else if (is_int($vData) || is_float($vData))
		{
			return self::SConvNumber($vData);
		}
		else if (is_string($vData))
		{
			return self::SConvString($vData);
		}
		else if (is_array($vData))
		{
			return self::SConvArray($vData, $bOneLine, $iIndentWidth);
		}
		else if (is_null($vData))
		{
			return 'null';
		}
		return self::_SConvUnsupported($vData);
	}

	/**
	 * 将boolean类型转成string
	 * 
	 * @param boolean $bData 待转换的数据
	 * @return string
	 * @api
	 */
	public static function SConvBoolean($bData)
	{
		return $bData ? 'true' : 'false';
	}

	/**
	 * 将数字转成string
	 *
	 * @param int|float $iData 待转换的数据 
	 * @return string
	 * @api
	 */
	public static function SConvNumber($iData)
	{
		return strval($iData);
	}

	/**
	 * 将字符串进行转义, 转成能用文本保存的string
	 * 
	 * @param string $sData 待转换的数据
	 * @return string
	 * @api
	 */
	public static function SConvString($sData)
	{
		$sData = addcslashes($sData, "\n\r\t\v\f\\\$\"");
		return '"'.$sData.'"';
	}

	/**
	 * 将数组转成能用文本保存的字符串
	 * 这个字符串与php代码几乎一致, 但会进行格式化（换行，缩进）
	 * 
	 * @param array $aData 待转换的数组
	 * @param bool $bOneLine  是否需要将array的不同层级放在一行
	 * @param int $iIndentWidth 一个缩进使用的空格数量
	 * @param int $iLevel 缩进层级
	 * @return string 
	 * @api
	 */
	public static function SConvArray($aData, $bOneLine = false, $iIndentWidth = 2, $iLevel = 0)
	{
		$parenthesesIndent = '';
		$offset = $iLevel * $iIndentWidth;
		for ($i=0; $i<$offset; $i++)
		{
			$parenthesesIndent .= ' ';
		}
		$itemIndent = $parenthesesIndent;
		for ($i=0; $i<$iIndentWidth; $i++)
		{
			$itemIndent .= ' ';
		}
		
		$str = 'array(';
		if (!$bOneLine)
		{
			$str .= "\n";
		}
		$items = array();
		foreach ($aData as $key => $val)
		{
			$item = $itemIndent.self::SConvString($key).' => ';
			if (is_array($val))
			{
				$item .= self::SConvArray($val, $bOneLine, $iIndentWidth, $iLevel + 1);
			}
			else
			{
				$item .= self::SConvAny($val);
			}
			$items[] = $item;
		}
		$str .= implode(",\n", $items);
		if (!$bOneLine)
		{
			$str .= "\n";
		}
		return $str .= $parenthesesIndent.')';
	}

	private static function _SConvUnsupported($vData)
	{
		if (is_object($vData))
		{
			return self::SConvString(get_class($vData));
		}
		return self::SConvString('unknown');
	}
}
