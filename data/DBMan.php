<?php
/**
 * DBMan
 *
 * @package ko\data
 * @author zhangchu
 */

if (!defined('KO_DBMAN_TAG'))
{
	/**
	 * DBMan默认使用的分组
	 */
	define('KO_DBMAN_TAG', '');
}
/**
 * 封装 DBMan
 */
class Ko_Data_DBMan extends Ko_Data_KProxy
{
	private static $s_AInstance = array();

	protected function __construct ($sTag, $sExinfo = '')
	{
		KO_DEBUG >= 6 && Ko_Tool_Debug::VAddTmpLog('data/DBMan', '__construct:'.$sTag.'@'.$sExinfo);
		if (empty($sTag))
		{
			$sTag = KO_DBMAN_TAG;
		}
		parent::__construct('DBMan', $sTag, $sExinfo);
	}

	public static function OInstance($sName = '')
	{
		if (empty(self::$s_AInstance[$sName]))
		{
			self::$s_AInstance[$sName] = new self($sName);
		}
		return self::$s_AInstance[$sName];
	}

	/**
	 * 一条sql查询
	 */
	public function aSingleQuery($sKind, $iHintId, $sSql, $iCacheTime, $bMaster)
	{
		$oCtx = $this->_aGetCacheContext($iCacheTime);
		$aPara = array(
			'kind' => $sKind,
			'hintId' => intval($iHintId),
			'sql' => $sSql,
			'convert' => true,
			'master' => $bMaster ? true : false,
			);
		$oReturn = $this->_oProxy->invoke('sQuery', $aPara, $oCtx);
		return $this->_aFormatResult($oReturn);
	}

	/**
	 * 多条sql查询
	 */
	public function aMultiQuery($sKind, $iHintId, $aSqls, $iCacheTime, $bMaster)
	{
		$oCtx = $this->_aGetCacheContext($iCacheTime);
		$aPara = array(
			'kind' => $sKind,
			'hintId' => intval($iHintId),
			'sqls' => $aSqls,
			'convert' => true,
			'master' => $bMaster ? true : false,
			);
		$oReturn = $this->_oProxy->invoke('mQuery', $aPara, $oCtx);
		return $this->_aFormatMResult($oReturn);
	}

	private function _aFormatResult($oSqlRes)
	{
		$data = array();
		$insertId = isset($oSqlRes['insertId']) ? intval($oSqlRes['insertId']) : 0;
		$affectedRowNumber = isset($oSqlRes['affectedRowNumber']) ? intval($oSqlRes['affectedRowNumber']) : 0;
		if (isset($oSqlRes['rows']))
		{
			$rownum = count($oSqlRes['rows']);
			foreach($oSqlRes['rows'] as $i=>$row)
			{
				$array = array();
				foreach($oSqlRes['fields'] as $id=>$field)
				{
					$array[$field] = is_object($row[$id]) ? strval($row[$id]) : $row[$id];
				}
				$data[$i] = $array;
			}
		}
		else
		{
			$rownum = 0;
		}
		KO_DEBUG >= 7 && Ko_Tool_Debug::VAddTmpLog('data/DBMan', '_aFormatResult:insertid_'.$insertId.':affectedrows_'.$affectedRowNumber.':rownum_'.$rownum);
		return array('data' => $data,
					'rownum' => $rownum,
					'insertid' => $insertId,
					'affectedrows' => $affectedRowNumber);
	}

	private function _aFormatMResult($oMsqlRes)
	{
		$aResult = array();
		foreach ($oMsqlRes['results'] as $sqlres)
		{
			$aResult[] = $this->_aFormatResult($sqlres);
		}
		return $aResult;
	}
}

?>