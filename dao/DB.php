<?php
/**
 * DB
 *
 * @package ko
 * @subpackage dao
 * @author zhangchu
 */

/**
 * 对数据库分库分表的性质操作进行封装的接口
 */
interface IKo_Dao_DB
{
	/**
	 * @return int 返回 insertid
	 */
	public function iInsert($aData, $aUpdate = array(), $aChange = array());
	/**
	 * @return array 返回完整的信息array(data, rownum, insertid, affectedrows)
	 */
	public function aInsert($aData, $aUpdate = array(), $aChange = array());
	/**
	 * @param Ko_Tool_SQL|Ko_Tool_MONGO|array $oOption
	 * @return int 返回 affectedrows
	 */
	public function iUpdate($vHintId, $vKey, $aUpdate, $aChange=array(), $oOption=null);
	/**
	 * @param Ko_Tool_SQL|Ko_Tool_MONGO|array $oOption
	 * @return int 返回 affectedrows
	 */
	public function iUpdateByCond($vHintId, $oOption, $aUpdate, $aChange=array());
	/**
	 * @param Ko_Tool_SQL|Ko_Tool_MONGO|array $oOption
	 * @return int 返回 affectedrows
	 */
	public function iDelete($vHintId, $vKey, $oOption=null);
	/**
	 * @param Ko_Tool_SQL|Ko_Tool_MONGO|array $oOption
	 * @return int 返回 affectedrows
	 */
	public function iDeleteByCond($vHintId, $oOption);
	/**
	 * @return array 根据 _aKeyField 查询一条数据
	 */
	public function aGet($vHintId, $vKey);
	/**
	 * 配置 key 才可用
	 *
	 * @return array 根据 _aKeyField 查询多条数据
	 */
	public function aGetListByKeys($vHintId, $aKey, $sKeyField = '');
	/**
	 * useuo 为真 才可用
	 *
	 * @return array 从 UOBject 查询多条数据
	 */
	public function aGetDetails($oObjs, $sSplitField = '', $sKeyField = '', $bRetmap = true);
	/**
	 * @param Ko_Tool_SQL|Ko_Tool_MONGO|array $oOption
	 * @return array 根据 Option 查询
	 */
	public function aGetList($vHintId, $oOption, $iCacheTime=0);
	public function vDeleteCache($vHintId, $vKey);
}

/**
 * 对数据库分库分表的性质操作进行封装的实现
 */
class Ko_Dao_DB implements IKo_Dao_DB, IKo_Dao_DBHelp, IKo_Dao_Mysql
{
	const SPLIT_COUNT = 1000;		//数据量太大的情况下，切分为多个小块数据进行获取，防止返回数据超过ICE定义的最大尺寸

	//数据表配置
	private $_sTable;
	private $_sSplitField;			//分表字段，为空表示不分表，如果有分表字段，该字段不能为0
	private $_aKeyField;			//唯一字段，为空表示没有唯一字段或者分表字段就是唯一字段，唯一不一定是全局唯一，也可以是在 _sSplitField 指定值范围内唯一
	private $_sIdKey;				//idGenerator 标示，用于生成 _sSplitField 或者 _aKeyField 指定字段的值

	//DBAgent 配置
	private $_sDBAgentName = '';

	//缓存配置
	private $_sMCacheName = '';
	private $_iMCacheTime = 0;

	//UObject 配置
	private $_bUseUO = false;
	private $_aUoFields = array();
	private $_sUoName = '';

	//其他属性 配置
	private $_bIsSplitString = false;
	private $_bIsDirectSlave = false;
	private $_bIsMongoDB = false;

	//中间层对象
	private $_oSqlAgent;
	private $_oIdGenerator;
	private $_oDBCache;
	private $_oUObject;
	private $_oDirectMysql;			//直连数据库对象

