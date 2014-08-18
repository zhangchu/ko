<?php
/**
 * Redis
 *
 * @package ko\data
 * @author zhangchu
 */

/**
 * 封装 Redis 对象的实现
 *     忽略 redis 连接错误
 */
class Ko_Data_Redis
{
	private static $s_aInstances = array();
	
	private $_oRedis = null;

	protected function __construct ($sTag, $sRedisHost)
	{
		KO_DEBUG >= 6 && Ko_Tool_Debug::VAddTmpLog('data/Redis', '__construct:'.$sTag);
		if (!strlen($sRedisHost))
		{
			$sRedisHost = KO_REDIS_HOST;
		}
		list($host, $port) = explode(':', $sRedisHost);
		$this->_oRedis = new Redis;
		if (!$this->_oRedis->connect($host, $port))
		{
			$this->_oRedis = null;
		}
	}

	public static function OInstance($sName = '', $sRedisHost = '')
	{
		if (empty(self::$s_aInstances[$sName]))
		{
			self::$s_aInstances[$sName] = new self($sName, $sRedisHost);
		}
		return self::$s_aInstances[$sName];
	}
	
	public function iDel($aKey)
	{
		return $this->_oRedis->del($aKey);
	}
	
	public function bExists($sKey)
	{
		return $this->_oRedis->exists($sKey);
	}

	public function bExpire($sKey, $iSecond)
	{
		return $this->_oRedis->expire($sKey, $iSecond);
	}
	
	public function bExpireAt($sKey, $iTimestamp)
	{
		return $this->_oRedis->expireAt($sKey, $iTimestamp);
	}
	
	public function aKeys($sPattern)
	{
		return $this->_oRedis->keys($sPattern);
	}
	
	public function bPersist($sKey)
	{
		return $this->_oRedis->persist($sKey);
	}
	
	public function bRename($sKey, $sNewkey)
	{
		return $this->_oRedis->rename($sKey, $sNewkey);
	}
	
	public function bRenameNX($sKey, $sNewkey)
	{
		return $this->_oRedis->renameNx($sKey, $sNewkey);
	}

	public function iTTL($sKey)
	{
		return $this->_oRedis->ttl($sKey);
	}
	
	public function iType($sKey)
	{
		return $this->_oRedis->type($sKey);
	}
	
	/**
	 * @return int|boolean the length of the string after the append operation.
	 *                     false if key holds a value that is not a string
	 */
	public function vAppend($sKey, $sValue)
	{
		return $this->_oRedis->append($sKey, $sValue);
	}
	
	/**
	 * @return int|boolean the value of key after the increment/decrement
	 *                     false if the key contains a value of the wrong type or contains a string that can not be represented as integer.
	 */
	public function vIncr($sKey, $iValue = 1)
	{
		return $this->_oRedis->incrBy($sKey, $iValue);
	}

	/**
	 * @return boolean always true
	 */
	public function bSet($sKey, $sValue)
	{
		return $this->_oRedis->set($sKey, $sValue);
	}
	
	public function bSetEx($sKey, $iSecond, $sValue)
	{
		return $this->_oRedis->setex($sKey, $iSecond, $sValue);
	}
	
	public function bSetNX($sKey, $sValue)
	{
		return $this->_oRedis->setnx($sKey, $sValue);
	}
	
	/**
	 * @return boolean always true
	 */
	public function bMSet($aData)
	{
		return $this->_oRedis->mset($aData);
	}
	
	public function bMSetNX($aData)
	{
		return $this->_oRedis->msetnx($aData);
	}
	
	/**
	 * @return string|boolean the value of key
	 *                        false if key does not exist
	 *                        false if key holds a value that is not a string
	 */
	public function vGet($sKey)
	{
		return $this->_oRedis->get($sKey);
	}
	
	/**
	 * @return string|boolean the value of key
	 *                        false if key does not exist
	 *                        false if key holds a value that is not a string
	 */
	public function vGetSet($sKey, $sValue)
	{
		return $this->_oRedis->getSet($sKey, $sValue);
	}
	
	public function aMGet($aKey)
	{
		return $this->_oRedis->mGet($aKey);
	}
	
