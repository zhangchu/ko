<?php
/**
 * SQL
 *
 * @package ko\tool
 * @author zhangchu
 */

/**
 * 封装 SQL 语句实现
 */
class Ko_Tool_SQL
{
	const FUNC_GEOMFROMTEXT = 1;

	private $_sFields = '*';
	private $_sIndex = '';
	private $_sWhere = '';
	private $_sGroupBy = '';
	private $_sHaving = '';
	private $_sOrderBy = '';
	private $_iOffset = 0;
	private $_iLimit = 0;
	private $_bIgnore = false;
	private $_bCalcFoundRows = false;
	private $_bForceMaster = false;
	private $_bForceInactive = false;

	private $_iFoundRows = 0;

	/**
	 * @return Ko_Tool_SQL
	 */
	public function oClone()
	{
		$option = new self;
		$option->_sFields = $this->_sFields;
		$option->_sIndex = $this->_sIndex;
		$option->_sWhere = $this->_sWhere;
		$option->_sGroupBy = $this->_sGroupBy;
		$option->_sHaving = $this->_sHaving;
		$option->_sOrderBy = $this->_sOrderBy;
		$option->_iOffset = $this->_iOffset;
		$option->_iLimit = $this->_iLimit;
		$option->_bIgnore = $this->_bIgnore;
		$option->_bCalcFoundRows = $this->_bCalcFoundRows;
		$option->_bForceMaster = $this->_bForceMaster;
		$option->_bForceInactive = $this->_bForceInactive;
		$option->_iFoundRows = $this->_iFoundRows;
		return $option;
	}
	
	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oSelect()
	{
		$this->_sFields = Ko_Tool_Option::SEscapeWhere(func_get_args());
		return $this;
	}

	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oUseIndex($sIndex)
	{
		return $this->_oIndex($sIndex, 'USE');
	}

	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oIgnoreIndex($sIndex)
	{
		return $this->_oIndex($sIndex, 'IGNORE');
	}

	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oForceIndex($sIndex)
	{
		return $this->_oIndex($sIndex, 'FORCE');
	}

	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oWhere()
	{
		$this->_sWhere = Ko_Tool_Option::SEscapeWhere(func_get_args());
		return $this;
	}

	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oAnd()
	{
		$where = Ko_Tool_Option::SEscapeWhere(func_get_args());
		if (strlen($this->_sWhere))
		{
			$this->_sWhere = '('.$this->_sWhere.') AND ('.$where.')';
		}
		else
		{
			$this->_sWhere = $where;
		}
		return $this;
	}

	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oOr()
	{
		$where = Ko_Tool_Option::SEscapeWhere(func_get_args());
		if (strlen($this->_sWhere))
		{
			$this->_sWhere = '('.$this->_sWhere.') OR ('.$where.')';
		}
		else
		{
			$this->_sWhere = $where;
		}
		return $this;
	}

	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oGroupBy($sGroupBy)
	{
		$this->_sGroupBy = $sGroupBy;
		return $this;
	}

	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oHaving()
	{
		$this->_sHaving = Ko_Tool_Option::SEscapeWhere(func_get_args());
		return $this;
	}

	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oOrderBy($sOrderBy)
	{
		$this->_sOrderBy = $sOrderBy;
		return $this;
	}

	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oOffset($iOffset)
	{
		$this->_iOffset = max(0, $iOffset);
		return $this;
	}

	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oLimit($iLimit)
	{
		$this->_iLimit = max(0, $iLimit);
		return $this;
	}

	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oIgnore($bIgnore)
	{
		$this->_bIgnore = $bIgnore;
		return $this;
	}

	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oCalcFoundRows($bCalcFoundRows)
	{
		$this->_bCalcFoundRows = $bCalcFoundRows;
		return $this;
	}

	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oForceMaster($bForceMaster)
	{
		$this->_bForceMaster = $bForceMaster;
		return $this;
	}

	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oForceInactive($bForceInactive)
	{
		$this->_bForceInactive = $bForceInactive;
		return $this;
	}

	public function vSetFoundRows($iFoundRows)
	{
		assert($this->_bCalcFoundRows);
		$this->_iFoundRows = $iFoundRows;
	}

	/**
	 * @return int
	 */
	public function iGetFoundRows()
	{
		assert($this->_bCalcFoundRows);
		return $this->_iFoundRows;
	}
	
	/**
	 * @return string
	 */
	public function sWhereOrderLimit()
	{
		return $this->_sFormatWhere($this->_sWhere)
			.' '.$this->_sFormatOrder($this->_sOrderBy)
			.' '.$this->_sFormatLimit(0, $this->_iLimit);
	}

