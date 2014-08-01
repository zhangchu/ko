<?php
/**
 * IPLocator
 *
 * @package ko\data
 * @author zhangchu
 */

//include_once('../ko.class.php');

/**
 * 封装 IPLocator 的接口
 */
class Ko_Data_IPLocator extends Ko_Data_KProxy
{
	const PROXY_ARRMAX = 500;
	private static $s_OInstance;

	private static $s_aChineseMainlandPrefix = array(
		'安徽' => 1, '北京' => 1, '福建' => 1, '甘肃' => 1, '广东' => 1,
		'广西' => 1, '贵州' => 1, '海南' => 1, '河北' => 1, '河南' => 1,
		'黑龙' => 1, '湖北' => 1, '湖南' => 1, '吉林' => 1, '江苏' => 1,
		'江西' => 1, '辽宁' => 1, '内蒙' => 1, '宁夏' => 1, '青海' => 1,
		'山东' => 1, '山西' => 1, '陕西' => 1, '上海' => 1, '四川' => 1,
		'天津' => 1, '西藏' => 1, '新疆' => 1, '云南' => 1, '浙江' => 1,
		'重庆' => 1, '中国' => 1, '全国' => 1, '华东' => 1, '华北' => 1,
		'中经' => 1, '內蒙' => 1, '聚友' => 1, '中科' => 1, '奇虎' => 1,
		'联通' => 1, '本机' => 1, '局域' => 1, 'CNNI' => 1, 'UCWE' => 1,
	);
	private static $s_aHkmotwPrefix = array(
		'澳门' => 1, '香港' => 1, '台湾' => 1,
	);
	
	const RANGE_C1_FOREIGN = 0;
	const RANGE_C1_CHINESEMAINLAND = 1;
	const RANGE_C1_HKMOTW = 2;
	
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
	
	public function iGetRangeC1($sIp)
	{
		$location = $this->sGetLocation($sIp);
		$head = Ko_Tool_Str::SConvert2UTF8(substr($location, 0, 4));
		if (isset(self::$s_aChineseMainlandPrefix[$head]))
		{
			return self::RANGE_C1_CHINESEMAINLAND;
		}
		else if (isset(self::$s_aHkmotwPrefix[$head]))
		{
			return self::RANGE_C1_HKMOTW;
		}
		return self::RANGE_C1_FOREIGN;
	}
}

/*
$ip = Ko_Data_IPLocator::OInstance();

$ret = $ip->sGetLocation('119.161.156.146');
var_dump($ret);

$ret = $ip->aGetLocations(array('192.168.0.1', '119.161.156.146', '192.168.0.1', '114.113.225.190'));
var_dump($ret);

*/