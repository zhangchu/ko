<?php
/**
 * OAuth
 *
 * @package ko\tool
 * @author zhangchu
 */

class Ko_Tool_OAuth
{
	/**
	 * 生成 token 和 secret
	 *
	 * @return string
	 */
	public static function SGenKey()
	{
		return md5(uniqid('', true));
	}

	/**
	 * 检查参数是否完整正确
	 */
	public static function BCheckParas($aReq, $bTempToken)
	{
		$bXAuth = $bTempToken && isset($aReq['x_auth_mode']);
		if (!isset($aReq['oauth_consumer_key'])
			|| ('HMAC-SHA1' !== $aReq['oauth_signature_method'])
			|| !isset($aReq['oauth_timestamp'])
			|| !isset($aReq['oauth_nonce'])
			|| (isset($aReq['oauth_version']) && '1.0' !== $aReq['oauth_version'])
			|| !isset($aReq['oauth_signature'])
			|| (!$bXAuth && $bTempToken && !isset($aReq['oauth_token']) && !isset($aReq['oauth_callback']))
			|| (!$bXAuth && $bTempToken && isset($aReq['oauth_token']) && !isset($aReq['oauth_verifier']))
			|| (!$bXAuth && !$bTempToken && !isset($aReq['oauth_token']))
			|| ($bXAuth && 'client_auth' !== $aReq['x_auth_mode'])
			|| ($bXAuth && !isset($aReq['x_auth_username']))
			|| ($bXAuth && !isset($aReq['x_auth_password'])))
		{
			return false;
		}
		return true;
	}

	/**
	 * 生成签名串
	 *
	 * @return string
	 */
	public static function SGetSignature($sMethod, $sBaseUri, $aReq, $sClientSecret, $sTokenSecret)
	{
		$sBase = self::_SGetSignatureBase($sMethod, $sBaseUri, $aReq);
		return self::_SEncode_HMAC_SHA1($sBase, $sClientSecret, $sTokenSecret);
	}

	private static function _SEncode_HMAC_SHA1($sBase, $sClientSecret, $sTokenSecret)
	{
		$sKey = self::_SEncode_Percent($sClientSecret).'&'.self::_SEncode_Percent($sTokenSecret);
		return base64_encode(hash_hmac('sha1', $sBase, $sKey, true));
	}
	
	private static function _SGetSignatureBase($sMethod, $sBaseUri, $aReq)
	{
		return self::_SEncode_Percent($sMethod)
			.'&'.self::_SEncode_Percent($sBaseUri)
			.'&'.self::_SEncode_Percent(self::_SGetNormalizedParams($aReq));
	}
	
	private static function _SGetNormalizedParams($aReq)
	{
		$data = array();
		foreach ($aReq as $k => $v)
		{
			if ('oauth_signature' === $k)
			{
				continue;
			}
			if (is_array($v))
			{
				if (self::_BKeyIsContinuousNumber($v))
				{
					foreach ($v as $v2)
					{
						$data[] = array(
							'k' => self::_SEncode_Percent($k.'[]'),
							'v' => self::_SEncode_Percent($v2),
						);
					}
				}
				else
				{
					foreach ($v as $k2 => $v2)
					{
						$data[] = array(
							'k' => self::_SEncode_Percent($k.'['.$k2.']'),
							'v' => self::_SEncode_Percent($v2),
						);
					}
				}
			}
			else
			{
				$data[] = array(
					'k' => self::_SEncode_Percent($k),
					'v' => self::_SEncode_Percent($v),
					);
			}
		}
		usort($data, array('self', '_ISortPara_Callback'));
		$data2 = array();
		foreach ($data as $v)
		{
			$data2[] = $v['k'].'='.$v['v'];
		}
		return implode('&', $data2);
	}
	
	private static function _BKeyIsContinuousNumber($map)
	{
		$size = count($map);
		for ($i=0; $i<$size; ++$i)
		{
			if (!isset($map[$i]))
			{
				return false;
			}
		}
		return true;
	}

	private static function _ISortPara_Callback($a, $b)
	{
		$ret = strcmp($a['k'], $b['k']);
		if (0 === $ret)
		{
			$ret = strcmp($a['v'], $b['v']);
		}
		return $ret;
	}

	private static function _SEncode_Percent($sStr)
	{
		return str_replace('%7E', '~', rawurlencode($sStr));
	}
}
