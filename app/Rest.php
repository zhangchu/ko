<?php
/**
 * Rest
 *
 * @package ko\apps
 * @author zhangchu
 */

class Ko_App_Rest extends Ko_Mode_Rest
{
	public static function VInvoke($sApp, $sMethod, $sUri, $vInput = null, &$iErrno = 0, &$sError = '')
	{
		$uri = $sApp.'/'.$sUri;
		$rest = Ko_Tool_Singleton::OInstance('Ko_App_Rest');
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

	public function run($ns = '')
	{
		$uri = Ko_Web_Request::SGet('uri');
		$req_method = Ko_Web_Request::SRequestMethod(true);
		if ('POST' === $req_method)
		{
			$method = Ko_Web_Request::SPost('method');
			if ('PUT' === $method || 'DELETE' === $method)
			{
				$req_method = $method;
			}
		}
		$input = ('GET' === $req_method) ? $_GET : $_POST;
		unset($input['uri']);
		unset($input['method']);
		if (isset($input['jsondata']))
		{
			$input = json_decode($input['jsondata'], true);
		}

		$uri = substr($ns, strlen(KO_APPS_NS) + 1).'/'.$uri;
		$rest = new self;
		$data = $rest->aCall($req_method, $uri, $input);

		$render = new Ko_View_Render_JSON;
		$render->oSetData($data)->oSend();
	}
}
