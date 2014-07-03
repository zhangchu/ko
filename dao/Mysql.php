<?php
/**
 * Mysql
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
 * 数据表分表直连操作类接口
 */
interface IKo_Dao_Mysql
{
	/**
	 * @return int
	 */
	public function iTableCount();
	/**
	 * @return Ko_Data_Mysql
	 */
	public function oConnectDB($no);
	/**
	 * @return string
	 */
	public function sGetRealTableName($no);
	public function vDoFetchSelect($sSql, $fnCallback);
}

/**
 * 数据表分表直连操作类实现
 */
class Ko_Dao_Mysql implements IKo_Dao_Mysql, IKo_Dao_MysqlAgent
{
	private $_sKind;

	protected function __construct($sKind, $bSlave = false)
	{
		$this->_sKind = $sKind;
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
		return 1;
	}

	/**
	 * @return Ko_Data_Mysql
	 */
	public function oConnectDB($no)
	{
		return Ko_Data_Mysql::OInstance(KO_DB_HOST, KO_DB_USER, KO_DB_PASS, KO_DB_NAME);
	}

	/**
	 * @return string
	 */
	public function sGetRealTableName($no)
	{
		return $this->_sKind;
	}

	public function vDoFetchSelect($sSql, $fnCallback)
	{
		$tcount = $this->iTableCount();
		for ($i=0; $i<$tcount; ++$i)
		{
			$oMysql = $this->oConnectDB($i);
			$oMysql->bQuery($sSql);
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
$test = Ko_Dao_Mysql::OInstance('server_setting');
$sql = "select * from server_setting limit 2";
$test->vDoFetchSelect($sql, 'fntest');
$test->vDoFetchSelect($sql, array('A', 'fntest2'));

$test = Ko_Dao_Mysql::OInstance('kind_setting');
$sql = "select *, length(kind) from kind_setting limit 2";
$test->vDoFetchSelect($sql, 'fntest');
*/
?>