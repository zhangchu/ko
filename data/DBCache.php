<?php
/**
 * DBCache
 *
 * @package ko\data
 * @author zhangchu
 */

/**
 * 封装使用 MCache 和 InProcCache 对数据库信息进行缓存的
 */
class Ko_Data_DBCache
{
	const CHECK_MEM_COUNT = 10000;					//s_aCache 达到这个数量左右时，清空，防止内存占用太大，导致崩溃

	private static $s_aCache = array();             //(进程内)数据缓存

	private $_sKind;								//数据库表名
	private $_sMCacheName = '';						//MCache缓存分组
	private $_iMCacheTime = 0;						//MCache缓存时间，为0表示不使用MCache
	private $_oMCache;								//MCache句柄

	public function __construct($sKind, $sMCacheName, $iMCacheTime)
	{
		KO_DEBUG >= 6 && Ko_Tool_Debug::VAddTmpLog('data/DBCache', '__construct:'.$sKind.':'.$sMCacheName.':'.$iMCacheTime);
		$this->_sKind = $sKind;
		$this->_sMCacheName = $sMCacheName;
		$this->_iMCacheTime = $iMCacheTime;
	}

	/**
	 * 将数据设置进入 cache
	 */
	public function vSet($sId, $aItem, $bCheckMemSize)
	{
		KO_DEBUG >= 3 && Ko_Tool_Debug::VAddTmpLog('data/DBCache', 'vSet:'.$this->_sKind.':'.$sId);
		$sKey = $this->_sGetCacheKey($sId);
		if ($this->_iMCacheTime)
		{
			$encClass = 'Ko_Tool_Enc_'.KO_DB_CACHE_ENC;
			$vbp = $encClass::SEncode($aItem);
			$this->_oGetMCache()->bSet($sKey, $vbp, $this->_iMCacheTime);
		}
		if ($bCheckMemSize)
		{
			$this->_vCheckInProcMem();
		}
		self::$s_aCache[$sKey] = $aItem;
	}

	/**
	 * 将数据从 cache 删除
	 */
	public function vDel($sId)
	{
		KO_DEBUG >= 3 && Ko_Tool_Debug::VAddTmpLog('data/DBCache', 'vDel:'.$this->_sKind.':'.$sId);
		$sKey = $this->_sGetCacheKey($sId);
		if ($this->_iMCacheTime)
		{
			$this->_oGetMCache()->bDelete($sKey);
		}
		unset(self::$s_aCache[$sKey]);
	}

	/**
	 * 从 cache 查询数据，数据不存在返回 false，否则返回数组
	 */
	public function vGet($sId, $bCheckMemcache)
	{
		KO_DEBUG >= 3 && Ko_Tool_Debug::VAddTmpLog('data/DBCache', 'vGet:'.$this->_sKind.':'.$sId);
		$sKey = $this->_sGetCacheKey($sId);
		if (isset(self::$s_aCache[$sKey]))
		{
			KO_DEBUG >= 1 && $this->_iMCacheTime && $bCheckMemcache && Ko_Tool_Debug::VAddTmpLog('stat/InProc', 'cache');
			KO_DEBUG >= 3 && Ko_Tool_Debug::VAddTmpLog('data/DBCache', 'vGet_InProc:'.$this->_sKind.':'.$sId);
			return self::$s_aCache[$sKey];
		}

		$aRet = false;
		if ($this->_iMCacheTime && $bCheckMemcache)
		{
			$vbp = $this->_oGetMCache()->vGet($sKey);
			$encClass = 'Ko_Tool_Enc_'.KO_DB_CACHE_ENC;
			$o = $encClass::ADecode($vbp);
			if (false !== $o)
			{
				KO_DEBUG >= 1 && Ko_Tool_Debug::VAddTmpLog('stat/InProc', 'miss');
				$this->_vCheckInProcMem();
				KO_DEBUG >= 3 && Ko_Tool_Debug::VAddTmpLog('data/DBCache', 'vGet_MCache:'.$this->_sKind.':'.$sId);
				self::$s_aCache[$sKey] = $aRet = $o;
			}
		}
		return $aRet;
	}

	/**
	 * 将在 cache 中的 id 过滤掉，返回不在 cache 里面的 id 列表
	 */
	public function aFilterInCache($aId)
	{
		KO_DEBUG >= 3 && Ko_Tool_Debug::VAddTmpLog('data/DBCache', 'aFilterInCache:'.$this->_sKind.':'.count($aId));
		$this->_vCheckInProcMem();
		$aKey = $this->_aConvertKeys($aId);
		if ($this->_iMCacheTime)
		{
			$aFilterKey = $this->_aFilterInProc($aId, $aKey, false);
			if (empty($aFilterKey))
			{
				KO_DEBUG >= 1 && Ko_Tool_Debug::VAddTmpLog('stat/InProc2', count($aId).':0');
				return array();
			}

			$vals = $this->_oGetMCache()->vGet($aFilterKey);
			foreach ($vals as $k => $v)
			{
				$encClass = 'Ko_Tool_Enc_'.KO_DB_CACHE_ENC;
				$o = $encClass::ADecode($v);
				if (false !== $o)
				{
					self::$s_aCache[$k] = $o;
				}
			}
			KO_DEBUG >= 1 && Ko_Tool_Debug::VAddTmpLog('stat/InProc2', (count($aId) - count($aFilterKey) + count($vals)).':'.count($vals));
		}
		return $this->_aFilterInProc($aId, $aKey, true);
	}

	private function _aFilterInProc($aId, $aKey, $bRetid)
	{
		$aRet = array();
		foreach ($aKey as $k => $v)
		{
			if (!isset(self::$s_aCache[$v]))
			{
				$aRet[] = $bRetid ? $aId[$k] : $v;
			}
		}
		KO_DEBUG >= 7 && Ko_Tool_Debug::VAddTmpLog('data/DBCache', '_aFilterInProc:'.$this->_sKind.':'.count($aId).'-'.count($aKey).'-'.count($aRet).':'.($bRetid ? 'true' : 'false'));
		return $aRet;
	}

	private function _aConvertKeys($aId)
	{
		return array_map(array($this, '_sGetCacheKey'), $aId);
	}

	private function _sGetCacheKey($sKey)
	{
		return 'kodb_'.$this->_sKind.':'.$sKey;
	}

	private function _vCheckInProcMem()
	{
		if (count(self::$s_aCache) > self::CHECK_MEM_COUNT)
		{
			self::$s_aCache = array();
			KO_DEBUG >= 5 && Ko_Tool_Debug::VAddTmpLog('data/DBCache', '_vCheckInProcMem_Clear');
		}
	}

	private function _oGetMCache()
	{
		if (is_null($this->_oMCache))
		{
			$this->_oMCache = Ko_Data_MCAgent::OInstance($this->_sMCacheName);
		}
		return $this->_oMCache;
	}
}

?>