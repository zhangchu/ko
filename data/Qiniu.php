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
	private $_aDomainList = array();
	
	public function __construct($sAccessKey, $sSecretKey, $aDomainList)
	{
		$this->_sAccessKey = $sAccessKey;
		$this->_sSecretKey = $sSecretKey;
		$this->_aDomainList = $aDomainList;
	}
	
	protected function _bWriteFile($sFilename, $sExt, $sDomain, &$sDest)
	{
		if (false !== ($cl = curl_init('http://upload.qiniu.com/')))
		{
			$postdata = array(
				'token' => $this->_sGetUploadToken($sDomain),
				'file' => '@'.$sFilename,
			);
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
	
	public function sRead($sDomain, $sDest)
	{
		if (isset($this->_aDomainList[$sDomain]))
		{
			$url = 'http://'.$this->_aDomainList[$sDomain].'/'.$sDest;
			if (false !== ($content = file_get_contents($url)))
			{
				return $content;
			}
		}
		return '';
	}
	
	public function sGetUrl($sDomain, $sDest, $sBriefTag)
	{
		if (isset($this->_aDomainList[$sDomain]))
		{
			return 'http://'.$this->_aDomainList[$sDomain].'/'.$sDest.'?'.$sBriefTag;
		}
		return '';
	}
	
	public function aParseUrl($sUrl)
	{
		$arr = parse_url($sUrl);
		$domain = array_search($arr['host'], $this->_aDomainList);
		return array($domain, substr($arr['path'], 1), $arr['query']);
	}

	private function _sGetUploadToken($scope)
	{
		$returnBody = array(
			'key' => '$(key)',
			'w' => '$(imageInfo.width)',
			'h' => '$(imageInfo.height)',
			'mime' => '${mimeType}',
			'fsize' => '${fsize}',
		);
		$policy = array(
			'scope' => $scope,
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
