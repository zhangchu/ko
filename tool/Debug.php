<?php
/**
 * Debug
 *
 * @package ko\tool
 * @author zhangchu
 */

//include_once('../ko.class.php');

/**
 * 调试工具实现
 */
class Ko_Tool_Debug
{
	/**
	 * 增加一条调试记录
	 */
	public static function VAddTmpLog($sTag, $sLog)
	{
		if (KO_DEBUG)
		{
			if (!file_exists(KO_LOG_FILE))
			{
				@touch(KO_LOG_FILE);
				@chmod(KO_LOG_FILE, 0666);
			}
			if (is_writable(KO_LOG_FILE))
			{
				$sLog = sprintf("%s\t%s\t%d\t%s\t%s\n", date('Y-m-d H:i:s'), Ko_Tool_Ip::SGetClientIP(), getmypid(), $sTag, $sLog);
				$fp = fopen(KO_LOG_FILE, 'a');
				fwrite($fp, $sLog);
				fclose($fp);

				$filesize = filesize(KO_LOG_FILE);
				if ($filesize >= 1024 * 1024 * 1024)
				{
					$newfilename = KO_LOG_FILE.'.'.date('YmdHis');
					@rename(KO_LOG_FILE, $newfilename);
				}
			}
		}
	}
}

/*

Ko_Tool_Debug::VAddTmpLog('hello world!', 'test');

*/
?>