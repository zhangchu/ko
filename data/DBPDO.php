<?php
/**
 * DBPDO
 *
 * @package ko\data
 * @author zhangchu
 */

/**
 * 使用PDO的方式来连接Mysql
 */
class Ko_Data_DBPDO
{
	private static $s_AInstance = array();

	private $_oPDO;
	
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
		$pdo = $this->_oGetPDO();
		if (false === ($pdos = $pdo->query($sSql)))
		{
			$einfo = $pdo->errorInfo();
			throw new Exception($einfo[2], $einfo[1]);
		}
		$data = $pdos->fetchAll(PDO::FETCH_ASSOC);
		return array('data' => $data,
			'rownum' => count($data),
			'insertid' => intval($pdo->lastInsertId()),
			'affectedrows' => $pdos->rowCount(),
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

	private function _oGetPDO()
	{
		if (is_null($this->_oPDO))
		{
			$dsn = 'mysql:dbname='.KO_DB_NAME.';host='.KO_DB_HOST;
			$this->_oPDO = new PDO($dsn, KO_DB_USER, KO_DB_PASS);
		}
		return $this->_oPDO;
	}
}
