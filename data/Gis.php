<?php
/**
 * Gis
 *
 * @package ko
 * @subpackage data
 * @author zhangchu
 */

//include_once('../ko.class.php');

/**
 * 封装 Gis 的接口
 */
interface IKo_Data_Gis
{
	public static function OInstance();
	public function iGetRegion($fLat, $fLng);
}

class Ko_Data_Gis extends Ko_Data_KProxy implements IKo_Data_Gis
{
	private static $s_OInstance;

	protected function __construct ()
	{
		KO_DEBUG >= 6 && Ko_Tool_Debug::VAddTmpLog('data/Gis', '__construct');
		parent::__construct('Gis');
	}

	public static function OInstance()
	{
		if (empty(self::$s_OInstance))
		{
			self::$s_OInstance = new self();
		}
		return self::$s_OInstance;
	}

	public function iGetRegion($fLat, $fLng)
	{
		$aPara = array(
			'lat' => floatval($fLat),
			'lng' => floatval($fLng),
			);
		$ret = $this->_oProxy->invoke('getRegion', $aPara);
		return $ret['regionid'];
	}
}

/*
$gis = Ko_Data_Gis::OInstance();

$start = microtime(true);
$ret = $gis->iGetRegion($argv[1], $argv[2]);
$end = microtime(true);
if ($ret)
{
	$info = new KRegion_infoApi;
	$ret = $info->aGet($ret);
	Ko_Tool_Str::VConvert2GB18030($ret);
	echo $ret['id']."\t".$ret['cname']."\n";
}
echo ($end - $start)."\n";
*/
