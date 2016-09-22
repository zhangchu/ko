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
	private $_aIceSetting = null;

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
		$pdo = $this->_oGetPDO($sKind, $iHintId, true, false);
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
		return $this->_aSingleQuery($sKind, $iHintId, $bMaster, $sSql, 0);
	}

	/**
	 * 多条sql查询
	 */
	public function aMultiQuery($sKind, $iHintId, $aSqls, $iCacheTime, $bMaster)
	{
		$ret = array();
		foreach ($aSqls as $k => $sSql) {
			$ret[$k] = $this->_aSingleQuery($sKind, $iHintId, $bMaster, $sSql, 0);
		}
		return $ret;
	}

	/**
	 * @param \PDO $pdo
	 */
	private function _aSingleQuery($sKind, $iHintId, $bMaster, $sSql, $iReconnect)
	{
		$pdo = $this->_oGetPDO($sKind, $iHintId, $bMaster, $iReconnect ? true : false);
		if (false === ($pdos = $pdo->query($sSql))) {
			$einfo = $pdo->errorInfo();
			if (2006 === $einfo[1] || false === $einfo[1]) {    //MySQL server has gone away
				if (Ko_Data_Mysql::MAX_RECONN > $iReconnect) {
					return $this->_aSingleQuery($sKind, $iHintId, $bMaster, $sSql, $iReconnect + 1);
				}
			}
			throw new Exception($einfo[2], $einfo[1]);
		}
		$data = $pdos->fetchAll(PDO::FETCH_ASSOC);
		return array('data' => $data,
			'rownum' => count($data),
			'insertid' => intval($pdo->lastInsertId()),
			'affectedrows' => $pdos->rowCount(),
		);
	}

	private function _oGetPDO($sKind, $iHintId, $bMaster, $bReConn)
	{
		switch (KO_MYSQL_PDO_SETTING) {
			case 'mfw-localcache':
				if (!is_null($this->_oInTransaction)) {
					if ($bReConn) {
						$this->_oInTransaction = $this->_oGetConn($sKind, $iHintId, $bMaster, $bReConn);
					}
					return $this->_oInTransaction;
				}
				return $this->_oGetConn($sKind, $iHintId, $bMaster, $bReConn);
			default:
				if ($bReConn) {
					$this->_oPDO = null;
				}
				if (is_null($this->_oPDO)) {
					list($host, $port) = explode(':', KO_DB_HOST);
					$dsn = 'mysql:dbname=' . KO_DB_NAME . ';host=' . $host;
					if (!empty($port)) {
						$dsn .= ';port=' . $port;
					}
					$this->_oPDO = new \PDO($dsn, KO_DB_USER, KO_DB_PASS);
				}
				return $this->_oPDO;
		}
	}

	/**
	 * @return \PDO
	 */
	private function _oGetConn($sKind, $iHintId, $bMaster, $bReConn)
	{
		if (is_null($this->_aIceSetting)) {
			$this->_aIceSetting = KCommon_localCache::get('mfw', 'ice_setting');
		}
		if (isset($this->_aIceSetting['kind_setting'][$sKind])) {
			$table_num = count($this->_aIceSetting['kind_setting'][$sKind]);
			if ($table_num) {
				$no = $iHintId % $table_num;
				$sid = $this->_aIceSetting['kind_setting'][$sKind][$no]['sid'];
				$db_name = $this->_aIceSetting['kind_setting'][$sKind][$no]['db_name'];
				if ($bMaster || empty($this->_aIceSetting['server_setting'][$sid]['slave'])) {
					$host = $this->_aIceSetting['server_setting'][$sid]['master']['host'];
					$port = $this->_aIceSetting['server_setting'][$sid]['master']['port'];
					$user = $this->_aIceSetting['server_setting'][$sid]['master']['user'];
					$passwd = $this->_aIceSetting['server_setting'][$sid]['master']['passwd'];
				} else {
					$sno = array_rand($this->_aIceSetting['server_setting'][$sid]['slave']);
					$host = $this->_aIceSetting['server_setting'][$sid]['slave'][$sno]['host'];
					$port = $this->_aIceSetting['server_setting'][$sid]['slave'][$sno]['port'];
					$user = $this->_aIceSetting['server_setting'][$sid]['slave'][$sno]['user'];
					$passwd = $this->_aIceSetting['server_setting'][$sid]['slave'][$sno]['passwd'];
				}
				$connKey = $sid . '_' . ($bMaster ? 1 : 0);
				if ($bReConn) {
					unset($this->_aConns[$connKey]);
				}
				if (!isset($this->_aConns[$connKey])) {
					$dsn = 'mysql:dbname=' . $db_name . ';host=' . $host . ';port=' . $port;
					$this->_aConns[$connKey] = new \PDO($dsn, $user, $passwd, array(
						\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES binary',
					));
				}
				return $this->_aConns[$connKey];
			}
		}
		assert(0);
	}
}