	public function __construct($sTable, $sSplitField, $vKeyField, $sIdKey='', $sDBAgentName='', $sMCacheName = '', $iMCacheTime = 3600, $bUseUO = false, $aUoFields = array(), $sUoName = '')
	{
		$this->_sTable = $sTable;
		$this->_sSplitField = $sSplitField;
		$this->_aKeyField = $this->_aNormalizedKeyField($vKeyField, $sSplitField);
		$this->_sIdKey = $sIdKey;
		$this->_sDBAgentName = $sDBAgentName;
		$this->_sMCacheName = $sMCacheName;
		$this->_iMCacheTime = $iMCacheTime;
		$this->_bUseUO = $bUseUO;
		$this->_aUoFields = $aUoFields;
		$this->_sUoName = $sUoName;
	}

	/**
	 * @return string
	 */
	public function sGetTableName()
	{
		return $this->_sTable;
	}

	/**
	 * @return string
	 */
	public function sGetSplitField()
	{
		return $this->_sSplitField;
	}

	/**
	 * @return array
	 */
	public function aGetKeyField()
	{
		return $this->_aKeyField;
	}

	/**
	 * @return array
	 */
	public function aGetIndexField()
	{
		$aField = array();
		if (strlen($this->_sSplitField))
		{
			$aField[] = $this->_sSplitField;
		}
		return array_merge($aField, $this->_aKeyField);
	}

	/**
	 * @return string
	 */
	public function sGetAutoIdField()
	{
		if (count($this->_aKeyField))
		{
			return $this->_aKeyField[0];
		}
		return $this->_sSplitField;
	}

	/**
	 * @return string
	 */
	public function sGetIdKey()
	{
		return $this->_sIdKey;
	}

	/**
	 * @return int
	 */
	public function iGetHintId($vHintId)
	{
		if ($this->_bIsSplitString)
		{
			return 1 + hexdec(substr(md5(strtolower($vHintId)), -4));
		}
		return intval($vHintId);
	}

	public function vGetAttribute($sName)
	{
		$sName = strtolower($sName);
		switch ($sName)
		{
		case 'issplitstring':
			return $this->_bIsSplitString;
		case 'isdirectslave':
			return $this->_bIsDirectSlave;
		case 'ismongodb':
			return $this->_bIsMongoDB;
		}
	}

	public function vSetAttribute($sName, $vValue)
	{
		$sName = strtolower($sName);
		switch ($sName)
		{
		case 'issplitstring':
			$this->_bIsSplitString = $vValue;
			break;
		case 'isdirectslave':
			$this->_bIsDirectSlave = $vValue;
			break;
		case 'ismongodb':
			$this->_bIsMongoDB = $vValue;
			break;
		}
	}

	//////////////////////////// 写入操作 ////////////////////////////

	/**
	 * @return int
	 */
	public function iInsert($aData, $aUpdate = array(), $aChange = array())
	{
		$info = $this->aInsert($aData, $aUpdate, $aChange);
		return $info['insertid'];
	}

	/**
	 * @return array
	 */
	public function aInsert($aData, $aUpdate = array(), $aChange = array())
	{
		if ($bGenId = (strlen($this->_sIdKey) && strlen($autoIdField = $this->sGetAutoIdField()) && !array_key_exists($autoIdField, $aData)))
		{
			$aData[$autoIdField] = $this->_oGetIdGenerator()->iGetNewTimeID($this->_sIdKey);
		}
		$vHintId = strlen($this->_sSplitField) ? $aData[$this->_sSplitField] : 1;
		$aRet = $this->_oGetSqlAgent()->aInsert($this->_sTable, $this->iGetHintId($vHintId), $aData, $aUpdate, $aChange);
		if ($bGenId)
		{
			$aRet['insertid'] = $aData[$autoIdField];
		}
		if (2 == $aRet['affectedrows'])
		{
			$this->_vDelCache($vHintId, $aData);
		}
		return $aRet;
	}

