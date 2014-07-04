<?php
/**
 * MCacheEx
 *
 * @package ko\dao
 * @author zhangchu
 */

//include_once('../ko.class.php');

/**
 * 封装模块内部的 MCache 的实现
 */
class Ko_Dao_MCacheEx extends Ko_Dao_ModuleCache
{
	private $_oMCache;

	public function __construct ($sModuleName, $sName = '')
	{
		parent::__construct ($sModuleName);
		$this->_oMCache = Ko_Data_MCAgent::OInstance($sName);
	}

	/**
	 * @return bool
	 */
	public function bSet($sKey, $sValue, $iExpire = 0)
	{
		$sKey = $this->_sModuleName($sKey);
		return $this->_oMCache->bSet($sKey, $sValue, $iExpire);
	}

	/**
	 * @return bool
	 */
	public function bSetObj($sKey, $sValue, $iExpire = 0)
	{
		$sKey = $this->_sModuleName($sKey);
		return $this->_oMCache->bSetObj($sKey, $sValue, $iExpire);
	}

	/**
	 * @return bool
	 */
	public function bAdd($sKey, $sValue, $iExpire = 0)
	{
		$sKey = $this->_sModuleName($sKey);
		return $this->_oMCache->bAdd($sKey, $sValue, $iExpire);
	}

	/**
	 * @return bool
	 */
	public function bAddObj($sKey, $sValue, $iExpire = 0)
	{
		$sKey = $this->_sModuleName($sKey);
		return $this->_oMCache->bAddObj($sKey, $sValue, $iExpire);
	}

	/**
	 * @return bool
	 */
	public function bReplace($sKey, $sValue, $iExpire = 0)
	{
		$sKey = $this->_sModuleName($sKey);
		return $this->_oMCache->bReplace($sKey, $sValue, $iExpire);
	}

	/**
	 * @return bool
	 */
	public function bReplaceObj($sKey, $sValue, $iExpire = 0)
	{
		$sKey = $this->_sModuleName($sKey);
		return $this->_oMCache->bReplaceObj($sKey, $sValue, $iExpire);
	}

	/**
	 * @return mixed
	 */
	public function vGet($vKey)
	{
		$vKey = $this->_sModuleName($vKey);
		$ret = $this->_oMCache->vGet($vKey);
		if (is_array($vKey) && is_array($ret))
		{
			$ret = $this->_aDeleteModuleName($ret);
		}
		return $ret;
	}

	/**
	 * @return mixed
	 */
	public function vGetObj($vKey)
	{
		$vKey = $this->_sModuleName($vKey);
		$ret = $this->_oMCache->vGetObj($vKey);
		if (is_array($vKey) && is_array($ret))
		{
			$ret = $this->_aDeleteModuleName($ret);
		}
		return $ret;
	}

	/**
	 * @return bool
	 */
	public function bDelete($sKey)
	{
		$sKey = $this->_sModuleName($sKey);
		return $this->_oMCache->bDelete($sKey);
	}

	/**
	 * @return int
	 */
	public function iIncrement($sKey, $iValue = 1)
	{
		$sKey = $this->_sModuleName($sKey);
		return $this->_oMCache->iIncrement($sKey, $iValue);
	}

	/**
	 * @return int
	 */
	public function iIncrementEx($sKey, $iValue = 1, $iExpire = 0)
	{
		$sKey = $this->_sModuleName($sKey);
		return $this->_oMCache->iIncrementEx($sKey, $iValue, $iExpire);
	}

	/**
	 * @return int
	 */
	public function iDecrement($sKey, $iValue = 1)
	{
		$sKey = $this->_sModuleName($sKey);
		return $this->_oMCache->iDecrement($sKey, $iValue);
	}
}

/*
$obj = new Ko_Dao_MCacheEx('ABC');
$key = 'test_ko_mcache';
$keys = array('test_ko_mcache');

$ret = $obj->bDelete($key);
var_dump($ret);

$value = 'abc';
$ret = $obj->bAdd($key, $value);
var_dump($ret);

$ret = $obj->vGet($key);
var_dump($ret);

$ret = $obj->vGet($keys);
var_dump($ret);

$ret = $obj->bDelete($key);
var_dump($ret);

$ret = $obj->vGet($key);
var_dump($ret);

$ret = $obj->vGet($keys);
var_dump($ret);

$value = 'abc';
$ret = $obj->bSet($key, $value);
var_dump($ret);

$ret = $obj->vGet($key, 0);
var_dump($ret);

$value = 'cba';
$ret = $obj->bReplace($key, $value);
var_dump($ret);

$ret = $obj->vGet($key, 0);
var_dump($ret);

$ret = $obj->bDelete($key);
var_dump($ret);


$value = array('a' => '123', '中文' => '测试');
$ret = $obj->bAddObj($key, $value);
var_dump($ret);

$ret = $obj->vGetObj($key);
var_dump($ret);

$ret = $obj->vGetObj($keys);
var_dump($ret);

$ret = $obj->bDelete($key);
var_dump($ret);

$ret = $obj->vGetObj($key);
var_dump($ret);

$ret = $obj->vGetObj($keys);
var_dump($ret);

$value = array('a' => '123', '中文' => '测试');
$ret = $obj->bSetObj($key, $value);
var_dump($ret);

$ret = $obj->vGetObj($key, 0);
var_dump($ret);

$value = array('b' => '123', '文中' => '测试');
$ret = $obj->bReplaceObj($key, $value);
var_dump($ret);

$ret = $obj->vGetObj($key, 0);
var_dump($ret);

$ret = $obj->bDelete($key);
var_dump($ret);


$ret = $obj->iIncrement($key, 10);
var_dump($ret);

$ret = $obj->iIncrementEx($key, 10);
var_dump($ret);

$ret = $obj->iIncrement($key, 2);
var_dump($ret);

$ret = $obj->iDecrement($key, 5);
var_dump($ret);

*/
?>