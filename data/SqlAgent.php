<?php
/**
 * SqlAgent
 *
 * @package ko\data
 * @author zhangchu
 */

/**
 * 封装常用的 SQL 语句实现，insert/update/delete/select
 */
class Ko_Data_SqlAgent
{
	private static $s_aInstance = array();

	private $_sTag;

	private $_oEngine;

	protected function __construct($sTag)
	{
		$this->_sTag = $sTag;
	}

	public static function OInstance($sTag)
	{
		if (empty(self::$s_aInstance[$sTag]))
		{
			self::$s_aInstance[$sTag] = new self($sTag);
		}
		return self::$s_aInstance[$sTag];
	}

	public function aInsertMulti($sKind, $iHintId, $aData, $oOption)
	{
		$sql = $oOption->sInsertMultiSql($sKind, $aData);
		return $this->_aQuery($sKind, $iHintId, $sql);
	}

	public function aInsert($sKind, $iHintId, $aData, $aUpdate, $aChange, $oOption)
	{
		$sql = $oOption->sInsertSql($sKind, $aData, $aUpdate, $aChange);
		return $this->_aQuery($sKind, $iHintId, $sql);
	}

	public function iUpdate($sKind, $iHintId, $aUpdate, $aChange, $oOption)
	{
		$sql = $oOption->sUpdateSql($sKind, $aUpdate, $aChange);
		$info = $this->_aQuery($sKind, $iHintId, $sql);
		return $info['affectedrows'];
	}

	public function iDelete($sKind, $iHintId, $oOption)
	{
		$sql = $oOption->sDeleteSql($sKind);
		$info = $this->_aQuery($sKind, $iHintId, $sql);
		return $info['affectedrows'];
	}

	public function aSelect($sKind, $iHintId, $oOption, $iCacheTime, $bMaster)
	{
		$sql = $oOption->vSQL($sKind);
		$info = $this->_aQuery($sKind, $iHintId, $sql, $iCacheTime, $bMaster);
		if ($oOption->bCalcFoundRows())
		{
			$oOption->vSetFoundRows($info[1]['data'][0]['FOUND_ROWS()']);
			return $info[0]['data'];
		}
		else
		{
			return $info['data'];
		}
	}

	//////////////////////////// 工具函数 ////////////////////////////

	private function _oGetEngine()
	{
		if (is_null($this->_oEngine))
		{
			switch (KO_DB_ENGINE)
			{
			case 'kproxy':
				$this->_oEngine = Ko_Data_DBMan::OInstance($this->_sTag);
				break;
			case 'mysql':
				$this->_oEngine = Ko_Data_DBMysql::OInstance($this->_sTag);
				break;
			case 'mysql-pdo':
				$this->_oEngine = Ko_Data_DBPDO::OInstance($this->_sTag);
				break;
			default:
				assert(0);
			}
		}
		return $this->_oEngine;
	}

	private function _aQuery($sKind, $iHintId, $vSql, $iCacheTime=0, $bMaster=false)
	{
		if (is_array($vSql))
		{
			KO_DEBUG >= 1 && Ko_Tool_Debug::VAddTmpLog('data/SqlAgent', 'M:'.$sKind.':'.$iHintId.':'.$iCacheTime.':'.implode(':', $vSql));
			$ret = $this->_oGetEngine()->aMultiQuery($sKind, $iHintId, $vSql, $iCacheTime, $bMaster);
		}
		else
		{
			KO_DEBUG >= 1 && Ko_Tool_Debug::VAddTmpLog('data/SqlAgent', 'S:'.$sKind.':'.$iHintId.':'.$iCacheTime.':'.$vSql);
			$ret = $this->_oGetEngine()->aSingleQuery($sKind, $iHintId, $vSql, $iCacheTime, $bMaster);
		}
		return $ret;
	}
}

?>