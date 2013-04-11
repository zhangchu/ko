<?php
/**
 * IDMan
 *
 * @package ko
 * @subpackage data
 * @author zhangchu
 */

//include_once('../ko.class.php');

/**
 * 封装 IDMan 的接口
 */
interface IKo_Data_IDMan
{
	public static function OInstance();
	public function sGetNewStringLongID($sKind);
	public function iGetNewTimeID($sKind);
}

/**
 * 封装使用 KProxy 的 IDMan 的实现
 */
class Ko_Data_IDMan extends Ko_Data_KProxy implements IKo_Data_IDMan
{
	private static $s_OInstance;

	protected function __construct ()
	{
		KO_DEBUG >= 6 && Ko_Tool_Debug::VAddTmpLog('data/IDMan', '__construct');
		parent::__construct('IdMan');
	}

	public static function OInstance()
	{
		if (empty(self::$s_OInstance))
		{
			self::$s_OInstance = new self();
		}
		return self::$s_OInstance;
	}

	public function sGetNewStringLongID($sKind)
	{
		$aPara = array(
			'kind' => $sKind,
			);
		$ret = $this->_oProxy->invoke('newId', $aPara);
		KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/IDMan', 'newId:'.$sKind.':'.$ret['id']);
		return $ret['id'];
	}

	public function iGetNewTimeID($sKind)
	{
		$aPara = array(
			'kind' => $sKind,
			);
		$ret = $this->_oProxy->invoke('newTimeId', $aPara);
		KO_DEBUG >= 2 && Ko_Tool_Debug::VAddTmpLog('data/IDMan', 'newTimeId:'.$sKind.':'.$ret['id']);
		return $ret['id'];
	}
}

/*

$obj = Ko_Data_IDMan::OInstance();

$ret = $obj->sGetNewStringLongID('photo');
var_dump($ret);

$ret = $obj->iGetNewTimeID('zhangchu');
var_dump($ret);

$ret = $obj->iGetNewTimeID('zhangchut');
var_dump($ret);

$obj = new Ko_Data_IDMan;
*/
?>