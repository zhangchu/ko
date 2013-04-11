<?php
/**
 * SaeStorage
 *
 * @package ko
 * @subpackage data
 * @author zhangchu
 */

class Ko_Data_SaeStorage extends Ko_Data_Storage
{
	public function bWrite($sContent, $sExt, $sDomain, &$sDest)
	{
		$sDest = str_replace('.', '_', uniqid('', true)).'.'.trim($sExt, '.');
		return false !== Ko_Tool_Singleton::OInstance('SaeStorage')->write($sDomain, $sDest, $sContent);
	}
	
	public function sRead($sDomain, $sDest)
	{
		$ret = Ko_Tool_Singleton::OInstance('SaeStorage')->read($sDomain, $sDest);
		if (false === $ret)
		{
			return '';
		}
		return $ret;
	}
	
	public function sGetUrl($sDomain, $sDest, $sBriefTag)
	{
		$sBriefTag = trim($sBriefTag, '.');
		if (0 == strlen($sBriefTag))
		{
			return Ko_Tool_Singleton::OInstance('SaeStorage')->getUrl($sDomain, $sDest);
		}
		list($type, $brief) = explode('.', $sBriefTag, 2);
		assert(isset($this->_aBriefConf[$type][$brief]));
		list($name, $ext) = explode('.', $sDest, 2);
		$dest = $name.'.'.$sBriefTag.'.'.$ext;
		if (false === Ko_Tool_Singleton::OInstance('SaeStorage')->fileExists($sDomain, $dest))
		{
			$this->bGenBrief($sDomain, $sDest, $sBriefTag);
		}
		return Ko_Tool_Singleton::OInstance('SaeStorage')->getUrl($sDomain, $dest);
	}
	
	public function aParseUrl($sUrl)
	{
		$info = parse_url($sUrl);
		list($subdomain, $tmp) = explode('.', $info['host'], 2);
		list($appname, $domain) = explode('-', $subdomain, 2);
		$arr = explode('.', ltrim($info['path'], '/'));
		assert(count($arr) >= 2);
		$sDest = array_shift($arr).'.'.array_pop($arr);
		return array($domain, $sDest, implode('.', $arr));
	}
	
	public function bGenBrief($sDomain, $sDest, $sBriefTag)
	{
		$sBriefTag = trim($sBriefTag, '.');
		if (0 == strlen($sBriefTag))
		{
			return true;
		}
		list($type, $brief) = explode('.', $sBriefTag, 2);
		assert(isset($this->_aBriefConf[$type][$brief]));
		$master = Ko_Tool_Singleton::OInstance('SaeStorage')->read($sDomain, $sDest);
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
		$ret = Ko_Tool_Singleton::OInstance('SaeStorage')->write($sDomain, $name.'.'.$sBriefTag.'.'.$ext, $slave);
		if (false === $ret)
		{
			return false;
		}
		return true;
	}
}