	/**
	 * @return int|boolean the length of the string at key, or 0 when key does not exist.
	 *                     false if key holds a value that is not a string
	 */
	public function vStrlen($sKey)
	{
		return $this->_oRedis->strlen($sKey);
	}
	
	/**
	 * @return string|boolean false if key holds a value that is not a string
	 */
	public function vGetRange($sKey, $iStart, $iEnd)
	{
		return $this->_oRedis->getRange($sKey, $iStart, $iEnd);
	}
	
	/**
	 * @return int|boolean the length of the string after it was modified by the command.
	 *                     false if key holds a value that is not a string
	 */
	public function vSetRange($sKey, $iOffset, $sValue)
	{
		return $this->_oRedis->setRange($sKey, $iOffset, $sValue);
	}
	
	/**
	 * @return int|boolean the number of fields that were removed from the hash, not including specified but non existing fields.
	 *                     false if key holds a value that is not a hash
	 */
	public function vHDel($sKey, $sField)
	{
		return $this->_oRedis->hDel($sKey, $sField);
	}

	public function bHExists($sKey, $sField)
	{
		return $this->_oRedis->hExists($sKey, $sField);
	}
	
	/**
	 * @return string|boolean the value associated with field
	 *                        false if field is not present in the hash or key does not exist
	 */
	public function vHGet($sKey, $sField)
	{
		return $this->_oRedis->hGet($sKey, $sField);
	}
	
	/**
	 * @return array|boolean list of values associated with the given fields, in the same order as they are requested.
	 *                       false if key holds a value that is not a hash
	 */
	public function vHMGet($sKey, $aField)
	{
		$ret = $this->_oRedis->hMGet($sKey, $aField);
		return is_array($ret) ? $ret : false;
	}
	
	/**
	 * @return array|boolean list of fields and their values stored in the hash
	 *                       false if key holds a value that is not a hash
	 */
	public function vHGetAll($sKey)
	{
		return $this->_oRedis->hGetAll($sKey);
	}
	
	/**
	 * @return array|boolean list of fields in the hash, or an empty list when key does not exist.
	 *                       false if key holds a value that is not a hash
	 */
	public function vHKeys($sKey)
	{
		return $this->_oRedis->hKeys($sKey);
	}
	
	/**
	 * @return array|boolean list of values in the hash, or an empty list when key does not exist.
	 *                       false if key holds a value that is not a hash
	 */
	public function vHVals($sKey)
	{
		return $this->_oRedis->hVals($sKey);
	}
	
	/**
	 * @return int|boolean number of fields in the hash, or 0 when key does not exist.
	 *                     false if key holds a value that is not a hash
	 */
	public function vHLen($sKey)
	{
		return $this->_oRedis->hLen($sKey);
	}
	
	/**
	 * @return int|boolean the value at field after the increment operation.
	 *                     false if key holds a value that is not a hash
	 */
	public function vHIncr($sKey, $sField, $iValue = 1)
	{
		return $this->_oRedis->hIncrBy($sKey, $sField, $iValue);
	}
	
	/**
	 * @return int|boolean 1 if field is a new field in the hash and value was set. 
	 *                     0 if field already exists in the hash and the value was updated.
	 *                     false if key holds a value that is not a hash
	 */
	public function vHSet($sKey, $sField, $sValue)
	{
		return $this->_oRedis->hSet($sKey, $sField, $sValue);
	}
	
	public function bHSetNX($sKey, $sField, $sValue)
	{
		return $this->_oRedis->hSetNx($sKey, $sField, $sValue);
	}
	
	public function bHMSet($sKey, $aData)
	{
		return $this->_oRedis->hMset($sKey, $aData);
	}
	
	/**
	 * @return array|boolean a two-element array with key and value.
	 *                       false if no element could be popped and the timeout expired.
	 */
	public function vBLPop($aKey, $iTimeout)
	{
		$ret = $this->_oRedis->blPop($aKey, $iTimeout);
		return (is_array($ret) && 2 == count($ret)) ? $ret : false;
	}
	
