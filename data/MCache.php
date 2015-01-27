<?php
/**
 * MCache
 *
 * @package ko\data
 * @author zhangchu
 */

//include_once('../ko.class.php');

/**
 * 封装 KProxy 的 MCache 的实现
 */
class Ko_Data_MCache extends Ko_Data_KProxy
{
	const PROXY_ARRMAX = 500;
	private static $s_aInstances = array();

	protected function __construct ($sTag, $sExinfo)
	{
		KO_DEBUG >= 6 && Ko_Tool_Debug::VAddTmpLog('data/MCache', '__construct:'.$sTag);
		parent::__construct('MCache' , $sTag, $sExinfo);
	}

	public static function OInstance($sName = '', $sExinfo = '')
	{
		$key = strlen($sExinfo) ? $sName.':'.$sExinfo : $sName;
		if (empty(self::$s_aInstances[$key]))
		{
			self::$s_aInstances[$key] = new self($sName, $sExinfo);
		}
		return self::$s_aInstances[$key];
	}

	public function bSet($sKey, $sValue, $iExpire = 0)
	{
		KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/MCache', 'set:'.$sKey.':'.strlen($sValue).':'.$iExpire);
		try
		{
			assert(0 < strlen($sKey) && strlen($sKey) <= 250);
			if (strlen($sValue) >= 1024*1024 - 256)
			{
				return false;
			}
			$aPara = array(
				'key' => $sKey,
				'value' => strval($sValue),
				'expire' => intval($iExpire),
				'nozip' => true,
				);
			$ret = $this->_oProxy->invoke('set', $aPara);
			return $ret['ok'];
		}
		catch(Exception $ex)
		{
			return false;
		}
	}

	public function bSetObj($sKey, $oValue, $iExpire = 0)
	{
		KO_DEBUG >= 4 && Ko_Tool_Debug::VAddTmpLog('data/MCache', 'bSetObj:'.$sKey.':'.$iExpire);
		return $this->bSet($sKey, Ko_Tool_Enc::SEncode($oValue), $iExpire);
	}

	public function bAdd($sKey, $sValue, $iExpire = 0)
	{
		KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/MCache', 'add:'.$sKey.':'.strlen($sValue).':'.$iExpire);
		try
		{
			assert(0 < strlen($sKey) && strlen($sKey) <= 250);
			if (strlen($sValue) >= 1024*1024 - 256)
			{
				return false;
			}
			$aPara = array(
				'key' => $sKey,
				'value' => strval($sValue),
				'expire' => intval($iExpire),
				'nozip' => true,
				);
			$ret = $this->_oProxy->invoke('add', $aPara);
			return $ret['ok'];
		}
		catch(Exception $ex)
		{
			return false;
		}
	}

	public function bAddObj($sKey, $oValue, $iExpire = 0)
	{
		KO_DEBUG >= 4 && Ko_Tool_Debug::VAddTmpLog('data/MCache', 'bAddObj:'.$sKey.':'.$iExpire);
		return $this->bAdd($sKey, Ko_Tool_Enc::SEncode($oValue), $iExpire);
	}

	public function bReplace($sKey, $sValue, $iExpire = 0)
	{
		KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/MCache', 'replace:'.$sKey.':'.strlen($sValue).':'.$iExpire);
		try
		{
			assert(0 < strlen($sKey) && strlen($sKey) <= 250);
			if (strlen($sValue) >= 1024*1024 - 256)
			{
				return false;
			}
			$aPara = array(
				'key' => $sKey,
				'value' => strval($sValue),
				'expire' => intval($iExpire),
				'nozip' => true,
				);
			$ret = $this->_oProxy->invoke('replace', $aPara);
			return $ret['ok'];
		}
		catch(Exception $ex)
		{
			return false;
		}
	}

	public function bReplaceObj($sKey, $oValue, $iExpire = 0)
	{
		KO_DEBUG >= 4 && Ko_Tool_Debug::VAddTmpLog('data/MCache', 'bReplaceObj:'.$sKey.':'.$iExpire);
		return $this->bReplace($sKey, Ko_Tool_Enc::SEncode($oValue), $iExpire);
	}

