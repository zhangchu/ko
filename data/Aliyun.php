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
	private $_aDomainList = array();
	
	public function __construct($sAccessKey, $sSecretKey, $aDomainList)
	{
		$this->_sAccessKey = $sAccessKey;
		$this->_sSecretKey = $sSecretKey;
		$this->_aDomainList = $aDomainList;
	}
	
	protected function _bWriteFile($sFilename, $sExt, $sDomain, &$sDest)
	{
		if (false !== ($cl = curl_init('http://'.$sDomain.'.oss-cn-beijing.aliyuncs.com/')))
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
				'file' => '@'.$sFilename.';type='.Ko_Tool_Mime::sGetMimeType($sExt),
			);
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
	
	public function sRead($sDomain, $sDest)
	{
		if (isset($this->_aDomainList[$sDomain]))
		{
			$url = 'http://'.$this->_aDomainList[$sDomain]['oss'].'/'.$sDest;
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
			return 'http://'.$this->_aDomainList[$sDomain]['cdn'].'/'.$sDest.'@'.$sBriefTag;
		}
		return '';
	}
	
	public function aParseUrl($sUrl)
	{
		list($url, $query) = explode('@', $sUrl, 2);
		$arr = parse_url($url);
		foreach ($this->_aDomainList as $k => $v)
		{
			if ($v['cdn'] === $arr['host'])
			{
				$domain = $k;
				break;
			}
		}
		return array($domain, substr($arr['path'], 1), $query);
	}
}
