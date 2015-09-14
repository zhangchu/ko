<?php
/**
 * Image_Gd
 *
 * @package ko\tool\image
 * @author zhangchu
 */

class Ko_Tool_Image_Gd
{
	private static $s_aExtFunc = array(
		'gif' => array('imagegif', 0, 0),
		'png' => array('imagepng', 9, 9),
		'jpg' => array('imagejpeg', 90, 98),
	);

	public static function VExif($sSrc, $iFlag = 0)
	{
		if ($iFlag & Ko_Tool_Image::FLAG_SRC_BLOB)
		{
			$tmpfile = tempnam(KO_TEMPDIR, '');
			file_put_contents($tmpfile, $sSrc);
			$exif = @exif_read_data($tmpfile);
			unlink($tmpfile);
		}
		else
		{
			$exif = @exif_read_data($sSrc);
		}
		return $exif;
	}
	
	public static function VInfo($sSrc, $iFlag = 0)
	{
		if ($iFlag & Ko_Tool_Image::FLAG_SRC_BLOB)
		{
			$tmpfile = tempnam(KO_TEMPDIR, '');
			file_put_contents($tmpfile, $sSrc);
			$arr = getimagesize($tmpfile);
			$exif = @exif_read_data($tmpfile);
			unlink($tmpfile);
		}
		else
		{
			$arr = getimagesize($sSrc);
			$exif = @exif_read_data($sSrc);
		}
		$info = array(
			'width' => intval($arr[0]),
			'height' => intval($arr[1]),
			'type' => image_type_to_extension($arr[2], false),
			'orientation' => $exif['IFD0']['Orientation'],
		);
		if ($info['width'] && $info['height'])
		{
			return $info;
		}
		return false;
	}
	
	public static function VCrop($sSrc, $sDst, $iWidth, $iHeight, $iFlag = 0, $aOption = array())
	{
		$imgsrc = self::_VCreateImage($sSrc, $iFlag);
		if (false === $imgsrc)
		{
			return false;
		}
		$w = imagesx($imgsrc);
		$h = imagesy($imgsrc);
		if ($aOption['srcx'] || $aOption['srcy'] || $aOption['srcw'] || $aOption['srch'])
		{
			$w = min($aOption['srcw'] ? $aOption['srcw'] : $w, $w - $aOption['srcx']);
			$h = min($aOption['srch'] ? $aOption['srch'] : $h, $h - $aOption['srcy']);
			if ($w <= 0 || $h <= 0 || $aOption['srcx'] < 0 || $aOption['srcy'] < 0)
			{
				$aOption['srcx'] = $aOption['srcy'] = 0;
				$w = imagesx($imgsrc);
				$h = imagesy($imgsrc);
			}
		}

		if ($w / $h > $iWidth / $iHeight)
		{	//原图片比例宽了，左右需要裁切
			$src_y = $aOption['srcy'];
			$src_h = $h;
			$src_w = $src_h * $iWidth / $iHeight;
			$src_x = $aOption['srcx'] + ($w - $src_w) / 2;
		}
		else
		{	//源图片比例高了，上下需要裁切
			$src_x = $aOption['srcx'];
			$src_w = $w;
			$src_h = $src_w * $iHeight / $iWidth;
			$src_y = $aOption['srcy'] + ($h - $src_h) / 2;
		}

		$imgdst = imagecreatetruecolor($iWidth, $iHeight);
		$ret = imagecopyresampled($imgdst, $imgsrc, 0, 0, $src_x, $src_y, $iWidth, $iHeight, $src_w, $src_h);
		if (false !== $ret)
		{
			$ret = self::_VSaveImage($imgdst, $sDst, false, $iFlag);
		}
		imagedestroy($imgdst);
		imagedestroy($imgsrc);
		return $ret;
	}
	
