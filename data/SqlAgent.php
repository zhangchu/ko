<?php
/**
 * SqlAgent
 *
 * @package ko
 * @subpackage data
 * @author zhangchu
 */

/**
 * 封装常用的 SQL 语句接口，insert/update/delete/select
 */
interface IKo_Data_SqlAgent
{
	public static function OInstance($sTag);
	public function aInsert($sKind, $iHintId, $aData, $aUpdate, $aChange);
	public function iUpdate($sKind, $iHintId, $aUpdate, $aChange, $oOption);
	public function iDelete($sKind, $iHintId, $oOption);
	public function aSelect($sKind, $iHintId, $oOption, $iCacheTime, $bMaster);
}

/**
 * 封装常用的 SQL 语句实现，insert/update/delete/select
 */
class Ko_Data_SqlAgent implements IKo_Data_SqlAgent
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

	public function aInsert($sKind, $iHintId, $aData, $aUpdate, $aChange)
	{
		$sql = $this->_sInsertSql($sKind, $aData, $aUpdate, $aChange);
		return $this->_aQuery($sKind, $iHintId, $sql);
	}

	public function iUpdate($sKind, $iHintId, $aUpdate, $aChange, $oOption)
	{
		$sql = $this->_sUpdateSql($sKind, $aUpdate, $aChange, $oOption->sWhere(), $oOption->sOrderBy(), $oOption->iLimit());
		$info = $this->_aQuery($sKind, $iHintId, $sql);
		return $info['affectedrows'];
	}

	public function iDelete($sKind, $iHintId, $oOption)
	{
		$sql = $this->_sDeleteSql($sKind, $oOption->sWhere(), $oOption->sOrderBy(), $oOption->iLimit());
		$info = $this->_aQuery($sKind, $iHintId, $sql);
		return $info['affectedrows'];
	}

	public function aSelect($sKind, $iHintId, $oOption, $iCacheTime, $bMaster)
	{
		if ($oOption->bCalcFoundRows())
		{
			$sqls = array();
			$sqls[0] = $this->_sSelectSql($sKind, $oOption->sWhere(), $oOption->sOrderBy(),
				$oOption->iOffset(), max(1, $oOption->iLimit()), $oOption->sFields(),
				$oOption->sGroupBy(), $oOption->sHaving(), true);
			$sqls[1] = 'SELECT FOUND_ROWS()';
			$info = $this->_aQuery($sKind, $iHintId, $sqls, $iCacheTime, $bMaster);
			$oOption->vSetFoundRows($info[1]['data'][0]['FOUND_ROWS()']);
			return $info[0]['data'];
		}
		else
		{
			$sql = $this->_sSelectSql($sKind, $oOption->sWhere(), $oOption->sOrderBy(),
				$oOption->iOffset(), $oOption->iLimit(), $oOption->sFields(),
				$oOption->sGroupBy(), $oOption->sHaving(), false);
			$info = $this->_aQuery($sKind, $iHintId, $sql, $iCacheTime, $bMaster);
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
			if (is_array($v))
			{
				assert(1 === count($v));
				$set[] = $k.' = '.$v[0];
			}
			else
			{
				$set[] = $k.' = "'.Ko_Data_Mysql::SEscape($v).'"';
			}
		}
		foreach($aChange as $k => $v)
		{
			if (is_numeric($v))
			{
				$abs = abs($v);
				$set[] = $k.' = '.$k.' '.($v >= 0 ? '+' : '-').' '.$abs;
			}
			else
			{
				$set[] = $k.' = CONCAT('.$k.', "'.Ko_Data_Mysql::SEscape($v).'")';
			}
		}
		return implode(', ', $set);
	}

	private function _sInsertSql($sKind, $aData, $aUpdate, $aChange)
	{
		$sql = 'INSERT INTO '.$sKind.' SET '.$this->_sGetSetSql($aData, array());
		if (!empty($aUpdate) || !empty($aChange))
		{
			$sql .= ' ON DUPLICATE KEY UPDATE '.$this->_sGetSetSql($aUpdate, $aChange);
		}
		return $sql;
	}

	private function _sUpdateSql($sKind, $aUpdate, $aChange, $sWhere, $sOrder, $iLimit)
	{
		$sql = 'UPDATE '.$sKind.' SET '.$this->_sGetSetSql($aUpdate, $aChange);
		$sql .= ' '.$this->_sFormatWhere($sWhere);
		$sql .= ' '.$this->_sFormatOrder($sOrder);
		$sql .= ' '.$this->_sFormatLimit(0, $iLimit);
		return $sql;
	}

	private function _sDeleteSql($sKind, $sWhere, $sOrder, $iLimit)
	{
		$sql = 'DELETE FROM '.$sKind;
		$sql .= ' '.$this->_sFormatWhere($sWhere);
		$sql .= ' '.$this->_sFormatOrder($sOrder);
		$sql .= ' '.$this->_sFormatLimit(0, $iLimit);
		return $sql;
	}

	private function _sSelectSql($sKind, $sWhere, $sOrder, $iStart, $iNum, $sFields, $sGroup, $sHaving, $bFoundrows)
	{
		if ($bFoundrows)
		{
			$sql = 'SELECT SQL_CALC_FOUND_ROWS '.$sFields.' FROM '.$sKind;
		}
		else
		{
			$sql = 'SELECT '.$sFields.' FROM '.$sKind;
		}
		$sql .= ' '.$this->_sFormatWhere($sWhere);
		$sql .= ' '.$this->_sFormatGroup($sGroup);
		$sql .= ' '.$this->_sFormatHaving($sHaving);
		$sql .= ' '.$this->_sFormatOrder($sOrder);
		$sql .= ' '.$this->_sFormatLimit($iStart, $iNum);
		return $sql;
	}

	private function _sFormatWhere($sWhere)
	{
		$sWhere = trim($sWhere);
		if ('' != $sWhere && strtoupper(substr($sWhere, 0, 6)) != 'WHERE ')
		{
			$sWhere = 'WHERE '.$sWhere;
		}
		return $sWhere;
	}

	private function _sFormatGroup($sGroup)
	{
		$sGroup = trim($sGroup);
		if ('' != $sGroup && strtoupper(substr($sGroup, 0, 6)) != 'GROUP ')
		{
			$sGroup = 'GROUP BY '.$sGroup;
		}
		return $sGroup;
	}

	private function _sFormatHaving($sHaving)
	{
		$sHaving = trim($sHaving);
		if ('' != $sHaving && strtoupper(substr($sHaving, 0, 7)) != 'HAVING ')
		{
			$sHaving = 'HAVING '.$sHaving;
		}
		return $sHaving;
	}

	private function _sFormatOrder($sOrder)
	{
		$sOrder = trim($sOrder);
		if ('' != $sOrder && strtoupper(substr($sOrder, 0, 6)) != 'ORDER ')
		{
			$sOrder = 'ORDER BY '.$sOrder;
		}
		return $sOrder;
	}

	private function _sFormatLimit($iStart, $iNum)
	{
		$iStart = intval($iStart);
		$iNum = intval($iNum);
		if ($iStart)
		{
			return 'LIMIT '.$iStart.', '.$iNum;
		}
		if ($iNum)
		{
			return 'LIMIT '.$iNum;
		}
		return '';
	}
}

?>