	/**
	 * @param Ko_Tool_SQL|Ko_Tool_MONGO|array $oOption
	 * @return int
	 */
	public function iUpdate($vHintId, $vKey, $aUpdate, $aChange=array(), $oOption=null)
	{
		$aKey = $this->_aNormalizedKey($vKey);
		$oOption = $this->_vNormalizeOption($oOption);
		return $this->_iUpdate($vHintId, $aKey, $aUpdate, $aChange, $oOption, false);
	}

	/**
	 * @param Ko_Tool_SQL|Ko_Tool_MONGO|array $oOption
	 * @return int
	 */
	public function iUpdateByCond($vHintId, $oOption, $aUpdate, $aChange=array())
	{
		assert(!Ko_Tool_Option::BIsWhereEmpty($oOption, $this->_bIsMongoDB));
		if (($this->_bUseUO || $this->_iMCacheTime) && count($this->_aKeyField))
		{
			$oOption = $this->_vBuildOption($oOption, $vHintId, array());
			$oOption = $this->_oWriteOption2ReadOption($oOption);
			$aInfo = $this->_oGetSqlAgent()->aSelect($this->_sTable, $this->iGetHintId($vHintId), $oOption, 0, true);
			$iRet = 0;
			foreach ($aInfo as $key)
			{
				$oOptionNew = $oOption->oClone();
				$iRet += $this->_iUpdate($vHintId, $key, $aUpdate, $aChange, $oOptionNew, false);
			}
			return $iRet;
		}
		return $this->_iUpdate($vHintId, array(), $aUpdate, $aChange, $oOption, true);
	}

	/**
	 * @param Ko_Tool_SQL|Ko_Tool_MONGO|array $oOption
	 * @return int
	 */
	public function iDelete($vHintId, $vKey, $oOption=null)
	{
		$aKey = $this->_aNormalizedKey($vKey);
		$oOption = $this->_vNormalizeOption($oOption);
		return $this->_iDelete($vHintId, $aKey, $oOption, false);
	}

	/**
	 * @param Ko_Tool_SQL|Ko_Tool_MONGO|array $oOption
	 * @return int
	 */
	public function iDeleteByCond($vHintId, $oOption)
	{
		assert(!Ko_Tool_Option::BIsWhereEmpty($oOption, $this->_bIsMongoDB));
		if (($this->_bUseUO || $this->_iMCacheTime) && count($this->_aKeyField))
		{
			$oOption = $this->_vBuildOption($oOption, $vHintId, array());
			$oOption = $this->_oWriteOption2ReadOption($oOption);
			$aInfo = $this->_oGetSqlAgent()->aSelect($this->_sTable, $this->iGetHintId($vHintId), $oOption, 0, true);
			$iRet = 0;
			foreach ($aInfo as $key)
			{
				$oOptionNew = $oOption->oClone();
				$iRet += $this->_iDelete($vHintId, $key, $oOptionNew, false);
			}
			return $iRet;
		}
		return $this->_iDelete($vHintId, array(), $oOption, true);
	}

	//////////////////////////// 读取操作 ////////////////////////////

	/**
	 * @return array
	 */
	public function aGet($vHintId, $vKey)
	{
		$aKey = $this->_aNormalizedKey($vKey);
		return $this->_aGet($vHintId, $aKey, false);
	}

