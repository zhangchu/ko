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
	
	public static function VCrop($sSrc, $sDst, $iWidth, $iHeight, $iFlag = 0)
	{
		try
		{
			$imgsrc = self::_VCreateImage($sSrc, $iFlag);
			self::_VAlignImage($imgsrc);
			foreach($imgsrc as $frame)
			{
				$frame->cropThumbnailImage($iWidth, $iHeight);
			}
			$ret = self::_VSaveImage($imgsrc, $sDst, false, $iFlag);
			$imgsrc->destroy();
			return $ret;
		}
		catch (Exception $e)
		{
			return false;
		}
	}
	
	public static function VResize($sSrc, $sDst, $iWidth = 0, $iHeight = 0, $iFlag = 0)
	{
		try
		{
			$imgsrc = self::_VCreateImage($sSrc, $iFlag);
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
			$ret = self::_VSaveImage($imgsrc, $sDst, false, $iFlag);
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
		if ($iFlag & Ko_Tool_Image::FLAG_SRC_BLOB)
		{
			$img = new Imagick();
			$img->readImageBlob($sSrc);
			return $img;
		}
		return new Imagick($sSrc);
	}
	
	private static function _VSaveImage($oImg, $sDst, $bLossless, $iFlag)
	{
		$ext = strtolower(pathinfo($sDst, PATHINFO_EXTENSION));
		assert(isset(self::$s_aExtFunc[$ext]));
		$oImg->setImageFormat($ext);
		$oImg->resetIterator();
		if (!$bLossless)
		{
			$oImg->setImageCompressionQuality(self::$s_aExtFunc[$ext][0]);
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
}
