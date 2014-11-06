<?php
/**
 * RedisK
 *
 * @package ko\data
 * @author zhangchu
 */

/**
 * 封装 KProxy 的 Redis 的实现
 */
class Ko_Data_RedisK extends Ko_Data_KProxy
{
	const REDIS_NOT_FOUND = 0;
	const REDIS_STRING = 1;
	const REDIS_SET = 2;
	const REDIS_LIST = 3;
	const REDIS_ZSET = 4;
	const REDIS_HASH = 5;
	
	const BEFORE = 'before';
	const AFTER = 'after';
	
	private static $s_aInstances = array();

	protected function __construct ($sTag)
	{
		KO_DEBUG >= 6 && Ko_Tool_Debug::VAddTmpLog('data/RedisK', '__construct:'.$sTag);
		parent::__construct('Redis' , $sTag);
	}
	
	public static function OInstance($sName = '', $sRedisHost = '')
	{
		if (empty(self::$s_aInstances[$sName]))
		{
			self::$s_aInstances[$sName] = new self($sName);
		}
		return self::$s_aInstances[$sName];
	}

	public function iDel($aKey)
	{
		if (!is_array($aKey))
		{
			return $this->iDel(array($aKey));
		}
		$ret = 0;
		foreach ($aKey as $sKey)
		{
			$aPara = array(
				'key' => $sKey,
				'cmd' => array('DEL', $sKey),
			);
			$res = $this->_oProxy->invoke('_1CALL', $aPara);
			$ret += $this->_iRes2Int($res);
		}
		return $ret;
	}

	public function bExists($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('EXISTS', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_bResInt2Bool($res);
	}

	public function bExpire($sKey, $iSecond)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('EXPIRE', $sKey, $iSecond),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_bResInt2Bool($res);
	}

