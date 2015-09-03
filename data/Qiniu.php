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
				'token' => $this->_sGetUploadToken(),
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

	private function _sGetUploadToken()
	{
		$returnBody = array(
			'key' => '$(key)',
			'w' => '$(imageInfo.width)',
			'h' => '$(imageInfo.height)',
			'mime' => '${mimeType}',
			'fsize' => '${fsize}',
		);
		$policy = array(
			'scope' => $this->_sScope,
			'deadline' => time() + 86400,
			'returnBody' => json_encode($returnBody),
		);
		$encodedPutPolicy = self::urlsafe_base64_encode(json_encode($policy));
		$encodedSign = self::urlsafe_base64_encode(hash_hmac('sha1', $encodedPutPolicy, $this->_sSecretKey, true));
		return $this->_sAccessKey.':'.$encodedSign.':'.$encodedPutPolicy;
	}
	
	private static function urlsafe_base64_encode($data)
	{
		return strtr(base64_encode($data), '+/', '-_');
	}
}