	/**
	 * @return array
	 */
	public function aGetListByKeys($vHintId, $aKey, $sKeyField = '')
	{
		assert(1 == count($this->_aKeyField));

		//获取 id 列表
		$keyField = strlen($sKeyField) ? $sKeyField : $this->_aKeyField[0];
		$aKey = Ko_Tool_Utils::AObjs2ids($aKey, $keyField);

		//排重，得到键列表
		list($idkeymap, $keyidmap) = $this->_aGetListByKeys_KeyIdMap($vHintId, $aKey, 0 == strlen($this->_sSplitField));
		$allkeys = array_keys($keyidmap);

		//过滤掉 InProcCache / MCache 中存在的key
		$keys = $this->_oGetDBCache()->aFilterInCache($allkeys);
		KO_DEBUG >= 1 && Ko_Tool_Debug::VAddTmpLog('stat/aGetListByKeys', count($allkeys).':'.count($keys));

		//从 DB / LCache 获取剩下的
		$this->_vGetListByKeysEx($vHintId, $keys, $idkeymap, $keyidmap);

		//拼装并返回结果
		$aRet = array();
		foreach ($aKey as $key)
		{
			$aRet[$key] = $this->_aGet($vHintId, array($this->_aKeyField[0] => $key), true);
		}
		return $aRet;
	}

	/**
	 * @return array
	 */
	public function aGetDetails($oObjs, $sSplitField = '', $sKeyField = '', $bRetmap = true)
	{
		$keyCount = count($this->_aKeyField);
		assert($this->_bUseUO && 1 >= $keyCount);

		//获取 id 列表
		$splitField = strlen($sSplitField) ? $sSplitField : $this->_sSplitField;
		$keyField = $keyCount ? (strlen($sKeyField) ? $sKeyField : $this->_aKeyField[0]) : $splitField;
		list($uids, $ids) = $this->_aObjs2IntIds($oObjs, $splitField, $keyField);

		//去0，排重，返回剩下的键列表
		$keyidmap = $this->_aGetDetails_KeyIdMap($uids, $ids, 0 == $keyCount);
		$allkeys = array_keys($keyidmap);

		//过滤掉 InProcCache / MCache 中存在的key
		$keys = $this->_oGetDBCache()->aFilterInCache($allkeys);
		KO_DEBUG >= 1 && Ko_Tool_Debug::VAddTmpLog('stat/aGetDetails', count($allkeys).':'.count($keys));

		//从 UObject / LCache 获取剩下的
		$this->_vGetDetailsEx($keys, $keyidmap);

		//拼装并返回结果
		$aRet = array();
		foreach ($uids as $i => $uid)
		{
			$key = $bRetmap ? $ids[$i] : $i;
			$aRet[$key] = $this->_aGet($uid, $keyCount ? array($this->_aKeyField[0] => $ids[$i]) : array(), -1, true);
		}
		return $aRet;
	}

	/**
	 * @param Ko_Tool_SQL|Ko_Tool_MONGO|array $oOption
	 * @return array
	 */
	public function aGetList($vHintId, $oOption, $iCacheTime=0)
	{
		$oOption = $this->_vBuildOption($oOption, $vHintId, array());
		return $this->_oGetSqlAgent()->aSelect($this->_sTable, $this->iGetHintId($vHintId), $oOption, $iCacheTime, false);
	}

	public function vDeleteCache($vHintId, $vKey)
	{
		$aKey = $this->_aNormalizedKey($vKey);
		$this->_vDelCache($vHintId, $aKey);
	}

	/**
	 * @return int
	 */
	public function iTableCount()
	{
		return $this->_oGetDirectMysql()->iTableCount();
	}

	/**
	 * @return Ko_Data_Mysql
	 */
	public function oConnectDB($no)
	{
		return $this->_oGetDirectMysql()->oConnectDB($no);
	}

	/**
	 * @return string
	 */
	public function sGetRealTableName($no)
	{
		return $this->_oGetDirectMysql()->sGetRealTableName($no);
	}

	public function vDoFetchSelect($sSql, $fnCallback)
	{
		$this->_oGetDirectMysql()->vDoFetchSelect($sSql, $fnCallback);
	}

	//////////////////////////// 私有函数 ////////////////////////////

