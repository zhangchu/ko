<?php
/**
 * Input
 *
 * @package ko\tool
 * @author zhangchu
 */

/**
 * 检测和规范输入数据实现
 */
class Ko_Tool_Input
{
	const T_NOCLEAN     = 0;            // 不做处理
	const T_INT         = 1;            // 转换成integer
	const T_UINT        = 2;            // 转换成无符号integer
	const T_NUM         = 3;            // 转换成number
	const T_STR         = 4;            // 转换成string，并去除两边的空格
	const T_NOTRIM      = 5;            // 转换成string，保留空格
	const T_FILE        = 6;            // 转换成file，不支持数组提交
	const T_HTML        = 7;            // HTML提交，不支持数组提交
	const T_RICHHTML    = 8;            // HTML提交，支持更多标签，不支持数组提交
	const T_ARRAY		= 9;			// 数组类型，其数据类型取决于其键名的前缀

	private static $s_bInit = false;

	private static $s_aTypeMap = array(
		'i' => self::T_INT,
		'u' => self::T_UINT,
		'n' => self::T_NUM,
		's' => self::T_STR,
		'v' => self::T_NOTRIM,
		'f' => self::T_FILE,
		'h' => self::T_HTML,
		'r' => self::T_RICHHTML,
		'a' => self::T_ARRAY,
	);

	private static $s_aSuperGlobal = array ('g' => '_GET', 'p' => '_POST', 'c' => '_COOKIE', 'r' => '_REQUEST', 'f' => '_FILES');

	/**
	 * 从变量名的第一个字母获取类型，获取不到返回 Ko_Tool_Input::T_STR
	 * @param string $sVarName 变量名称
	 * @return int
	 */
	public static function IGetType($sVarName)
	{
		$first = substr($sVarName, 0, 1);
		return self::$s_aTypeMap[$first] ?: self::T_STR;
	}
	
	/**
	 * 直接对一个变量进行参数校验
	 * $param mixed $vValue 可以是一个值，也可以是一个数组
	 * $param mixed $vVarType 可以是一个类型，也可以是一个类型数组，与 $vValue 对应
	 * @return mixed
	 */
	public static function VCleanVar($vValue, $vVarType, $sCharset = KO_CHARSET)
	{
		if (is_array($vValue) && is_array($vVarType))
		{
			$aRet = array();
			foreach ($vValue as $k => $v)
			{
				self::_VCleanOne($aRet, $vVarType, $k, $v, $sCharset);
			}
			return $aRet;
		}
		$aRet = array();
		self::_VCleanOneType($aRet, array(), $vVarType, 'v', $vValue, $sCharset);
		return $aRet['v'];
	}

	/**
	 * 解析指定名称的 cgi 参数
	 * @param string $sSource 参数的来源，取值为 'g' 'p' 'c' 'r' 'f'，分别代表 $_GET $_POST $_COOKIE $_REQUEST $_FILES
	 * @param string $sVarName 参数的名称
	 * @param int $iVarType 指定参数的类型
	 * @return mixed 类型检查后的参数
	 */
	public static function VClean($sSource, $sVarName, $iVarType = false, $sCharset = KO_CHARSET)
	{
		self::_VInit();

		if (false === $iVarType)
		{
			$first = substr($sVarName, 0, 1);
			if (isset(self::$s_aTypeMap[$first]))
			{
				$iVarType = self::$s_aTypeMap[$first];
			}
			else if (is_array($GLOBALS[self::$s_aSuperGlobal[$sSource]][$sVarName]))
			{
				$iVarType = self::T_ARRAY;
			}
		}
		$aRet = array();
		self::_VCleanOneType($aRet, array(), $iVarType, $sVarName, $GLOBALS[self::$s_aSuperGlobal[$sSource]][$sVarName], $sCharset);
		return $aRet[$sVarName];
	}
	
	/**
	 * 从 $_GET & $_POST 解析指定名称的 cgi 参数，$_POST 优先
	 * @param string $sVarName 参数的名称
	 * @param int $iVarType 指定参数的类型
	 * @return mixed 类型检查后的参数
	 */
	public static function VCleanOneGP($sVarName, $iVarType = false, $sCharset = KO_CHARSET)
	{
		if (isset($_POST[$sVarName]))
		{
			return self::VClean('p', $sVarName, $iVarType, $sCharset);
		}
		return self::VClean('g', $sVarName, $iVarType, $sCharset);
	}

