<?php
/**
 * Image_Gd
 *
 * @package ko
 * @subpackage tool
 * @author zhangchu
 */

class Ko_Tool_Image_Gd implements IKo_Tool_Image
{
	private static $s_aExtFunc = array(
		'gif' => array('imagegif', 0, 0),
		'png' => array('imagepng', 9, 9),
		'jpg' => array('imagejpeg', 90, 98),
	);
	
	public static function VValidImageType($sFile)
	{
		$type = exif_imagetype($sFile);
		switch ($type)
		{
		case IMAGETYPE_GIF:
			return 'gif';
		case IMAGETYPE_JPEG:
			return 'jpg';
		case IMAGETYPE_PNG:
			return 'png';
		}
		return false;
	}

	public static function VCrop($sSrc, $sDst, $iWidth, $iHeight, $iFlag = 0)
	{
		$imgsrc = self::_VCreateImage($sSrc, $iFlag);
		if (false === $imgsrc)
		{
			return false;
		}
		$w = imagesx($imgsrc);
		$h = imagesy($imgsrc);

		if ($w / $h > $iWidth / $iHeight)
		{	//原图片比例宽了，左右需要裁切
			$src_y = 0;
			$src_h = $h;
			$src_w = $src_h * $iWidth / $iHeight;
			$src_x = ($w - $src_w) / 2;
		}
		else
		{	//源图片比例高了，上下需要裁切
			$src_x = 0;
			$src_w = $w;
			$src_h = $src_w * $iHeight / $iWidth;
			$src_y = ($h - $src_h) / 2;
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
	
	public static function VResize($sSrc, $sDst, $iWidth = 0, $iHeight = 0, $iFlag = 0)
	{
		$imgsrc = self::_VCreateImage($sSrc, $iFlag);
		if (false === $imgsrc)
		{
			return false;
		}
		$dst_w = $w = imagesx($imgsrc);
		$dst_h = $h = imagesy($imgsrc);
		
		if ($iWidth > 0 && $dst_w > $iWidth)
		{
			$dst_h = intval($dst_h * $iWidth / $dst_w);
			$dst_w = $iWidth;
		}
		if ($iHeight > 0 && $dst_h > $iHeight)
		{
			$dst_w = intval($dst_w * $iHeight / $dst_h);
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
	
	private static function _VCreateImage($sSrc, $iFlag)
	{
		if ($iFlag & Ko_Tool_Image::FLAG_SRC_BLOB)
		{
			return imagecreatefromstring($sSrc);
		}
		return imagecreatefromstring(file_get_contents($sSrc));
	}
	
	private static function _VSaveImage($oImg, $sDst, $bLossless, $iFlag)
	{
		$ext = strtolower(pathinfo($sDst, PATHINFO_EXTENSION));
		assert(isset(self::$s_aExtFunc[$ext]));
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
}