	private function _aGet($vHintId, $aKey, $bOnlyFromInProcCache)
	{
		$sCacheKey = $this->_sGetCacheKey($vHintId, $aKey);
		$aRet = $this->_oGetDBCache()->vGet($sCacheKey, !$bOnlyFromInProcCache);
		KO_DEBUG >= 1 && !$bOnlyFromInProcCache && Ko_Tool_Debug::VAddTmpLog('stat/aGet', ($aRet !== false) ? 'cache' : 'miss');
		if ($aRet !== false)
		{
			return $aRet;
		}
		if ($bOnlyFromInProcCache)
		{
			return array();
		}
		$oOption = $this->_oCreateOption();
		$oOption = $this->_vBuildOption($oOption, $vHintId, $aKey);
		$oOption->oLimit(1);
		$aRet = $this->_oGetSqlAgent()->aSelect($this->_sTable, $this->iGetHintId($vHintId), $oOption, 0, true);
		$aRet = empty($aRet) ? array() : $aRet[0];
		if (!empty($aRet))
		{
			$this->_oGetDBCache()->vSet($sCacheKey, $aRet, true);
		}
		return $aRet;
	}

	private function _iUpdate($vHintId, $aKey, $aUpdate, $aChange, $oOption, $bNoCache)
	{
		$oOption = $this->_vBuildOption($oOption, $vHintId, $aKey);
		$iRet = $this->_oGetSqlAgent()->iUpdate($this->_sTable, $this->iGetHintId($vHintId), $aUpdate, $aChange, $oOption);
		if (0 != $iRet && !$bNoCache)
		{
			$this->_vDelCache($vHintId, $aKey);
		}
		return $iRet;
	}

	private function _iDelete($vHintId, $aKey, $oOption, $bNoCache)
	{
		$oOption = $this->_vBuildOption($oOption, $vHintId, $aKey);
		$iRet = $this->_oGetSqlAgent()->iDelete($this->_sTable, $this->iGetHintId($vHintId), $oOption);
		if (0 != $iRet && !$bNoCache)
		{
			$this->_vDelCache($vHintId, $aKey);
		}
		return $iRet;
	}

	private function _vGetListByKeysEx($vHintId, $aKeys, $aIdKeyMap, $aKeyIdMap)
	{
		$ucount = count($aKeys);
		for ($c=0; ; $c+=self::SPLIT_COUNT)
		{
			$uoids = array();
			for ($i=$c; $i<$ucount && $i<$c+self::SPLIT_COUNT; $i++)
			{
				$uoids[$i-$c] = $aKeyIdMap[$aKeys[$i]];
			}
			if (empty($uoids))
			{
				break;
			}
			$oOption = $this->_oCreateOption();
			if ($this->_bIsMongoDB)
			{
				$oOption->oWhere(array($this->_aKeyField[0] => array('$in' => $uoids)));
			}
			else
			{
				$oOption->oWhere($this->_aKeyField[0].' IN (?)', $uoids);
			}
			$oOption = $this->_vBuildOption($oOption, $vHintId, array());
			$aRet = $this->_oGetSqlAgent()->aSelect($this->_sTable, $this->iGetHintId($vHintId), $oOption, 0, true);
			foreach ($aRet as $v)
			{
				if (!empty($v))
				{
					$sCacheKey = $aIdKeyMap[$v[$this->_aKeyField[0]]];
					$this->_oGetDBCache()->vSet($sCacheKey, $v, false);
				}
			}
		}
	}

	private function _vGetDetailsEx($aKeys, $aKeyIdMap)
	{
		$ucount = count($aKeys);
		for ($c=0; ; $c+=self::SPLIT_COUNT)
		{
			$uoids = array();
			for ($i=$c; $i<$ucount && $i<$c+self::SPLIT_COUNT; $i++)
			{
				$uoids[$i-$c] = $this->_oGetUObject()->oCreateLOID($aKeyIdMap[$aKeys[$i]][0], $aKeyIdMap[$aKeys[$i]][1]);
			}
			if (empty($uoids))
			{
				break;
			}
			$aRet = $this->_oGetUObject()->aGetUObjectDetailLong($uoids, $this->_aUoFields);
			foreach ($aRet as $i=>$item)
			{
				if (!empty($item))
				{
					$this->_oGetDBCache()->vSet($aKeys[$i+$c], $item, false);
				}
			}
		}
	}

