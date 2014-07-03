<?php
/**
 * File
 *
 * @package ko\dao
 * @author zhangchu
 */

//include_once('../ko.class.php');

/**
 * 普通文件操作类
 */
class Ko_Dao_File
{
	const FGETS_SIZE = 1024;

	private $_hFile;

	public function __construct($sPath,$sMode)
	{
		$this->_hFile = fopen($sPath, $sMode);
		assert($this->_hFile!==false);
	}

	public function __destruct()
	{
		fclose($this->_hFile);
	}

	/**
	 * @return bool
	 */
	public function bEof()
	{
		return feof($this->_hFile);
	}

	/**
	 * @return string
	 */
	public function sGets($iLength = Ko_Dao_File::FGETS_SIZE)
	{
		return fgets($this->_hFile, $iLength);
	}
}

/*************************test*********************
	$a=new Ko_Dao_File('./File.php','r');

	while(!$a->bEof())
	{
		$aa=$a->sGets();
		echo $aa;
	}

/*************************test*********************/

?>