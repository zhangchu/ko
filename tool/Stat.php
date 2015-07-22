<?php
/**
 * Stat
 *
 * @package ko\tool
 * @author zhangchu
 */

//include_once(dirname(__FILE__).'/../ko.class.php');

/**
 * 统计工具实现
 */
class Ko_Tool_Stat
{
	public static function VStatCache($sFilename)
	{
		$result = array();
		$fp = fopen($sFilename, 'r');
		if (false !== $fp)
		{
			while (!feof($fp))
			{
				$line = fgets($fp);
				list($date, $ip, $pid, $tag, $log) = explode("\t", trim($line), 5);
				if ($tag == 'stat/aGet'
					|| $tag == 'stat/InProc')
				{
					if ('miss' == $log)
					{
						$result[$tag]['miss'] ++;
					}
					else if ('cache' == $log)
					{
						$result[$tag]['cache'] ++;
					}
				}
				else if ($tag == 'stat/aGetListByKeys'
					|| $tag == 'stat/aGetDetails'
					|| $tag == 'stat/InProc2')
				{
					list($all, $miss) = explode(':', $log);
					$result[$tag]['miss'] += $miss;
					$result[$tag]['cache'] += $all - $miss;
				}
			}
			fclose($fp);
		}
		foreach ($result as $tag => $stat)
		{
			$all = $stat['miss'] + $stat['cache'];
			echo $tag,"\tall:",$all,"\tcache:",$stat['cache'],"\t",round($stat['cache']*100/$all, 1),'%',"\n";
		}
	}
}

//Ko_Tool_Stat::VStatCache($argv[1]);

?>