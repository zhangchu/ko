<?php
/**
 * Storage
 *
 * @package ko\data
 * @author zhangchu
 */

class Ko_Data_Storage extends Ko_Busi_Api
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
	 *   'urlmap' => url映射表，将外部网络文件转换为内部文件
	 *   'uni' => 文件排重表
	 *   'size' => 图片尺寸表
	 *   'fileinfo' => 文件信息表，包括文件尺寸，mimetype, 真实文件名，创建时间等
	 * )
	 * </pre>
	 *
	 * <b>数据库例表</b>
	 * <pre>
	 *   CREATE TABLE s_file_uni (
	 *     md5 BINARY(16) not null default '',
	 *     dest varchar(128) not null default '',
	 *     ref int unsigned not null default 0,
	 *     UNIQUE KEY (md5)
	 *   ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
	 *   CREATE TABLE `s_file_urlmap` (
	 *     `url` varchar(512) NOT NULL DEFAULT '',
	 *     `dest` varchar(128) NOT NULL DEFAULT '',
	 *     `ref` int unsigned not null default 0,
	 *     UNIQUE KEY (`url`)
	 *   ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
	 *   CREATE TABLE `s_file_size` (
	 *     `dest` varchar(128) NOT NULL DEFAULT '',
	 *     width int unsigned not null default 0,
	 *     height int unsigned not null default 0,
	 *     UNIQUE KEY (`dest`)
	 *   ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
	 *   CREATE TABLE `s_file_fileinfo` (
	 *     `dest` varchar(128) NOT NULL DEFAULT '',
	 *     `size` int unsigned not null default 0,
	 *     `mimetype` varchar(64) NOT NULL DEFAULT '',
	 *     `filename` varchar(256) NOT NULL DEFAULT '',
	 *     `ctime` timestamp NOT NULL DEFAULT 0,
	 *     UNIQUE KEY (`dest`)
	 *   ) ENGINE=InnoDB DEFAULT CHARSET=UTF8;
	 *   CREATE TABLE `s_file_exif` (
	 *     `dest` varchar(128) NOT NULL DEFAULT '',
	 *     `exif` blob not null default '',
	 *     UNIQUE KEY (`dest`)
	 *   ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
	 * </pre>
	 *
	 * @var array
	 */
	protected $_aConf = array();
	
	public function aGetFileInfo($sDest)
	{
		assert(strlen($this->_aConf['fileinfo']));
		
		$fileinfoDao = $this->_aConf['fileinfo'].'Dao';
		return $this->$fileinfoDao->aGet($sDest);
	}
	
	public function aGetFilesInfo($aDest)
	{
		assert(strlen($this->_aConf['fileinfo']));
		
		$fileinfoDao = $this->_aConf['fileinfo'].'Dao';
		return $this->$fileinfoDao->aGetListByKeys($aDest);
	}
	
	public function aGetImageSize($sDest)
	{
		assert(strlen($this->_aConf['size']));
		
		$sizeDao = $this->_aConf['size'].'Dao';
		return $this->$sizeDao->aGet($sDest);
	}
	
	public function aGetImagesSize($aDest)
	{
		assert(strlen($this->_aConf['size']));
		
		$sizeDao = $this->_aConf['size'].'Dao';
		return $this->$sizeDao->aGetListByKeys($aDest);
	}

	public function aGetImageExif($sDest)
	{
		assert(strlen($this->_aConf['exif']));

		$exifDao = $this->_aConf['exif'].'Dao';
		$ret = $this->$exifDao->aGet($sDest);
		$exif = Ko_Tool_Enc::ADecode($ret['exif']);
		if (false === $exif)
		{
			return array();
		}
		return $exif;
	}
	
	public function bUpload2Storage($aFile, &$sDest, $bOnlyImage = true)
	{
		if (UPLOAD_ERR_OK  === $aFile['error'] && $aFile['size'])
		{
			$ret = $this->bContent2Storage(file_get_contents($aFile['tmp_name']), $sDest, $bOnlyImage);
			if ($ret)
			{
				$this->_vSetFileinfo($sDest, $aFile['type'], $aFile['name']);
			}
			return $ret;
		}
		return false;
	}
	
	public function bWebUrl2Storage($sUrl, &$sDest, $bOnlyImage = true)
	{
		if ($this->_bIsUrlExist($sUrl, $sDest))
		{
			return true;
		}
		
		$content = file_get_contents($sUrl);
		if (false !== $content)
		{
			$ret = $this->bContent2Storage($content, $sDest, $bOnlyImage);
			if ($ret)
			{
				$this->_vSetUrl($sUrl, $sDest);
			}
			return $ret;
		}
		return false;
	}
	
	public function bContent2Storage($sContent, &$sDest, $bOnlyImage = true)
	{
		$imginfo = Ko_Tool_Image::VInfo($sContent, Ko_Tool_Image::FLAG_SRC_BLOB);
		if (false !== $imginfo)
		{
			$ret = $this->bWrite($sContent, $imginfo['type'], $sDest);
			if ($ret)
			{
				if (5 <= $imginfo['orientation'] && $imginfo['orientation'] <= 8)
				{
					$this->_vSetSize($sDest, $imginfo['height'], $imginfo['width']);
				}
				else
				{
					$this->_vSetSize($sDest, $imginfo['width'], $imginfo['height']);
				}

				if (strlen($this->_aConf['exif'])
					&& (false !== ($exif = Ko_Tool_Image::VExif($sContent, Ko_Tool_Image::FLAG_SRC_BLOB))))
				{
					$this->_vSetExif($sDest, $exif);
				}
			}
		}
		else if (!$bOnlyImage)
		{
			$ret = $this->bWrite($sContent, '', $sDest);
		}
		else
		{
			$ret = false;
		}
		return $ret;
	}
	
	public function bWrite($sContent, $sExt, &$sDest)
	{
		if (strlen($this->_aConf['uni']))
		{
			$md5 = md5($sContent, true);
			if ($this->_bIsMd5Exist($md5, $sDest))
			{
				return true;
			}
		}
		$ret = $this->_bWrite($sContent, $sExt, $sDest);
		if ($ret)
		{
			$this->_vSetMd5($md5, $sDest);
			$this->_vSetFilesize($sDest, strlen($sContent));
		}
		return $ret;
	}
	
	public function bWriteFile($sFilename, $sExt, &$sDest)
	{
		if (strlen($this->_aConf['uni']))
		{
			$md5 = md5_file($sFilename, true);
			if ($this->_bIsMd5Exist($md5, $sDest))
			{
				return true;
			}
		}
		$ret = $this->_bWriteFile($sFilename, $sExt, $sDest);
		if ($ret)
		{
			$this->_vSetMd5($md5, $sDest);
			$this->_vSetFilesize($sDest, filesize($sFilename));
		}
		return $ret;
	}
	
	private function _vSetFileinfo($sDest, $mimetype, $filename)
	{
		if (strlen($this->_aConf['fileinfo']))
		{
			$fileinfoDao = $this->_aConf['fileinfo'].'Dao';
			$data = array(
				'dest' => $sDest,
				'mimetype' => $mimetype,
				'filename' => $filename,
				'ctime' => date('Y-m-d H:i:s'),
			);
			$update = array(
				'mimetype' => $mimetype,
				'filename' => $filename,
			);
			$this->$fileinfoDao->aInsert($data, $update);
		}
	}
	
	private function _vSetFilesize($sDest, $filesize)
	{
		if (strlen($this->_aConf['fileinfo']))
		{
			$fileinfoDao = $this->_aConf['fileinfo'].'Dao';
			$data = array(
				'dest' => $sDest,
				'size' => $filesize,
				'ctime' => date('Y-m-d H:i:s'),
			);
			$update = array(
				'size' => $filesize,
			);
			$this->$fileinfoDao->aInsert($data, $update);
		}
	}
	
	private function _vSetSize($sDest, $width, $height)
	{
		if (strlen($this->_aConf['size']))
		{
			$sizeDao = $this->_aConf['size'].'Dao';
			$data = array(
				'dest' => $sDest,
				'width' => $width,
				'height' => $height,
			);
			$update = array(
				'width' => $width,
				'height' => $height,
			);
			$this->$sizeDao->aInsert($data, $update);
		}
	}

	private function _vSetExif($sDest, $exif)
	{
		unset($exif['MakerNote']);
		unset($exif['UserComment']);

		$exifDao = $this->_aConf['exif'].'Dao';
		$data = array(
			'dest' => $sDest,
			'exif' => Ko_Tool_Enc::SEncode($exif),
		);
		$update = array(
			'exif' => Ko_Tool_Enc::SEncode($exif),
		);
		$this->$exifDao->aInsert($data, $update);
	}
	
	private function _bIsUrlExist($url, &$sDest)
	{
		if (strlen($this->_aConf['urlmap']))
		{
			$urlmapDao = $this->_aConf['urlmap'].'Dao';
			$info = $this->$urlmapDao->aGet($url);
			if (!empty($info))
			{
				$this->$urlmapDao->iUpdate($url, array(), array('ref' => 1));
				$sDest = $info['dest'];
				return true;
			}
		}
		return false;
	}
	
	private function _vSetUrl($url, $sDest)
	{
		if (strlen($this->_aConf['urlmap']))
		{
			$urlmapDao = $this->_aConf['urlmap'].'Dao';
			$aData = array(
				'url' => $url,
				'dest' => $sDest,
				'ref' => 1,
			);
			$this->$urlmapDao->aInsert($aData, array(), array('ref' => 1));
		}
	}
	
	private function _bIsMd5Exist($md5, &$sDest)
	{
		if (strlen($this->_aConf['uni']))
		{
			$uniDao = $this->_aConf['uni'].'Dao';
			$info = $this->$uniDao->aGet($md5);
			if (!empty($info))
			{
				$this->$uniDao->iUpdate($md5, array(), array('ref' => 1));
				$sDest = $info['dest'];
				return true;
			}
		}
		return false;
	}
	
	private function _vSetMd5($md5, $sDest)
	{
		if (strlen($this->_aConf['uni']))
		{
			$uniDao = $this->_aConf['uni'].'Dao';
			$aData = array(
				'md5' => $md5,
				'dest' => $sDest,
				'ref' => 1,
			);
			$this->$uniDao->aInsert($aData, array(), array('ref' => 1));
		}
	}
	
	protected function _bWrite($sContent, $sExt, &$sDest)
	{
		$tmpfile = tempnam(KO_TEMPDIR, '');
		file_put_contents($tmpfile, $sContent);
		$ret = $this->_bWriteFile($tmpfile, $sExt, $sDest);
		unlink($tmpfile);
		return $ret;
	}
	
	protected function _bWriteFile($sFilename, $sExt, &$sDest)
	{
		return $this->_bWrite(file_get_contents($sFilename), $sExt, $sDest);
	}
	
	public function sRead($sDest)
	{
		assert(0);
	}

	public function sGetUrl($sDest, $sBriefTag)
	{
		assert(0);
	}
	
	public function aParseUrl($sUrl)
	{
		assert(0);
	}
	
	public function bGenBrief($sDest, $sBriefTag)
	{
		assert(0);
	}
}
