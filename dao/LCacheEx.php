<?php
/**
 * LCacheEx
 *
 * @package ko\dao
 * @author zhangchu
 */

//include_once('../ko.class.php');

/**
 * 封装模块内部的 LCache 的实现
 */
class Ko_Dao_LCacheEx extends Ko_Dao_ModuleCache
{
	private $_oLCache;

	public function __construct ($sModuleName)
	{
		parent::__construct ($sModuleName);
		$this->_oLCache = Ko_Data_LCAgent::OInstance();
	}

	/**
	 * @return bool
	 */
	public function bSet($sKey, $sValue)
	{
		$sKey = $this->_sModuleName($sKey);
		return $this->_oLCache->bSet($sKey, $sValue);
	}

	/**
	 * @return bool
	 */
	public function bSetObj($sKey, $sValue)
	{
		$sKey = $this->_sModuleName($sKey);
		return $this->_oLCache->bSetObj($sKey, $sValue);
	}

	/**
	 * @return mixed
	 */
	public function vGet($vKey, $iExpire)
	{
		$vKey = $this->_sModuleName($vKey);
		$ret = $this->_oLCache->vGet($vKey, $iExpire);
		if (is_array($vKey) && is_array($ret))
		{
			$ret = $this->_aDeleteModuleName($ret);
		}
		return $ret;
	}

	/**
	 * @return mixed
	 */
	public function vGetObj($vKey, $iExpire)
	{
		$vKey = $this->_sModuleName($vKey);
		$ret = $this->_oLCache->vGetObj($vKey, $iExpire);
		if (is_array($vKey) && is_array($ret))
		{
			$ret = $this->_aDeleteModuleName($ret);
		}
		return $ret;
	}

	/**
	 * @return bool
	 */
	public function bRemove($sKey)
	{
		$sKey = $this->_sModuleName($sKey);
		return $this->_oLCache->bRemove($sKey);
	}

	/**
	 * @return int
	 */
	public function iPlus($sKey, $iValue, $iExpire)
	{
		$sKey = $this->_sModuleName($sKey);
		return $this->_oLCache->iPlus($sKey, $iValue, $iExpire);
	}
}

/*

$obj = new Ko_Dao_LCacheEx('MNAME');
$key = 'test_ko_lcache';
$keys = array('test_ko_lcache');

$value = 'abc';
$ret = $obj->bSet($key, $value);
var_dump($ret);

$ret = $obj->vGet($key, 0);
var_dump($ret);

$ret = $obj->vGet($keys, 0);
var_dump($ret);

$value = array('a' => '123', '中文' => '测试');
$ret = $obj->bSetObj($key, $value);
var_dump($ret);

$ret = $obj->vGetObj($key, 0);
var_dump($ret);

$ret = $obj->vGetObj($keys, 0);
var_dump($ret);

$ret = $obj->iPlus($key, 10, 5);
var_dump($ret);

$ret = $obj->iPlus($key, 1, 5);
var_dump($ret);

$ret = $obj->bRemove($key);
var_dump($ret);

*/
?>