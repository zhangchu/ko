<?php
/**
 * UserId
 *
 * @package ko\data
 * @author zhangchu
 */

//include_once('../ko.class.php');

/**
 * 封装 UserId 的接口
 */
class Ko_Data_UserId extends Ko_Data_KProxy
{
	private static $s_OInstance;

	protected function __construct ()
	{
		KO_DEBUG >= 6 && Ko_Tool_Debug::VAddTmpLog('data/UserId', '__construct');
		parent::__construct('UserId');
	}

	public static function OInstance()
	{
		if (empty(self::$s_OInstance))
		{
			self::$s_OInstance = new self();
		}
		return self::$s_OInstance;
	}

	public function iGetUserId()
	{
		$aPara = array();
		$ret = $this->_oProxy->invoke('getUserId', $aPara);
		return $ret['userid'];
	}
}

/*
$UserId = Ko_Data_UserId::OInstance();

$start = microtime(true);
$ret = $UserId->iGetUserId();
$end = microtime(true);
var_dump($ret);
echo ($end - $start)."\n";
//*/