	/**
	 * @return array|boolean a two-element array with key and value.
	 *                       false if no element could be popped and the timeout expired.
	 */
	public function vBRPop($aKey, $iTimeout)
	{
		$ret = $this->_oRedis->brPop($aKey, $iTimeout);
		return (is_array($ret) && 2 == count($ret)) ? $ret : false;
	}
	
	/**
	 * @return string|boolean the requested element
	 *                        false if index is out of range or key holds a value that is not a list
	 */
	public function vLIndex($sKey, $iIndex)
	{
		return $this->_oRedis->lIndex($sKey, $iIndex);
	}
	
	/**
	 * @return int|boolean the length of the list at key.
	 *                     false if key holds a value that is not a list
	 */
	public function vLLen($sKey)
	{
		return $this->_oRedis->lSize($sKey);
	}
	
	/**
	 * @return int|boolean the length of the list after the insert operation, or -1 when the value pivot was not found.
	 *                     false if key holds a value that is not a list
	 */
	public function vLInsert($sKey, $sPostion, $sPivot, $sValue)
	{
		return $this->_oRedis->lInsert($sKey, $sPostion, $sPivot, $sValue);
	}
	
	/**
	 * @return string|boolean the value of the first element
	 *                        false if key does not exist or key holds a value that is not a list
	 */
	public function vLPop($sKey)
	{
		return $this->_oRedis->lPop($sKey);
	}
	
	/**
	 * @return string|boolean the value of the last element
	 *                        false if key does not exist or key holds a value that is not a list
	 */
	public function vRPop($sKey)
	{
		return $this->_oRedis->rPop($sKey);
	}
	
	/**
	 * @return string|boolean the element being popped and pushed.
	 *                        false if src does not exist or src holds a value that is not a list
	 *                        false if des holds a value that is not a list
	 */
	public function vRPopLPush($sSrc, $sDes)
	{
		return $this->_oRedis->rpoplpush($sSrc, $sDes);
	}

	/**
	 * @return int|boolean the length of the list after the push operations.
	 *                     false if key holds a value that is not a list
	 */
	public function vLPush($sKey, $sValue)
	{
		return $this->_oRedis->lPush($sKey, $sValue);
	}

	/**
	 * @return int|boolean the length of the list after the push operations.
	 *                     false if key holds a value that is not a list
	 */
	public function vRPush($sKey, $sValue)
	{
		return $this->_oRedis->rPush($sKey, $sValue);
	}
	
	/**
	 * @return int|boolean the length of the list after the push operations.
	 *                     0 if key does not exist
	 *                     false if key holds a value that is not a list
	 */
	public function vLPushX($sKey, $sValue)
	{
		return $this->_oRedis->lPushx($sKey, $sValue);
	}
	
	/**
	 * @return int|boolean the length of the list after the push operations.
	 *                     0 if key does not exist
	 *                     false if key holds a value that is not a list
	 */
	public function vRPushX($sKey, $sValue)
	{
		return $this->_oRedis->rPushx($sKey, $sValue);
	}
	
	/**
	 * @return array|boolean list of elements in the specified range.
	 *                       false if key holds a value that is not a list
	 */
	public function vLRange($sKey, $iStart, $iStop)
	{
		$ret = $this->_oRedis->lRange($sKey, $iStart, $iStop);
		return is_array($ret) ? $ret : false;
	}
	
	/**
	 * @return int|boolean the number of removed elements.
	 *                     false if key holds a value that is not a list
	 */
	public function vLRem($sKey, $iCount, $sValue)
	{
		return $this->_oRedis->lRem($sKey, $sValue, $iCount);
	}
	
	public function bLSet($sKey, $iIndex, $sValue)
	{
		return $this->_oRedis->lSet($sKey, $iIndex, $sValue);
	}
	
	public function bLTrim($sKey, $iStart, $iStop)
	{
		return $this->_oRedis->lTrim($sKey, $iStart, $iStop);
	}
	
	/**
	 * @return int|boolean the number of elements that were added to the set, not including all the elements already present into the set.
	 *                     false if key holds a value that is not a set
	 */
	public function vSAdd($sKey, $sMember)
	{
		return $this->_oRedis->sAdd($sKey, $sMember);
	}
	
