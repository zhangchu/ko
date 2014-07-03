<?php
/**
 * Api
 *
 * @package ko\busi
 * @author zhangchu
 */

//include_once('../ko.class.php');

/**
 * 逻辑层本模块接口基类
 */
class Ko_Busi_Api extends Ko_Busi_Func
{
	/**
	 * 自动创建 $xxxApi | $xxxFunc | $xxxDao 对象
	 *
	 * @return mixed
	 */
	public function __get($sName)
	{
		if ('Api' === substr($sName, -3))
		{
			$this->$sName = Ko_Tool_Object::OCreate($this, $sName);
			return $this->$sName;
		}
		else if ('Func' === substr($sName, -4))
		{
			$this->$sName = Ko_Tool_Object::OCreateInThisModule($this, $sName);
			return $this->$sName;
		}
		return parent::__get($sName);
	}
}

/*

class KKo_Busi_Test_Dao extends Ko_Dao_Factory
{
	protected $_aDaoConf = array(
		'infodb' => array(
			'kind' => 's_user_info',
			'type' => 'userone',
			'split' => 'uid',
			),
		);
}

class KKo_Busi_Test_aFunc extends Ko_Busi_Func
{
	public function get($uid)
	{
		return $this->infodbDao->aGet($uid);
	}
}

class KKo_Busi_Test_Api extends Ko_Busi_Api
{
	public function get($uid)
	{
		return $this->aFunc->get($uid);
	}
}

class KKo_Busi_aApi extends Ko_Busi_Api
{
	public function get($uid)
	{
		return $this->test_Api->get($uid);
	}
}

class KKo_Busi_bApi extends Ko_Busi_Api
{
	public function get($uid)
	{
		return $this->aApi->get($uid);
	}
}

$api = new KKo_Busi_bApi;
$ret = $api->get(337);
var_dump($ret)

*/
?>