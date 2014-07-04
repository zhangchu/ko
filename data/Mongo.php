<?php
/**
 * Mongo
 *
 * @package ko\data
 * @author zhangchu
 */

/**
 * 封装 Mongo 连接
 */
class Ko_Data_Mongo
{
	private static $s_AInstance = array();
	private $_hLink;
	private $_sDbName;
	
	protected function __construct($sHost, $sReplicaSet, $sUser, $sPasswd, $sDbName)
	{
		$options = array();
		if (strlen($sReplicaSet))
		{
			$options['replicaSet'] = $sReplicaSet;
		}
		if (strlen($sUser))
		{
			$options['username'] = $sUser;
		}
		if (strlen($sPasswd))
		{
			$options['password'] = $sPasswd;
		}
		$this->_sDbName = $sDbName;
		$this->_hLink = new MongoClient($sHost, $options);
	}
	
	/**
	 * @return Ko_Data_Mongo
	 */
	public static function OInstance($sHost, $sReplicaSet, $sUser, $sPasswd, $sDbName)
	{
		$tag = strlen($sReplicaSet) ? $sReplicaSet : $sHost;
		if (empty(self::$s_AInstance[$tag]))
		{
			self::$s_AInstance[$tag] = new self($sHost, $sReplicaSet, $sUser, $sPasswd, $sDbName);
		}
		return self::$s_AInstance[$tag];
	}
	
	/**
	 * @return MongoDB
	 */
	public function oSelectDB()
	{
		return $this->_hLink->selectDB($this->_sDbName);
	}

	/**
	 * @return MongoCollection
	 */
	public function oSelectCollection($sCollection)
	{
		return $this->_hLink->selectCollection($this->_sDbName, $sCollection);
	}
}
