<?php
/**
 * 使用说明
 *
 * <b>简介</b>
 * <pre>
 *   服务器监控，IP发现，角色发现
 * </pre>
 *
 * <b>数据库例表</b>
 * <pre>
 *   CREATE TABLE ko_monitor_adminlog(
 *     id int unsigned not null auto_increment,
 *     kind varchar(64) not null default '',
 *     infoid varchar(128) not null default '',
 *     action tinyint not null default 0,
 *     content blob not null default '',
 *     admin blob not null default '',
 *     ip varchar(100) not null default '',
 *     ctime timestamp not null default CURRENT_TIMESTAMP,
 *     PRIMARY KEY(id),
 *     KEY(kind, id),
 *     KEY(kind, infoid, id)
 *   )ENGINE=MyISAM DEFAULT CHARSET=UTF8;
 *   CREATE TABLE ko_monitor_ipsegment(
 *     ip int unsigned not null default 0,
 *     mask int unsigned not null default 0,
 *     online tinyint unsigned not null default 0,
 *     unique(ip, mask)
 *   )ENGINE=MyISAM DEFAULT CHARSET=UTF8;
 *   CREATE TABLE ko_monitor_ip(
 *     ip int unsigned not null default 0,
 *     online tinyint unsigned not null default 0,
 *     unique(ip)
 *   )ENGINE=MyISAM DEFAULT CHARSET=UTF8;
 *   CREATE TABLE ko_monitor_iprole(
 *     ip int unsigned not null default 0,
 *     role varchar(20) not null default '',
 *     config blob,
 *     unique (ip, role)
 *   )ENGINE=MyISAM DEFAULT CHARSET=UTF8;
 *   CREATE TABLE ko_monitor_ipreport(
 *     ip int unsigned not null default 0,
 *     type varchar(64) not null default '',
 *     report blob,
 *     ctime timestamp not null default 0,
 *     unique(ip, type)
 *   )ENGINE=MyISAM DEFAULT CHARSET=UTF8;
 * </pre>
 *
 * <b>_aConf 配置</b>
 * @see Ko_Mode_Monitor::$_aConf
 *
 * @package ko\mode
 * @author zhangchu
 */

/**
 * 实现
 */
class Ko_Mode_Monitor extends Ko_Busi_Api
{
	/**
	 * 配置数组
	 *
	 * <pre>
	 * array(
	 *   'community' => snmp设置
	 *   'ipsegmentApi' => ip段表
	 *   'ipApi' => ip表
	 *   'iproleApi' => ip角色表
	 *   'ipreportApi' => ip报告表
	 *   'rolecheckApi' => 角色检查
	 *   'tcpservices' => 知名的tcp服务
	 *   'tcpservicesalias' => tcp服务的一些别名
	 *   'udpservices' => 知名的udp服务
	 *   'unixservices' => 知名的unix socket服务
	 *   'findrole_range' => 单位：秒，获取角色时会在这个范围内延迟若干分钟，以防止中央服务器的瞬间压力
	 *   'report_range' => 单位：秒，汇报状况时会在这个范围内延迟若干分钟，以防止中央服务器的瞬间压力
	 *   'codelist' => array(), 需要同步的监控程序代码
	 *   'rsynctag' => array('from' => , 'to' => ), 同步监控程序使用的根路径和 rsync 模块
	 * )
	 * </pre>
	 *
	 * @var array
	 */
	protected $_aConf = array();
	
	/**
	 * 检查所有服务器状况
	 */
	public function vCheck()
	{
		$ipApi = $this->_aConf['ipApi'];
		$option = new Ko_Tool_SQL;
		$iplist = $this->$ipApi->aGetList($option->oOrderBy('ip'));
		foreach ($iplist as $ipinfo)
		{
			$ip = long2ip($ipinfo['ip']);
			$memtotal = $this->_sGetMemTotal($ip);
			if (strlen($memtotal))
			{
				$this->_aInsertIpReport($ipinfo['ip'], 'hw_mem', $memtotal);
			}
			$cpuinfo = $this->_sGetCpuInfo($ip);
			if (strlen($cpuinfo))
			{
				$this->_aInsertIpReport($ipinfo['ip'], 'hw_cpu', $cpuinfo);
			}
		}
	}
	
