<?php
/**
 * FastDFS
 *
 * @package ko\data
 * @author zhangchu
 */

//define('KO_IMAGE', 'Gd');
//include_once(dirname(__FILE__).'/../ko.class.php');

class Ko_Data_FastDFS extends Ko_Data_Storage
{
	protected function _bWrite($sContent, $sExt, &$sDest)
	{
		$ret = fastdfs_storage_upload_by_filebuff1($sContent, trim($sExt, '.'));
		if (false === $ret)
		{
			return false;
		}
		$sDest = $ret;
		return true;
	}
	
	public function sRead($sDest)
	{
		$ret = fastdfs_storage_download_file_to_buff1($sDest);
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
			return $sDest;
		}
		list($type, $brief) = explode('.', $sBriefTag, 2);
		assert(isset($this->_aBriefConf[$type][$brief]));
		return fastdfs_gen_slave_filename($sDest, '.'.$sBriefTag);
	}
	
	public function aParseUrl($sUrl)
	{
		$arr = explode('.', $sUrl);
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
		$master = fastdfs_storage_download_file_to_buff1($sDest);
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
		$ret = fastdfs_storage_upload_slave_by_filebuff1($slave, $sDest, '.'.$sBriefTag, $sExt);
		if (false === $ret)
		{
			return false;
		}
		return true;
	}
}

/*
class A extends Ko_Data_FastDFS
{
	protected $_aBriefConf = array(
		'photo' => array(
			'' => array('width' => '1000', 'height' => '1000', 'crop' => false),
			'small' => array('width' => '160', 'height' => '0', 'crop' => false),
			'logo' => array('width' => '120', 'height' => '120', 'crop' => true),
		),
	);
}
$api = new A;
//$ret = $api->bUpload($argv[1], null, $dest);
//var_dump($ret);
//var_dump($dest);
$dest = 'mfwStorage1/M00/E3/D6/wKgBvlCTvAWAVW1tAAcS4aNkRG0830.jpg';

$a = array('photo', 'photo.small', 'photo.logo');
foreach ($a as $v)
{
	$url = $api->sGetUrl(null, $dest, $v);
	var_dump($url);
	$arr = $api->aParseUrl($url);
	var_dump($arr);
	$ret = $api->bGenBrief($arr[0], $arr[1], $arr[2]);
	var_dump($ret);
}
//$ret = fastdfs_storage_delete_file1($dest);
//var_dump($ret);
*/