	public static function VResize($sSrc, $sDst, $iWidth = 0, $iHeight = 0, $iFlag = 0, $aOption = array())
	{
		$imgsrc = self::_VCreateImage($sSrc, $iFlag);
		if (false === $imgsrc)
		{
			return false;
		}
		$dst_w = $w = imagesx($imgsrc);
		$dst_h = $h = imagesy($imgsrc);
		if ((0 == $iWidth || $iWidth >= $w) && (0 == $iHeight || $iHeight >= $h))
		{	//原图尺寸不够直接进行复制，忽略格式和option
			imagedestroy($imgsrc);
			return self::_VCopyImage($sSrc, $sDst, $iFlag);
		}
		
		if ($iWidth > 0 && $dst_w > $iWidth)
		{
			$dst_h = max(1, intval($dst_h * $iWidth / $dst_w));
			$dst_w = $iWidth;
		}
		if ($iHeight > 0 && $dst_h > $iHeight)
		{
			$dst_w = max(1, intval($dst_w * $iHeight / $dst_h));
			$dst_h = $iHeight;
		}
		
		$imgdst = imagecreatetruecolor($dst_w, $dst_h);
		$ret = imagecopyresampled($imgdst, $imgsrc, 0, 0, 0, 0, $dst_w, $dst_h, $w, $h);
		if (false !== $ret)
		{
			$ret = self::_VSaveImage($imgdst, $sDst, false, $iFlag);
		}
		imagedestroy($imgdst);
		imagedestroy($imgsrc);
		return $ret;
	}

	public static function VRotate($sSrc, $sDst, $fAngle, $iBgColor = 0xffffff, $iFlag = 0)
	{
		$imgsrc = self::_VCreateImage($sSrc, $iFlag);
		if (false === $imgsrc)
		{
			return false;
		}
		
		$imgdst = imagerotate($imgsrc, 360 - $fAngle, $iBgColor);
		$ret = self::_VSaveImage($imgdst, $sDst, true, $iFlag);
		imagedestroy($imgdst);
		imagedestroy($imgsrc);
		return $ret;
	}

	public static function VFlipH($sSrc, $sDst, $iFlag = 0)
	{
		$imgsrc = self::_VCreateImage($sSrc, $iFlag);
		if (false === $imgsrc)
		{
			return false;
		}
		$w = imagesx($imgsrc);
		$h = imagesy($imgsrc);
		
		$halfw = intval($w / 2);
		for ($i=0; $i<$halfw; ++$i)
		{
			for ($j=0; $j<$h; ++$j)
			{
				$left = imagecolorat($imgsrc, $i, $j);
				$right = imagecolorat($imgsrc, $w - $i - 1, $j);
				imagesetpixel($imgsrc, $i, $j, $right);
				imagesetpixel($imgsrc, $w - $i - 1, $j, $left);
			}
		}

		$ret = self::_VSaveImage($imgsrc, $sDst, true, $iFlag);
		imagedestroy($imgsrc);
		return $ret;
	}
	
	public static function VFlipV($sSrc, $sDst, $iFlag = 0)
	{
		$imgsrc = self::_VCreateImage($sSrc, $iFlag);
		if (false === $imgsrc)
		{
			return false;
		}
		$w = imagesx($imgsrc);
		$h = imagesy($imgsrc);
		
		$halfh = intval($h / 2);
		for ($i=0; $i<$halfh; ++$i)
		{
			for ($j=0; $j<$w; ++$j)
			{
				$left = imagecolorat($imgsrc, $j, $i);
				$right = imagecolorat($imgsrc, $j, $h - $i - 1);
				imagesetpixel($imgsrc, $j, $i, $right);
				imagesetpixel($imgsrc, $j, $h - $i - 1, $left);
			}
		}

		$ret = self::_VSaveImage($imgsrc, $sDst, true, $iFlag);
		imagedestroy($imgsrc);
		return $ret;
	}
	