	/**
	 * 汇报当前服务器服务状况
	 */
	public function vReport($bImmediately = false)
	{
		$ipinfo = $this->_aGetInnerIpInfo();
		$ip = ip2long($ipinfo['ip']);
		$bImmediately || $this->_vSleepRange($ip, $this->_aConf['report_range']);
		
		$iproleApi = $this->_aConf['iproleApi'];
		$option = new Ko_Tool_SQL;
		$rolelist = $this->$iproleApi->aGetList($option->oWhere('ip = ?', $ip));
		foreach ($rolelist as &$v)
		{
			$v['config'] = Ko_Tool_Enc_Serialize::ADecode($v['config']);
			$rolecheckApi = $this->_aConf['rolecheckApi'];
			$methodname = 'aCheck_'.$v['role'];
			if (method_exists($this->$rolecheckApi, $methodname))
			{
				$report = $this->$rolecheckApi->$methodname();
				$this->_aInsertIpReport($ip, 'role_'.$v['role'], $report);
			}
		}
		unset($v);
	}
	
	/**
	 * 获取当前服务器扮演的角色
	 */
	public function vFindRole($bImmediately = false)
	{
		$ipinfo = $this->_aGetInnerIpInfo();
		$ip = ip2long($ipinfo['ip']);
		$bImmediately || $this->_vSleepRange($ip, $this->_aConf['report_range']);

		$aConfig = array();
		$aUnknown = array();
		$infos = Ko_Tool_CMD::ANetstatInfo();
		foreach ($infos as $info)
		{
			$sip = long2ip($ip);
			if ('tcp' === $info[0])
			{
				list($service, $pname, $pid, $localaddr) = $this->_aParseTcpService($sip, $info);
				if (in_array($service, $this->_aConf['tcpservices'], true))
				{
					$aConfig['tcp_'.$service][$pid][$pname][] = $localaddr;
				}
				else
				{
					$aUnknown[] = implode("\t", $info);
					echo date('Y-m-d H:i:s'),"\t",$sip,"\t",$service,"\tUNKNOWN\n";
				}
			}
			else if ('udp' === $info[0])
			{
				list($service, $pid, $socket) = $this->_aParseUdpService($sip, $info);
				if (in_array($service, $this->_aConf['udpservices'], true))
				{
					$aConfig['udp_'.$service][$pid][$service][] = $socket;
				}
				else
				{
					$aUnknown[] = implode("\t", $info);
					echo date('Y-m-d H:i:s'),"\t",$sip,"\t",$service,"\tUNKNOWN\n";
				}
			}
			else if ('unix' === $info[0])
			{
				list($service, $pid, $socket) = $this->_aParseUnixService($sip, $info);
				if (in_array($service, $this->_aConf['unixservices'], true))
				{
					$aConfig['unix_'.$service][$pid][$service][] = $socket;
				}
				else
				{
					$aUnknown[] = implode("\t", $info);
					echo date('Y-m-d H:i:s'),"\t",$sip,"\t",$service,"\tUNKNOWN\n";
				}
			}
			else
			{
				$aUnknown[] = implode("\t", $info);
				echo date('Y-m-d H:i:s'),"\t",$sip,"\t",$info[0],"\tUNKNOWN\n";
			}
		}
		
		$this->_vInsertIpRoles($ip, $aConfig, $aUnknown);
	}
	
