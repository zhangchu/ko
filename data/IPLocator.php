<?php
/**
 * IPLocator
 *
 * @package ko
 * @subpackage data
 * @author zhangchu
 */

//include_once('../ko.class.php');

/**
 * 封装 UObject 的接口
 */
interface IKo_Data_IPLocator
{
	public static function OInstance();
	public function sGetLocation($sIp);
	public function aGetLocations($aIp);
}

class Ko_Data_IPLocator extends Ko_Data_KProxy implements IKo_Data_IPLocator
{
	const PROXY_ARRMAX = 1000;
	private static $s_OInstance;

	protected function __construct ()
	{
		KO_DEBUG >= 6 && Ko_Tool_Debug::VAddTmpLog('data/IPLocator', '__construct');
		parent::__construct('IPLocator');
	}

	public static function OInstance()
	{
		if (empty(self::$s_OInstance))
		{
			self::$s_OInstance = new self();
		}
		return self::$s_OInstance;
	}

	public function sGetLocation($sIp)
	{
		$oCtx = $this->_aGetCacheContext(86400);
		$aPara = array(
			'ip' => strval($sIp),
			);
		$ret = $this->_oProxy->invoke('getLocation', $aPara, $oCtx);
		return $ret['location'];
	}
	
	public function aGetLocations($aIp)
	{
		$oCtx = $this->_aGetCacheContext(86400);
		$aIp = array_map('strval', $aIp);
		$len = count($aIp);
		$ret = array();
		for ($i=0; $i<$len; $i+=self::PROXY_ARRMAX)
		{
			$aPara = array(
				'ips' => array_slice($aIp, $i, self::PROXY_ARRMAX),
				);
			$tmp = $this->_oProxy->invoke('getLocations', $aPara, $oCtx);
			$ret = array_merge($ret, $tmp['locations']);
		}
		return $ret;
	}
}

/*
$ip = Ko_Data_IPLocator::OInstance();

$ret = $ip->sGetLocation('119.161.156.146');
var_dump($ret);

$ret = $ip->aGetLocations(array('192.168.0.1', '119.161.156.146', '192.168.0.1', '114.113.225.190'));
var_dump($ret);

*/