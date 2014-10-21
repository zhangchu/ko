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
	private $_sFields = '*';
	private $_sWhere = '';
	private $_sGroupBy = '';
	private $_sHaving = '';
	private $_sOrderBy = '';
	private $_iOffset = 0;
	private $_iLimit = 0;
	private $_bCalcFoundRows = false;
	private $_bForceMaster = false;

	private $_iFoundRows = 0;

	/**
	 * @return Ko_Tool_SQL
	 */
	public function oClone()
	{
		$option = new self;
		$option->_sFields = $this->_sFields;
		$option->_sWhere = $this->_sWhere;
		$option->_sGroupBy = $this->_sGroupBy;
		$option->_sHaving = $this->_sHaving;
		$option->_sOrderBy = $this->_sOrderBy;
		$option->_iOffset = $this->_iOffset;
		$option->_iLimit = $this->_iLimit;
		$option->_bCalcFoundRows = $this->_bCalcFoundRows;
		$option->_bForceMaster = $this->_bForceMaster;
		$option->_iFoundRows = $this->_iFoundRows;
		return $option;
	}
	
	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oSelect($sFields)
	{
		$this->_sFields = $sFields;
		return $this;
	}

	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oWhere()
	{
		$this->_sWhere = $this->_sEscapeWhere(func_get_args());
		return $this;
	}

	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oAnd()
	{
		$where = $this->_sEscapeWhere(func_get_args());
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
		$where = $this->_sEscapeWhere(func_get_args());
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
		$this->_sHaving = $this->_sEscapeWhere(func_get_args());
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
		$this->_iOffset = $iOffset;
		return $this;
	}

	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oLimit($iLimit)
	{
		$this->_iLimit = $iLimit;
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
			.' '.$this->_sFormatWhere($this->_sWhere)
			.' '.$this->_sFormatGroup($this->_sGroupBy)
			.' '.$this->_sFormatHaving($this->_sHaving)
			.' '.$this->_sFormatOrder($this->_sOrderBy)
			.' '.$this->_sFormatLimit($this->_iOffset, $this->_iLimit);
		return $this->_bCalcFoundRows ? array($sql, 'SELECT FOUND_ROWS()') : $sql;
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

	private function _sEscapeWhere($aArgs)
	{
		$iArgNum = count($aArgs);
		assert($iArgNum && false === strpos($aArgs[0], '\'') && false === strpos($aArgs[0], '"'));

		$where = $aArgs[0];
		$pos = 0;
		for ($i=1; $i<$iArgNum; ++$i)
		{
			$pos = strpos($where, '?', $pos);
			if (false === $pos)
			{
				break;
			}
			if (is_array($aArgs[$i]))
			{
				$escapeArg = array_map(array('Ko_Data_Mysql', 'SEscape'), $aArgs[$i]);
				$sReplace = '"'.implode('", "', $escapeArg).'"';
			}
			else
			{
				$sReplace = '"'.Ko_Data_Mysql::SEscape($aArgs[$i]).'"';
			}
			$where = substr($where, 0, $pos).$sReplace.substr($where, $pos + 1);
			$pos += strlen($sReplace);
		}
		return $where;
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