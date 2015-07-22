<?php
/**
 * Xhprof
 *
 * @package ko\tool
 * @author zhangchu
 */

if (!defined('KO_XHPROF'))
{
	/**
	 * 是否开启性能分析
	 */
	define('KO_XHPROF', false);
}
if (!defined('KO_XHPROF_LIBDIR'))
{
	/**
	 * xhprof_lib 目录位置
	 */
	define('KO_XHPROF_LIBDIR', '');
}
if (!defined('KO_XHPROF_WEBBASE'))
{
	/**
	 * xhprof_html web位置
	 */
	define('KO_XHPROF_WEBBASE', '');
}
if (!defined('KO_XHPROF_TMPDIR'))
{
	/**
	 * xhprof 临时文件存储位置
	 */
	define('KO_XHPROF_TMPDIR', KO_TEMPDIR.'xhprof/');
}
if (!defined('KO_XHPROF_FLAGS'))
{
	/**
	 * xhprof 默认标志
	 */
	define('KO_XHPROF_FLAGS', XHPROF_FLAGS_NO_BUILTINS | XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);
}

class Ko_Tool_Xhprof
{
	private static $s_bRunning = false;
	private static $s_vData;
	
	public static function VEnable($iFlags = KO_XHPROF_FLAGS, $aOptions = array())
	{
		if (self::_isEnable())
		{
			xhprof_enable($iFlags, $aOptions);
		}
	}
	
	public static function ADisable()
	{
		if (self::_isEnable())
		{
			return xhprof_disable();
		}
		return array();
	}
	
	public static function VStart()
	{
		if (self::_isEnable())
		{
			register_shutdown_function(array(__CLASS__, 'VEnd'));
			ob_start();
			self::VEnable();
			self::$s_bRunning = true;
		}
	}
	
	public static function VEnd()
	{
		if (self::$s_bRunning)
		{
			self::$s_bRunning = false;
			self::$s_vData = self::ADisable();
			
			if ('' !== KO_XHPROF_LIBDIR)
			{
				require_once (KO_XHPROF_LIBDIR.'utils/xhprof_lib.php');
				require_once (KO_XHPROF_LIBDIR.'utils/xhprof_runs.php');

				$xhprof_runs = new XHProfRuns_Default(KO_XHPROF_TMPDIR);
				$type = 'ko';
				self::$s_vData = $xhprof_runs->save_run(self::$s_vData, $type);
				
				if ('' !== KO_XHPROF_WEBBASE)
				{
					self::$s_vData = KO_XHPROF_WEBBASE
						.'?run='.urlencode(self::$s_vData).'&source='.urlencode($type);
					if (self::_isHtmlData())
					{
						echo '<a target="_blank" style="position:absolute;top:0;left:0;z-index:9999;" href="',
							htmlspecialchars(self::$s_vData),'">XHProf</a>';
					}
					header('X-Xhprof-Link: '.self::$s_vData);
				}
			}

			ob_end_flush();
		}
		return self::$s_vData;
	}
	
	private static function _isEnable()
	{
		return KO_XHPROF && function_exists('xhprof_enable');
	}
	
	private static function _isHtmlData()
	{
		$content = ob_get_contents();
		return strtoupper(substr($content, 0, 15)) === '<!DOCTYPE HTML>';
	}
}
