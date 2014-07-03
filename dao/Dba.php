<?php
/**
 * Dba
 *
 * @package ko\dao
 * @author zhangchu
 */

//include_once('../ko.class.php');

/**
 * DBA文件封装
 */
class Ko_Dao_Dba
{
	private $_hFile;

	public function __construct($sPath, $sMode, $sHandler='gdbm')
	{
		$this->_hFile = dba_open($sPath, $sMode, $sHandler);
		assert($this->_hFile!==false);
	}

	public function __destruct()
	{
		dba_close($this->_hFile);
	}

	/**
	 * @return bool
	 */
	public function bDelete($sKey)
	{
		return dba_delete($sKey, $this->_hFile);
	}

	/**
	 * @return bool
	 */
	public function bExists($sKey)
	{
		return dba_exists($sKey, $this->_hFile);
	}

	/**
	 * @return string
	 */
	public function sFetch($sKey)
	{
		return dba_fetch($sKey, $this->_hFile);
	}

	/**
	 * @return string
	 */
	public function sFirstkey()
	{
		return dba_firstkey($this->_hFile);
	}

	/**
	 * @return bool
	 */
	public function bInsert($sKey, $sValue)
	{
		return @dba_insert($sKey, $sValue, $this->_hFile);
	}

	/**
	 * @return string
	 */
	public function sNextkey()
	{
		return dba_nextkey($this->_hFile);
	}

	/**
	 * @return bool
	 */
	public function bReplace($sKey, $sValue)
	{
		return dba_replace($sKey, $sValue, $this->_hFile);
	}
}

/*************************test********************

	$a=new Ko_Dao_Dba("./test.db","c");
	var_dump($a);
	$i=$a->bExists("key");
	var_dump($i);
	$i=$a->bInsert("key", "123");
	var_dump($i);
	$i=$a->bExists("key");
	var_dump($i);
	$i=$a->bInsert("key", "abc");
	var_dump($i);
	$i=$a->sFetch("key");
	var_dump($i);
	$i=$a->bReplace("key", "okok");
	var_dump($i);
	$i=$a->bReplace("key2", "okok2");
	var_dump($i);
	$i=$a->bReplace("key3", "okok3");
	var_dump($i);
	$i=$a->sFetch("key");
	var_dump($i);
	$i=$a->sFetch("key2");
	var_dump($i);
	$i=$a->bDelete("key");
	var_dump($i);
	$i=$a->bDelete("key");
	var_dump($i);
	$i=$a->sFetch("key");
	var_dump($i);
	$a = null;

	$a=new Ko_Dao_Dba("./test.db", "c");

	$key = $a->sFirstkey();
	while ($key !== false)
	{
		$v = $a->sFetch($key);
		echo $key.' '.$v."\n";
		$key = $a->sNextkey();
	}

	unlink("./test.db");

*************************test*********************/
?>