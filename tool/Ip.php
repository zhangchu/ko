<?php
/**
 * Ip
 *
 * @package ko\tool
 * @author zhangchu
 */

//include_once('../ko.class.php');

/**
 * IP 相关函数实现
 */
class Ko_Tool_Ip
{
	/**
	 * 判断一个 ip 是否是内网 ip
	 *
	 * @return bool
	 */
	public static function BIsInnerIP($sIp)
	{
		if ('127.0.0.1' === $sIp)
		{
			return true;
		}
		list($i1, $i2, $i3, $i4) = explode('.', $sIp, 4);
		return ($i1 == 10 || ($i1 == 172 && 16 <= $i2 && $i2 < 32) || ($i1 == 192 && $i2 == 168));
	}

	/**
	 * 输入一堆ip，找出其中第一个外网 ip，如果没有外网ip，返回最后一个合法ip，否则返回 unknown
	 *
	 * <pre>
	 * eg1
	 *    $sIp = '202.105.1.3'  返回  '202.105.1.3'
	 *    $sIp = '192.168.0.1, 202.105.1.3 202.105.1.1 192.168.10.3'  返回  '202.105.1.3'
	 *    $sIp = ''  返回  'unknown'
	 *    $sIp = '192.168.0.1 192.168.10.3; 10.102.2.1, 192.168.10.3'  返回  '192.168.10.3'
	 * </pre>
	 *
	 * @return string
	 */
	public static function SGetFirstOuterIP($sIp)
	{
		$ips = preg_split('/;|,|\s/', $sIp);
		$sIp = 'unknown';
		foreach ($ips as $ip)
		{
			$ip = trim($ip);
			if (false === ip2long($ip))
			{
				continue;
			}
			$sIp = $ip;
			if (!self::BIsInnerIP($ip))
			{
				break;
			}
		}
		return $sIp;
	}
	
	/**
	 * 输入一堆ip，找出其中最后一个外网 ip，如果没有外网ip，返回最后一个合法ip，否则返回 unknown
	 *
	 * <pre>
	 * eg1
	 *    $sIp = '202.105.1.3'  返回  '202.105.1.3'
	 *    $sIp = '192.168.0.1, 202.105.1.3 202.105.1.1 192.168.10.3'  返回  '202.105.1.1'
	 *    $sIp = ''  返回  'unknown'
	 *    $sIp = '192.168.0.1 192.168.10.3; 10.102.2.1, 192.168.10.3'  返回  '192.168.10.3'
	 * </pre>
	 *
	 * @return string
	 */
	public static function SGetLastOuterIP($sIp)
	{
		$ips = preg_split('/;|,|\s/', $sIp);
		$sIp = 'unknown';
		$sLast = 'unknown';
		foreach ($ips as $ip)
		{
			$ip = trim($ip);
			if (false === ip2long($ip))
			{
				continue;
			}
			$sLast = $ip;
			if (!self::BIsInnerIP($ip))
			{
				$sIp = $ip;
			}
		}
		if ('unknown' == $sIp)
		{
			$sIp = $sLast;
		}
		return $sIp;
	}

