<?php
/**
 * KProxy
 *
 * @package ko\data
 * @author zhangchu
 */

/*
  proto array kxi_Proxy::invoke(string $method, array $params [, array $ctx]);
  proto void kxi_Proxy::invoke_oneway(string $method, array $params [, array $ctx]);
  proto void kxi_Proxy::set_context(array $ctx);
  proto array kxi_Proxy::get_context();
  proto string kxi_Proxy::service();
 */

//include_once('../ko.class.php');

/**
 * 完成创建 KProxy 连接相关操作
 */
class Ko_Data_KProxy
{
	private static $s_sCaller;
	private static $s_aProxylist = array();

	protected $_oProxy;

	protected function __construct ($sName, $sTag = '', $sExinfo = '')
	{
		$sProxyStr = $sName.(strlen($sTag) ? '#' : '').$sTag.'@';
		if (strlen($sExinfo))
		{
			$sProxyStr .= $sExinfo;
		}
		else
		{
			$sProxyStr .= 'tcp:'.KO_PROXY.':9999 timeout=70000';
		}
		KO_DEBUG >= 6 && Ko_Tool_Debug::VAddTmpLog('data/KProxy', '__construct:'.$sProxyStr);
		if (!isset(self::$s_aProxylist[$sProxyStr]))
		{
			$aContext = array(
				'CALLER' => self::_SGetCaller(),
				);
			self::$s_aProxylist[$sProxyStr] = kxi_engine()->stringToProxy($sProxyStr);
			self::$s_aProxylist[$sProxyStr]->set_context($aContext);
			KO_DEBUG >= 5 && Ko_Tool_Debug::VAddTmpLog('data/KProxy', '__construct_Create:'.$sProxyStr);
		}
		$this->_oProxy = self::$s_aProxylist[$sProxyStr];
		if (KO_DEBUG && (isset($_GET['dumpkproxy']) || isset($_GET['kproxydump'])))
		{
			$this->_oProxy = new Ko_Data_KProxyWrap($sProxyStr, $this->_oProxy);
		}
	}

	protected function _aGetCacheContext($iSecond)
	{
		$aContext = array(
			'CALLER' => self::_SGetCaller(),
			'CACHE' => intval($iSecond),
			);
		return $aContext;
	}

	private static function _SGetCaller()
	{
		if (is_null(self::$s_sCaller))
		{
			self::$s_sCaller = sprintf('%s:%s:%08x', Ko_Tool_Module::SGetScriptFullName(), Ko_Tool_Ip::SGetServerIp(), mt_rand());
			KO_DEBUG >= 5 && Ko_Tool_Debug::VAddTmpLog('data/KProxy', '_SGetCaller_Create:'.self::$s_sCaller);
		}
		return self::$s_sCaller;
	}
}

/*

$ret = new Ko_Data_KProxy('');
var_dump($ret);

*/
?>