	/**
	 * 解析所有 $_REQUEST $_FILES 数据
	 * @param array $aValTypes 自定义数据类型。
	 * 如：
	 * <code>
	 * array(
	 *   'uid' => Ko_Tool_Input::T_UINT,
	 *   'key' => Ko_Tool_Input::T_STR,
	 * )
	 * </code>
	 * @return array 返回数据中包括 $aValTypes 中定义的数据，也包括符合命名规范的参数(首字母在self::$s_aTypeMap中已定义)
	 */
	public static function ACleanAll($aValTypes = array(), $sCharset = KO_CHARSET)
	{
		self::_VInit();

		$aRet = array();
		foreach ($_REQUEST as $k => $v)
		{
			self::_VCleanOne($aRet, $aValTypes, $k, $v, $sCharset);
		}
		foreach ($_FILES as $k => $v)
		{
			$aRet[$k] = self::_VDoClean($v, self::T_FILE, $sCharset);
		}
		return $aRet;
	}
	
	/**
	 * 单独解析所有 $_GET & $_POST 数据，$_POST 优先
	 * @return array 返回数据中包括 $aValTypes 中定义的数据，也包括符合命名规范的参数(首字母在self::$s_aTypeMap中已定义)
	 */
	public static function ACleanAllGP($aValTypes = array(), $sCharset = KO_CHARSET)
	{
		self::_VInit();

		$aRet = array();
		foreach ($_GET as $k => $v)
		{
			self::_VCleanOne($aRet, $aValTypes, $k, $v, $sCharset);
		}
		foreach ($_POST as $k => $v)
		{
			self::_VCleanOne($aRet, $aValTypes, $k, $v, $sCharset);
		}
		return $aRet;
	}

	/**
	 * 单独解析所有 $_GET 数据
	 * @return array 返回数据中包括 $aValTypes 中定义的数据，也包括符合命名规范的参数(首字母在self::$s_aTypeMap中已定义)
	 */
	public static function ACleanAllGet($aValTypes = array(), $sCharset = KO_CHARSET)
	{
		self::_VInit();

		$aRet = array();
		foreach ($_GET as $k => $v)
		{
			self::_VCleanOne($aRet, $aValTypes, $k, $v, $sCharset);
		}
		return $aRet;
	}

	/**
	 * 单独解析所有 $_POST 数据
	 * @return array 返回数据中包括 $aValTypes 中定义的数据，也包括符合命名规范的参数(首字母在self::$s_aTypeMap中已定义)
	 */
	public static function ACleanAllPost($aValTypes = array(), $sCharset = KO_CHARSET)
	{
		self::_VInit();

		$aRet = array();
		foreach ($_POST as $k => $v)
		{
			self::_VCleanOne($aRet, $aValTypes, $k, $v, $sCharset);
		}
		return $aRet;
	}

	/**
	 * 判断邮件地址是否合法
	 * @return bool
	 */
	public static function BIsEmail($sEmail)
	{
		return filter_var($sEmail, FILTER_VALIDATE_EMAIL) ? true : false;
	}

	private static function _VInit()
	{
		if (!self::$s_bInit)
		{
			if (get_magic_quotes_gpc())
			{
				self::_VStripslashesRecursively($_GET);
				self::_VStripslashesRecursively($_POST);
				self::_VStripslashesRecursively($_COOKIE);
				self::_VStripslashesRecursively($_REQUEST);
			}
			ini_set('magic_quotes_runtime', 0);
			self::$s_bInit = true;
		}
	}

	private static function _VStripslashesRecursively(&$vValue)
	{
		if (is_array($vValue))
		{
			foreach ($vValue as $sKey => &$vVal)
			{
				self::_VStripslashesRecursively($vVal);
			}
			unset($vVal);
		}
		else if (is_string($vValue))
		{
			$vValue = stripslashes($vValue);
		}
	}

	private static function _VCleanOne(&$aRet, $aValTypes, $sName, $vValue, $sCharset)
	{
		if (isset($aValTypes[$sName]))
		{
			self::_VCleanOneType($aRet, $aValTypes[$sName], is_array($aValTypes[$sName]) ? self::T_ARRAY : $aValTypes[$sName], $sName, $vValue, $sCharset);
		}
		else
		{
			$first = substr($sName, 0, 1);
			if (isset(self::$s_aTypeMap[$first]))
			{
				self::_VCleanOneType($aRet, array(), self::$s_aTypeMap[$first], $sName, $vValue, $sCharset);
			}
			else if (is_array($vValue))
			{
				self::_VCleanOneType($aRet, array(), self::T_ARRAY, $sName, $vValue, $sCharset);
			}
		}
	}

	private static function _VCleanOneType(&$aRet, $aValTypes, $iType, $sName, $vValue, $sCharset)
	{
		if (self::T_ARRAY == $iType)
		{
			if (is_array($vValue))
			{
				$aRet[$sName] = array();
				foreach ($vValue as $k => $v)
				{
					self::_VCleanOne($aRet[$sName], $aValTypes, $k, $v, $sCharset);
				}
			}
		}
		else
		{
			$aRet[$sName] = self::_VDoClean($vValue, $iType, $sCharset);
		}
	}

