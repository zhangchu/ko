<?php
/**
 * LCache
 *
 * @package ko\data
 * @author zhangchu
 */

//include_once('../ko.class.php');

/**
 * 封装 KProxy 的 LCache 的实现
 */
class Ko_Data_LCache extends Ko_Data_KProxy
{
	const PROXY_ARRMAX = 500;
	private static $s_oInstance;

	protected function __construct ()
	{
		KO_DEBUG >= 6 && Ko_Tool_Debug::VAddTmpLog('data/LCache', '__construct');
		parent::__construct('LCache');
	}

	public static function OInstance()
	{
		if (empty(self::$s_oInstance))
		{
			self::$s_oInstance = new self();
		}
		return self::$s_oInstance;
	}

	public function bSet($sKey, $sValue)
	{
		KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/LCache', 'set:'.$sKey.':'.strlen($sValue));
		try
		{
			$aPara = array(
				'key' => $sKey,
				'value' => strval($sValue),
				);
			assert($aPara['value'] !== '');		//不允许设置空串，空串被认为是不存在数据
			$ret = $this->_oProxy->invoke('set', $aPara);
			return $ret['ok'];
		}
		catch(Exception $ex)
		{
			return false;
		}
	}

	public function bSetObj($sKey, $oValue)
	{
		KO_DEBUG >= 4 && Ko_Tool_Debug::VAddTmpLog('data/LCache', 'bSetObj:'.$sKey);
		return $this->bSet($sKey, Ko_Tool_Enc::SEncode($oValue));
	}

	private function _vGet($sKey, $iExpire, $bIsObj)
	{
		try
		{
			KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/LCache', 'get:'.$sKey.':'.$iExpire);
			$aPara = array(
				'key' => $sKey,
				'expire' => intval($iExpire),
				);
			$ret = $this->_oProxy->invoke('get', $aPara);
			return strlen($ret['value']) ? ($bIsObj ? Ko_Tool_Enc::ADecode($ret['value']) : strval($ret['value'])) : false;
		}
		catch(Exception $ex)
		{
			return false;
		}
	}

	private function _aGet($aKey, $iExpire, $bIsObj)
	{
		try
		{
			if (empty($aKey))
			{
				KO_DEBUG >= 4 && Ko_Tool_Debug::VAddTmpLog('data/LCache', '_aGet_Empty:'.$iExpire.':'.($bIsObj ? 'true' : 'false'));
				return array();
			}
			$aKey = array_unique($aKey);
			$len = count($aKey);
			$tmpret = array();
			for ($i=0; $i<$len; $i+=self::PROXY_ARRMAX)
			{
				KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/LCache', 'getAll:'.$iExpire.':'.$len.'-'.$i);
				$aPara = array(
					'keys' => array_slice($aKey, $i, self::PROXY_ARRMAX),
					'expire' => intval($iExpire),
					);
				$ret = $this->_oProxy->invoke('getAll', $aPara);
				$tmpret = array_merge($tmpret, $ret['items']);
			}
			$ret = array();
			foreach ($tmpret as $k => $v)
			{
				if (strlen($v))
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
						$ret[$k] = strval($v);
					}
				}
			}
			return $ret;
		}
		catch(Exception $ex)
		{
			return false;
		}
	}

	private function _vGetEx($vKey, $iExpire, $bIsObj)
	{
		if(is_array($vKey))
		{
			return $this->_aGet($vKey, $iExpire, $bIsObj);
		}
		return $this->_vGet($vKey, $iExpire, $bIsObj);
	}

	public function vGet($vKey, $iExpire)
	{
		return $this->_vGetEx($vKey, $iExpire, false);
	}

	public function vGetObj($vKey, $iExpire)
	{
		return $this->_vGetEx($vKey, $iExpire, true);
	}

	public function bRemove($sKey)
	{
		KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/LCache', 'remove:'.$sKey);
		try
		{
			$aPara = array(
				'key' => $sKey,
				);
			$ret = $this->_oProxy->invoke('remove', $aPara);
			return $ret['ok'];
		}
		catch(Exception $ex)
		{
			return false;
		}
	}

	public function iPlus($sKey, $iValue, $iExpire)
	{
		KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/LCache', 'plus:'.$sKey.':'.$iValue.':'.$iExpire);
		try
		{
			$aPara = array(
				'key' => $sKey,
				'value' => intval($iValue),
				'expire' => intval($iExpire),
				);
			$ret = $this->_oProxy->invoke('plus', $aPara);
			return $ret['value'];
		}
		catch(Exception $ex)
		{
			return false;
		}
	}
}

/*

$obj = Ko_Data_LCache::OInstance();
$key = 'test_ko_lcache';

$value = 'abc';
$ret = $obj->bSet($key, $value);
var_dump($ret);

$ret = $obj->vGet($key, 0);
var_dump($ret);

$value = array('a' => '123', '中文' => '测试');
$ret = $obj->bSetObj($key, $value);
var_dump($ret);

$ret = $obj->vGetObj($key, 0);
var_dump($ret);

$ret = $obj->vGetObj(array($key, 'zc'), 0);
var_dump($ret);

$ret = $obj->iPlus($key, 10, 5);
var_dump($ret);

$ret = $obj->iPlus($key, 1, 5);
var_dump($ret);

$ret = $obj->bRemove($key);
var_dump($ret);

$obj = new Ko_Data_LCache;
*/

?>