	public function bExpireAt($sKey, $iTimestamp)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('EXPIREAT', $sKey, $iTimestamp),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_bResInt2Bool($res);
	}

	public function aKeys($sPattern)
	{
		assert(0);
	}

	public function bPersist($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('PERSIST', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_bResInt2Bool($res);
	}

	public function bRename($sKey, $sNewkey)
	{
		assert(0);
	}

	public function bRenameNX($sKey, $sNewkey)
	{
		assert(0);
	}

	public function iTTL($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('TTL', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_iRes2Int($res);
	}

	public function iType($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('TYPE', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		assert(is_string($res['result']));
		switch($res['result'])
		{
		case '+string':
			return self::REDIS_STRING;
		case '+set':
			return self::REDIS_SET;
		case '+list':
			return self::REDIS_LIST;
		case '+zset':
			return self::REDIS_ZSET;
		case '+hash':
			return self::REDIS_HASH;
		}
		return self::REDIS_NOT_FOUND;
	}
	
	/**
	 * @return int|boolean the length of the string after the append operation.
	 *                     false if key holds a value that is not a string
	 */
	public function vAppend($sKey, $sValue)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('APPEND', $sKey, $sValue),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	/**
	 * @return int|boolean the value of key after the increment/decrement
	 *                     false if the key contains a value of the wrong type or contains a string that can not be represented as integer.
	 */
	public function vIncr($sKey, $iValue = 1)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('INCRBY', $sKey, $iValue),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	/**
	 * @return boolean always true
	 */
	public function bSet($sKey, $sValue)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('SET', $sKey, $sValue),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_bResStr2Bool($res);
	}
	
	public function bSetEx($sKey, $iSecond, $sValue)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('SETEX', $sKey, $iSecond, $sValue),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_bResStr2Bool($res);
	}
	
	public function bSetNX($sKey, $sValue)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('SETNX', $sKey, $sValue),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_bResInt2Bool($res);
	}
	
	/**
	 * @return boolean always true
	 */
	public function bMSet($aData)
	{
		foreach ($aData as $k => $v)
		{
			$this->bSet($k, $v);
		}
		return true;
	}
	
	public function bMSetNX($aData)
	{
		assert(0);
	}
	
	/**
	 * @return string|boolean the value of key
	 *                        false if key does not exist
	 *                        false if key holds a value that is not a string
	 */
	public function vGet($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('GET', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vResBlob2StrBool($res);
	}
	
	/**
	 * @return string|boolean the value of key
	 *                        false if key does not exist
	 *                        false if key holds a value that is not a string
	 */
	public function vGetSet($sKey, $sValue)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('GETSET', $sKey, $sValue),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vResBlob2StrBool($res);
	}
	
	public function aMGet($aKey)
	{
		$aPara = array(
			'keys' => $aKey,
		);
		$res = $this->_oProxy->invoke('getMulti', $aPara);
		assert(is_array($res['values']));
		$ret = array();
		foreach ($aKey as $k)
		{
			if (isset($res['values'][$k]))
			{
				assert($res['values'][$k] INSTANCEOF vbs_B);
				$ret[] = strval($res['values'][$k]);
			}
			else
			{
				$ret[] = false;
			}
		}
		return $ret;
	}
	
	/**
	 * @return int|boolean the length of the string at key, or 0 when key does not exist.
	 *                     false if key holds a value that is not a string
	 */
	public function vStrlen($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('STRLEN', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	/**
	 * @return string|boolean false if key holds a value that is not a string
	 */
	public function vGetRange($sKey, $iStart, $iEnd)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('GETRANGE', $sKey, $iStart, $iEnd),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vResBlob2StrBool($res);
	}
	
	/**
	 * @return int|boolean the length of the string after it was modified by the command.
	 *                     false if key holds a value that is not a string
	 */
	public function vSetRange($sKey, $iOffset, $sValue)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('SETRANGE', $sKey, $iOffset, $sValue),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}

	/**
	 * @return int|boolean the number of fields that were removed from the hash, not including specified but non existing fields.
	 *                     false if key holds a value that is not a hash
	 */
	public function vHDel($sKey, $sField)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('HDEL', $sKey, $sField),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	public function bHExists($sKey, $sField)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('HEXISTS', $sKey, $sField),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_bResInt2Bool($res);
	}
	
	/**
	 * @return string|boolean the value associated with field
	 *                        false if field is not present in the hash or key does not exist
	 */
	public function vHGet($sKey, $sField)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('HGET', $sKey, $sField),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vResBlob2StrBool($res);
	}
	
	/**
	 * @return array|boolean list of values associated with the given fields, in the same order as they are requested.
	 *                       false if key holds a value that is not a hash
	 */
	public function vHMGet($sKey, $aField)
	{
		$cmd = array_merge(array('HMGET', $sKey), $aField);
		$aPara = array(
			'key' => $sKey,
			'cmd' => $cmd,
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		$ret = $this->_vRes2ListBool($res);
		if (is_bool($ret))
		{
			return $ret;
		}
		return $this->_aGetHashArr($aField, $ret);
	}
	
	/**
	 * @return array|boolean list of fields and their values stored in the hash
	 *                       false if key holds a value that is not a hash
	 */
	public function vHGetAll($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('HGETALL', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2HashBool($res);
	}
	
	/**
	 * @return array|boolean list of fields in the hash, or an empty list when key does not exist.
	 *                       false if key holds a value that is not a hash
	 */
	public function vHKeys($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('HKEYS', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2ListBool($res);
	}
	
	/**
	 * @return array|boolean list of values in the hash, or an empty list when key does not exist.
	 *                       false if key holds a value that is not a hash
	 */
	public function vHVals($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('HVALS', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2ListBool($res);
	}
	
	/**
	 * @return int|boolean number of fields in the hash, or 0 when key does not exist.
	 *                     false if key holds a value that is not a hash
	 */
	public function vHLen($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('HLEN', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	/**
	 * @return int|boolean the value at field after the increment operation.
	 *                     false if key holds a value that is not a hash
	 */
	public function vHIncr($sKey, $sField, $iValue = 1)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('HINCRBY', $sKey, $sField, $iValue),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	/**
	 * @return int|boolean 1 if field is a new field in the hash and value was set. 
	 *                     0 if field already exists in the hash and the value was updated.
	 *                     false if key holds a value that is not a hash
	 */
	public function vHSet($sKey, $sField, $sValue)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('HSET', $sKey, $sField, $sValue),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	public function bHSetNX($sKey, $sField, $sValue)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('HSETNX', $sKey, $sField, $sValue),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_bResInt2Bool($res);
	}
	
	public function bHMSet($sKey, $aData)
	{
		$cmd = array('HMSET', $sKey);
		foreach ($aData as $k => $v)
		{
			$cmd[] = $k;
			$cmd[] = $v;
		}
		$aPara = array(
			'key' => $sKey,
			'cmd' => $cmd,
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_bResStr2Bool($res);
	}
	
	/**
	 * @return array|boolean a two-element array with key and value.
	 *                       false if no element could be popped and the timeout expired.
	 */
	public function vBLPop($aKey, $iTimeout)
	{
		assert(0);
	}
	
	/**
	 * @return array|boolean a two-element array with key and value.
	 *                       false if no element could be popped and the timeout expired.
	 */
	public function vBRPop($aKey, $iTimeout)
	{
		assert(0);
	}
	
	/**
	 * @return string|boolean the requested element
	 *                        false if index is out of range or key holds a value that is not a list
	 */
	public function vLIndex($sKey, $iIndex)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('LINDEX', $sKey, $iIndex),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vResBlob2StrBool($res);
	}
	
	/**
	 * @return int|boolean the length of the list at key.
	 *                     false if key holds a value that is not a list
	 */
	public function vLLen($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('LLEN', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	/**
	 * @return int|boolean the length of the list after the insert operation, or -1 when the value pivot was not found.
	 *                     false if key holds a value that is not a list
	 */
	public function vLInsert($sKey, $sPostion, $sPivot, $sValue)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('LINSERT', $sKey, $sPostion, $sPivot, $sValue),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	/**
	 * @return string|boolean the value of the first element
	 *                        false if key does not exist or key holds a value that is not a list
	 */
	public function vLPop($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('LPOP', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vResBlob2StrBool($res);
	}
	
	/**
	 * @return string|boolean the value of the last element
	 *                        false if key does not exist or key holds a value that is not a list
	 */
	public function vRPop($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('RPOP', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vResBlob2StrBool($res);
	}
	
	/**
	 * @return string|boolean the element being popped and pushed.
	 *                        false if src does not exist or src holds a value that is not a list
	 *                        false if des holds a value that is not a list
	 */
	public function vRPopLPush($sSrc, $sDes)
	{
		assert($sSrc === $sDes);
		$aPara = array(
			'key' => $sSrc,
			'cmd' => array('RPOPLPUSH', $sSrc, $sDes),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vResBlob2StrBool($res);
	}
	
	/**
	 * @return int|boolean the length of the list after the push operations.
	 *                     false if key holds a value that is not a list
	 */
	public function vLPush($sKey, $sValue)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('LPUSH', $sKey, $sValue),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	/**
	 * @return int|boolean the length of the list after the push operations.
	 *                     false if key holds a value that is not a list
	 */
	public function vRPush($sKey, $sValue)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('RPUSH', $sKey, $sValue),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	/**
	 * @return int|boolean the length of the list after the push operations.
	 *                     0 if key does not exist
	 *                     false if key holds a value that is not a list
	 */
	public function vLPushX($sKey, $sValue)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('LPUSHX', $sKey, $sValue),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	/**
	 * @return int|boolean the length of the list after the push operations.
	 *                     0 if key does not exist
	 *                     false if key holds a value that is not a list
	 */
	public function vRPushX($sKey, $sValue)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('RPUSHX', $sKey, $sValue),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	/**
	 * @return array|boolean list of elements in the specified range.
	 *                       false if key holds a value that is not a list
	 */
	public function vLRange($sKey, $iStart, $iStop)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('LRANGE', $sKey, $iStart, $iStop),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2ListBool($res);
	}
	
	/**
	 * @return int|boolean the number of removed elements.
	 *                     false if key holds a value that is not a list
	 */
	public function vLRem($sKey, $iCount, $sValue)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('LREM', $sKey, $iCount, $sValue),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	public function bLSet($sKey, $iIndex, $sValue)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('LSET', $sKey, $iIndex, $sValue),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_bResStr2Bool($res);
	}
	
	public function bLTrim($sKey, $iStart, $iStop)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('LTRIM', $sKey, $iStart, $iStop),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_bResStr2Bool($res);
	}
	
	/**
	 * @return int|boolean the number of elements that were added to the set, not including all the elements already present into the set.
	 *                     false if key holds a value that is not a set
	 */
	public function vSAdd($sKey, $sMember)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('SADD', $sKey, $sMember),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	/**
	 * @return int|boolean the cardinality (number of elements) of the set, or 0 if key does not exist.
	 *                     false if key holds a value that is not a set
	 */
	public function vSCard($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('SCARD', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	/**
	 * @return array|boolean list with members of the resulting set.
	 *                       false if key holds a value that is not a set
	 */
	public function vSDiff($aKey)
	{
		assert(0);
	}
	
	/**
	 * @return int|boolean the number of elements in the resulting set.
	 *                     false if key holds a value that is not a set
	 */
	public function vSDiffStore($sKey, $aKey)
	{
		assert(0);
	}
	
	/**
	 * @return array|boolean list with members of the resulting set.
	 *                       false if key holds a value that is not a set
	 */
	public function vSInter($aKey)
	{
		assert(0);
	}
	
	/**
	 * @return int|boolean the number of elements in the resulting set.
	 *                     false if key holds a value that is not a set
	 */
	public function vSInterStore($sKey, $aKey)
	{
		assert(0);
	}
	
	/**
	 * @return array|boolean list with members of the resulting set.
	 *                       false if key holds a value that is not a set
	 */
	public function vSUnion($aKey)
	{
		assert(0);
	}
	
	/**
	 * @return int|boolean the number of elements in the resulting set.
	 *                     false if key holds a value that is not a set
	 */
	public function vSUnionStore($sKey, $aKey)
	{
		assert(0);
	}
	
	public function bSIsMember($sKey, $sMember)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('SISMEMBER', $sKey, $sMember),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_bResInt2Bool($res);
	}
	
	/**
	 * @return array|boolean all elements of the set.
	 *                       false if key holds a value that is not a set
	 */
	public function vSMembers($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('SMEMBERS', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2ListBool($res);
	}
	
	public function bSMove($sSrc, $sDes, $sMember)
	{
		assert(0);
	}
	
	/**
	 * @return string|boolean the removed element
	 *                        false if key does not exist or key holds a value that is not a set
	 */
	public function vSPop($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('SPOP', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vResBlob2StrBool($res);
	}
	
	/**
	 * @return string|boolean the randomly selected element
	 *                        false if key does not exist or key holds a value that is not a set
	 */
	public function vSRandMember($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('SRANDMEMBER', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vResBlob2StrBool($res);
	}
	
	/**
	 * @return int|boolean the number of members that were removed from the set, not including non existing members.
	 *                     false if key holds a value that is not a set
	 */
	public function vSRem($sKey, $sMember)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('SREM', $sKey, $sMember),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	/**
	 * @return int|boolean The number of elements added to the sorted sets, not including elements already existing for which the score was updated.
	 *                     false if key holds a value that is not a sorted set
	 */
	public function vZAdd($sKey, $fScore, $sMember)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('ZADD', $sKey, $fScore, $sMember),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	/**
	 * @return int|boolean the cardinality (number of elements) of the sorted set, or 0 if key does not exist.
	 *                     false if key holds a value that is not a sorted set
	 */
	public function vZCard($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('ZCARD', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	/**
	 * @return int|boolean the number of elements in the specified score range.
	 *                     false if key holds a value that is not a sorted set
	 */
	public function vZCount($sKey, $sMin, $sMax)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('ZCOUNT', $sKey, $sMin, $sMax),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	/**
	 * @return array|boolean list of elements in the specified range (optionally with their scores).
	 *                       false if key holds a value that is not a sorted set
	 */
	public function vZRange($sKey, $iStart, $iStop, $bWithScores = false)
	{
		$cmd = array('ZRANGE', $sKey, $iStart, $iStop);
		if ($bWithScores)
		{
			$cmd[] = 'WITHSCORES';
		}
		$aPara = array(
			'key' => $sKey,
			'cmd' => $cmd,
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $bWithScores ? $this->_vRes2HashBool($res) : $this->_vRes2ListBool($res);
	}
	
	/**
	 * @return array|boolean list of elements in the specified score range (optionally with their scores).
	 *                       false if key holds a value that is not a sorted set
	 */
	public function vZRangeByScore($sKey, $sMin, $sMax, $aOption = array())
	{
		$bWithScores = isset($aOption['withscores']) && $aOption['withscores'];
		$cmd = array('ZRANGEBYSCORE', $sKey, $sMin, $sMax);
		if ($bWithScores)
		{
			$cmd[] = 'WITHSCORES';
		}
		if (isset($aOption['limit'][0]) && isset($aOption['limit'][1]))
		{
			$cmd[] = 'LIMIT';
			$cmd[] = $aOption['limit'][0];
			$cmd[] = $aOption['limit'][1];
		}
		$aPara = array(
			'key' => $sKey,
			'cmd' => $cmd,
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $bWithScores ? $this->_vRes2HashBool($res) : $this->_vRes2ListBool($res);
	}
	
	/**
	 * @return array|boolean list of elements in the specified range (optionally with their scores).
	 *                       false if key holds a value that is not a sorted set
	 */
	public function vZRevRange($sKey, $iStart, $iStop, $bWithScores = false)
	{
		$cmd = array('ZREVRANGE', $sKey, $iStart, $iStop);
		if ($bWithScores)
		{
			$cmd[] = 'WITHSCORES';
		}
		$aPara = array(
			'key' => $sKey,
			'cmd' => $cmd,
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $bWithScores ? $this->_vRes2HashBool($res) : $this->_vRes2ListBool($res);
	}
	
	/**
	 * @return array|boolean list of elements in the specified score range (optionally with their scores).
	 *                       false if key holds a value that is not a sorted set
	 */
	public function vZRevRangeByScore($sKey, $sMax, $sMin, $aOption = array())
	{
		$bWithScores = isset($aOption['withscores']) && $aOption['withscores'];
		$cmd = array('ZREVRANGEBYSCORE', $sKey, $sMax, $sMin);
		if ($bWithScores)
		{
			$cmd[] = 'WITHSCORES';
		}
		if (isset($aOption['limit'][0]) && isset($aOption['limit'][1]))
		{
			$cmd[] = 'LIMIT';
			$cmd[] = $aOption['limit'][0];
			$cmd[] = $aOption['limit'][1];
		}
		$aPara = array(
			'key' => $sKey,
			'cmd' => $cmd,
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $bWithScores ? $this->_vRes2HashBool($res) : $this->_vRes2ListBool($res);
	}
	
	/**
	 * @return int|boolean the number of elements removed.
	 *                     false if key holds a value that is not a sorted set
	 */
	public function vZRemRangeByRank($sKey, $iStart, $iStop)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('ZREMRANGEBYRANK', $sKey, $iStart, $iStop),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	/**
	 * @return int|boolean the number of elements removed.
	 *                     false if key holds a value that is not a sorted set
	 */
	public function vZRemRangeByScore($sKey, $sMin, $sMax)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('ZREMRANGEBYSCORE', $sKey, $sMin, $sMax),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	/**
	 * @return int|boolean the rank of member.
	 *                     false If member does not exist in the sorted set or key does not exist
	 *                     false if key holds a value that is not a sorted set
	 */
	public function vZRank($sKey, $sMember)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('ZRANK', $sKey, $sMember),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	/**
	 * @return int|boolean the rank of member.
	 *                     false If member does not exist in the sorted set or key does not exist
	 *                     false if key holds a value that is not a sorted set
	 */
	public function vZRevRank($sKey, $sMember)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('ZREVRANK', $sKey, $sMember),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	/**
	 * @return double|boolean the score of member (a double precision floating point number), represented as string.
	 *                        false If member does not exist in the sorted set or key does not exist
	 *                        false if key holds a value that is not a sorted set
	 */
	public function vZScore($sKey, $sMember)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('ZSCORE', $sKey, $sMember),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		$ret = $this->_vResBlob2StrBool($res);
		if (is_bool($ret))
		{
			return $ret;
		}
		return floatval($ret);
	}
	
	/**
	 * @return int|boolean The number of members removed from the sorted set, not including non existing members.
	 *                     false if key holds a value that is not a sorted set
	 */
	public function vZRem($sKey, $sMember)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('ZREM', $sKey, $sMember),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	/**
	 * @return double|boolean the new score of member (a double precision floating point number), represented as string.
	 *                        false if key holds a value that is not a sorted set
	 */
	public function vZIncr($sKey, $fValue, $sMember)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('ZINCRBY', $sKey, $fValue, $sMember),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		$ret = $this->_vResBlob2StrBool($res);
		if (is_bool($ret))
		{
			return $ret;
		}
		return floatval($ret);
	}
	
	/**
	 * @return int|boolean the number of elements in the resulting sorted set at destination.
	 *                     false if key holds a value that is not a sorted set
	 */
	public function vZInterStore($sKey, $aKey, $aWeight, $sAggregate = 'SUM')
	{
		$cmd = array('ZINTERSTORE', $sKey, count($aKey));
		foreach($aKey as $sName)
		{
			$cmd[] = $sName;
		}
		$cmd[] = 'WEIGHTS';
		foreach($aWeight as $iWeight)
		{
			$cmd[] = $iWeight;
		}
		$cmd[] = 'AGGREGATE';
		$cmd[] = $sAggregate;
		$aPara = array(
			'key' => $sKey,
			'cmd' => $cmd,
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	/**
	 * @return int|boolean the number of elements in the resulting sorted set at destination.
	 *                     false if key holds a value that is not a sorted set
	 */
	public function vZUnionStore($sKey, $aKey, $aWeight, $sAggregate = 'SUM')
	{
		$cmd = array('ZUNIONSTORE', $sKey, count($aKey));
		foreach($aKey as $sName)
		{
			$cmd[] = $sName;
		}
		$cmd[] = 'WEIGHTS';
		foreach($aWeight as $iWeight)
		{
			$cmd[] = $iWeight;
		}
		$cmd[] = 'AGGREGATE';
		$cmd[] = $sAggregate;
		$aPara = array(
			'key' => $sKey,
			'cmd' => $cmd,
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}

	private function _iRes2Int($aRes)
	{
		assert(is_int($aRes['result']));
		return $aRes['result'];
	}
	
	private function _bResInt2Bool($aRes)
	{
		return (is_int($aRes['result']) && $aRes['result']) ? true : false;
	}
	
	private function _vRes2IntBool($aRes)
	{
		return is_int($aRes['result']) ? $aRes['result'] : false;
	}
	
	private function _bResStr2Bool($aRes)
	{
		return is_string($aRes['result']) && '+' === substr($aRes['result'], 0, 1);
	}
	
	private function _vResBlob2StrBool($aRes)
	{
		return ($aRes['result'] INSTANCEOF vbs_B) ? strval($aRes['result']) : false;
	}
	
	private function _vRes2ListBool($aRes)
	{
		if (!is_array($aRes['result']))
		{
			return false;
		}
		$ret = array();
		foreach($aRes['result'] as $v)
		{
			if($v INSTANCEOF vbs_B)
			{
				$ret[] = strval($v);
			}
			else
			{
				$ret[] = false;
			}
		}
		return $ret;
	}

	private function _vRes2HashBool($aRes)
	{
		if (!is_array($aRes['result']))
		{
			return false;
		}
		$len = count($aRes['result']);
		assert(0 === $len % 2);
		$ret = array();
		for ($i=0; $i<$len; $i+=2)
		{
			assert($aRes['result'][$i] INSTANCEOF vbs_B);
			assert($aRes['result'][$i+1] INSTANCEOF vbs_B);
			$ret[strval($aRes['result'][$i])] = strval($aRes['result'][$i+1]);
		}
		return $ret;
	}
	
	private function _aGetHashArr($aKey, $aValue)
	{
		$klen = count($aKey);
		$vlen = count($aValue);
		assert($klen === $vlen);
		$ret = array();
		$i = 0;
		foreach ($aKey as $key)
		{
			$ret[$key] = $aValue[$i];
			$i ++;
		}
		return $ret;
	}
}

?>