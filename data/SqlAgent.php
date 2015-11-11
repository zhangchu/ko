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
		$sql = $this->_sInsertMultiSql($sKind, $aData, $oOption);
		return $this->_aQuery($sKind, $iHintId, $sql);
	}

	public function aInsert($sKind, $iHintId, $aData, $aUpdate, $aChange, $oOption)
	{
		$sql = $this->_sInsertSql($sKind, $aData, $aUpdate, $aChange, $oOption);
		return $this->_aQuery($sKind, $iHintId, $sql);
	}

	public function iUpdate($sKind, $iHintId, $aUpdate, $aChange, $oOption)
	{
		$sql = $this->_sUpdateSql($sKind, $aUpdate, $aChange, $oOption);
		$info = $this->_aQuery($sKind, $iHintId, $sql);
		return $info['affectedrows'];
	}

	public function iDelete($sKind, $iHintId, $oOption)
	{
		$sql = $this->_sDeleteSql($sKind, $oOption);
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
	
	private function _sGetSetSql($aUpdate, $aChange)
	{
		assert(is_array($aUpdate));
		assert(is_array($aChange));

		$set = array();
		foreach($aUpdate as $k => $v)
		{
			$k = ('`' === $k[0]) ? $k : '`'.$k.'`';
			$set[] = $k.' = "'.Ko_Data_Mysql::SEscape($v).'"';
		}
		foreach($aChange as $k => $v)
		{
			$k = ('`' === $k[0]) ? $k : '`'.$k.'`';
			$abs = abs($v);
			$set[] = $k.' = '.$k.' '.($v >= 0 ? '+' : '-').' '.$abs;
		}
		return implode(', ', $set);
	}

	private function _sInsertMultiSql($sKind, $aData, $oOption)
	{
		assert(!empty($aData));
		$keys = array_keys($aData[0]);
		assert(!empty($keys));

		$fields = $keys;
		foreach ($fields as &$field)
		{
			$field = ('`' === $field[0]) ? $field : '`'.$field.'`';
		}
		unset($field);

		if ($oOption->bIgnore())
		{
			$sql = 'INSERT IGNORE ';
		}
		else
		{
			$sql = 'INSERT ';
		}
		$sql .= 'INTO '.$sKind.' ('.implode(', ', $fields).') VALUES ';
		$values = array();
		foreach ($aData as $data)
		{
			$vs = array();
			foreach ($keys as $key)
			{
				$vs[] = Ko_Data_Mysql::SEscape($data[$key]);
			}
			$values[] = '("'.implode('", "', $vs).'")';
		}
		$sql .= implode(', ', $values);
		return $sql;
	}

	private function _sInsertSql($sKind, $aData, $aUpdate, $aChange, $oOption)
	{
		if ($oOption->bIgnore())
		{
			$sql = 'INSERT IGNORE ';
		}
		else
		{
			$sql = 'INSERT ';
		}
		$sql .= 'INTO '.$sKind.' SET '.$this->_sGetSetSql($aData, array());
		if (!empty($aUpdate) || !empty($aChange))
		{
			$sql .= ' ON DUPLICATE KEY UPDATE '.$this->_sGetSetSql($aUpdate, $aChange);
		}
		return $sql;
	}

	private function _sUpdateSql($sKind, $aUpdate, $aChange, $oOption)
	{
		$sql = 'UPDATE '.$sKind
			.' SET '.$this->_sGetSetSql($aUpdate, $aChange)
			.' '.$oOption->sWhereOrderLimit();
		return $sql;
	}

	private function _sDeleteSql($sKind, $oOption)
	{
		$sql = 'DELETE FROM '.$sKind
			.' '.$oOption->sWhereOrderLimit();
		return $sql;
	}
}

?>