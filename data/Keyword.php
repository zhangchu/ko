<?php
/**
 * Keyword
 *
 * @package ko\data
 * @author zhangchu
 */

/**
 * 封装 KeywordMan 的接口
 */
class Ko_Data_Keyword extends Ko_Data_KProxy
{
	private static $s_OInstance;

	protected function __construct ()
	{
		KO_DEBUG >= 6 && Ko_Tool_Debug::VAddTmpLog('data/Keyword', '__construct');
		parent::__construct('KeywordMan');
	}

	public static function OInstance()
	{
		if (empty(self::$s_OInstance))
		{
			self::$s_OInstance = new self();
		}
		return self::$s_OInstance;
	}
	
	public function aSearch($sCategory, $sContent)
	{
		$aPara = array(
			'category' => $sCategory,
			'content' => $sContent,
			'charset' => 'utf8',
		);
		$ret = $this->_oProxy->invoke('search', $aPara);
		return $ret['wordPoses'];
	}
	
	public function aWordCount($sCategory, $sContent)
	{
		$aPara = array(
			'category' => $sCategory,
			'content' => $sContent,
			'charset' => 'utf8',
		);
		$ret = $this->_oProxy->invoke('wordcount', $aPara);
		return $ret['counts'];
	}
}