	/**
	 * @return string|array
	 */
	public function vSQL($sKind)
	{
		$sql = 'SELECT'.($this->_bCalcFoundRows ? ' SQL_CALC_FOUND_ROWS' : '').' '.$this->_sFields
			.' FROM '.$sKind
			.' '.$this->_sIndex
			.' '.$this->_sFormatWhere($this->_sWhere)
			.' '.$this->_sFormatGroup($this->_sGroupBy)
			.' '.$this->_sFormatHaving($this->_sHaving)
			.' '.$this->_sFormatOrder($this->_sOrderBy)
			.' '.$this->_sFormatLimit($this->_iOffset,
				$this->_bCalcFoundRows ? max(1, $this->_iLimit) : $this->_iLimit);
		return $this->_bCalcFoundRows ? array($sql, 'SELECT FOUND_ROWS()') : $sql;
	}

	public function sInsertMultiSql($sKind, $aData)
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

		if ($this->bIgnore())
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

	public function sInsertSql($sKind, $aData, $aUpdate, $aChange)
	{
		if ($this->bIgnore())
		{
			$sql = 'INSERT IGNORE ';
		}
		else
		{
			$sql = 'INSERT ';
		}
		$sql .= 'INTO '.$sKind.' SET '.self::_sGetSetSql($aData, array());
		if (!empty($aUpdate) || !empty($aChange))
		{
			$sql .= ' ON DUPLICATE KEY UPDATE '.self::_sGetSetSql($aUpdate, $aChange);
		}
		return $sql;
	}

	public function sUpdateSql($sKind, $aUpdate, $aChange)
	{
		$sql = 'UPDATE '.$sKind
			.' SET '.self::_sGetSetSql($aUpdate, $aChange)
			.' '.$this->sWhereOrderLimit();
		return $sql;
	}

	public function sDeleteSql($sKind)
	{
		$sql = 'DELETE FROM '.$sKind
			.' '.$this->sWhereOrderLimit();
		return $sql;
	}

	/**
	 * @return string
	 */
	public function sFields()
	{
		return $this->_sFields;
	}

	/**
	 * @return string
	 */
	public function sIndex()
	{
		return $this->_sIndex;
	}

	/**
	 * @return string
	 */
	public function sWhere()
	{
		return $this->_sWhere;
	}

	/**
	 * @return string
	 */
	public function sGroupBy()
	{
		return $this->_sGroupBy;
	}

	/**
	 * @return string
	 */
	public function sHaving()
	{
		return $this->_sHaving;
	}

	/**
	 * @return string
	 */
	public function sOrderBy()
	{
		return $this->_sOrderBy;
	}

	/**
	 * @return int
	 */
	public function iOffset()
	{
		return $this->_iOffset;
	}

	/**
	 * @return int
	 */
	public function iLimit()
	{
		return $this->_iLimit;
	}

	/**
	 * @return boolean
	 */
	public function bIgnore()
	{
		return $this->_bIgnore;
	}

	/**
	 * @return boolean
	 */
	public function bCalcFoundRows()
	{
		return $this->_bCalcFoundRows;
	}

	/**
	 * @return boolean
	 */
	public function bForceMaster()
	{
		return $this->_bForceMaster;
	}

	/**
	 * @return boolean
	 */
	public function bForceInactive()
	{
		return $this->_bForceInactive;
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

	private function _oIndex($sIndex, $sAction)
	{
		if (strlen($sIndex))
		{
			$this->_sIndex = $sAction.' INDEX ('.$sIndex.')';
		}
		else
		{
			$this->_sIndex = '';
		}
		return $this;
	}

	private static function _sGetSetSql($aUpdate, $aChange)
	{
		assert(is_array($aUpdate));
		assert(is_array($aChange));

		$set = array();
		foreach($aUpdate as $k => $v)
		{
			$k = ('`' === $k[0]) ? $k : '`'.$k.'`';
			$set[] = $k.' = '.self::_sGetSetValue($v);
		}
		foreach($aChange as $k => $v)
		{
			$k = ('`' === $k[0]) ? $k : '`'.$k.'`';
			$abs = abs($v);
			$set[] = $k.' = '.$k.' '.($v >= 0 ? '+' : '-').' '.$abs;
		}
		return implode(', ', $set);
	}

	private static function _sGetSetValue($value)
	{
		if (is_null($value))
		{
			$ret = 'NULL';
		}
		else if (is_array($value) && isset($value['function']))
		{
			switch ($value['function'])
			{
			case self::FUNC_GEOMFROMTEXT:
				$ret = 'GeomFromText("'.Ko_Data_Mysql::SEscape($value['value']).'")';
				break;
			default:
				assert(false);
				break;
			}
		}
		else
		{
			$ret = '"'.Ko_Data_Mysql::SEscape($value).'"';
		}
		return $ret;
	}
}

?>