	/**
	 * @return int|boolean the cardinality (number of elements) of the set, or 0 if key does not exist.
	 *                     false if key holds a value that is not a set
	 */
	public function vSCard($sKey)
	{
		return $this->_oRedis->sCard($sKey);
	}
	
	/**
	 * @return array|boolean list with members of the resulting set.
	 *                       false if key holds a value that is not a set
	 */
	public function vSDiff($aKey)
	{
		return $this->_oRedis->sDiff($aKey);
	}
	
	/**
	 * @return int|boolean the number of elements in the resulting set.
	 *                     false if key holds a value that is not a set
	 */
	public function vSDiffStore($sKey, $aKey)
	{
		return call_user_func_array(array($this->_oRedis, 'sDiffStore'), array_merge(array($sKey), $aKey));
	}
	
	/**
	 * @return array|boolean list with members of the resulting set.
	 *                       false if key holds a value that is not a set
	 */
	public function vSInter($aKey)
	{
		return $this->_oRedis->sInter($aKey);
	}
	
	/**
	 * @return int|boolean the number of elements in the resulting set.
	 *                     false if key holds a value that is not a set
	 */
	public function vSInterStore($sKey, $aKey)
	{
		return call_user_func_array(array($this->_oRedis, 'sInterStore'), array_merge(array($sKey), $aKey));
	}
	
	/**
	 * @return array|boolean list with members of the resulting set.
	 *                       false if key holds a value that is not a set
	 */
	public function vSUnion($aKey)
	{
		return $this->_oRedis->sUnion($aKey);
	}
	
	/**
	 * @return int|boolean the number of elements in the resulting set.
	 *                     false if key holds a value that is not a set
	 */
	public function vSUnionStore($sKey, $aKey)
	{
		return call_user_func_array(array($this->_oRedis, 'sUnionStore'), array_merge(array($sKey), $aKey));
	}
	
	public function bSIsMember($sKey, $sMember)
	{
		return $this->_oRedis->sIsMember($sKey, $sMember);
	}
	
	/**
	 * @return array|boolean all elements of the set.
	 *                       false if key holds a value that is not a set
	 */
	public function vSMembers($sKey)
	{
		return $this->_oRedis->sMembers($sKey);
	}
	
	public function bSMove($sSrc, $sDes, $sMember)
	{
		return $this->_oRedis->sMove($sSrc, $sDes, $sMember);
	}
	
	/**
	 * @return string|boolean the removed element
	 *                        false if key does not exist or key holds a value that is not a set
	 */
	public function vSPop($sKey)
	{
		return $this->_oRedis->sPop($sKey);
	}
	
	/**
	 * @return string|boolean the randomly selected element
	 *                        false if key does not exist or key holds a value that is not a set
	 */
	public function vSRandMember($sKey)
	{
		return $this->_oRedis->sRandMember($sKey);
	}
	
	/**
	 * @return int|boolean the number of members that were removed from the set, not including non existing members.
	 *                     false if key holds a value that is not a set
	 */
	public function vSRem($sKey, $sMember)
	{
		return $this->_oRedis->sRem($sKey, $sMember);
	}
	
	/**
	 * @return int|boolean The number of elements added to the sorted sets, not including elements already existing for which the score was updated.
	 *                     false if key holds a value that is not a sorted set
	 */
	public function vZAdd($sKey, $fScore, $sMember)
	{
		return $this->_oRedis->zAdd($sKey, $fScore, $sMember);
	}
	
	/**
	 * @return int|boolean the cardinality (number of elements) of the sorted set, or 0 if key does not exist.
	 *                     false if key holds a value that is not a sorted set
	 */
	public function vZCard($sKey)
	{
		return $this->_oRedis->zCard($sKey);
	}
	
	/**
	 * @return int|boolean the number of elements in the specified score range.
	 *                     false if key holds a value that is not a sorted set
	 */
	public function vZCount($sKey, $sMin, $sMax)
	{
		return $this->_oRedis->zCount($sKey, $sMin, $sMax);
	}
	
