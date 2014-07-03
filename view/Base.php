<?php
/**
 * Base
 *
 * @package ko\view
 * @author zhangchu
 */

class Ko_View_Base
{
	/**
	 * 自动创建魔法函数
	 *
	 * 自动创建 $xxxApi 对象
	 *
	 * @return mixed
	 */
	public function __get($sName)
	{
		if ($sName == '_smarty')
		{
			$this->_smarty = Ko_Tool_Singleton::OInstance('Ko_View_Smarty');
			return $this->_smarty;
		}
		else if(substr($sName, -3) === 'Api')
		{
			$this->$sName = Ko_Tool_Object::OCreateFromRoot($this, $sName);
			return $this->$sName;
		}
		return null;
	}
}

?>