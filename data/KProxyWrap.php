<?php
/**
 * KProxyWrap
 *
 * @package ko\data
 * @author zhangchu
 */

class Ko_Data_KProxyWrap
{
	private $_sProxyStr;
	private $_oProxy;
	
	public function __construct($proxystr, $proxy)
	{
		$this->_sProxyStr = $proxystr;
		$this->_oProxy = $proxy;
	}
	
	public function __call($sName, $aArgs)
	{
		$start = microtime(true);
		$ret = call_user_func_array(array($this->_oProxy, $sName), $aArgs);
		$end = microtime(true);
		$data = array(
			'str' => $this->_sProxyStr,
			'name' => $sName,
			'para' => $aArgs,
			'time' => $end - $start
		);
		echo Ko_Html_Utils::SArr2html($data),'<br><br>';
		return $ret;
	}
}
