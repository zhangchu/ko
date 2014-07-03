<?php
/**
 * Func
 *
 * @package ko\busi
 * @author zhangchu
 */

//include_once('../ko.class.php');

/**
 * 逻辑层内部逻辑基类
 */
class Ko_Busi_Func
{
	/**
	 * 自动创建 $xxxDao 对象
	 *
	 * @return mixed
	 */
	public function __get($sName)
	{
		if ('Dao' === substr($sName, -3))
		{
			$dao = Ko_Tool_Object::OCreateInThisModule($this, 'Dao');
			$this->$sName = $dao->oGetDao($sName);
			return $this->$sName;
		}
		return null;
	}
}

/*

class KKo_Busi_Dao extends Ko_Dao_Factory
{
	protected $_aDaoConf = array(
		'infodb' => array(
			'kind' => 's_user_info',
			'type' => 'userone',
			'split' => 'uid',
			),
		);
}

class KKo_Busi_Func extends Ko_Busi_Func
{
	public function get($uid)
	{
		return $this->infodbDao->aGet($uid);
	}
}

$func = new KKo_Busi_Func;
$ret = $func->get(337);
var_dump($ret)

*/
?>