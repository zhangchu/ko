<?php
/**
 * Image_Imagick
 *
 * @package ko
 * @subpackage tool
 * @author zhangchu
 */

class Ko_Tool_Image_Imagick implements IKo_Tool_Image
{
	private static $s_aExtFunc = array(
		'gif' => array(0, true),
		'png' => array(0, false),
		'jpg' => array(90, false),
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
	
	public static function VCrop($sSrc, $sDst, $iWidth, $iHeight, $iFlag = 0, $aOption = array())
	{
		try
		{
			$imgsrc = self::_VCreateImage($sSrc, $iFlag);
			self::_VAlignImage($imgsrc);
			if (!empty($aOption['sharpen']))
			{
				$imgsrc->sharpenImage($aOption['sharpen']['radius'], $aOption['sharpen']['sigma']);
			}
			if ($aOption['srcx'] || $aOption['srcy'] || $aOption['srcw'] || $aOption['srch'])
			{
				$w = $imgsrc->getImageWidth();
				$h = $imgsrc->getImageHeight();
				$w = min($aOption['srcw'] ? $aOption['srcw'] : $w, $w - $aOption['srcx']);
				$h = min($aOption['srch'] ? $aOption['srch'] : $h, $h - $aOption['srcy']);
				if ($w <= 0 || $h <= 0 || $aOption['srcx'] < 0 || $aOption['srcy'] < 0)
				{
					$aOption['srcx'] = $aOption['srcy'] = $aOption['srcw'] = $aOption['srch'] = 0;
				}
			}
			foreach($imgsrc as $frame)
			{
				if ($aOption['srcx'] || $aOption['srcy'] || $aOption['srcw'] || $aOption['srch'])
				{
					$frame->cropImage($w, $h, $aOption['srcx'], $aOption['srcy']);
					$frame->setImagePage($w, $h, 0, 0);
				}
				$frame->cropThumbnailImage($iWidth, $iHeight);
			}
			if ($aOption['strip'])
			{
				$imgsrc->stripImage();
			}
			$ret = self::_VSaveImage($imgsrc, $sDst, false, $iFlag, intval($aOption['quality']));
			$imgsrc->destroy();
			return $ret;
		}
		catch (Exception $e)
		{
			return false;
		}
	}
	
	public static function VResize($sSrc, $sDst, $iWidth = 0, $iHeight = 0, $iFlag = 0, $aOption = array())
	{
		try
		{
			$imgsrc = self::_VCreateImage($sSrc, $iFlag);
			$page = $imgsrc->getImagePage();
			$w = $page['width'];
			$h = $page['height'];
			if ((0 == $iWidth || $iWidth >= $w) && (0 == $iHeight || $iHeight >= $h))
			{	//原图尺寸不够直接进行复制，忽略格式和option
				$imgsrc->destroy();
				return self::_VCopyImage($sSrc, $sDst, $iFlag);
			}
			if (!empty($aOption['sharpen']))
			{
				$imgsrc->sharpenImage($aOption['sharpen']['radius'], $aOption['sharpen']['sigma']);
			}
			foreach($imgsrc as $frame)
			{
				$page = $frame->getImagePage();
				$dst_w = $page['width'];
				$dst_h = $page['height'];
				if ($iWidth > 0 && $dst_w > $iWidth)
				{
					$dst_h = $dst_h * $iWidth / $dst_w;
					$dst_w = $iWidth;
				}
				if ($iHeight > 0 && $dst_h > $iHeight)
				{
					$dst_w = $dst_w * $iHeight / $dst_h;
					$dst_h = $iHeight;
				}
				$rw = $dst_w / $page['width'];
				$rh = $dst_h / $page['height'];
				$frame->scaleImage($frame->getImageWidth() * $rw, $frame->getImageHeight() * $rh);
				$frame->setImagePage($dst_w, $dst_h, $page['x'] * $rw, $page['y'] * $rh);
			}
			if ($aOption['strip'])
			{
				$imgsrc->stripImage();
			}
			$ret = self::_VSaveImage($imgsrc, $sDst, false, $iFlag, intval($aOption['quality']));
			$imgsrc->destroy();
			return $ret;
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	public static function VRotate($sSrc, $sDst, $fAngle, $iBgColor = 0xffffff, $iFlag = 0)
	{
		try
		{
			$pixel = new ImagickPixel('#'.dechex($iBgColor));
			$imgsrc = self::_VCreateImage($sSrc, $iFlag);
			$fAngle = (($fAngle % 360) + 360) % 360;
			$oblique = 0 != ($fAngle % 90);
			if ($oblique)
			{
				self::_VAlignImage($imgsrc);
				foreach($imgsrc as $frame)
				{
					$frame->rotateImage($pixel, $fAngle);
				}
			}
			else if ($fAngle)
			{
				foreach($imgsrc as $frame)
				{
					$page = $frame->getImagePage();
					$frame->rotateImage($pixel, $fAngle);
					switch ($fAngle)
					{
					case 90:
						$frame->setImagePage($page['height'], $page['width'], $page['height'] - $page['y'] - $frame->getImageWidth(), $page['x']);
						break;
					case 180:
						$frame->setImagePage($page['width'], $page['height'], $page['width'] - $page['x'] - $frame->getImageWidth(), $page['height'] - $page['y'] - $frame->getImageHeight());
						break;
					case 270:
						$frame->setImagePage($page['height'], $page['width'], $page['y'], $page['width'] - $page['x'] - $frame->getImageHeight());
						break;
					}
				}
			}
			$ret = self::_VSaveImage($imgsrc, $sDst, true, $iFlag);
			$imgsrc->destroy();
			return $ret;
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	public static function VFlipH($sSrc, $sDst, $iFlag = 0)
	{
		try
		{
			$imgsrc = self::_VCreateImage($sSrc, $iFlag);
			foreach($imgsrc as $frame)
			{
				$page = $frame->getImagePage();
				$frame->flopImage();
				$frame->setImagePage($page['width'], $page['height'], $page['width'] - $page['x'] - $frame->getImageWidth(), $page['y']);
			}
			$ret = self::_VSaveImage($imgsrc, $sDst, true, $iFlag);
			$imgsrc->destroy();
			return $ret;
		}
		catch (Exception $e)
		{
			return false;
		}
	}
	
	public static function VFlipV($sSrc, $sDst, $iFlag = 0)
	{
		try
		{
			$imgsrc = self::_VCreateImage($sSrc, $iFlag);
			foreach($imgsrc as $frame)
			{
				$page = $frame->getImagePage();
				$frame->flipImage();
				$frame->setImagePage($page['width'], $page['height'], $page['x'], $page['height'] - $page['y'] - $frame->getImageHeight());
			}
			$ret = self::_VSaveImage($imgsrc, $sDst, true, $iFlag);
			$imgsrc->destroy();
			return $ret;
		}
		catch (Exception $e)
		{
			return false;
		}
	}
	
	public static function VComposite($sSrc, $sDst, $sComposite, $iX, $iY, $iFlag = 0, $aOption = array())
	{
		try
		{
			$imgsrc = self::_VCreateImage($sSrc, $iFlag);
			$imgcomposite = self::_VCreateImageObject($sComposite, $iFlag & Ko_Tool_Image::FLAG_COMPOSITE_BLOB);
			$composite_w = $imgcomposite->getImageWidth();
			$composite_h = $imgcomposite->getImageHeight();
			foreach($imgsrc as $frame)
			{
				$page = $frame->getImagePage();
				$x = $iX;
				$y = $iY;
				if ($aOption['xyflag'] & Ko_Tool_Image::XYFLAG_X_CENTER)
				{
					$x += ($page['width'] - $composite_w) / 2;
				}
				else if ($aOption['xyflag'] & Ko_Tool_Image::XYFLAG_X_RIGHT)
				{
					$x = $page['width'] - $composite_w - $x;
				}
				if ($aOption['xyflag'] & Ko_Tool_Image::XYFLAG_Y_CENTER)
				{
					$y += ($page['height'] - $composite_h) / 2;
				}
				else if ($aOption['xyflag'] & Ko_Tool_Image::XYFLAG_Y_BOTTOM)
				{
					$y = $page['height'] - $composite_h - $y;
				}
				$frame->compositeImage($imgcomposite, Imagick::COMPOSITE_DEFAULT, $x - $page['x'], $y - $page['y']);
			}
			$ret = self::_VSaveImage($imgsrc, $sDst, true, $iFlag);
			$imgsrc->destroy();
			return $ret;
		}
		catch (Exception $e)
		{
			return false;
		}
	}
	
	private static function _VAlignImage($oImg)
	{
		$oImg->resetIterator();
		$framecount = $oImg->getNumberImages();
		for ($i=1; $i<$framecount; ++$i)
		{
			$oImg->setImageIndex($i);
			$oImg->setImage($oImg->coalesceImages());
		}
	}
	
	private static function _VCreateImage($sSrc, $iFlag)
	{
		return self::_VCreateImageObject($sSrc, $iFlag & Ko_Tool_Image::FLAG_SRC_BLOB);
	}
	
	private static function _VCreateImageObject($sSrc, $bSrcIsBlob)
	{
		if ($bSrcIsBlob)
		{
			$img = new Imagick();
			$img->readImageBlob($sSrc);
			return $img;
		}
		return new Imagick($sSrc);
	}
	
	private static function _VSaveImage($oImg, $sDst, $bLossless, $iFlag, $iQuality = 0)
	{
		$ext = strtolower(pathinfo($sDst, PATHINFO_EXTENSION));
		if (!isset(self::$s_aExtFunc[$ext]))
		{
			$ext = 'jpg';
		}
		$oImg->setImageFormat($ext);
		$oImg->resetIterator();
		if (!$bLossless)
		{
			$oImg->setImageCompressionQuality($iQuality ? $iQuality : self::$s_aExtFunc[$ext][0]);
		}
		if ($iFlag & Ko_Tool_Image::FLAG_DST_BLOB)
		{
			return $oImg->getImagesBlob();
		}
		if (self::$s_aExtFunc[$ext][1])
		{
			return $oImg->writeImages($sDst, true);
		}
		return $oImg->writeImage($sDst);
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
			return copy($sSrc, $sDst);
		}
	}
}
