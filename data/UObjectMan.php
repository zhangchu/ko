<?php
/**
 * UobjectMan
 *
 * @package ko\data
 * @author zhangchu
 */

/**
 * 使用 KProxy 方式调用 UObject
 */
class Ko_Data_UObjectMan extends Ko_Data_KProxy
{
	private static $s_aInstance = array();	//UObject对象数组

	private $_sKind;
	private $_sSplitField;
	private $_sKeyField;

	protected function __construct ($sKind, $sSplitField, $sKeyField, $sUoName)
	{
		KO_DEBUG >= 6 && Ko_Tool_Debug::VAddTmpLog('data/UObjectMan', '__construct:'.$sKind.':'.$sSplitField.':'.$sKeyField.':'.$sUoName);
		parent::__construct('UObjectMan', $sUoName);

		$this->_sKind = $sKind;
		$this->_sSplitField = $sSplitField;
		$this->_sKeyField = $sKeyField;
	}

	public static function OInstance($sKind, $sSplitField, $sKeyField, $sUoName='')
	{
		if (empty(self::$s_aInstance[$sKind.':'.$sKeyField]))
		{
			self::$s_aInstance[$sKind.':'.$sKeyField] = new self($sKind, $sSplitField, $sKeyField, $sUoName);
		}
		return self::$s_aInstance[$sKind.':'.$sKeyField];
	}

	public function aGetUObjectDetailLong($aIds, $aFields)
	{
		KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/UObjectMan', 'getComplexObjects:'.$this->_sKind.':'.$this->_sSplitField.':'.$this->_sKeyField.':'.count($aIds));
		$aPara = array(
			'kind' => $this->_sKind,
			'idname' => $this->_sKeyField,
			'fields' => is_array($aFields) ? $aFields : array(),
			'oids' => $aIds,
			);
		$uores = $this->_oProxy->invoke('getComplexObjects', $aPara);
		return $this->_aFormatData($uores['rows'], $uores['fields']);
	}

	public function vInvalidate($iUid, $iId)
	{
		KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/UObjectMan', 'invalidateComplex:'.$this->_sKind.':'.$this->_sSplitField.':'.$this->_sKeyField.':'.$iUid.':'.$iId);
		try
		{
			$loid = $this->oCreateLOID($iUid, $iId);
			$aPara = array(
				'kind' => $this->_sKind,
				'idname' => $this->_sKeyField,
				'oid' => $loid,
				);
			$this->_oProxy->invoke('invalidateComplex', $aPara);
		}
		catch (Exception $ex)
		{
		}
	}

	public function oCreateLOID($iUid, $iId)
	{
		return array(intval($iUid), intval($iId));
	}
	
	public function vUpgradeKind()
	{
		KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/UObjectMan', 'upgradeKind:'.$this->_sKind);
		try
		{
			$aPara = array(
				'kind' => $this->_sKind,
				);
			$this->_oProxy->invoke('upgradeKind', $aPara);
		}
		catch (Exception $ex)
		{
		}
	}
	
	public function vClearKind()
	{
		KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/UObjectMan', 'clearKind:'.$this->_sKind);
		try
		{
			$aPara = array(
				'kind' => $this->_sKind,
				);
			$this->_oProxy->invoke('clearKind', $aPara);
		}
		catch (Exception $ex)
		{
		}
	}

	////////////////////////////////// 私有函数 //////////////////////////////////

	private function _aFormatData($aData, $aFields)
	{
		$ret = array();
		foreach($aData as $i=>$row)
		{
			$allisnull = true;
			$item = array();
			foreach($aFields as $id=>$field)
			{
				if ($allisnull && !is_null($row[$id]))
				{
					$allisnull = false;
				}
				$item[$field] = $row[$id];
			}
			$ret[$i] = $allisnull ? array() : $item;
		}
		return $ret;
	}
}

/*

$kind = 's_user_info';
$obj = Ko_Data_UObjectMan::OInstance($kind, 'uid');

$loid1 = $obj->oCreateLOID(337, 337);
$loid2 = $obj->oCreateLOID(338, 338);
$loid3 = $obj->oCreateLOID(1, 1);
$loid4 = $obj->oCreateLOID(0, 0);
$arr = array($loid1, $loid2, $loid3, $loid4);
$ret = $obj->aGetUObjectDetailLong($arr, array('uid', 'real_name', 'nick'));
var_dump($ret);

$nick1 = 'test337';
$nick2 = '337test';
$nick = $ret[0]['nick'];
if ($nick == $nick1)
{
	$nick = $nick2;
}
else
{
	$nick = $nick1;
}
$dbobj = Ko_Data_DBMan::OInstance();
$sql = 'update s_user_info set nick = "'.Ko_Data_Mysql::SEscape($nick).'" where uid = "337"';
$ret = $dbobj->aSingleQuery($kind, '337', $sql, 0);
var_dump($ret);

$ret = $obj->vInvalidate('337', '337');
var_dump($ret);

$ret = $obj->vInvalidate('1212121', '1212121');
var_dump($ret);

$ret = $obj->aGetUObjectDetailLong($arr, array('uid', 'real_name', 'nick'));
var_dump($ret);

$obj = new Ko_Data_UObjectMan;

*/
?>