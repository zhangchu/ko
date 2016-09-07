<?php
/**
 * DBPDO
 *
 * @package ko\data
 * @author zhangchu
 */

if (!defined('KO_MYSQL_PDO_SETTING')) {
	define('KO_MYSQL_PDO_SETTING', '');
}

/**
 * 使用PDO的方式来连接Mysql
 */
class Ko_Data_DBPDO
{
	private static $s_AInstance = array();

	private $_oPDO;

	private $_aConns = array();

	/**
	 * @var \PDO
	 */
	private $_oInTransaction = null;

	protected function __construct($sTag)
	{
	}

	public static function OInstance($sName = '')
	{
		if (empty(self::$s_AInstance[$sName])) {
			self::$s_AInstance[$sName] = new self($sName);
		}
		return self::$s_AInstance[$sName];
	}

	public function bBeginTransaction($sKind, $iHintId)
	{
		assert(is_null($this->_oInTransaction));
		$pdo = $this->_oGetPDO($sKind, $iHintId, true);
		$ret = $pdo->beginTransaction();
		if ($ret) {
			$this->_oInTransaction = $pdo;
		}
		return $ret;
	}

	public function bCommit()
	{
		assert(!is_null($this->_oInTransaction));
		$ret = $this->_oInTransaction->commit();
		if ($ret) {
			$this->_oInTransaction = null;
		}
		return $ret;
	}

	public function bRollBack()
	{
		assert(!is_null($this->_oInTransaction));
		$ret = $this->_oInTransaction->rollBack();
		if ($ret) {
			$this->_oInTransaction = null;
		}
		return $ret;
	}

	/**
	 * 一条sql查询
	 */
	public function aSingleQuery($sKind, $iHintId, $sSql, $iCacheTime, $bMaster)
	{
		$pdo = $this->_oGetPDO($sKind, $iHintId, $bMaster);
		return $this->_aSingleQuery($pdo, $sSql);
	}

	/**
	 * 多条sql查询
	 */
	public function aMultiQuery($sKind, $iHintId, $aSqls, $iCacheTime, $bMaster)
	{
		$ret = array();
		$pdo = $this->_oGetPDO($sKind, $iHintId, $bMaster);
		foreach ($aSqls as $k => $sSql) {
			$ret[$k] = $this->_aSingleQuery($pdo, $sSql);
		}
		return $ret;
	}

	/**
	 * @param \PDO $pdo
	 */
	private function _aSingleQuery($pdo, $sSql)
	{
		if (false === ($pdos = $pdo->query($sSql))) {
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

	private function _oGetPDO($sKind, $iHintId, $bMaster)
	{
		switch (KO_MYSQL_PDO_SETTING) {
			case 'mfw-localcache':
				if (!is_null($this->_oInTransaction)) {
					return $this->_oInTransaction;
				}
				return $this->_oGetConn($sKind, $iHintId, $bMaster);
			default:
				if (is_null($this->_oPDO)) {
					list($host, $port) = explode(':', KO_DB_HOST);
					$dsn = 'mysql:dbname=' . KO_DB_NAME . ';host=' . $host;
					if (!empty($port)) {
						$dsn .= ';port=' . $port;
					}
					$this->_oPDO = new PDO($dsn, KO_DB_USER, KO_DB_PASS);
				}
				return $this->_oPDO;
		}
	}

	/**
	 * @return \PDO
	 */
	private function _oGetConn($sKind, $iHintId, $bMaster)
	{
		$ice_settings = KCommon_localCache::get('mfw', 'ice_setting');
		if (isset($ice_settings['kind_setting'][$sKind])) {
			$table_num = count($ice_settings['kind_setting'][$sKind]);
			if ($table_num) {
				$no = $iHintId % $table_num;
				$sid = $ice_settings['kind_setting'][$sKind][$no]['sid'];
				$db_name = $ice_settings['kind_setting'][$sKind][$no]['db_name'];
				if ($bMaster || empty($ice_settings['server_setting'][$sid]['slave'])) {
					$host = $ice_settings['server_setting'][$sid]['master']['host'];
					$port = $ice_settings['server_setting'][$sid]['master']['port'];
					$user = $ice_settings['server_setting'][$sid]['master']['user'];
					$passwd = $ice_settings['server_setting'][$sid]['master']['passwd'];
				} else {
					$sno = array_rand($ice_settings['server_setting'][$sid]['slave']);
					$host = $ice_settings['server_setting'][$sid]['slave'][$sno]['host'];
					$port = $ice_settings['server_setting'][$sid]['slave'][$sno]['port'];
					$user = $ice_settings['server_setting'][$sid]['slave'][$sno]['user'];
					$passwd = $ice_settings['server_setting'][$sid]['slave'][$sno]['passwd'];
				}
				$connKey = $sid . '_' . ($bMaster ? 1 : 0);
				if (!isset($this->_aConns[$connKey])) {
					$dsn = 'mysql:dbname=' . $db_name . ';host=' . $host . ';port=' . $port;
					$this->_aConns[$connKey] = new \PDO($dsn, $user, $passwd);
				}
				return $this->_aConns[$connKey];
			}
		}
		assert(0);
	}
}