	public static function VComposite($sSrc, $sDst, $sComposite, $iX, $iY, $iFlag = 0, $aOption = array())
	{
		$imgsrc = self::_VCreateImage($sSrc, $iFlag);
		if (false === $imgsrc)
		{
			return false;
		}
		$w = imagesx($imgsrc);
		$h = imagesy($imgsrc);
		$imgcomposite = self::_VCreateImageObject($sComposite, $iFlag & Ko_Tool_Image::FLAG_COMPOSITE_BLOB);
		if (false === $imgcomposite)
		{
			return false;
		}
		$composite_w = imagesx($imgcomposite);
		$composite_h = imagesy($imgcomposite);

		if ($aOption['xyflag'] & Ko_Tool_Image::XYFLAG_X_CENTER)
		{
			$iX += ($w - $composite_w) / 2;
		}
		else if ($aOption['xyflag'] & Ko_Tool_Image::XYFLAG_X_RIGHT)
		{
			$iX = $w - $composite_w - $iX;
		}
		if ($aOption['xyflag'] & Ko_Tool_Image::XYFLAG_Y_CENTER)
		{
			$iY += ($h - $composite_h) / 2;
		}
		else if ($aOption['xyflag'] & Ko_Tool_Image::XYFLAG_Y_BOTTOM)
		{
			$iY = $h - $composite_h - $iY;
		}
		
		$dstimg = imagecreatetruecolor($composite_w, $composite_h);
		imagecopy($dstimg, $imgsrc, 0, 0, $iX, $iY, $composite_w, $composite_h);
		imagecopy($dstimg, $imgcomposite, 0, 0, 0, 0, $composite_w, $composite_h);
		imagecopy($imgsrc, $dstimg, $iX, $iY, 0, 0, $composite_w, $composite_h);
		imagedestroy($dstimg);
			
		$ret = self::_VSaveImage($imgsrc, $sDst, true, $iFlag);
		imagedestroy($imgcomposite);
		imagedestroy($imgsrc);
		return $ret;
	}
	
	private static function _VCreateImage($sSrc, $iFlag)
	{
		return self::_VCreateImageObject($sSrc, $iFlag & Ko_Tool_Image::FLAG_SRC_BLOB);
	}
	
	private static function _VCreateImageObject($sSrc, $bSrcIsBlob)
	{
		if ($bSrcIsBlob)
		{
			return imagecreatefromstring($sSrc);
		}
		return imagecreatefromstring(file_get_contents($sSrc));
	}
	
	private static function _VSaveImage($oImg, $sDst, $bLossless, $iFlag)
	{
		$ext = strtolower(pathinfo($sDst, PATHINFO_EXTENSION));
		if (!isset(self::$s_aExtFunc[$ext]))
		{
			$ext = 'jpg';
		}
		$imagefunc = self::$s_aExtFunc[$ext][0];
		if ($iFlag & Ko_Tool_Image::FLAG_DST_BLOB)
		{
			$tmpfile = tempnam(KO_TEMPDIR, '');
			if (false === $tmpfile)
			{
				return false;
			}
			$ret = $imagefunc($oImg, $tmpfile, self::$s_aExtFunc[$ext][$bLossless ? 2 : 1]);
			if (false === $ret)
			{
				unlink($tmpfile);
				return false;
			}
			$content = file_get_contents($tmpfile);
			unlink($tmpfile);
			return $content;
		}
		return $imagefunc($oImg, $sDst, self::$s_aExtFunc[$ext][$bLossless ? 2 : 1]);
	}
	
	private static function _VCopyImage($sSrc, $sDst, $iFlag)
	{
		if (($iFlag & Ko_Tool_Image::FLAG_SRC_BLOB) && ($iFlag & Ko_Tool_Image::FLAG_DST_BLOB))
		{
			return $sSrc;
		}
		else if ($iFlag & Ko_Tool_Image::FLAG_SRC_BLOB)
		{
			return strlen($sSrc) === file_put_contents($sDst, $sSrc);
		}
		else if ($iFlag & Ko_Tool_Image::FLAG_DST_BLOB)
		{
			return file_get_contents($sSrc);
		}
		else
		{
			$sSrc = file_get_contents($sSrc);
			if (false === $sSrc)
			{
				return false;
			}
			return strlen($sSrc) === file_put_contents($sDst, $sSrc);
		}
	}
}
