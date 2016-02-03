<?php
/**
 * Request
 *
 * @package ko\web
 * @author jiangjw & zhangchu
 */

class Ko_Web_Request
{
	public static function AGet($bTrim = true, $sCharset = KO_CHARSET)
	{
		$aValTypes = array();
		foreach ($_GET as $k => $v)
		{
			$aValTypes[$k] = $bTrim ? Ko_Tool_Input::T_STR : Ko_Tool_Input::T_NOTRIM;
		}
		return Ko_Tool_Input::ACleanAllGet($aValTypes, $sCharset);
	}
	
	public static function APost($bTrim = true, $sCharset = KO_CHARSET)
	{
		$aValTypes = array();
		foreach ($_POST as $k => $v)
		{
			$aValTypes[$k] = $bTrim ? Ko_Tool_Input::T_STR : Ko_Tool_Input::T_NOTRIM;
		}
		return Ko_Tool_Input::ACleanAllPost($aValTypes, $sCharset);
	}
	
	public static function AInput($bTrim = true, $sCharset = KO_CHARSET)
	{
		$aValTypes = array();
		foreach ($_GET as $k => $v)
		{
			$aValTypes[$k] = $bTrim ? Ko_Tool_Input::T_STR : Ko_Tool_Input::T_NOTRIM;
		}
		foreach ($_POST as $k => $v)
		{
			$aValTypes[$k] = $bTrim ? Ko_Tool_Input::T_STR : Ko_Tool_Input::T_NOTRIM;
		}
		return Ko_Tool_Input::ACleanAllGP($aValTypes, $sCharset);
	}
	
	public static function SGet($sName, $bTrim = true, $sCharset = KO_CHARSET)
	{
		return Ko_Tool_Input::VClean('g', $sName,
			$bTrim ? Ko_Tool_Input::T_STR : Ko_Tool_Input::T_NOTRIM, $sCharset);
	}
	
	public static function SPost($sName, $bTrim = true, $sCharset = KO_CHARSET)
	{
		return Ko_Tool_Input::VClean('p', $sName,
			$bTrim ? Ko_Tool_Input::T_STR : Ko_Tool_Input::T_NOTRIM, $sCharset);
	}
	
	public static function SInput($sName, $bTrim = true, $sCharset = KO_CHARSET)
	{
		return Ko_Tool_Input::VCleanOneGP($sName,
			$bTrim ? Ko_Tool_Input::T_STR : Ko_Tool_Input::T_NOTRIM, $sCharset);
	}
	
	public static function SGetHtml($sName, $bRich = false, $sCharset = KO_CHARSET)
	{
		return Ko_Tool_Input::VClean('g', $sName,
			$bRich ? Ko_Tool_Input::T_RICHHTML : Ko_Tool_Input::T_HTML, $sCharset);
	}
	
	public static function SPostHtml($sName, $bRich = false, $sCharset = KO_CHARSET)
	{
		return Ko_Tool_Input::VClean('p', $sName,
			$bRich ? Ko_Tool_Input::T_RICHHTML : Ko_Tool_Input::T_HTML, $sCharset);
	}
	
	public static function SInputHtml($sName, $bRich = false, $sCharset = KO_CHARSET)
	{
		return Ko_Tool_Input::VCleanOneGP($sName,
			$bRich ? Ko_Tool_Input::T_RICHHTML : Ko_Tool_Input::T_HTML, $sCharset);
	}
	
	public static function IGet($sName, $bUnsigned = false)
	{
		return Ko_Tool_Input::VClean('g', $sName,
			$bUnsigned ? Ko_Tool_Input::T_UINT : Ko_Tool_Input::T_INT);
	}

	public static function IPost($sName, $bUnsigned = false)
	{
		return Ko_Tool_Input::VClean('p', $sName,
			$bUnsigned ? Ko_Tool_Input::T_UINT : Ko_Tool_Input::T_INT);
	}

	public static function IInput($sName, $bUnsigned = false)
	{
		return Ko_Tool_Input::VCleanOneGP($sName,
			$bUnsigned ? Ko_Tool_Input::T_UINT : Ko_Tool_Input::T_INT);
	}
	
