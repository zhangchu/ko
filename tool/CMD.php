<?php
/**
 * CMD
 *
 * @package ko
 * @subpackage tool
 * @author zhangchu
 */

/**
 * 硬件相关函数接口
 */
interface IKo_Tool_CMD
{
	/**
	 * 获取本机IP列表
	 *
	 * @return array
	 */
	public static function AFindIpInfo();
	/**
	 * 获取 ping 某个服务器的信息
	 *
	 * @return boolean
	 */
	public static function BPingIpInfo($sIp);
	/**
	 * 获取netstat信息
	 *
	 * @return array
	 */
	public static function ANetstatInfo();
	/**
	 * 获取crontab信息
	 *
	 * @return array
	 */
	public static function AGetCrontab();
	/**
	 * 设置crontab信息
	 */
	public static function VSetCrontab($aData);
	/**
	 * rsync 同步文件
	 *
	 * @return boolean
	 */
	public static function BRsync($sSrc, $sDest);
	/**
	 * 获取内存大小
	 *
	 * @return int
	 */
	public static function IGetMemTotal();
	/**
	 * 获取CPU信息
	 *
	 * @return array
	 */
	public static function AGetCPUInfo();
}

/**
 * 硬件相关函数实现
 */
class Ko_Tool_CMD implements IKo_Tool_CMD
{
	/**
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
	 * @return boolean
	 */
	public static function BPingIpInfo($sIp)
	{
		list($output, $retval) = self::_AExecCmd('/bin/ping -W 1 -c 1 '.escapeshellarg($sIp));
		return '1 packets transmitted, 1 received, 0% packet loss, ' === substr($output[4], 0, 51);
	}
	
	/**
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
	 * @return array
	 */
	public static function AGetCrontab()
	{
		list($output, $retval) = self::_AExecCmd('/usr/bin/crontab -l');
		return $output;
	}

	public static function VSetCrontab($aData)
	{
		$h = popen('/usr/bin/crontab', 'w');
		fwrite($h, implode("\n", $aData));
		pclose($h);
	}
	
	/**
	 * @return boolean
	 */
	public static function BRsync($sSrc, $sDest)
	{
		//$sSrc 要支持通配符，不能进行 escapeshellarg 编码
		list($output, $retval) = self::_AExecCmd('/usr/bin/rsync -auv '.$sSrc.' '.escapeshellarg($sDest));
		return 0 == $retval;
	}
	
	/**
	 * @return int
	 */
	public static function IGetMemTotal()
	{
		$lines = file_get_contents('/proc/meminfo');
		$lines = explode("\n", $lines);
		foreach ($lines as $line)
		{
			list($tag, $info) = explode(':', $line);
			if ('MemTotal' === $tag)
			{
				 return ceil(intval(trim($info)) / (1024. * 1024.)).' G';
			}
		}
		return '';
	}
	
	/**
	 * @return array
	 */
	public static function AGetCPUInfo()
	{
		$ret = array();
		$lines = file_get_contents('/proc/cpuinfo');
		$lines = explode("\n", $lines);
		$modelname = '';
		$cachesize = '';
		$physical = '';
		$siblings = '';
		$cores = '';
		foreach ($lines as $line)
		{
			if (0 === strlen($line))
			{
				$ret[$physical] = array(
					'model name' => $modelname,
					'cache size' => $cachesize,
					'siblings' => $siblings,
					'cpu cores' => $cores,
				);
			}
			list($tag, $info) = explode(':', $line);
			$tag = trim($tag);
			if ('model name' === $tag)
			{
				$modelname = trim($info);
			}
			else if ('cache size' === $tag)
			{
				$cachesize = trim($info);
			}
			else if ('physical id' === $tag)
			{
				$physical = trim($info);
			}
			else if ('siblings' === $tag)
			{
				$siblings = trim($info);
			}
			else if ('cpu cores' === $tag)
			{
				$cores = trim($info);
			}
		}
		ksort($ret);
		return $ret;
	}
	
	private static function _AExecCmd($cmd)
	{
		$output = array();
		$retval = 0;
		exec($cmd, $output, $retval);
		return array($output, $retval);
	}
}
