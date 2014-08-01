<?php
/**
 * MemCache
 *
 * @package ko\data
 * @author zhangchu
 */

//define('KO_ENC', 'Serialize');
//include_once('../ko.class.php');

/**
 * 封装直连 memcache 的实现
 */
class Ko_Data_MemCache
{
	const PROXY_ARRMAX = 500;
	private static $s_aInstances = array();
	
	private $_oMemcache = null;

	protected function __construct ($sTag)
	{
		KO_DEBUG >= 6 && Ko_Tool_Debug::VAddTmpLog('data/MemCache', '__construct:'.$sTag);
		$this->_oMemcache = $this->_oCreateMemcache($sTag);
	}
	
	protected function _oCreateMemcache($sTag)
	{
		list($host, $port) = explode(':', KO_MC_HOST);
		$mc = new Memcache;
		if (!$mc->connect($host, $port))
		{
			$mc = null;
		}
		return $mc;
	}

	public static function OInstance($sName = '', $sExinfo = '')
	{
		if (empty(self::$s_aInstances[$sName]))
		{
			self::$s_aInstances[$sName] = new self($sName);
		}
		return self::$s_aInstances[$sName];
	}

	public function bSet($sKey, $sValue, $iExpire = 0)
	{
		KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/MemCache', 'set:'.$sKey.':'.strlen($sValue).':'.$iExpire);
		if ($this->_oMemcache)
		{
			return $this->_oMemcache->set($sKey, $sValue, 0, $iExpire);
		}
		return false;
	}

	public function bSetObj($sKey, $oValue, $iExpire = 0)
	{
		KO_DEBUG >= 4 && Ko_Tool_Debug::VAddTmpLog('data/MemCache', 'bSetObj:'.$sKey.':'.$iExpire);
		return $this->bSet($sKey, Ko_Tool_Enc::SEncode($oValue), $iExpire);
	}

	public function bAdd($sKey, $sValue, $iExpire = 0)
	{
		KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/MemCache', 'add:'.$sKey.':'.strlen($sValue).':'.$iExpire);
		if ($this->_oMemcache)
		{
			return $this->_oMemcache->add($sKey, $sValue, 0, $iExpire);
		}
		return false;
	}

	public function bAddObj($sKey, $oValue, $iExpire = 0)
	{
		KO_DEBUG >= 4 && Ko_Tool_Debug::VAddTmpLog('data/MemCache', 'bAddObj:'.$sKey.':'.$iExpire);
		return $this->bAdd($sKey, Ko_Tool_Enc::SEncode($oValue), $iExpire);
	}

	public function bReplace($sKey, $sValue, $iExpire = 0)
	{
		KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/MemCache', 'replace:'.$sKey.':'.strlen($sValue).':'.$iExpire);
		if ($this->_oMemcache)
		{
			return $this->_oMemcache->replace($sKey, $sValue, 0, $iExpire);
		}
		return false;
	}

	public function bReplaceObj($sKey, $oValue, $iExpire = 0)
	{
		KO_DEBUG >= 4 && Ko_Tool_Debug::VAddTmpLog('data/MemCache', 'bReplaceObj:'.$sKey.':'.$iExpire);
		return $this->bReplace($sKey, Ko_Tool_Enc::SEncode($oValue), $iExpire);
	}

	private function _vGet($sKey, $bIsObj)
	{
		KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/MemCache', 'get:'.$sKey);
		if ($this->_oMemcache)
		{
			$ret = $this->_oMemcache->get($sKey);
			if (false === $ret)
			{
				return false;
			}
			return $bIsObj ? Ko_Tool_Enc::ADecode($ret) : $ret;
		}
		return false;
	}

	private function _aGet($aKey, $bIsObj)
	{
		if (is_null($this->_oMemcache))
		{
			return false;
		}
		if (empty($aKey))
		{
			KO_DEBUG >= 4 && Ko_Tool_Debug::VAddTmpLog('data/MemCache', '_aGet_Empty:'.($bIsObj ? 'true' : 'false'));
			return array();
		}
		$aKey = array_unique($aKey);
		$len = count($aKey);
		$tmpret = array();
		for ($i=0; $i<$len; $i+=self::PROXY_ARRMAX)
		{
			KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/MemCache', 'getMulti:'.$len.'-'.$i);
			$ret = $this->_oMemcache->get(array_slice($aKey, $i, self::PROXY_ARRMAX));
			if (false === $ret)
			{
				return false;
			}
			$tmpret = array_merge($tmpret, $ret);
		}
		$ret = array();
		foreach ($tmpret as $k => $v)
		{
			if ($bIsObj)
			{
				$o = Ko_Tool_Enc::ADecode($v);
				if (false !== $o)
				{
					$ret[$k] = $o;
				}
			}
			else
			{
				$ret[$k] = $v;
			}
		}
		return $ret;
	}

	private function _vGetEx($vKey, $bIsObj)
	{
		if(is_array($vKey))
		{
			return $this->_aGet($vKey, $bIsObj);
		}
		return $this->_vGet($vKey, $bIsObj);
	}

	public function vGet($vKey)
	{
		return $this->_vGetEx($vKey, false);
	}

	public function vGetObj($vKey)
	{
		return $this->_vGetEx($vKey, true);
	}

	public function bDelete($sKey)
	{
		KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/MemCache', 'delete:'.$sKey);
		if ($this->_oMemcache)
		{
			return $this->_oMemcache->delete($sKey);
		}
		return false;
	}

	public function iIncrement($sKey, $iValue = 1)
	{
		KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/MemCache', 'increment:'.$sKey.':'.$iValue);
		if ($this->_oMemcache)
		{
			return $this->_oMemcache->increment($sKey, $iValue);
		}
		return false;
	}

	public function iIncrementEx($sKey, $iValue = 1, $iExpire = 0)
	{
		KO_DEBUG >= 4 && Ko_Tool_Debug::VAddTmpLog('data/MemCache', 'iIncrementEx:'.$sKey.':'.$iValue.':'.$iExpire);
		$ret = $this->iIncrement($sKey, $iValue);
		if ($ret !== false)
		{
			return $ret;
		}
		$ret = $this->bAdd($sKey, $iValue, $iExpire);
		if ($ret !== false)
		{
			return $iValue;
		}
		return $this->iIncrement($sKey, $iValue);
	}

	public function iDecrement($sKey, $iValue = 1)
	{
		KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/MemCache', 'decrement:'.$sKey.':'.$iValue);
		if ($this->_oMemcache)
		{
			return $this->_oMemcache->decrement($sKey, $iValue);
		}
		return false;
	}
}


/*
$obj = Ko_Data_MemCache::OInstance();
$key = 'test_ko_memcache';

$ret = $obj->bDelete($key);
var_dump($ret);

$value = 'abc';
$ret = $obj->bAdd($key, $value);
var_dump($ret);

$ret = $obj->vGet($key);
var_dump($ret);

$ret = $obj->vGet(array($key, 'zc'));
var_dump($ret);

$ret = $obj->bDelete($key);
var_dump($ret);

$ret = $obj->vGet($key);
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

$ret = $obj->bDelete($key);
var_dump($ret);

$ret = $obj->vGetObj($key);
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

$obj = new Ko_Data_MemCache;

*/
?>