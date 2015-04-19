<?php
/**
 * Aliyun
 *
 * @package ko\data
 * @author zhangchu
 */

/**
 * 阿里云存储的基本支持
 */
class Ko_Data_Aliyun extends Ko_Data_Storage
{
	private $_sAccessKey;
	private $_sSecretKey;
	private $_sDomain;
	private $_aDomainList = array();
	
	public function __construct($sAccessKey, $sSecretKey, $sDomain, $aDomainList)
	{
		$this->_sAccessKey = $sAccessKey;
		$this->_sSecretKey = $sSecretKey;
		$this->_sDomain = $sDomain;
		$this->_aDomainList = $aDomainList;
	}
	
	protected function _bWriteFile($sFilename, $sExt, &$sDest)
	{
		if (false !== ($cl = curl_init('http://'.$this->_sDomain.'.oss-cn-beijing.aliyuncs.com/')))
		{
			$sDest = str_replace('.', '_', uniqid('', true)).'.'.trim($sExt, '.');
			$policy = array(
				'expiration' => date('Y-m-d\TH:i:s', time() + 86400).'.000Z',
				'conditions' => array(
					array(
						'key' => $sDest,
					),
				),
			);
			$policy = base64_encode(json_encode($policy));
			$signature = base64_encode(hash_hmac('sha1', $policy, $this->_sSecretKey, 'true'));
			$postdata = array(
				'OSSAccessKeyId' => $this->_sAccessKey,
				'policy' => $policy,
				'Signature' => $signature,
				'key' => $sDest,
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
					$code = curl_getinfo($cl, CURLINFO_HTTP_CODE);
					if (200 <= $code && $code < 300)
					{
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
		$url = 'http://'.$this->_aDomainList['oss'].'/'.$sDest;
		if (false !== ($content = file_get_contents($url)))
		{
			return $content;
		}
		return '';
	}
	
	public function sGetUrl($sDest, $sBriefTag)
	{
		return 'http://'.$this->_aDomainList['cdn'].'/'.$sDest.'@'.$sBriefTag;
	}
	
	public function aParseUrl($sUrl)
	{
		list($url, $query) = explode('@', $sUrl, 2);
		$arr = parse_url($url);
		if ($arr['host'] !== $this->_aDomainList['cdn'])
		{
			return array('', '');
		}
		return array(substr($arr['path'], 1), $query);
	}
}
