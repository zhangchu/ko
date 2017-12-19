<?php

if (!defined('KO_CODE_COVERAGE')) {
	define('KO_CODE_COVERAGE', 0);
}

class Ko_Tool_CodeCoverage
{
	private static $s_sRoot = '';
	private static $s_aWhiteList = array();
	private static $s_aBlackList = array();

	public static function root()
	{
		return self::$s_sRoot;
	}

	public static function start($root, $wl, $bl)
	{
		self::$s_sRoot = $root;
		self::$s_aWhiteList = $wl;
		self::$s_aBlackList = $bl;

		if (KO_CODE_COVERAGE) {
			xdebug_start_code_coverage();
			register_shutdown_function('Ko_Tool_CodeCoverage::end', time());
		}
	}

	public static function end($code_coverage_start)
	{
		$ret = xdebug_get_code_coverage();
		foreach ($ret as $filename => $lines) {
			$ccname = self::ccpath($filename);
			if (false !== $ccname) {
				$filemtime = filemtime($filename);
				if ($filemtime > $code_coverage_start) {
					unlink($ccname);
				} else {
					self::_write($ccname, $filemtime, $lines);
				}
			}
		}
	}

	public static function ccname($filename)
	{
		foreach (self::$s_aBlackList as $v) {
			if (0 === strncmp($v, $filename, strlen($v))) {
				return false;
			}
		}
		$tag = false;
		foreach (self::$s_aWhiteList as $k => $v) {
			if (0 == strncmp($k, $filename, strlen($k))) {
				$tag = $k;
				break;
			}
		}
		if (false === $tag) {
			return false;
		}
		$name = str_replace('/', '_', substr($filename, strlen($tag)));
		if (strlen($name) > 100) {
			$name = md5($name) . '-' . substr($name, -100);
		}
		return self::$s_aWhiteList[$tag] . '-' . $name;
	}

	public static function ccpath($filename)
	{
		$ccname = self::ccname($filename);
		if (false === $ccname) {
			return false;
		}
		$ccdir = KO_RUNTIME_DIR . '/cc/';
		if (!is_dir($ccdir)) {
			mkdir($ccdir);
			chmod($ccdir, 0777);
		}
		return $ccdir . $ccname;
	}

	public static function read($filename)
	{
		$ccname = self::ccpath($filename);
		if (false !== $ccname) {
			return self::_read($ccname, filemtime($filename));
		}
		return array();
	}

	private static function _read($ccname, $filemtime)
	{
		$lines = array();
		if (is_file($ccname)) {
			$fp = fopen($ccname, 'r');
			$oldfilemtime = 0;
			if ($fp) {
				while (!feof($fp)) {
					$line = fgets($fp);
					list($k, $v) = explode("\t", trim($line), 2);
					if ('filemtime' == $k) {
						$oldfilemtime = intval($v);
						if ($oldfilemtime != $filemtime) {
							$oldfilemtime = 0;
							break;
						}
					} else if ($k) {
						$lines[$k] = intval($v);
					}
				}
				fclose($fp);
				if (!$oldfilemtime) {
					unlink($ccname);
				}
			}
		}
		return $lines;
	}

	private static function _write($ccname, $filemtime, $lines)
	{
		if (is_file($ccname)) {
			$oldlines = self::_read($ccname, $filemtime);
			foreach ($oldlines as $k => $v) {
				$lines[$k] += $v;
			}
		}
		$fp = fopen($ccname, 'w');
		if ($fp) {
			fwrite($fp, "filemtime\t" . $filemtime . "\n");
			foreach ($lines as $k => $v) {
				fwrite($fp, $k . "\t" . $v . "\n");
			}
			fclose($fp);
			chmod($ccname, 0666);
		}
	}
}