	/**
	 * 查找当前服务器所在网段的所有可以 ping 的 IP
	 */
	public function vFindIp($sMask = '')
	{
		$ipinfo = $this->_aGetInnerIpInfo();
		$ip = ip2long($ipinfo['ip']);
		if (0 === strlen($sMask))
		{
			$sMask = $ipinfo['mask'];
		}
		$mask = ip2long($sMask);
		$this->_aInsertIpSegment($ip, $mask);
		echo date('Y-m-d H:i:s'),"\tipinfo\t",long2ip($ip),"\t",long2ip($mask),"\n";
		
		$ipc = Ko_Tool_Ip::IGetIpCountInMask($mask);
		for ($i=1; $i<$ipc-1; $i++)
		{
			$nip = ($ip & $mask) + $i;
			$ping = Ko_Tool_CMD::BPingIpInfo(long2ip($nip));
			if ($ping)
			{
				$this->_aInsertIp($nip);
			}
			echo date('Y-m-d H:i:s'),"\t",$i,"\t",long2ip($nip),"\t",$ping ? 'true' : 'false',"\n";
		}
	}
	
	/**
	 * 同步监控代码到当前所有服务器列表
	 */
	public function vSyncAllIp()
	{
		$ipApi = $this->_aConf['ipApi'];
		$option = new Ko_Tool_SQL;
		$iplist = $this->$ipApi->aGetList($option->oOrderBy('ip'));
		foreach ($iplist as $ipinfo)
		{
			$ip = long2ip($ipinfo['ip']);
			$this->vSyncOneIp($ip);
		}
	}
	
	/**
	 * 同步监控代码到指定服务器，通常应用应重新实现该函数
	 */
	public function vSyncOneIp($sIp)
	{
		foreach ($this->_aConf['codelist'] as $code)
		{
			$code = rtrim($code, "/\\");
			$ret = Ko_Tool_CMD::BRsync($this->_aConf['rsynctag']['from'].'/'.$code, $sIp.'::'.$this->_aConf['rsynctag']['to'].'/'.dirname($code));
			echo $sIp,' ',$ret ? 'ok' : 'error',"\n";
		}
	}
	
	private function _vSleepRange($sTag, $iRange)
	{
		if ($iRange)
		{
			$second = hexdec(substr(md5($sTag), -4)) % $iRange;
			echo date('Y-m-d H:i:s'),"\tSleep\t",$second,"\n";
			sleep($second);
		}
		return true;
	}
	
	private function _aGetInnerIpInfo()
	{
		$ret = Ko_Tool_CMD::AFindIpInfo();
		foreach ($ret as $v)
		{
			if (Ko_Tool_Ip::BIsInnerIP($v['ip']))
			{
				return $v;
			}
		}
		assert(0);
	}
	
	private function _aInsertIpReport($sIp, $sType, $aReport)
	{
		$report = empty($aReport) ? '' : Ko_Tool_Enc_Serialize::SEncode($aReport);
		$aData = array(
			'ip' => $sIp,
			'type' => $sType,
			'report' => $report,
			'ctime' => date('Y-m-d H:i:s'),
		);
		$ipreportApi = $this->_aConf['ipreportApi'];
		return $this->$ipreportApi->aInsert($aData, $aData);
	}
	
	private function _aInsertIpRole($sIp, $sRole, $aConfig)
	{
		$config = empty($aConfig) ? '' : Ko_Tool_Enc_Serialize::SEncode($aConfig);
		$aData = array(
			'ip' => $sIp,
			'role' => $sRole,
			'config' => $config,
		);
		$iproleApi = $this->_aConf['iproleApi'];
		return $this->$iproleApi->aInsert($aData, $aData);
	}
	
	private function _aInsertIpSegment($sIp, $sMask)
	{
		$aData = array(
			'ip' => $sIp,
			'mask' => $sMask,
		);
		$ipsegmentApi = $this->_aConf['ipsegmentApi'];
		return $this->$ipsegmentApi->aInsert($aData, $aData);
	}
	
	private function _aInsertIp($sIp)
	{
		$aData = array(
			'ip' => $sIp,
		);
		$ipApi = $this->_aConf['ipApi'];
		return $this->$ipApi->aInsert($aData, $aData);
	}
	