	private function _vGet($sKey, $bIsObj)
	{
		try
		{
			assert(0 < strlen($sKey) && strlen($sKey) <= 250);
			KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/MCache', 'get:'.$sKey);
			$aPara = array(
				'key' => $sKey,
				);
			$ret = $this->_oProxy->invoke('get', $aPara);
			if (!isset($ret['value']))
			{
				return false;
			}
			return $bIsObj ? Ko_Tool_Enc::ADecode($ret['value']) : strval($ret['value']);
		}
		catch(Exception $ex)
		{
			return false;
		}
	}

	private function _aGet($aKey, $bIsObj)
	{
		try
		{
			if (empty($aKey))
			{
				KO_DEBUG >= 4 && Ko_Tool_Debug::VAddTmpLog('data/MCache', '_aGet_Empty:'.($bIsObj ? 'true' : 'false'));
				return array();
			}
			$aKey = array_unique($aKey);
			foreach ($aKey as $k => $sKey)
			{
				if (0 == strlen($sKey) || strlen($sKey) > 250)
				{
					unset($aKey[$k]);
				}
			}
			$len = count($aKey);
			$tmpret = array();
			for ($i=0; $i<$len; $i+=self::PROXY_ARRMAX)
			{
				KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/MCache', 'getMulti:'.$len.'-'.$i);
				$aPara = array(
					'keys' => array_slice($aKey, $i, self::PROXY_ARRMAX),
					);
				$ret = $this->_oProxy->invoke('getMulti', $aPara);
				$tmpret = array_merge($tmpret, $ret['values']);
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
					$ret[$k] = strval($v);
				}
			}
			return $ret;
		}
		catch(Exception $ex)
		{
			return false;
		}
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
		KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/MCache', 'delete:'.$sKey);
		try
		{
			assert(0 < strlen($sKey) && strlen($sKey) <= 250);
			$aPara = array(
				'key' => $sKey,
				);
			$ret = $this->_oProxy->invoke('delete', $aPara);
			return $ret['ok'];
		}
		catch(Exception $ex)
		{
			return false;
		}
	}

	public function iIncrement($sKey, $iValue = 1)
	{
		KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/MCache', 'increment:'.$sKey.':'.$iValue);
		try
		{
			assert(0 < strlen($sKey) && strlen($sKey) <= 250);
			$aPara = array(
				'key' => $sKey,
				'value' => intval($iValue),
				);
			$ret = $this->_oProxy->invoke('increment', $aPara);
			if ($ret['ok'] && $ret['value'] >= 0)
			{
				return $ret['value'];
			}
		}
		catch(Exception $ex)
		{
		}
		return false;
	}

	public function iIncrementEx($sKey, $iValue = 1, $iExpire = 0)
	{
		KO_DEBUG >= 4 && Ko_Tool_Debug::VAddTmpLog('data/MCache', 'iIncrementEx:'.$sKey.':'.$iValue.':'.$iExpire);
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
		KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/MCache', 'decrement:'.$sKey.':'.$iValue);
		try
		{
			assert(0 < strlen($sKey) && strlen($sKey) <= 250);
			$aPara = array(
				'key' => $sKey,
				'value' => intval($iValue),
				);
			$ret = $this->_oProxy->invoke('decrement', $aPara);
			if ($ret['ok'] && $ret['value'] >= 0)
			{
				return $ret['value'];
			}
		}
		catch(Exception $ex)
		{
		}
		return false;
	}

	public function sWhichServer($sKey)
	{
		KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/MCache', 'whichServer:'.$sKey);
		try
		{
			assert(0 < strlen($sKey) && strlen($sKey) <= 250);
			$aPara = array(
				'key' => $sKey,
				);
			$ret = $this->_oProxy->invoke('whichServer', $aPara);
			return $ret['real'];
		}
		catch(Exception $ex)
		{
			return false;
		}
	}
	
	public function aAllServers()
	{
		KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/MCache', 'allServers');
		try
		{
			$aPara = array();
			$ret = $this->_oProxy->invoke('allServers', $aPara);
			return $ret;
		}
		catch(Exception $ex)
		{
			return false;
		}
	}
	
	public function aStatAllServers()
	{
		KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/MCache', 'statAllServers');
		try
		{
			$aPara = array();
			$ret = $this->_oProxy->invoke('statAllServers', $aPara);
			return $ret;
		}
		catch(Exception $ex)
		{
			return false;
		}
	}
}