	private function _vDelCache($vHintId, $aKey)
	{
		if ($this->_bUseUO)
		{
			$key = count($this->_aKeyField) ? $aKey[$this->_aKeyField[0]] : $vHintId;
			$this->_oGetUObject()->vInvalidate($vHintId, $key);
		}
		$sCacheKey = $this->_sGetCacheKey($vHintId, $aKey);
		$this->_oGetDBCache()->vDel($sCacheKey);
	}

	private function _aGetListByKeys_KeyIdMap($vHintId, $aKey, $bSplitIsEmpty)
	{
		$aIdKeyMap = $aKeyIdMap = array();
		foreach ($aKey as $key)
		{
			$sCacheKey = '';
			if (!$bSplitIsEmpty)
			{
				$sCacheKey .= urlencode($vHintId).':';
			}
			$sCacheKey .= $this->_aKeyField[0].':'.urlencode($key);
			$aIdKeyMap[$key] = $sCacheKey;
			$aKeyIdMap[$sCacheKey] = $key;
		}
		return array($aIdKeyMap, $aKeyIdMap);
	}

	private function _aGetDetails_KeyIdMap($aUids, $aIds, $bKeyIsEmpty)
	{
		$aKeyIdMap = array();
		foreach ($aUids as $i=>$uid)
		{
			if (0 == $uid || 0 == $aIds[$i])
			{
				continue;
			}
			$sCacheKey = urlencode($uid);
			if (!$bKeyIsEmpty)
			{
				$sCacheKey .= ':'.$this->_aKeyField[0].':'.urlencode($aIds[$i]);
			}
			$aKeyIdMap[$sCacheKey] = array($uid, $aIds[$i]);
		}
		return $aKeyIdMap;
	}

	private function _sGetCacheKey($vHintId, $aKey)
	{
		$keys = array();
		if (strlen($this->_sSplitField))
		{
			$keys[] = urlencode($vHintId);
		}
		foreach ($this->_aKeyField as $key)
		{
			$keys[] = $key.':'.urlencode($aKey[$key]);
		}
		return implode(':', $keys);
	}

	private function _vBuildOption($oOption, $vHintId, $aKey)
	{
		if (strlen($this->_sSplitField))
		{
			if ($this->_bIsMongoDB)
			{
				if (is_array($oOption))
				{
					$oOption[$this->_sSplitField] = $vHintId;
				}
				else
				{
					$oOption->oAnd(array($this->_sSplitField => $vHintId));
				}
			}
			else
			{
				$oOption->oAnd($this->_sSplitField.' = ?', $vHintId);
			}
		}
		foreach ($this->_aKeyField as $key)
		{
			if (array_key_exists($key, $aKey))
			{
				if ($this->_bIsMongoDB)
				{
					if (is_array($oOption))
					{
						$oOption[$key] = $aKey[$key];
					}
					else
					{
						$oOption->oAnd(array($key => $aKey[$key]));
					}
				}
				else
				{
					$oOption->oAnd($key.' = ?', $aKey[$key]);
				}
			}
		}
		return $oOption;
	}

	private function _oGetSqlAgent()
	{
		if (is_null($this->_oSqlAgent))
		{
			if ($this->_bIsMongoDB)
			{
				$this->_oSqlAgent = Ko_Data_MongoDB::OInstance($this->_sDBAgentName);
			}
			else
			{
				$this->_oSqlAgent = Ko_Data_SqlAgent::OInstance($this->_sDBAgentName);
			}
		}
		return $this->_oSqlAgent;
	}

