<?php
/**
 * XTool
 *
 * @package ko\mode
 * @author zhangchu
 */

/**
 * 实现
 */
class Ko_Mode_XTool
{
	private static $s_aMysqlType = array(
		'tinyint' => array('cellinfo' => array('width' => 50),
			'editinfo' => array('type' => 'text', 'size' => 5),
			'queryinfo' => array('type' => 'text', 'size' => 5)),
		'smallint' => array('cellinfo' => array('width' => 70),
			'editinfo' => array('type' => 'text', 'size' => 7),
			'queryinfo' => array('type' => 'text', 'size' => 7)),
		'mediumint' => array('cellinfo' => array('width' => 90),
			'editinfo' => array('type' => 'text', 'size' => 10),
			'queryinfo' => array('type' => 'text', 'size' => 9)),
		'int' => array('cellinfo' => array('width' => 100),
			'editinfo' => array('type' => 'text', 'size' => 12),
			'queryinfo' => array('type' => 'text', 'size' => 10)),
		'bigint' => array('cellinfo' => array('width' => 120),
			'editinfo' => array('type' => 'text', 'size' => 21),
			'queryinfo' => array('type' => 'text', 'size' => 12)),
		'float' => array('cellinfo' => array('width' => 120),
			'editinfo' => array('type' => 'text', 'size' => 15),
			'queryinfo' => array('type' => 'text', 'size' => 10)),
		'double' => array('cellinfo' => array('width' => 120),
			'editinfo' => array('type' => 'text', 'size' => 18),
			'queryinfo' => array('type' => 'text', 'size' => 10)),
		'date' => array('cellinfo' => array('width' => 100),
			'editinfo' => array('type' => 'text', 'size' => 12),
			'queryinfo' => array('type' => 'text', 'size' => 10)),
		'timestamp' => array('cellinfo' => array('width' => 150),
			'editinfo' => array('type' => 'text', 'size' => 20),
			'queryinfo' => array('type' => 'text', 'size' => 19)),
		'tinytext' => array('cellinfo' => array('width' => 300),
			'editinfo' => array('type' => 'textarea', 'rows' => 5, 'cols' => 60),
			'queryinfo' => array('type' => 'text', 'size' => 12)),
		'text' => array('cellinfo' => array('width' => 300),
			'editinfo' => array('type' => 'textarea', 'rows' => 5, 'cols' => 60),
			'queryinfo' => array('type' => 'text', 'size' => 12)),
		'mediumtext' => array('cellinfo' => array('width' => 300),
			'editinfo' => array('type' => 'textarea', 'rows' => 5, 'cols' => 60),
			'queryinfo' => array('type' => 'text', 'size' => 12)),
		'longtext' => array('cellinfo' => array('width' => 300),
			'editinfo' => array('type' => 'textarea', 'rows' => 5, 'cols' => 60),
			'queryinfo' => array('type' => 'text', 'size' => 12)),
		);

	private static $s_aOperatorText = array(
		'lt' => '<',
		'le' => '<=',
		'gt' => '>',
		'ge' => '>=',
		'like' => 'like',
		);

	private static $s_aDBIndexTag = array('PRI', 'UNI', 'MUL');

	/**
	 * @return array
	 */
	public static function AGetDBTypeInfo($sType)
	{
		$arr = explode(' ', $sType);
		foreach ($arr as $v)
		{
			list($type, $size) = self::_AGetDBSizeInfo($v);
			if ('char' == $type || 'varchar' == $type)
			{
				if ($size <= 20)
				{
					$size = 10;
					$width = 90;
				}
				else if ($size <= 40)
				{
					$size = 20;
					$width = 120;
				}
				else if ($size <= 100)
				{
					$size = 40;
					$width = 200;
				}
				else
				{
					$size = 60;
					$width = 300;
				}
				return array('cellinfo' => array('width' => $width),
					'editinfo' => array('type' => 'text', 'size' => $size),
					'queryinfo' => array('type' => 'text', 'size' => 12));
			}
			else if ('enum' == $type)
			{
				$valuearr = array();
				$values = explode(',', $size);
				foreach ($values as $value)
				{
					$value = trim(trim($value), '\'');
					$valuearr[$value] = $value;
				}
				return array('cellinfo' => array('width' => 100),
					'editinfo' => array('type' => 'radio', 'values' => $valuearr),
					'queryinfo' => array('type' => 'select', 'values' => $valuearr));
			}
			else if (isset(self::$s_aMysqlType[$type]))
			{
				return self::$s_aMysqlType[$type];
			}
		}
		return array('cellinfo' => array('width' => 100),
			'editinfo' => array('type' => 'text', 'size' => 10),
			'queryinfo' => array('type' => 'text', 'size' => 8));
	}

	/**
	 * @return string
	 */
	public static function SGetOperatorText($sOperator)
	{
		if (isset(self::$s_aOperatorText[$sOperator]))
		{
			return self::$s_aOperatorText[$sOperator];
		}
		return '=';
	}

	public static function VGetOperatorSql($sOperator, $sField, $sValue, $oOption)
	{
		switch ($sOperator)
		{
		case 'lt':
			$oOption->oAnd($sField.' < ?', $sValue);
			break;
		case 'gt':
			$oOption->oAnd($sField.' > ?', $sValue);
			break;
		case 'le':
			$oOption->oAnd($sField.' <= ?', $sValue);
			break;
		case 'ge':
			$oOption->oAnd($sField.' >= ?', $sValue);
			break;
		case 'like':
			$oOption->oAnd($sField.' like ?', '%'.$sValue.'%');
			break;
		default:
			$oOption->oAnd($sField.' = ?', $sValue);
			break;
		}
	}

	/**
	 * @return boolean
	 */
	public static function BIsDBIndexTag($sKey)
	{
		return in_array($sKey, self::$s_aDBIndexTag, true);
	}

	private static function _AGetDBSizeInfo($sSubType)
	{
		@list($type, $size) = explode('(', $sSubType, 2);
		return array($type, rtrim($size, ')'));
	}
}

?>