	private function _vInsertIpRoles($sIp, $aConfig, $aUnknown)
	{
		foreach ($aConfig as $service => &$v)
		{
			foreach ($v as $pid => &$vv)
			{
				ksort($vv);
				foreach ($vv as $pname => &$vvv)
				{
					sort($vvv);
				}
				unset($vvv);
			}
			unset($vv);
		}
		unset($v);
		
		foreach ($aConfig as $service => $v)
		{
			usort($v, array($this, '_iComIpRoles'));
			$config = array();
			foreach ($v as $pid => $vv)
			{
				foreach ($vv as $pname => $vvv)
				{
					$config[$pid][] = array(
						'pname' => $pname,
						'localaddr' => implode(' ', $vvv),
					);
				}
			}
			$this->_aInsertIpRole($sIp, $service, $config);
			echo date('Y-m-d H:i:s'),"\t",long2ip($sIp),"\t",$service,"\n";
		}
		
		$newroles = array_keys($aConfig);
		if (!empty($aUnknown))
		{
			$newroles[] = 'unknown';
			$this->_aInsertIpRole($sIp, 'unknown', $aUnknown);
		}
		
		$iproleApi = $this->_aConf['iproleApi'];
		$option = new Ko_Tool_SQL;
		$this->$iproleApi->iDeleteByCond($option->oWhere('ip = ? and role not in (?)', $sIp, $newroles));
	}
	
	private function _iComIpRoles($a, $b)
	{
		$ka = array_keys($a);
		$kb = array_keys($b);
		if ($ka === $kb)
		{
			if ($a === $b)
			{
				return 0;
			}
			return ($a < $b) ? -1 : 1;
		}
		return ($ka < $kb) ? -1 : 1;
	}
	
	private function _aParseTcpService($sIp, $aInfo)
	{
		list($pid, $pname) = explode('/', $aInfo[6]);
		if (':873' === substr($aInfo[3], -4))
		{
			$service = 'rsync';
		}
		else if (isset($this->_aConf['tcpservicesalias'][$pname]))
		{
			$service = $this->_aConf['tcpservicesalias'][$pname];
		}
		else
		{
			$service = $pname;
		}
		return array($service, $pname, $pid, $this->_aNormalizeLocalAddr($sIp, $aInfo[3]));
	}
	
	private function _aParseUdpService($sIp, $aInfo)
	{
		list($pid, $pname) = explode('/', $aInfo[5]);
		return array($pname, $pid, $this->_aNormalizeLocalAddr($sIp, $aInfo[3]));
	}
	
	private function _aParseUnixService($sIp, $aInfo)
	{
		list($pid, $pname) = explode('/', $aInfo[8]);
		return array($pname, $pid, $aInfo[9]);
	}
	
	private function _aNormalizeLocalAddr($sIp, $sLocalAddr)
	{
		$len = strlen($sIp) + 1;
		if (0 === strncmp($sIp.':', $sLocalAddr, $len))
		{
			return 'innerhost:'.substr($sLocalAddr, $len);
		}
		return $sLocalAddr;
	}
	
	private function _sGetMemTotal($sIp)
	{
		$ret = snmpget($sIp, $this->_aConf['community'], 'hrMemorySize.0', 3000);
		if (false !== $ret)
		{
			list($t, $s) = explode(':', $ret);
			return ceil(intval(trim($s)) / (1024. * 1024.)).' G';
		}
		return '';
	}
	
	private function _sGetCpuInfo($sIp)
	{
		$cpuinfo = array();
		$ret = snmpwalk($sIp, $this->_aConf['community'], 'hrDeviceEntry', 300000);
		if (is_array($ret))
		{
			$len = 0;
			foreach ($ret as $k => $v)
			{
				list($t, $s) = explode(':', $v);
				if ('INTEGER' !== $t)
				{
					$len = $k;
					break;
				}
			}
			for ($i=0; $i<$len; $i++)
			{
				list($t, $s) = explode(':', $ret[$len+$i], 2);
				if (' HOST-RESOURCES-TYPES::hrDeviceProcessor' == $s)
				{
					list($t, $s) = explode(':', $ret[2*$len+$i], 2);
					$cpuinfo[trim($s)]++;
				}
			}
		}
		$ret = array();
		foreach ($cpuinfo as $k => $v)
		{
			$ret[] = $v.'个逻辑CPU，'.$k;
		}
		return implode(' || ', $ret);
	}
}
