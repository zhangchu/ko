<?php
/**
 * Storage
 *
 * @package ko
 * @subpackage data
 * @author zhangchu
 */

interface IKo_Data_Storage
{
	public function bWrite($sContent, $sExt, $sDomain, &$sDest);
	public function sRead($sDomain, $sDest);

	public function sGetUniqStr($sDomain, $sDest, $sFilename, $sMimetype);
	public function aParseUniqStr($sUniqStr);
	
	public function sGetUrl($sDomain, $sDest, $sBriefTag);
	public function aParseUrl($sUrl);
	public function bGenBrief($sDomain, $sDest, $sBriefTag);
}

class Ko_Data_Storage implements IKo_Data_Storage
{
	/**
	 * 配置数组
	 *
	 * <pre>
	 * array(
	 *   'photo' => array(
	 *     '' => array('width' => '9980', 'height' => '9980', 'crop' => false),
	 *     'small' => array('width' => '160', 'height' => '120', 'crop' => false),
	 *     'smallv' => array('width' => '0', 'height' => '120', 'crop' => false),
	 *     'smallh' => array('width' => '160', 'height' => '0', 'crop' => false),
	 *     'logo' => array('width' => '120', 'height' => '120', 'crop' => true),
	 *     ...
	 *   ),
	 *   ...
	 * )
	 * </pre>
	 *
	 * @var array
	 */
	protected $_aBriefConf = array();
	
	public function bWrite($sContent, $sExt, $sDomain, &$sDest)
	{
		assert(0);
	}
	
	public function sRead($sDomain, $sDest)
	{
		assert(0);
	}

	public function sGetUniqStr($sDomain, $sDest, $sFilename, $sMimetype)
	{
		return urlencode($sDomain).'&'.urlencode($sDest).'&'.urlencode($sFilename).'&'.urlencode($sMimetype);
	}
	
	public function aParseUniqStr($sUniqStr)
	{
		list($sDomain, $sDest, $sFilename, $sMimetype) = explode('&', $sUniqStr, 4);
		return array(urldecode($sDomain), urldecode($sDest), urldecode($sFilename), urldecode($sMimetype));
	}
	
	public function sGetUrl($sDomain, $sDest, $sBriefTag)
	{
		assert(0);
	}
	
	public function aParseUrl($sUrl)
	{
		assert(0);
	}
	
	public function bGenBrief($sDomain, $sDest, $sBriefTag)
	{
		assert(0);
	}
}
