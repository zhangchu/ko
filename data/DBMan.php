<?php
/**
 * DBMan
 *
 * @package ko
 * @subpackage data
 * @author zhangchu
 */

/**
 * 封装 DBMan 的接口
 */
interface IKo_Data_DBMan
{
	public static function OInstance($sName = '');							//instance
	public function aSingleQuery($sKind, $iHintId, $sSql, $iCacheTime, $bMaster);		//一条sql查询
	public function aMultiQuery($sKind, $iHintId, $aSqls, $icacheTime, $bMaster);		//多条sql查询
}

/**
 * 封装 DBMan 的实现
 */
class Ko_Data_DBMan extends Ko_Data_KProxy implements IKo_Data_DBMan
{
	private static $s_AInstance = array();

	protected function __construct ($sTag, $sExinfo = '')
	{
		KO_DEBUG >= 6 && Ko_Tool_Debug::VAddTmpLog('data/DBMan', '__construct:'.$sTag.'@'.$sExinfo);
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

	public function aSingleQuery($sKind, $iHintId, $sSql, $iCacheTime, $bMaster)
	{
		$oCtx = $this->_aGetCacheContext($iCacheTime);
		$aPara = array(
			'kind' => $sKind,
			'hintId' => intval($iHintId),
			'sql' => $sSql,
			'convert' => true,
			'MASTER' => $bMaster ? true : false,
			);
		$oReturn = $this->_oProxy->invoke('sQuery', $aPara, $oCtx);
		return $this->_aFormatResult($oReturn);
	}

	public function aMultiQuery($sKind, $iHintId, $aSqls, $iCacheTime, $bMaster)
	{
		$oCtx = $this->_aGetCacheContext($iCacheTime);
		$aPara = array(
			'kind' => $sKind,
			'hintId' => intval($iHintId),
			'sqls' => $aSqls,
			'convert' => true,
			'MASTER' => $bMaster ? true : false,
			);
		$oReturn = $this->_oProxy->invoke('mQuery', $aPara, $oCtx);
		return $this->_aFormatMResult($oReturn);
	}

	private function _aFormatResult($oSqlRes)
	{
		$data = array();
		$insertId = intval($oSqlRes['insertId']);
		$affectedRowNumber = intval($oSqlRes['affectedRowNumber']);
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