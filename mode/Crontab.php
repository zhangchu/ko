<?php
/**
 * Crontab
 *
 * @package ko\mode
 * @author zhangchu
 */

class Ko_Mode_Crontab
{
	/**
	 * 配置数组
	 *
	 * <pre>
	 * array(
	 *   'crontab' => array(
	 *     array('day' => 1,                          每月1号
	 *           'week' => array(1, 4),               0（表示星期天）到 6（表示星期六）
	 *           'hour' => 1,
	 *           'minute' => 0,
	 *           'path' => 'subdir',
	 *           'fg' => true|false,  同一时间的程序，如果都设置 fg，可以保证执行顺序(后一个等待前一个完成在执行)
	 *           'cmd' => '/usr/bin/php xxx.php >> xxx.log 2>>xxx.err'),每周两次
	 *     array('hour' => array(6, 12, 18),
	 *           'minute' => 0,
	 *           'cmd' => '/usr/bin/php xxx.php >> xxx.log 2>>xxx.err'),每天3次
	 *     array('minute' => 30,
	 *           'path' => '..',
	 *           'cmd' => '/usr/bin/php xxx.php >> xxx.log 2>>xxx.err'),每小时一次
	 *     array('minute' => array(0, 15, 30, 45),
	 *           'cmd' => '/usr/bin/php xxx.php >> xxx.log 2>>xxx.err'),每15分钟1次
	 *   ),
	 * )
	 * </pre>
	 *
	 * @var array
	 */
	protected $_aConf = array();
	
	/**
	 * 应用程序的入口函数
	 */
	public function vRun()
	{
		$now = time();
		$ymd = date('Y-m-d', $now);
		$time = date('H:i', $now);
		$week = date('w', $now);
		$day = date('j', $now);
		list($hour, $minute) = explode(':', $time);
		$hour = intval($hour);
		$minute = intval($minute);

		$curdir = getcwd();
		foreach ($this->_aConf['crontab'] as $v)
		{
			if (!Ko_Tool_Time::BCheckTime($v, $minute, $hour, $week, $day))
			{
				continue;
			}
			if (isset($v['path']) && '.' !== $v['path'])
			{
				if (!chdir($v["path"]))
				{
					continue;
				}
			}

			$cmd = trim($v['cmd'], '&');
			if (!$v['fg'])
			{
				$cmd .= ' &';
			}
			echo '[',$ymd,' ',$time,'] ',$cmd,"\n";
			system($cmd);

			if (isset($v['path']) && '.' !== $v['path'])
			{
				chdir($curdir);
			}
		}
	}
}
