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
	private $_bInTransaction = false;
	private $_bForcePDO = false;

	private $_oEngine;
	private $_oPDOEngine;

	protected function __construct($sTag)
	{
		$this->_sTag = $sTag;
	}

	public static function OInstance($sTag = '')
	{
		if (empty(self::$s_aInstance[$sTag])) {
			self::$s_aInstance[$sTag] = new self($sTag);
		}
		return self::$s_aInstance[$sTag];
	}

	public function bBeginTransaction($sKind, $iHintId)
	{
		assert(!$this->_bInTransaction);
		$this->_bInTransaction = true;
		$ret = $this->_oGetEngine()->bBeginTransaction($sKind, $iHintId);
		if (!$ret) {
			$this->_bInTransaction = false;
		}
		return $ret;
	}

	public function bCommit()
	{
		assert($this->_bInTransaction);
		$ret = $this->_oGetEngine()->bCommit();
		if ($ret) {
			$this->_bInTransaction = false;
		}
		return $ret;
	}

	public function bRollBack()
	{
		assert($this->_bInTransaction);
		$ret = $this->_oGetEngine()->bRollBack();
		if ($ret) {
			$this->_bInTransaction = false;
		}
		return $ret;
	}

	public function vForcePDO($bEnable)
	{
		assert($this->_bForcePDO === (!$bEnable));
		$this->_bForcePDO = $bEnable;
	}

	/**
	 * @param \Ko_Tool_SQL $oOption
	 */
	public function aInsertMulti($sKind, $iHintId, $aData, $oOption)
	{
		$sql = $oOption->sInsertMultiSql($sKind, $aData);
		return $this->_aQuery($sKind, $iHintId, $sql, 0, true);
	}

	/**
	 * @param \Ko_Tool_SQL $oOption
	 */
	public function aInsert($sKind, $iHintId, $aData, $aUpdate, $aChange, $oOption)
	{
		$sql = $oOption->sInsertSql($sKind, $aData, $aUpdate, $aChange);
		return $this->_aQuery($sKind, $iHintId, $sql, 0, true);
	}

	/**
	 * @param \Ko_Tool_SQL $oOption
	 */
	public function iUpdate($sKind, $iHintId, $aUpdate, $aChange, $oOption)
	{
		$sql = $oOption->sUpdateSql($sKind, $aUpdate, $aChange);
		$info = $this->_aQuery($sKind, $iHintId, $sql, 0, true);
		return $info['affectedrows'];
	}

	/**
	 * @param \Ko_Tool_SQL $oOption
	 */
	public function iDelete($sKind, $iHintId, $oOption)
	{
		$sql = $oOption->sDeleteSql($sKind);
		$info = $this->_aQuery($sKind, $iHintId, $sql, 0, true);
		return $info['affectedrows'];
	}

	/**
	 * @param \Ko_Tool_SQL $oOption
	 */
	public function aSelect($sKind, $iHintId, $oOption, $iCacheTime, $bMaster)
	{
		$sql = $oOption->vSQL($sKind);
		$info = $this->_aQuery($sKind, $iHintId, $sql, $iCacheTime, $bMaster);
		if ($oOption->bCalcFoundRows()) {
			$oOption->vSetFoundRows($info[1]['data'][0]['FOUND_ROWS()']);
			return $info[0]['data'];
		} else {
			return $info['data'];
		}
	}

	//////////////////////////// 工具函数 ////////////////////////////

	/**
	 * @return Ko_Data_DBMan|Ko_Data_DBMysql|Ko_Data_DBPDO
	 */
	private function _oGetEngine()
	{
		if ($this->_bInTransaction || $this->_bForcePDO) {
			if (is_null($this->_oPDOEngine)) {
				$this->_oPDOEngine = Ko_Data_DBPDO::OInstance($this->_sTag);
			}
			return $this->_oPDOEngine;
		} else {
			if (is_null($this->_oEngine)) {
				switch (KO_DB_ENGINE) {
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
	}

	private function _aQuery($sKind, $iHintId, $vSql, $iCacheTime = 0, $bMaster = false)
	{
		if (is_array($vSql)) {
			KO_DEBUG >= 1 && Ko_Tool_Debug::VAddTmpLog('data/SqlAgent',
				'M:' . $sKind . ':' . $iHintId . ':' . $iCacheTime . ':' . implode(':', $vSql));
			$ret = $this->_oGetEngine()->aMultiQuery($sKind, $iHintId, $vSql, $iCacheTime, $bMaster);
		} else {
			KO_DEBUG >= 1 && Ko_Tool_Debug::VAddTmpLog('data/SqlAgent',
				'S:' . $sKind . ':' . $iHintId . ':' . $iCacheTime . ':' . $vSql);
			$ret = $this->_oGetEngine()->aSingleQuery($sKind, $iHintId, $vSql, $iCacheTime, $bMaster);
		}
		return $ret;
	}
}

?>