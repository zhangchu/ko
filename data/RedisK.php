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
class Ko_Data_RedisK extends Ko_Data_KProxy implements IKo_Data_Redis, IKo_Data_RedisAgent
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
	
	public function vAppend($sKey, $sValue)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('APPEND', $sKey, $sValue),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	public function vIncr($sKey, $iValue = 1)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('INCRBY', $sKey, $iValue),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
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
	
	public function vGet($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('GET', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vResBlob2StrBool($res);
	}
	
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
	
	public function vStrlen($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('STRLEN', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	public function vGetRange($sKey, $iStart, $iEnd)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('GETRANGE', $sKey, $iStart, $iEnd),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vResBlob2StrBool($res);
	}
	
	public function vSetRange($sKey, $iOffset, $sValue)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('SETRANGE', $sKey, $iOffset, $sValue),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}

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
	
	public function vHGet($sKey, $sField)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('HGET', $sKey, $sField),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vResBlob2StrBool($res);
	}
	
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
	
	public function vHGetAll($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('HGETALL', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2HashBool($res);
	}
	
	public function vHKeys($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('HKEYS', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2ListBool($res);
	}
	
	public function vHVals($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('HVALS', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2ListBool($res);
	}
	
	public function vHLen($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('HLEN', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	public function vHIncr($sKey, $sField, $iValue = 1)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('HINCRBY', $sKey, $sField, $iValue),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
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
	
	public function vBLPop($aKey, $iTimeout)
	{
		assert(0);
	}
	
	public function vBRPop($aKey, $iTimeout)
	{
		assert(0);
	}
	
	public function vLIndex($sKey, $iIndex)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('LINDEX', $sKey, $iIndex),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vResBlob2StrBool($res);
	}
	
	public function vLLen($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('LLEN', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	public function vLInsert($sKey, $sPostion, $sPivot, $sValue)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('LINSERT', $sKey, $sPostion, $sPivot, $sValue),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	public function vLPop($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('LPOP', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vResBlob2StrBool($res);
	}
	
	public function vRPop($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('RPOP', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vResBlob2StrBool($res);
	}
	
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
	
	public function vLPush($sKey, $sValue)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('LPUSH', $sKey, $sValue),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	public function vRPush($sKey, $sValue)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('RPUSH', $sKey, $sValue),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	public function vLPushX($sKey, $sValue)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('LPUSHX', $sKey, $sValue),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	public function vRPushX($sKey, $sValue)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('RPUSHX', $sKey, $sValue),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	public function vLRange($sKey, $iStart, $iStop)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('LRANGE', $sKey, $iStart, $iStop),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2ListBool($res);
	}
	
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
	
	public function vSAdd($sKey, $sMember)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('SADD', $sKey, $sMember),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	public function vSCard($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('SCARD', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	public function vSDiff($aKey)
	{
		assert(0);
	}
	
	public function vSDiffStore($sKey, $aKey)
	{
		assert(0);
	}
	
	public function vSInter($aKey)
	{
		assert(0);
	}
	
	public function vSInterStore($sKey, $aKey)
	{
		assert(0);
	}
	
	public function vSUnion($aKey)
	{
		assert(0);
	}
	
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
	
	public function vSPop($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('SPOP', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vResBlob2StrBool($res);
	}
	
	public function vSRandMember($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('SRANDMEMBER', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vResBlob2StrBool($res);
	}
	
	public function vSRem($sKey, $sMember)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('SREM', $sKey, $sMember),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	public function vZAdd($sKey, $fScore, $sMember)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('ZADD', $sKey, $fScore, $sMember),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	public function vZCard($sKey)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('ZCARD', $sKey),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	public function vZCount($sKey, $sMin, $sMax)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('ZCOUNT', $sKey, $sMin, $sMax),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
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
	
	public function vZRevRangeByScore($sKey, $sMin, $sMax, $aOption = array())
	{
		$bWithScores = isset($aOption['withscores']) && $aOption['withscores'];
		$cmd = array('ZREVRANGEBYSCORE', $sKey, $sMin, $sMax);
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
	
	public function vZRemRangeByRank($sKey, $iStart, $iStop)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('ZREMRANGEBYRANK', $sKey, $iStart, $iStop),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	public function vZRemRangeByScore($sKey, $sMin, $sMax)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('ZREMRANGEBYSCORE', $sKey, $sMin, $sMax),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	public function vZRank($sKey, $sMember)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('ZRANK', $sKey, $sMember),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
	public function vZRevRank($sKey, $sMember)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('ZREVRANK', $sKey, $sMember),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
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
	
	public function vZRem($sKey, $sMember)
	{
		$aPara = array(
			'key' => $sKey,
			'cmd' => array('ZREM', $sKey, $sMember),
		);
		$res = $this->_oProxy->invoke('_1CALL', $aPara);
		return $this->_vRes2IntBool($res);
	}
	
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
	
	public function vZInterStore($sKey, $aKey, $aWeight, $sAggregate = 'SUM')
	{
		assert(0);
	}
	
	public function vZUnionStore($sKey, $aKey, $aWeight, $sAggregate = 'SUM')
	{
		assert(0);
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
		for ($i=0; $i<$klen; $i++)
		{
			$ret[$aKey[$i]] = $aValue[$i];
		}
		return $ret;
	}
}

?>