	public static function FGet($sName)
	{
		return Ko_Tool_Input::VClean('g', $sName, Ko_Tool_Input::T_NUM);
	}
	
	public static function FPost($sName)
	{
		return Ko_Tool_Input::VClean('p', $sName, Ko_Tool_Input::T_NUM);
	}
	
	public static function FInput($sName)
	{
		return Ko_Tool_Input::VCleanOneGP($sName, Ko_Tool_Input::T_NUM);
	}
	
	public static function AFile($sName, $sCharset = KO_CHARSET)
	{
		return Ko_Tool_Input::VClean('f', $sName, Ko_Tool_Input::T_FILE, $sCharset);
	}
	
	public static function SRawInput()
	{
		static $input = null;
		if (null === $input)
		{
			$fh = fopen('php://input', 'r');
			$input = stream_get_contents($fh);
			fclose($fh);
		}
		return $input;
	}
	
	public static function SCookie($sName, $sCharset = KO_CHARSET)
	{
		return Ko_Tool_Input::VClean('c', $sName, Ko_Tool_Input::T_NOTRIM, $sCharset);
	}

	public static function ICookie($sName)
	{
		return Ko_Tool_Input::VClean('c', $sName, Ko_Tool_Input::T_INT);
	}

	public static function FCookie($sName)
	{
		return Ko_Tool_Input::VClean('c', $sName, Ko_Tool_Input::T_NUM);
	}
	
	public static function SPathInfo()
	{
		return self::_VServer('PATH_INFO');
	}
	
	public static function SRemoteAddr()
	{
		return self::_VServer('REMOTE_ADDR');
	}

	public static function SScriptName()
	{
		return self::_VServer('SCRIPT_NAME');
	}
	
	public static function SScriptFilename()
	{
		return self::_VServer('SCRIPT_FILENAME');
	}
	
	public static function SRequestMethod($bOverride = false)
	{
		$httpmethod = self::_VServer('REQUEST_METHOD');
		if ($bOverride && 'POST' === $httpmethod)
		{
			$method = self::_VServer('HTTP_X_HTTP_METHOD_OVERRIDE');
			if (null !== $method)
			{
				return $method;
			}
		}
		return $httpmethod;
	}
	
	public static function SRequestUri()
	{
		return self::_VServer('REQUEST_URI');
	}
	
	public static function SDocumentUri()
	{
		$uri = self::_VServer('DOCUMENT_URI');
		if (null === $uri)
		{
			list($uri, $qs) = explode('?', self::SRequestUri(), 2);
		}
		return $uri;
	}
	
	public static function SDocumentRoot()
	{
		$dr = Ko_Web_Config::SGetValue('documentroot');
		if ('' === $dr)
		{
			$dr = self::_VServer('DOCUMENT_ROOT');
		}
		return $dr;
	}
	
	public static function SServerName()
	{
		return self::_VServer('SERVER_NAME');
	}
	
	public static function SServerAddr()
	{
		return self::_VServer('SERVER_ADDR');
	}
	
	public static function SHttpHost()
	{
		return self::_VServer('HTTP_HOST');
	}
	
	public static function SHttpUserAgent()
	{
		return self::_VServer('HTTP_USER_AGENT');
	}
	
	public static function SHttpReferer()
	{
		return self::_VServer('HTTP_REFERER');
	}
	
	public static function SHttpVia()
	{
		return self::_VServer('HTTP_VIA');
	}

	public static function SHttpAuthorization()
	{
		return self::_VServer('HTTP_AUTHORIZATION');
	}

	public static function SHttpXForwardedFor()
	{
		return self::_VServer('HTTP_X_FORWARDED_FOR');
	}

	public static function SHttpXForwardedProto()
	{
		return self::_VServer('HTTP_X_FORWARDED_PROTO');
	}
	
	public static function SHttpXRequestedWith()
	{
		return self::_VServer('HTTP_X_REQUESTED_WITH');
	}
	
	public static function SPhpAuthUser()
	{
		return self::_VServer('PHP_AUTH_USER');
	}

	public static function SPhpAuthPw()
	{
		return self::_VServer('PHP_AUTH_PW');
	}

	private static function _VServer($sName)
	{
		return isset($_SERVER[$sName]) ? $_SERVER[$sName] : null;
	}
}
