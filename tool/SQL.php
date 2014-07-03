<?php
/**
 * SQL
 *
 * @package ko\tool
 * @author zhangchu
 */

/**
 * 封装 SQL 语句接口
 */
interface IKo_Tool_SQL
{
	/**
	 * @return Ko_Tool_SQL
	 */
	public function oClone();
	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oSelect($sFields);
	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oWhere();
	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oAnd();
	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oOr();
	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oGroupBy($sGroupBy);
	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oHaving();
	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oOrderBy($sOrderBy);
	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oOffset($iOffset);
	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oLimit($iLimit);
	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oCalcFoundRows($bCalcFoundRows);
	/**
	 * @return Ko_Tool_SQL 返回 $this
	 */
	public function oForceMaster($bForceMaster);

	/**
	 * @return string
	 */
	public function sFields();
	/**
	 * @return string
	 */
	public function sWhere();
	/**
	 * @return string
	 */
	public function sGroupBy();
	/**
	 * @return string
	 */
	public function sHaving();
	/**
	 * @return string
	 */
	public function sOrderBy();
	/**
	 * @return int
	 */
	public function iOffset();
	/**
	 * @return int
	 */
	public function iLimit();
	/**
	 * @return boolean
	 */
	public function bCalcFoundRows();
	/**
	 * @return boolean
	 */
	public function bForceMaster();

	public function vSetFoundRows($iFoundRows);
	/**
	 * @return int
	 */
	public function iGetFoundRows();
}

/**
 * 封装 SQL 语句实现
 */
class Ko_Tool_SQL implements IKo_Tool_SQL
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
	 * @return Ko_Tool_SQL
	 */
	public function oSelect($sFields)
	{
		$this->_sFields = $sFields;
		return $this;
	}

	/**
	 * @return Ko_Tool_SQL
	 */
	public function oWhere()
	{
		$this->_sWhere = $this->_sEscapeWhere(func_get_args());
		return $this;
	}

	/**
	 * @return Ko_Tool_SQL
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
	 * @return Ko_Tool_SQL
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
	 * @return Ko_Tool_SQL
	 */
	public function oGroupBy($sGroupBy)
	{
		$this->_sGroupBy = $sGroupBy;
		return $this;
	}

	/**
	 * @return Ko_Tool_SQL
	 */
	public function oHaving()
	{
		$this->_sHaving = $this->_sEscapeWhere(func_get_args());
		return $this;
	}

	/**
	 * @return Ko_Tool_SQL
	 */
	public function oOrderBy($sOrderBy)
	{
		$this->_sOrderBy = $sOrderBy;
		return $this;
	}

	/**
	 * @return Ko_Tool_SQL
	 */
	public function oOffset($iOffset)
	{
		$this->_iOffset = $iOffset;
		return $this;
	}

	/**
	 * @return Ko_Tool_SQL
	 */
	public function oLimit($iLimit)
	{
		$this->_iLimit = $iLimit;
		return $this;
	}

	/**
	 * @return Ko_Tool_SQL
	 */
	public function oCalcFoundRows($bCalcFoundRows)
	{
		$this->_bCalcFoundRows = $bCalcFoundRows;
		return $this;
	}

	/**
	 * @return Ko_Tool_SQL
	 */
	public function oForceMaster($bForceMaster)
	{
		$this->_bForceMaster = $bForceMaster;
		return $this;
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
}

?>