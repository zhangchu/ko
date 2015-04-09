<?php
/**
 * Base
 *
 * @package ko\app
 * @author zhangchu
 */

/**
 * 应用程序的基类
 *
 * @property Ko_View_Smarty $_smarty
 */
class Ko_App_Base
{
	/**
	 * 定义 cgi 参数类型
	 *
	 * 如：
	 * <code>
	 * array(
	 *   'uid' => Ko_Tool_Input::T_UINT,
	 *   'key' => Ko_Tool_Input::T_STR,
	 * )
	 * </code>
	 *
	 * @var array
	 */
	protected $_aReqType = array();
	/**
	 * 保存经过类型检查后的参数，包括 $_aReqType 中定义的参数，还包括符合 Ko_Tool_Input 命名规则的参数
	 *
	 * @var array
	 */
	protected $_aReq = array();

	/**
	 * 自动创建魔法函数
	 *
	 * 自动创建 $_smarty 对象 <code>Ko_View_Smarty</code>
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

	/**
	 * 应用程序的入口函数
	 */
	public function vRun()
	{
		//基本权限验证过程
		$this->vCheckAuth();

		//参数分析过程
		$this->vGetPara();

		//参数校验过程
		$this->vCheckPara();

		//主要逻辑计算过程
		$this->vMain();

		//输出 HTTP 协议头信息部分
		$this->vOutputHttp();

		//输出 HTTP 协议页面部分
		$this->vOutputPage();
	}

	/**
	 * 基本权限验证过程
	 */
	protected function vCheckAuth()
	{
	}

	/**
	 * 参数分析过程
	 * 缺省使用 Ko_Tool_Input 的分析过程
	 */
	protected function vGetPara()
	{
		$this->_aReq = Ko_Tool_Input::ACleanAll($this->_aReqType);
	}

	/**
	 * 参数校验过程
	 */
	protected function vCheckPara()
	{
	}

	/**
	 * 主要逻辑计算过程
	 */
	protected function vMain()
	{
	}

	/**
	 * 输出 HTTP 协议头信息部分
	 */
	protected function vOutputHttp()
	{
	}

	/**
	 * 输出 HTTP 协议页面部分
	 */
	protected function vOutputPage()
	{
		//输出页面标准头部
		$this->vOutputHead();

		//输出页面主体部分
		$this->vOutputBody();

		//输出页面标准尾部
		$this->vOutputTail();
	}

	/**
	 * 输出页面标准头部
	 */
	protected function vOutputHead()
	{
	}

	/**
	 * 输出页面主体部分
	 */
	protected function vOutputBody()
	{
	}

	/**
	 * 输出页面标准尾部
	 */
	protected function vOutputTail()
	{
	}
}
