<?php
/**
 * DBManCtrl
 *
 * @package ko\data
 * @author zhangchu
 */

//include_once('../ko.class.php');

/**
 * 封装 DBManCtrl
 */
class Ko_Data_DBManCtrl extends Ko_Data_KProxy
{
	private static $s_AInstance = array();

	protected function __construct ($sIpPort)
	{
		parent::__construct('DBManCtrl', '', 'tcp:'.$sIpPort.' timeout=70000');
	}

	public static function OInstance($sIpPort)
	{
		if (empty(self::$s_AInstance[$sIpPort]))
		{
			self::$s_AInstance[$sIpPort] = new self($sIpPort);
		}
		return self::$s_AInstance[$sIpPort];
	}

	public function bReload()
	{
		$aPara = array(
			);
		$info = $this->_oProxy->invoke('reloadDBSetting', $aPara);
		return $info['ok'];
	}

	public function sGetStat()
	{
		$aPara = array(
			);
		$info = $this->_oProxy->invoke('getStat', $aPara);
		return $info['stat'];
	}
}

/*
$ctrl = Ko_Data_DBManCtrl::OInstance('192.168.0.140:12321');
$ret = $ctrl->bReload();
var_dump($ret);
$ret = $ctrl->sGetStat();
var_dump($ret);
*/

?>