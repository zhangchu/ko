<?php
/**
 * DBMysql
 *
 * @package ko
 * @subpackage data
 * @author zhangchu
 */

class Ko_Data_DBMysql implements IKo_Data_DBMan
{
	private static $s_AInstance = array();

	private $_oMysql;

	protected function __construct ($sTag)
	{
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
		if (!$this->_oGetMysql()->bQuery($sSql))
		{
			throw new Exception($this->_oGetMysql()->sError(), $this->_oGetMysql()->iErrno());
		}
		$data = array();
		$rownum = 0;
		while ($info = $this->_oGetMysql()->aFetchAssoc())
		{
			$rownum ++;
			$data[] = $info;
		}
		return array('data' => $data,
			'rownum' => $rownum,
			'insertid' => $this->_oGetMysql()->iInsertId(),
			'affectedrows' => $this->_oGetMysql()->iAffectedRows(),
			);
	}

	public function aMultiQuery($sKind, $iHintId, $aSqls, $iCacheTime, $bMaster)
	{
		$ret = array();
		foreach ($aSqls as $k => $sSql)
		{
			$ret[$k] = $this->aSingleQuery($sKind, $iHintId, $sSql, $iCacheTime, $bMaster);
		}
		return $ret;
	}

	private function _oGetMysql()
	{
		if (is_null($this->_oMysql))
		{
			$this->_oMysql = Ko_Data_Mysql::OInstance(KO_DB_HOST, KO_DB_USER, KO_DB_PASS, KO_DB_NAME);
		}
		return $this->_oMysql;
	}
}

?>