	/**
	 * @return array|boolean list of elements in the specified range (optionally with their scores).
	 *                       false if key holds a value that is not a sorted set
	 */
	public function vZRange($sKey, $iStart, $iStop, $bWithScores = false)
	{
		return $this->_oRedis->zRange($sKey, $iStart, $iStop, $bWithScores);
	}
	
	/**
	 * @return array|boolean list of elements in the specified score range (optionally with their scores).
	 *                       false if key holds a value that is not a sorted set
	 */
	public function vZRangeByScore($sKey, $sMin, $sMax, $aOption = array())
	{
		return $this->_oRedis->zRangeByScore($sKey, $sMin, $sMax, $aOption);
	}
	
	/**
	 * @return array|boolean list of elements in the specified range (optionally with their scores).
	 *                       false if key holds a value that is not a sorted set
	 */
	public function vZRevRange($sKey, $iStart, $iStop, $bWithScores = false)
	{
		return $this->_oRedis->zRevRange($sKey, $iStart, $iStop, $bWithScores);
	}
	
	/**
	 * @return array|boolean list of elements in the specified score range (optionally with their scores).
	 *                       false if key holds a value that is not a sorted set
	 */
	public function vZRevRangeByScore($sKey, $sMax, $sMin, $aOption = array())
	{
		return $this->_oRedis->zRevRangeByScore($sKey, $sMax, $sMin, $aOption);
	}
	
	/**
	 * @return int|boolean the number of elements removed.
	 *                     false if key holds a value that is not a sorted set
	 */
	public function vZRemRangeByRank($sKey, $iStart, $iStop)
	{
		return $this->_oRedis->zRemRangeByRank($sKey, $iStart, $iStop);
	}
	
	/**
	 * @return int|boolean the number of elements removed.
	 *                     false if key holds a value that is not a sorted set
	 */
	public function vZRemRangeByScore($sKey, $sMin, $sMax)
	{
		return $this->_oRedis->zRemRangeByScore($sKey, $sMin, $sMax);
	}
	
	/**
	 * @return int|boolean the rank of member.
	 *                     false If member does not exist in the sorted set or key does not exist
	 *                     false if key holds a value that is not a sorted set
	 */
	public function vZRank($sKey, $sMember)
	{
		return $this->_oRedis->zRank($sKey, $sMember);
	}
	
	/**
	 * @return int|boolean the rank of member.
	 *                     false If member does not exist in the sorted set or key does not exist
	 *                     false if key holds a value that is not a sorted set
	 */
	public function vZRevRank($sKey, $sMember)
	{
		return $this->_oRedis->zRevRank($sKey, $sMember);
	}
	
	/**
	 * @return double|boolean the score of member (a double precision floating point number), represented as string.
	 *                        false If member does not exist in the sorted set or key does not exist
	 *                        false if key holds a value that is not a sorted set
	 */
	public function vZScore($sKey, $sMember)
	{
		return $this->_oRedis->zScore($sKey, $sMember);
	}
	
	/**
	 * @return int|boolean The number of members removed from the sorted set, not including non existing members.
	 *                     false if key holds a value that is not a sorted set
	 */
	public function vZRem($sKey, $sMember)
	{
		return $this->_oRedis->zRem($sKey, $sMember);
	}
	
	/**
	 * @return double|boolean the new score of member (a double precision floating point number), represented as string.
	 *                        false if key holds a value that is not a sorted set
	 */
	public function vZIncr($sKey, $fValue, $sMember)
	{
		return $this->_oRedis->zIncrBy($sKey, $fValue, $sMember);
	}
	
	/**
	 * @return int|boolean the number of elements in the resulting sorted set at destination.
	 *                     false if key holds a value that is not a sorted set
	 */
	public function vZInterStore($sKey, $aKey, $aWeight, $sAggregate = 'SUM')
	{
		return $this->_oRedis->zInter($sKey, $aKey, $aWeight, $sAggregate);
	}
	
	/**
	 * @return int|boolean the number of elements in the resulting sorted set at destination.
	 *                     false if key holds a value that is not a sorted set
	 */
	public function vZUnionStore($sKey, $aKey, $aWeight, $sAggregate = 'SUM')
	{
		return $this->_oRedis->zUnion($sKey, $aKey, $aWeight, $sAggregate);
	}
}

?>