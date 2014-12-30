<?php
/**
 * DLog
 *
 * @package ko\data
 * @author zhangchu
 */

/**
 * 封装dlog日志记录功能
 */
class Ko_Data_DLog extends Ko_Data_KProxy
{
	private static $s_OInstance;
	
	protected function __construct ()
	{
		KO_DEBUG >= 6 && Ko_Tool_Debug::VAddTmpLog('data/DLog', '__construct');
		parent::__construct('DLog');
	}

	public static function OInstance()
	{
		if (empty(self::$s_OInstance))
		{
			self::$s_OInstance = new self();
		}
		return self::$s_OInstance;
	}
	
	public static function VLog($identity, $tag, $locus, $content)
	{
		Ko_Data_DLog::OInstance()->_vLog($identity, $tag, $locus, $content);
	}

	private function _vLog($identity, $tag, $locus, $content)
	{
		$identity = str_replace(' ', ':', $identity);
		$tag = str_replace(' ', ':', $tag);
		$locus = str_replace(' ', ':', $locus);
		$aPara = array(
			'identity' => $identity,
			'tag'      => $tag,
			'locus'    => $locus,
			'content'  => strval($content),
		);
		$this->_oProxy->invoke_oneway('log', $aPara);
	}
}
