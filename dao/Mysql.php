<?php
/**
 * Mysql
 *
 * @package ko\dao
 * @author zhangchu
 */

/**
 * 数据表分表直连操作类
 */
class Ko_Dao_Mysql implements IKo_Dao_Table
{
	private $_sKind;

	protected function __construct($sKind)
	{
		$this->_sKind = $sKind;
	}

	public static function OInstance($sKind)
	{
		return new self($sKind);
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
	public function oConnectDB($no, $sTag = 'slave')
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

	public function vDoFetchSelect($sSql, $fnCallback, $sTag = 'slave')
	{
		$tcount = $this->iTableCount();
		for ($i=0; $i<$tcount; ++$i)
		{
			$oMysql = $this->oConnectDB($i, $sTag);
			$oMysql->bQuery($sSql);
			while ($info = $oMysql->aFetchAssoc())
			{
				call_user_func($fnCallback, $info, $i);
			}
		}
	}
}
