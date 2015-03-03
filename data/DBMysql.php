<?php
/**
 * DBMysql
 *
 * @package ko\data
 * @author zhangchu
 */

class Ko_Data_DBMysql
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

	/**
	 * 一条sql查询
	 */
	public function aSingleQuery($sKind, $iHintId, $sSql, $iCacheTime, $bMaster)
	{
		$mysql = $this->_oGetMysql();
		if (!$mysql->bQuery($sSql))
		{
			throw new Exception($mysql->sError(), $mysql->iErrno());
		}
		$data = array();
		$rownum = 0;
		while ($info = $mysql->aFetchAssoc())
		{
			$rownum ++;
			$data[] = $info;
		}
		return array('data' => $data,
			'rownum' => $rownum,
			'insertid' => $mysql->iInsertId(),
			'affectedrows' => $mysql->iAffectedRows(),
			);
	}

	/**
	 * 多条sql查询
	 */
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