	/**
	 * 根据下面规则，尽量返回距离用户端最近的外网IP
	 *
	 * <pre>
	 * 获取用户IP地址的三个属性的区别(HTTP_X_FORWARDED_FOR,HTTP_VIA,REMOTE_ADDR)
	 * 一、没有使用代理服务器的情况：
	 *    REMOTE_ADDR = 您的 IP
	 *    HTTP_VIA = 没数值或不显示
	 *    HTTP_X_FORWARDED_FOR = 没数值或不显示
	 * 二、使用透明代理服务器的情况：Transparent Proxies
	 *    REMOTE_ADDR = 最后一个代理服务器 IP
	 *    HTTP_VIA = 代理服务器 IP
	 *    HTTP_X_FORWARDED_FOR = 您的真实 IP ，经过多个代理服务器时，这个值类似如下：203.98.182.163, 203.129.72.215。
	 *    这类代理服务器还是将您的信息转发给您的访问对象，无法达到隐藏真实身份的目的。
	 * 三、使用普通匿名代理服务器的情况：Anonymous Proxies
	 *    REMOTE_ADDR = 最后一个代理服务器 IP
	 *    HTTP_VIA = 代理服务器 IP
	 *    HTTP_X_FORWARDED_FOR = 代理服务器 IP ，经过多个代理服务器时，这个值类似如下：203.98.182.163, 203.129.72.215。
	 *    隐藏了您的真实IP，但是向访问对象透露了您是使用代理服务器访问他们的。
	 * 四、使用欺骗性代理服务器的情况：Distorting Proxies
	 *    REMOTE_ADDR = 代理服务器 IP
	 *    HTTP_VIA = 代理服务器 IP
	 *    HTTP_X_FORWARDED_FOR = 随机的 IP ，经过多个代理服务器时，这个值类似如下：203.98.182.163, 203.129.72.215。
	 *    告诉了访问对象您使用了代理服务器，但编造了一个虚假的随机IP代替您的真实IP欺骗它。
	 * 五、使用高匿名代理服务器的情况：High Anonymity Proxies (Elite proxies)
	 *    REMOTE_ADDR = 代理服务器 IP
	 *    HTTP_VIA = 没数值或不显示
	 *    HTTP_X_FORWARDED_FOR = 没数值或不显示 ，经过多个代理服务器时，这个值类似如下：203.98.182.163, 203.129.72.215。
	 *    完全用代理服务器的信息替代了您的所有信息，就象您就是完全使用那台代理服务器直接访问对象。
	 * </pre>
	 *
	 * @return string
	 */
	public static function SGetClientIP()
	{
		static $ip = null;
		if (is_null($ip))
		{
			$fip = Ko_Web_Request::SHttpXForwardedFor()
				.' '.Ko_Web_Request::SHttpVia()
				.' '.Ko_Web_Request::SRemoteAddr();
			$ip = self::SGetFirstOuterIP($fip);
		}
		return $ip;
	}

	/**
	 * 尽量返回距离服务器最近的外网IP
	 *
	 * @return string
	 */
	public static function SGetProxyIP()
	{
		static $ip = null;
		if (is_null($ip))
		{
			$fip = Ko_Web_Request::SHttpXForwardedFor()
				.' '.Ko_Web_Request::SHttpVia()
				.' '.Ko_Web_Request::SRemoteAddr();
			$ip = self::SGetLastOuterIP($fip);
		}
		return $ip;
	}
	
	/**
	 * 从环境变量获取服务器Ip，如果获取不到有意义的ip，返回 unknown
	 *
	 * @return string
	 */
	public static function SGetServerIp()
	{
		static $ip = null;
		if (is_null($ip))
		{
			$ip = Ko_Web_Request::SServerAddr();
			if ($ip == '' || $ip == '127.0.0.1')
			{
				$ip = 'unknown';
			}
		}
		return $ip;
	}

	/**
	 * 获取子网掩码可用的ip数量
	 *
	 * @return int
	 */
	public static function IGetIpCountInMask($iMask)
	{
		for ($i=0; $i<32; $i++)
		{
			if (0 == $iMask)
			{
				return (0xffffffff >> $i) + 1;
			}
			$iMask = ($iMask << 1) & 0xffffffff;
		}
		return 0;
	}

	/**
	 * 判断IP是否和某个ip相等，或者落在某个ip段里面
	 * ip段使用形式如：192.168.1.0/24
	 *
	 * @return boolean
	 */
	public static function BCheck($sIp, $sIpMask)
	{
		$sIp = ip2long($sIp);
		list($sIpMask, $mask) = explode('/', $sIpMask);
		$sIpMask = ip2long($sIpMask);
		$mask = intval($mask);
		if (0 < $mask && $mask < 32)
		{
			$offset = 32 - $mask;
			$sIpMask = ($sIpMask >> $offset) << $offset;
			$sIp = ($sIp >> $offset) << $offset;
		}
		return $sIp === $sIpMask;
	}
}
