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

	public function sGetUniqStr($sDomain, $sDest, $iSize, $sMimetype, $sFilename);
	public function aParseUniqStr($sUniqStr);
	
	public function sGetUrl($sDomain, $sDest, $sBriefTag);
	public function aParseUrl($sUrl);
	public function bGenBrief($sDomain, $sDest, $sBriefTag);
}

class Ko_Data_Storage extends Ko_Busi_Api implements IKo_Data_Storage
{
	/**
	 * 缩略图配置数组
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
	/**
	 * 配置数组
	 *
	 * <pre>
	 * array(
	 *   'uni' => 文件排重表，在domain内部排重
	 * )
	 * </pre>
	 *
	 * <b>数据库例表</b>
	 * <pre>
	 *   CREATE TABLE s_image_uni (
	 *     md5 BINARY(16) not null default '',
	 *     domain varchar(32) not null default '',
	 *     dest varchar(128) not null default '',
	 *     ref int unsigned not null default 0,
	 *     UNIQUE KEY (md5, domain)
	 *   ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	 * </pre>
	 *
	 * @var array
	 */
	protected $_aConf = array();
	
	public function bWrite($sContent, $sExt, $sDomain, &$sDest)
	{
		if (strlen($this->_aConf['uni']))
		{
			$uniDao = $this->_aConf['uni'].'Dao';
			$md5 = md5($sContent, true);
			$key = array('md5' => $md5, 'domain' => $sDomain);
			$info = $this->$uniDao->aGet($key);
			if (!empty($info))
			{
				$this->$uniDao->iUpdate($key, array(), array('ref' => 1));
				$sDest = $info['dest'];
				return true;
			}
		}
		$ret = $this->_bWrite($sContent, $sExt, $sDomain, &$sDest);
		if ($ret && strlen($this->_aConf['uni']))
		{
			$aData = array(
				'md5' => $md5,
				'domain' => $sDomain,
				'dest' => $sDest,
				'ref' => 1,
			);
			$this->$uniDao->aInsert($aData, array(), array('ref' => 1));
		}
		return $ret;
	}
	
	protected function _bWrite($sContent, $sExt, $sDomain, &$sDest)
	{
		assert(0);
	}
	
	public function sRead($sDomain, $sDest)
	{
		assert(0);
	}

	public function sGetUniqStr($sDomain, $sDest, $iSize, $sMimetype, $sFilename)
	{
		return urlencode($sDomain).'&'.urlencode($sDest).'&'.urlencode($iSize).'&'.urlencode($sMimetype).'&'.urlencode($sFilename);
	}
	
	public function aParseUniqStr($sUniqStr)
	{
		list($sDomain, $sDest, $iSize, $sMimetype, $sFilename) = explode('&', $sUniqStr, 5);
		return array(urldecode($sDomain), urldecode($sDest), urldecode($iSize), urldecode($sMimetype), urldecode($sFilename));
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