	private static function _VConvertData($fnConvert, $vData, $sCharset)
	{
		if (is_array($vData))
		{
			foreach ($vData as $k => &$v)
			{
				$v = self::_VConvertData($fnConvert, $v, $sCharset);
			}
			unset($v);
			return $vData;
		}
		return call_user_func($fnConvert, $vData, $sCharset);
	}

	private static function _IConvert_INT($vData, $sCharset)
	{
		return intval($vData);
	}

	private static function _IConvert_UINT($vData, $sCharset)
	{
		return max(0, intval($vData));
	}

	private static function _IConvert_NUM($vData, $sCharset)
	{
		return $vData + 0;
	}

	private static function _IConvert_STR($vData, $sCharset)
	{
		return trim(strval($vData));
	}

	private static function _IConvert_NOTRIM($vData, $sCharset)
	{
		return strval($vData);
	}

	private static function _IConvert_HTML($vData, $sCharset)
	{
		if($_REQUEST['texttype'] == 'plain')
		{
			$vData = nl2br(htmlspecialchars($vData));
		}
		return Ko_Html_MsgParse::sParse($vData, 65535, $sCharset);
	}

	private static function _IConvert_RICHHTML($vData, $sCharset)
	{
		if($_REQUEST['texttype'] == 'plain')
		{
			$vData = nl2br(htmlspecialchars($vData));
		}
		return Ko_Html_WebParse::sParse($vData, 65535, $sCharset);
	}

	private static function _VDoClean($vData, $iType, $sCharset)
	{
		switch ($iType)
		{
		case self::T_NOCLEAN:
			return self::_VFilterErrorCode($vData, $sCharset);
		case self::T_INT:
			return self::_VConvertData(array('self', '_IConvert_INT'), $vData, $sCharset);
		case self::T_UINT:
			return self::_VConvertData(array('self', '_IConvert_UINT'), $vData, $sCharset);
		case self::T_NUM:
			return self::_VConvertData(array('self', '_IConvert_NUM'), $vData, $sCharset);
		case self::T_STR:
			$vData = self::_VConvertData(array('self', '_IConvert_STR'), $vData, $sCharset);
			return self::_VFilterErrorCode($vData, $sCharset);
		case self::T_NOTRIM:
			$vData = self::_VConvertData(array('self', '_IConvert_NOTRIM'), $vData, $sCharset);
			return self::_VFilterErrorCode($vData, $sCharset);
		case self::T_FILE:
			if (is_array($vData) && isset($vData['tmp_name']) && isset($vData['error']) && isset($vData['size']))
			{
				if (is_array($vData['tmp_name']))
				{
					$file = array();
					foreach ($vData['tmp_name'] as $k => $v)
					{
						self::_VParseFileArray($file, $k, $v, $vData['name'][$k], $vData['type'][$k], $vData['error'][$k], $vData['size'][$k]);
					}
					$vData = $file;
				}
			}
			else
			{
				$vData = array(
					'name'     => '',
					'type'     => '',
					'tmp_name' => '',
					'error'    => UPLOAD_ERR_NO_FILE,
					'size'     => 0,
				);
			}
			return self::_VFilterErrorCode($vData, $sCharset);
		case self::T_HTML:
			$vData = self::_VFilterErrorCode($vData, $sCharset);
			return self::_VConvertData(array('self', '_IConvert_HTML'), $vData, $sCharset);
		case self::T_RICHHTML:
			$vData = self::_VFilterErrorCode($vData, $sCharset);
			return self::_VConvertData(array('self', '_IConvert_RICHHTML'), $vData, $sCharset);
		}
		assert(0);
	}

	private static function _VFilterErrorCode($vData, $sCharset)
	{
		if (is_array($vData))
		{
			Ko_Tool_Str::VFilterErrorCode($vData, $sCharset, 'strict');
			return $vData;
		}
		return Ko_Tool_Str::SFilterErrorCode($vData, $sCharset, 'strict');
	}

	private static function _VParseFileArray(&$aFile, $sName, $vTmpNameValue, $vNameValue, $vTypeValue, $vErrorValue, $vSizeValue)
	{
		if (is_array($vTmpNameValue))
		{
			$aFile[$sName] = array();
			foreach ($vTmpNameValue as $k => $v)
			{
				self::_VParseFileArray($aFile[$sName], $k, $v, $vNameValue[$k], $vTypeValue[$k], $vErrorValue[$k], $vSizeValue[$k]);
			}
		}
		else
		{
			$aFile[$sName] = array(
				'name' => $vNameValue,
				'type' => $vTypeValue,
				'tmp_name' => $vTmpNameValue,
				'error' => $vErrorValue,
				'size' => $vSizeValue,
			);
		}
	}
}
