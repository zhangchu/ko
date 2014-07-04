<?php
/**
 * CMD
 *
 * @package ko\tool
 * @author zhangchu
 */

/**
 * 硬件相关函数
 */
class Ko_Tool_CMD
{
	/**
	 * 获取本机IP列表
	 *
	 * @return array
	 */
	public static function AFindIpInfo()
	{
		static $s_aInfos;
		if (is_null($s_aInfos))
		{
			$s_aInfos = array();
			list($output, $retval) = self::_AExecCmd('/sbin/ifconfig');
			foreach ($output as $line)
			{
				$line = trim($line);
				if ('inet addr:' === substr($line, 0, 10))
				{
					$list = explode('  ', $line);
					$ip = substr($list[0], 10);
					if ('127.0.0.1' === $ip)
					{
						continue;
					}
					$mask = '255.255.255.255';
					for ($i=1; $i<count($list); $i++)
					{
						if ('Mask:' === substr($list[$i], 0, 5))
						{
							$mask = substr($list[$i], 5);
							break;
						}
					}
					$s_aInfos[] = compact('ip', 'mask');
				}
			}
		}
		return $s_aInfos;
	}
	
	/**
	 * 获取 ping 某个服务器的信息
	 *
	 * @return boolean
	 */
	public static function BPingIpInfo($sIp)
	{
		list($output, $retval) = self::_AExecCmd('/bin/ping -W 1 -c 1 '.escapeshellarg($sIp));
		return '1 packets transmitted, 1 received, 0% packet loss, ' === substr($output[4], 0, 51);
	}
	
	/**
	 * 获取netstat信息
	 *
	 * @return array
	 */
	public static function ANetstatInfo()
	{
		static $s_aInfos;
		if (is_null($s_aInfos))
		{
			$s_aInfos = array();
			list($output, $retval) = self::_AExecCmd('/bin/netstat -npl');
			foreach ($output as $line)
			{
				if (0 === strlen($line))
				{
					continue;
				}
				$info = preg_split('/[\s]+/', $line);
				if ('Active' === $info[0] || 'Proto' === $info[0])
				{
					continue;
				}
				$s_aInfos[] = $info;
			}
		}
		return $s_aInfos;
	}

	/**
	 * 获取crontab信息
	 *
	 * @return array
	 */
	public static function AGetCrontab()
	{
		list($output, $retval) = self::_AExecCmd('/usr/bin/crontab -l');
		return $output;
	}

	/**
	 * 设置crontab信息
	 */
	public static function VSetCrontab($aData)
	{
		$h = popen('/usr/bin/crontab', 'w');
		fwrite($h, implode("\n", $aData));
		pclose($h);
	}
	
	/**
	 * rsync 同步文件
	 *
	 * @return boolean
	 */
	public static function BRsync($sSrc, $sDest)
	{
		//$sSrc 要支持通配符，不能进行 escapeshellarg 编码
		list($output, $retval) = self::_AExecCmd('/usr/bin/rsync -auv '.$sSrc.' '.escapeshellarg($sDest));
		return 0 == $retval;
	}

	private static function _AExecCmd($cmd)
	{
		$output = array();
		$retval = 0;
		exec($cmd, $output, $retval);
		return array($output, $retval);
	}
}