	private function _oGetIdGenerator()
	{
		if (is_null($this->_oIdGenerator))
		{
			$this->_oIdGenerator = Ko_Data_IDMan::OInstance();
		}
		return $this->_oIdGenerator;
	}

	private function _oGetDBCache()
	{
		if (is_null($this->_oDBCache))
		{
			$this->_oDBCache = new Ko_Data_DBCache($this->_sTable, $this->_sMCacheName, $this->_iMCacheTime);
		}
		return $this->_oDBCache;
	}

	private function _oGetUObject()
	{
		if (is_null($this->_oUObject))
		{
			$sKeyField = count($this->_aKeyField) ? $this->_aKeyField[0] : $this->_sSplitField;
			$this->_oUObject = Ko_Data_UObjectAgent::OInstance($this->_sTable, $this->_sSplitField, $sKeyField, $this->_sUoName);
		}
		return $this->_oUObject;
	}

	private function _oGetDirectMysql()
	{
		if (is_null($this->_oDirectMysql))
		{
			$this->_oDirectMysql = Ko_Dao_MysqlAgent::OInstance($this->_sTable, $this->_bIsDirectSlave);
		}
		return $this->_oDirectMysql;
	}

	private function _aObjs2IntIds($aObjs, $sUidKey, $sIdKey)
	{
		$uids = $ids = array();
		foreach($aObjs as $obj)
		{
			if (is_array($obj))
			{
				$uids[] = intval($obj[$sUidKey]);
				$ids[] = intval($obj[$sIdKey]);
			}
			else if (is_object($obj))
			{
				$uids[] = intval($obj->$sUidKey);
				$ids[] = intval($obj->$sIdKey);
			}
			else
			{
				$uids[] = $ids[] = intval($obj);
			}
		}
		return array($uids, $ids);
	}

	private function _aNormalizedKeyField($vKeyField, $sSplitField)
	{
		if (!is_array($vKeyField))
		{
			if (strlen($vKeyField))
			{
				$vKeyField = array($vKeyField);
			}
			else
			{
				$vKeyField = array();
			}
		}
		return array_values(array_unique(array_diff($vKeyField, array($sSplitField))));
	}

	private function _aNormalizedKey($vKey)
	{
		if (!is_array($vKey))
		{
			$vKey = count($this->_aKeyField) ? array($this->_aKeyField[0] => $vKey) : array();
		}
		foreach ($this->_aKeyField as $key)
		{
			assert(array_key_exists($key, $vKey));
		}
		return $vKey;
	}

	private function _vNormalizeOption($oOption)
	{
		if ($this->_bIsMongoDB)
		{
			if (!is_array($oOption) && !($oOption INSTANCEOF Ko_Tool_MONGO))
			{
				$oOption = array();
			}
		}
		else if (!($oOption INSTANCEOF Ko_Tool_SQL))
		{
			$oOption = new Ko_Tool_SQL;
		}
		return $oOption;
	}

	private function _oWriteOption2ReadOption($oOption)
	{
		if ($this->_bIsMongoDB)
		{
			if (is_array($oOption))
			{
				$tmp = new Ko_Tool_MONGO;
				$tmp->oWhere($oOption);
				$oOption = $tmp;
			}
			$aFields = array();
			foreach ($this->_aKeyField as $key)
			{
				$aFields[$key] = true;
			}
			$oOption->oSelect($aFields)->oOrderBy(array())->oOffset(0)->oLimit(0)->oCalcFoundRows(false);
		}
		else
		{
			$oOption->oSelect(implode(', ', $this->_aKeyField))->oOffset(0)->oGroupBy('')->oHaving('')->oCalcFoundRows(false);
		}
		return $oOption;
	}

	private function _oCreateOption()
	{
		if ($this->_bIsMongoDB)
		{
			$oOption = new Ko_Tool_MONGO;
		}
		else
		{
			$oOption = new Ko_Tool_SQL;
		}
		return $oOption;
	}
}

?>