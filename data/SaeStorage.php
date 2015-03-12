<?php
/**
 * SaeStorage
 *
 * @package ko\data
 * @author zhangchu
 */

class Ko_Data_SaeStorage extends Ko_Data_Storage
{
	private $_sDomain;
	
	public function __construct($sDomain)
	{
		$this->_sDomain = $sDomain;
	}
	
	protected function _bWrite($sContent, $sExt, &$sDest)
	{
		$sDest = str_replace('.', '_', uniqid('', true)).'.'.trim($sExt, '.');
		return false !== Ko_Tool_Singleton::OInstance('SaeStorage')->write($this->_sDomain, $sDest, $sContent);
	}
	
	public function sRead($sDest)
	{
		$ret = Ko_Tool_Singleton::OInstance('SaeStorage')->read($this->_sDomain, $sDest);
		if (false === $ret)
		{
			return '';
		}
		return $ret;
	}
	
	public function sGetUrl($sDest, $sBriefTag)
	{
		$sBriefTag = trim($sBriefTag, '.');
		if (0 == strlen($sBriefTag))
		{
			return Ko_Tool_Singleton::OInstance('SaeStorage')->getUrl($this->_sDomain, $sDest);
		}
		list($type, $brief) = explode('.', $sBriefTag, 2);
		assert(isset($this->_aBriefConf[$type][$brief]));
		list($name, $ext) = explode('.', $sDest, 2);
		$dest = $name.'.'.$sBriefTag.'.'.$ext;
		if (false === Ko_Tool_Singleton::OInstance('SaeStorage')->fileExists($this->_sDomain, $dest))
		{
			$this->bGenBrief($sDest, $sBriefTag);
		}
		return Ko_Tool_Singleton::OInstance('SaeStorage')->getUrl($this->_sDomain, $dest);
	}
	
	public function aParseUrl($sUrl)
	{
		$info = parse_url($sUrl);
		$arr = explode('.', ltrim($info['path'], '/'));
		assert(count($arr) >= 2);
		$sDest = array_shift($arr).'.'.array_pop($arr);
		return array($sDest, implode('.', $arr));
	}
	
	public function bGenBrief($sDest, $sBriefTag)
	{
		$sBriefTag = trim($sBriefTag, '.');
		if (0 == strlen($sBriefTag))
		{
			return true;
		}
		list($type, $brief) = explode('.', $sBriefTag, 2);
		assert(isset($this->_aBriefConf[$type][$brief]));
		$master = Ko_Tool_Singleton::OInstance('SaeStorage')->read($this->_sDomain, $sDest);
		if (false === $master)
		{
			return false;
		}
		$sExt = pathinfo($sDest, PATHINFO_EXTENSION);
		$method = $this->_aBriefConf[$type][$brief]['crop'] ? 'VCrop' : 'VResize';
		$slave = Ko_Tool_Image::$method($master, '1.'.$sExt, $this->_aBriefConf[$type][$brief]['width'], $this->_aBriefConf[$type][$brief]['height'], Ko_Tool_Image::FLAG_SRC_BLOB | Ko_Tool_Image::FLAG_DST_BLOB);
		if (false === $slave)
		{
			return false;
		}
		list($name, $ext) = explode('.', $sDest, 2);
		$ret = Ko_Tool_Singleton::OInstance('SaeStorage')->write($this->_sDomain, $name.'.'.$sBriefTag.'.'.$ext, $slave);
		if (false === $ret)
		{
			return false;
		}
		return true;
	}
}
