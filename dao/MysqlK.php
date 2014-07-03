<?php
/**
 * MysqlK
 *
 * @package ko\dao
 * @author zhangchu
 */

//define('KO_DB_HOST', '192.168.0.140');
//define('KO_DB_USER', 'dev');
//define('KO_DB_PASS', 'dev2008');
//define('KO_DB_NAME', 'dev_config');
//include_once('../ko.class.php');

/**
 * 数据表分表直连操作类实现，使用了 server_setting, kind_setting, table_setting 来决定分表位置
 */
class Ko_Dao_MysqlK implements IKo_Dao_Table
{
	private $_aServerList = array();
	private $_aTableList = array();

	protected function __construct($sKind, $bSlave = false)
	{
		$oMysql = Ko_Data_Mysql::OInstance(KO_DB_HOST, KO_DB_USER, KO_DB_PASS, KO_DB_NAME);

		$allmaster = array();
		$sql = 'select * from server_setting where master_sid = 0';
		$oMysql->bQuery($sql);
		while ($info = $oMysql->aFetchAssoc())
		{
			$allmaster[$info['sid']] = $info;
		}
		$allslave = array();
		if ($bSlave)
		{
			$sql = 'select * from server_setting where master_sid != 0 and active != 0';
			$oMysql->bQuery($sql);
			while ($info = $oMysql->aFetchAssoc())
			{
				$allslave[$info['master_sid']][] = $info;
			}
		}

		$sql = 'select * from table_setting where kind = "'.Ko_Data_Mysql::SEscape($sKind).'" order by no';
		$oMysql->bQuery($sql);
		while ($info = $oMysql->aFetchAssoc())
		{
			$this->_aTableList[] = $info;
			$sid = $info['sid'];
			if ($bSlave && !empty($allslave[$sid]))
			{
				$this->_aServerList[$sid] = $allslave[$sid][0];
			}
			else
			{
				$this->_aServerList[$sid] = $allmaster[$sid];
			}
		}
		assert(!empty($this->_aTableList));
	}
	
	public static function OInstance($sKind, $bSlave = false)
	{
		return new self($sKind, $bSlave);
	}

	/**
	 * @return int
	 */
	public function iTableCount()
	{
		return count($this->_aTableList);
	}

	/**
	 * @return Ko_Data_Mysql
	 */
	public function oConnectDB($no)
	{
		$sid = $this->_aTableList[$no]['sid'];
		return Ko_Data_Mysql::OInstance($this->_aServerList[$sid]['host'].':'.$this->_aServerList[$sid]['port'],
			$this->_aServerList[$sid]['user'],
			$this->_aServerList[$sid]['passwd'],
			$this->_aTableList[$no]['db_name']);
	}

	/**
	 * @return string
	 */
	public function sGetRealTableName($no)
	{
		if (count($this->_aTableList) == 1)
		{
			return $this->_aTableList[$no]['kind'];
		}
		return $this->_aTableList[$no]['kind'].'_'.$this->_aTableList[$no]['no'];
	}

	public function vDoFetchSelect($sSql, $fnCallback)
	{
		$kind = $this->_aTableList[0]['kind'];
		$tcount = $this->iTableCount();
		for ($i=0; $i<$tcount; ++$i)
		{
			$oMysql = $this->oConnectDB($i);
			$sql = str_replace($kind, $this->sGetRealTableName($i), $sSql);
			$oMysql->bQuery($sql);
			while ($info = $oMysql->aFetchAssoc())
			{
				call_user_func($fnCallback, $info, $i);
			}
		}
	}
}

/*
function fntest($info, $no)
{
	echo 'fntest '.$no."\n";
	var_dump($info);
}
class A
{
	function fntest2($info, $no)
	{
		echo 'A::fntest2 '.$no."\n";
		var_dump($info);
	}
}
$test = Ko_Dao_MysqlK::OInstance('s_user_info');
$sql = "select * from s_user_info limit 2";
$test->vDoFetchSelect($sql, 'fntest');
$test->vDoFetchSelect($sql, array('A', 'fntest2'));

$test = Ko_Dao_MysqlK::OInstance('s_life_province');
$sql = "select *, length(name) from s_life_province limit 2";
$test->vDoFetchSelect($sql, 'fntest');
*/
?>