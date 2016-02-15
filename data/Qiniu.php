<?php
/**
 * Qiniu
 *
 * @package ko\data
 * @author zhangchu
 */

/**
 * 七牛云存储的基本支持
 */
class Ko_Data_Qiniu extends Ko_Data_Storage
{
	private $_sAccessKey;
	private $_sSecretKey;
	private $_sScope;
	private $_sDomain;
	
	public function __construct($sAccessKey, $sSecretKey, $sScope, $sDomain)
	{
		$this->_sAccessKey = $sAccessKey;
		$this->_sSecretKey = $sSecretKey;
		$this->_sScope = $sScope;
		$this->_sDomain = $sDomain;
	}
	
	protected function _bWriteFile($sFilename, $sExt, &$sDest)
	{
		if (false !== ($cl = curl_init('http://upload.qiniu.com/')))
		{
			$postdata = array(
				'token' => $this->sGetUploadImageToken(),
			);
			if (class_exists('CURLFile'))
			{
				$postdata['file'] = new CURLFile($sFilename, Ko_Tool_Mime::sGetMimeType($sExt));
			}
			else
			{
				$postdata['file'] = '@'.$sFilename.';type='.Ko_Tool_Mime::sGetMimeType($sExt);
			}
			if (curl_setopt($cl, CURLOPT_RETURNTRANSFER, true)
				&& curl_setopt($cl, CURLOPT_POSTFIELDS, $postdata))
			{
				if (false !== ($ret = curl_exec($cl)))
				{
					$arr = json_decode($ret, true);
					if (is_array($arr) && isset($arr['key']))
					{
						$sDest = $arr['key'];
						curl_close($cl);
						return true;
					}
				}
			}
			curl_close($cl);
		}
		return false;
	}
	
	public function sRead($sDest)
	{
		$url = 'http://'.$this->_sDomain.'/'.$sDest;
		if (false !== ($content = file_get_contents($url)))
		{
			return $content;
		}
		return '';
	}
	
	public function sGetUrl($sDest, $sBriefTag)
	{
		if ('' == $sBriefTag)
		{
			return 'http://'.$this->_sDomain.'/'.$sDest;
		}
		return 'http://'.$this->_sDomain.'/'.$sDest.'?'.$sBriefTag;
	}
	
	public function aParseUrl($sUrl)
	{
		$arr = parse_url($sUrl);
		if ($arr['host'] !== $this->_sDomain)
		{
			return array('', '');
		}
		return array(substr($arr['path'], 1), $arr['query']);
	}

	public function aGetExif($sDest)
	{
		$url = 'http://'.$this->_sDomain.'/'.$sDest.'?exif';
		$ret = @file_get_contents($url);
		if (false === $ret)
		{
			return array();
		}
		return json_decode($ret, true);
	}

	public function sGetUploadImageToken($sCallbackUrl = '', $aCallbackInfo = array())
	{
		$returnBody = array(
			'key' => '$(key)',
			'width' => '$(imageInfo.width)',
			'height' => '$(imageInfo.height)',
			'orientation' => '$(imageInfo.orientation)',
			'name' => '$(fname)',
			'mime' => '${mimeType}',
			'fsize' => '${fsize}',
		);
		$policy = array(
			'scope' => $this->_sScope,
			'deadline' => time() + 604800,
			'returnBody' => json_encode($returnBody),
		);
		if (strlen($sCallbackUrl))
		{
			$policy['callbackUrl'] = $sCallbackUrl;
			$policy['callbackBody'] = array();
			foreach ($returnBody as $k => $v)
			{
				$policy['callbackBody'][] = $k.'='.$v;
			}
			foreach ($aCallbackInfo as $k => $v)
			{
				$policy['callbackBody'][] = $k.'='.$v;
			}
			$policy['callbackBody'] = implode('&', $policy['callbackBody']);
		}
		$encodedPutPolicy = self::urlsafe_base64_encode(json_encode($policy));
		$encodedSign = self::urlsafe_base64_encode(hash_hmac('sha1', $encodedPutPolicy, $this->_sSecretKey, true));
		return $this->_sAccessKey.':'.$encodedSign.':'.$encodedPutPolicy;
	}

	public function sGetUploadVideoToken($sCallbackUrl = '', $aCallbackInfo = array(), $sNotifyUrl = '')
	{
		$returnBody = array(
			'key' => '$(key)',
			'name' => '$(fname)',
			'mime' => '${mimeType}',
			'fsize' => '${fsize}',
			'avinfo' => '${avinfo}',
			'persistentId' => '${persistentId}',
		);
		$policy = array(
			'scope' => $this->_sScope,
			'deadline' => time() + 604800,
			'returnBody' => json_encode($returnBody),
			'persistentOps' => 'avthumb/mp4;avthumb/m3u8/segtime/15/vb/440k;vframe/jpg/offset/0/w/480/h/360',
		);
		if (strlen($sCallbackUrl))
		{
			$policy['callbackUrl'] = $sCallbackUrl;
			$policy['callbackBody'] = array();
			foreach ($returnBody as $k => $v)
			{
				$policy['callbackBody'][] = $k.'='.$v;
			}
			foreach ($aCallbackInfo as $k => $v)
			{
				$policy['callbackBody'][] = $k.'='.$v;
			}
			$policy['callbackBody'] = implode('&', $policy['callbackBody']);
		}
		if (strlen($sNotifyUrl))
		{
			$policy['persistentNotifyUrl'] = $sNotifyUrl;
		}
		$encodedPutPolicy = self::urlsafe_base64_encode(json_encode($policy));
		$encodedSign = self::urlsafe_base64_encode(hash_hmac('sha1', $encodedPutPolicy, $this->_sSecretKey, true));
		return $this->_sAccessKey.':'.$encodedSign.':'.$encodedPutPolicy;
	}

	public function bCheckCallback($sPath)
	{
		$authstr = Ko_Web_Request::SHttpAuthorization();
		if (strpos($authstr, 'QBox ') !== 0)
		{
			return false;
		}
		$auth = explode(':', substr($authstr,5));
		if(count($auth) != 2 || $auth[0] != $this->_sAccessKey)
		{
			return false;
		}
		$data = $sPath."\n".file_get_contents('php://input');
		return self::urlsafe_base64_encode(hash_hmac('sha1', $data, $this->_sSecretKey, true)) === $auth[1];
	}

	private static function urlsafe_base64_encode($data)
	{
		return strtr(base64_encode($data), '+/', '-_');
	}
}
