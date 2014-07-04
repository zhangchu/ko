<?php
/**
 * UobjectMysql
 *
 * @package ko\data
 * @author zhangchu
 */

/**
 * 使用直连 Mysql 方式调用 UObject 的替代实现
 */
class Ko_Data_UObjectMysql
{
	private static $s_aInstance = array();	//UObject对象数组

	private $_sKind;
	private $_sSplitField;
	private $_sKeyField;
	
	private $_oSqlAgent;

	protected function __construct ($sKind, $sSplitField, $sKeyField, $sUoName)
	{
		KO_DEBUG >= 6 && Ko_Tool_Debug::VAddTmpLog('data/UObjectMysql', '__construct:'.$sKind.':'.$sSplitField.':'.$sKeyField.':'.$sUoName);

		$this->_sKind = $sKind;
		$this->_sSplitField = $sSplitField;
		$this->_sKeyField = $sKeyField;
		$this->_oSqlAgent = Ko_Data_SqlAgent::OInstance($sUoName);
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
		KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/UObjectMysql', 'getComplexObjects:'.$this->_sKind.':'.$this->_sSplitField.':'.$this->_sKeyField.':'.count($aIds));
		
		$oOption = new Ko_Tool_SQL;
		$func = $this->_sSplitField == $this->_sKeyField ? '_vGetOptionSplit' : '_vGetOptionSplitAndKey';
		foreach ($aIds as $id)
		{
			call_user_func(array($this, $func), $oOption, $id);
		}
		$uores = $this->_oSqlAgent->aSelect($this->_sKind, 1, $oOption->oSelect(implode(', ', $aFields)), 0, true);
		return $this->_aFormatData($aIds, $uores);
	}

	public function vInvalidate($iUid, $iId)
	{
		KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/UObjectMysql', 'invalidateComplex:'.$this->_sKind.':'.$this->_sSplitField.':'.$this->_sKeyField.':'.$iUid.':'.$iId);
	}

	public function oCreateLOID($iUid, $iId)
	{
		return array($iUid, $iId);
	}

	////////////////////////////////// 私有函数 //////////////////////////////////

	private function _aFormatData($aIds, $aRes)
	{
		$func = $this->_sSplitField == $this->_sKeyField ? '_bCompareSplit' : '_bCompareSplitAndKey';
		return $this->_aUserFormatData($aIds, $aRes, array($this, $func));
	}
	
	private function _aUserFormatData($aIds, $aRes, $fnCompare)
	{
		$ret = array();
		foreach($aIds as $i=>$id)
		{
			$item = array();
			foreach($aRes as $res)
			{
				if (call_user_func($fnCompare, $res, $id))
				{
					$item = $res;
					break;
				}
			}
			$ret[$i] = $item;
		}
		return $ret;
	}
	
	private function _bCompareSplit($aRes, $aId)
	{
		return $aRes[$this->_sSplitField] == $aId[0];
	}
	
	private function _bCompareSplitAndKey($aRes, $aId)
	{
		return $aRes[$this->_sSplitField] == $aId[0] && $aRes[$this->_sKeyField] == $aId[1];
	}
	
	private function _vGetOptionSplit($oOption, $aId)
	{
		$oOption->oOr($this->_sSplitField.' = ?', $aId[0]);
	}

	private function _vGetOptionSplitAndKey($oOption, $aId)
	{
		$oOption->oOr($this->_sSplitField.' = ? AND '.$this->_sKeyField.' = ?', $aId[0], $aId[1]);
	}
}

?>