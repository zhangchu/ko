<?php
/**
 * MONGO
 *
 * @package ko\tool
 * @author zhangchu
 */

/**
 * 封装 MONGODB 查询实现
 */
class Ko_Tool_MONGO
{
	private $_aFields = array();
	private $_aWhere = array();
	private $_aOrderBy = array();
	private $_iOffset = 0;
	private $_iLimit = 0;
	private $_bCalcFoundRows = false;
	private $_bForceMaster = false;
	private $_bUpsert = false;
	private $_aCommand = array();

	private $_iFoundRows = 0;

	/**
	 * @return Ko_Tool_MONGO
	 */
	public function oClone()
	{
		$option = new self;
		$option->_aFields = $this->_aFields;
		$option->_aWhere = $this->_aWhere;
		$option->_aOrderBy = $this->_aOrderBy;
		$option->_iOffset = $this->_iOffset;
		$option->_iLimit = $this->_iLimit;
		$option->_bCalcFoundRows = $this->_bCalcFoundRows;
		$option->_bForceMaster = $this->_bForceMaster;
		$option->_bUpsert = $this->_bUpsert;
		$option->_aCommand = $this->_aCommand;
		$option->_iFoundRows = $this->_iFoundRows;
		return $option;
	}
	
	/**
	 * @return Ko_Tool_MONGO 返回 $this
	 */
	public function oSelect($aFields)
	{
		$this->_aFields = $aFields;
		return $this;
	}

	/**
	 * @return Ko_Tool_MONGO 返回 $this
	 */
	public function oWhere($aWhere)
	{
		$this->_aWhere = $aWhere;
		return $this;
	}

	/**
	 * @return Ko_Tool_MONGO 返回 $this
	 */
	public function oAnd($aWhere)
	{
		if (empty($this->_aWhere))
		{
			$this->_aWhere = $aWhere;
		}
		else
		{
			$this->_aWhere = array('$and' => array($this->_aWhere, $aWhere));
		}
		return $this;
	}

	/**
	 * @return Ko_Tool_MONGO 返回 $this
	 */
	public function oOr($aWhere)
	{
		if (empty($this->_aWhere))
		{
			$this->_aWhere = $aWhere;
		}
		else
		{
			$this->_aWhere = array('$or' => array($this->_aWhere, $aWhere));
		}
		return $this;
	}

	/**
	 * @return Ko_Tool_MONGO 返回 $this
	 */
	public function oOrderBy($aOrderBy)
	{
		$this->_aOrderBy = $aOrderBy;
		return $this;
	}

	/**
	 * @return Ko_Tool_MONGO 返回 $this
	 */
	public function oOffset($iOffset)
	{
		$this->_iOffset = $iOffset;
		return $this;
	}

	/**
	 * @return Ko_Tool_MONGO 返回 $this
	 */
	public function oLimit($iLimit)
	{
		$this->_iLimit = $iLimit;
		return $this;
	}

	/**
	 * @return Ko_Tool_MONGO 返回 $this
	 */
	public function oCalcFoundRows($bCalcFoundRows)
	{
		$this->_bCalcFoundRows = $bCalcFoundRows;
		return $this;
	}

	/**
	 * @return Ko_Tool_MONGO 返回 $this
	 */
	public function oForceMaster($bForceMaster)
	{
		$this->_bForceMaster = $bForceMaster;
		return $this;
	}

	/**
	 * @return Ko_Tool_MONGO 返回 $this
	 */
	public function oUpsert($bUpsert)
	{
		$this->_bUpsert = $bUpsert;
		return $this;
	}

	/**
	 * @return Ko_Tool_MONGO 返回 $this
	 */
	public function oCommand($aCommand)
	{
		$this->_aCommand = $aCommand;
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
	 * @return array
	 */
	public function aFields()
	{
		return $this->_aFields;
	}

	/**
	 * @return array
	 */
	public function aWhere()
	{
		return $this->_aWhere;
	}

	/**
	 * @return array
	 */
	public function aOrderBy()
	{
		return $this->_aOrderBy;
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

	/**
	 * @return boolean
	 */
	public function bUpsert()
	{
		return $this->_bUpsert;
	}

	/**
	 * @return array
	 */
	public function aCommand()
	{
		return $this->_aCommand;
	}
}

?>