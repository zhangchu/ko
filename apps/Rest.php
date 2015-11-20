<?php
/**
 * Rest
 *
 * @package ko\apps
 * @author zhangchu
 */

class Ko_Apps_Rest extends Ko_Mode_Rest
{
	public static function VInvoke($sApp, $sMethod, $sUri, $vInput = null, &$iErrno = 0, &$sError = '')
	{
		$uri = $sApp.'/'.$sUri;
		$rest = Ko_Tool_Singleton::OInstance('Ko_Apps_Rest');
		$ret = $rest->aCall($sMethod, $uri, $vInput);
		$iErrno = $ret['errno'];
		$sError = $ret['error'];
		return $ret['data'];
	}

	protected function _sGetClassname($sModule, $sResource)
	{
		$item = explode('/', $sModule);
		$ns = array_shift($item);
		$classname = KO_APPS_NS.'\\'.$ns.'\\MRest_';
		foreach ($item as $v)
		{
			$classname .= ucfirst($v).'_';
		}
		$classname .= $sResource;
		return $classname;
	}

	protected function _aLoadConf($sModule, $sResource)
	{
		$classname = $this->_sGetClassname($sModule, $sResource);
		if (!class_exists($classname) || !isset($classname::$s_aConf))
		{
			throw new Exception('资源不存在', self::ERROR_RESOURCE_INVALID);
		}
		return $classname::$s_aConf;
	}
}
