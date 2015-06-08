<?php
/**
 * Captcha
 *
 * 通用的验证码生成
 *
 * 基本使用方法：
 * $api = new Ko_Tool_Captcha();
 * $api->vBuild(100, 50, $font);
 * $api->vOutput();
 * //$api->sGetPhrase();  // 获取文本内容
 *
 * @package ko\tool
 * @author Loin
 * @package 通用工具类
 */

/**
 */
class Ko_Tool_Captcha
{
	private $_iTextColor = null;

	private $_iBackgroundColor = null;

	private $_hImage = null;

	private $_sPhrase = null;

	private $_bDistortion = true;

	private $_iMaxFrontLines = null;

	private $_iMaxBehindLines = null;

	private $_iMaxAngle = 8;

	private $_iMaxOffset = 5;

	private $_bInterpolation = true;

	private $_bIgnoreAllEffects = false;

	public function __construct($iLength = 4)
	{
		$this->_sPhrase = self::_SGeneratePhrase($iLength);
	}

	/**
	 * 设置是否平滑显示(默认为是)
	 * @param bool $bInterpolation
	 * @api
	 */
	public function vSetInterpolation($bInterpolation = true)
	{
		$this->_bInterpolation = $bInterpolation;
	}

	/**
	 * 设置是否变形(默认为是)
	 * @param $bDistortion
	 * @api
	 */
	public function vSetDistortion($bDistortion)
	{
		$this->_bDistortion = (bool)$bDistortion;
	}

	/**
	 * 设置是否忽略全部干扰效果(默认为否)
	 * @param $bIgnoreAllEffects
	 * @api
	 */
	public function vSetIgnoreAllEffects($bIgnoreAllEffects)
	{
		$this->_bIgnoreAllEffects = $bIgnoreAllEffects;
	}

	/**
	 * 设置文字后面最大的线条数
	 * @param $iLines
	 * @api
	 */
	public function vSetMaxBehindLines($iLines)
	{
		$this->_iMaxBehindLines = $iLines;
	}

	/**
	 * 设置文字前面最大的线条数
	 * @param $iLines
	 * @api
	 */
	public function vSetMaxFrontLines($iLines)
	{
		$this->_iMaxFrontLines = $iLines;
	}

	/**
	 * 设置文字最大旋转角度
	 * @param $iAngle
	 * @api
	 */
	public function vSetMaxAngle($iAngle)
	{
		$this->_iMaxAngle = $iAngle;
	}

	/**
	 * 设置文字最大偏移量
	 * @param $iOffset
	 * @api
	 */
	public function vSetMaxOffset($iOffset)
	{
		$this->_iMaxOffset = $iOffset;
	}

	/**
	 * 设置文字颜色(默认随机)
	 * @param $iR
	 * @param $iG
	 * @param $iB
	 * @api
	 */
	public function vSetTextColor($iR, $iG, $iB)
	{
		$this->_iTextColor = array($iR, $iG, $iB);
	}

	/**
	 * 设置背景颜色(默认随机)
	 * @param $iR
	 * @param $iG
	 * @param $iB
	 * @api
	 */
	public function vSetBackgroundColor($iR, $iG, $iB)
	{
		$this->_iBackgroundColor = array($iR, $iG, $iB);
	}

	/**
	 * 自定义指定验证码
	 * @param $sPhrase
	 * @api
	 */
	public function vSetPhrase($sPhrase)
	{
		$this->_sPhrase = strval($sPhrase);
	}

	/**
	 * 获取验证码
	 * @return string
	 * @api
	 */
	public function sGetPhrase()
	{
		return $this->_sPhrase;
	}

	/**
	 * 创建验证码
	 * @param int $iWidth 宽度
	 * @param int $iHeight 高度
	 * @param string $sFont 字体文件
	 * @param int $iFontSize 字体大小
	 * @api
	 */
	public function vBuild($iWidth = 150, $iHeight = 40, $sFont = null, $iFontSize = 0)
	{
		assert(!is_null($sFont) || file_exists($sFont));

		$image = imagecreatetruecolor($iWidth, $iHeight);
		if (is_null($this->_iBackgroundColor)) {
			$bg = imagecolorallocate(
				$image,
				mt_rand(200, 255),
				mt_rand(200, 255),
				mt_rand(200, 255)
			);
		} else {
			$bg = imagecolorallocate(
				$image,
				$this->_iBackgroundColor[0],
				$this->_iBackgroundColor[1],
				$this->_iBackgroundColor[2]
			);
		}
		imagefill($image, 0, 0, $bg);

		if (!$this->_bIgnoreAllEffects) {
			$square = $iWidth * $iHeight;
			$effects = mt_rand($square / 3000, $square / 2000);

			if (!is_null($this->_iMaxBehindLines) && $this->_iMaxBehindLines > 0) {
				$effects = min($this->_iMaxBehindLines, $effects);
			}

			if ($this->_iMaxBehindLines !== 0) {
				for ($i = 0; $i < $effects; $i++) {
					self::_VDrawLine($image, $iWidth, $iHeight);
				}
			}
		}

		$color = self::_IWritePhrase($image, $this->_sPhrase, $sFont, $iFontSize,
			$iWidth, $iHeight, $this->_iTextColor, $this->_iMaxAngle, $this->_iMaxOffset);

		if (!$this->_bIgnoreAllEffects) {
			$square = $iWidth * $iHeight;
			$effects = mt_rand($square / 3000, $square / 2000);

			if (!is_null($this->_iMaxFrontLines) && $this->_iMaxFrontLines > 0) {
				$effects = min($this->_iMaxFrontLines, $effects);
			}

			if ($this->_iMaxFrontLines !== 0) {
				for ($i = 0; $i < $effects; $i++) {
					self::_VDrawLine($image, $iWidth, $iHeight, $color);
				}
			}

			if ($this->_bDistortion) {
				$newimage = self::_HDistort($image, $iWidth, $iHeight, $bg, $this->_bInterpolation);
				imagedestroy($image);
				$image = $newimage;
			}

			if (is_null($this->_iTextColor) && is_null($this->_iBackgroundColor)) {
				self::_VPostEffect($image);
			}
		}

		$this->_hImage = $image;
	}

	/**
	 * 保存验证码图片文件
	 * @param string $sFilename 文件名
	 * @param int $iQuality 质量
	 * @api
	 */
	public function vSave($sFilename, $iQuality = 90)
	{
		imagejpeg($this->_hImage, $sFilename, $iQuality);
	}

	/**
	 * 返回验证码图片字节流
	 * @param int $iQuality 质量
	 * @return string
	 * @api
	 */
	public function sGet($iQuality = 90)
	{
		ob_start();
		imagejpeg($this->_hImage, null, $iQuality);
		return ob_get_clean();
	}

	/**
	 * 输出验证码图片
	 * @param int $iQuality 质量
	 * @api
	 */
	public function vOutput($iQuality = 90)
	{
		Ko_Web_Response::VSetContentType('image/jpeg');
		Ko_Web_Response::VSend();
		imagejpeg($this->_hImage, null, $iQuality);
	}

	private static function _VDrawLine($image, $width, $height, $color = null)
	{
		if (is_null($color)) {
			$color = imagecolorallocate(
				$image,
				mt_rand(100, 255),
				mt_rand(100, 255),
				mt_rand(100, 255)
			);
		}

		if (mt_rand(0, 1)) { //横向
			$Xa = mt_rand(0, $width / 2);
			$Ya = mt_rand(0, $height);
			$Xb = mt_rand($width / 2, $width);
			$Yb = mt_rand(0, $height);
		} else { //纵向
			$Xa = mt_rand(0, $width);
			$Ya = mt_rand(0, $height / 2);
			$Xb = mt_rand(0, $width);
			$Yb = mt_rand($height / 2, $height);
		}
		imagesetthickness($image, mt_rand(1, 3));
		imageline($image, $Xa, $Ya, $Xb, $Yb, $color);
	}

	private static function _IWritePhrase($image, $phrase, $font, $fontsize,
	                                      $width, $height, $textcolor, $maxangle, $maxoffset)
	{
		$size = abs(intval($fontsize));
		if ($size === 0) {
			$size = $width / strlen($phrase) - mt_rand(0, 3) - 1;
		}
		$box = imagettfbbox($size, 0, $font, $phrase);
		$textWidth = $box[2] - $box[0];
		$textHeight = $box[1] - $box[7];
		$x = ($width - $textWidth) / 2;
		$y = ($height - $textHeight) / 2 + $size;

		if (!count($textcolor)) {
			$textColor = array(
				mt_rand(0, 150),
				mt_rand(0, 150),
				mt_rand(0, 150),
			);
		} else {
			$textColor = $textcolor;
		}
		$color = imagecolorallocate($image, $textColor[0], $textColor[1], $textColor[2]);
		$phraseChar = Ko_Tool_Str::AStr2Arr($phrase);
		foreach ($phraseChar as $char) {
			$box = imagettfbbox($size, 0, $font, $char);
			$pos = $box[2] - $box[0];
			$angle = mt_rand(-$maxangle, $maxangle);
			$offset = mt_rand(-$maxoffset, $maxoffset);
			imagettftext($image, $size, $angle, $x, $y + $offset, $color, $font, $char);
			$x += $pos;
		}

		return $color;
	}

	private static function _VPostEffect($image)
	{
		if (!function_exists('imagefilter')) {
			return;
		}

		if (mt_rand(0, 1) == 0) {
			imagefilter($image, IMG_FILTER_NEGATE);
		}

		if (mt_rand(0, 10) == 0) {
			imagefilter($image, IMG_FILTER_EDGEDETECT);
		}

		imagefilter($image, IMG_FILTER_CONTRAST, mt_rand(-50, 10));

		if (mt_rand(0, 5) == 0) {
			imagefilter($image, IMG_FILTER_COLORIZE, mt_rand(-80, 50), mt_rand(-80, 50), mt_rand(-80, 50));
		}
	}

	private static function _HDistort($image, $width, $height, $bg, $interpolation)
	{
		$contents = imagecreatetruecolor($width, $height);
		$X = mt_rand(0, $width);
		$Y = mt_rand(0, $height);
		$phase = mt_rand(0, 10);
		$scale = 1.1 + mt_rand(0, 10000) / 30000;
		for ($x = 0; $x < $width; $x++) {
			for ($y = 0; $y < $height; $y++) {
				$Vx = $x - $X;
				$Vy = $y - $Y;
				$Vn = sqrt($Vx * $Vx + $Vy * $Vy);

				if ($Vn != 0) {
					$Vn2 = $Vn + 4 * sin($Vn / 30);
					$nX = $X + ($Vx * $Vn2 / $Vn);
					$nY = $Y + ($Vy * $Vn2 / $Vn);
				} else {
					$nX = $X;
					$nY = $Y;
				}
				$nY = $nY + $scale * sin($phase + $nX * 0.2);

				if ($interpolation) {
					$p = self::_IInterpolate(
						$nX - floor($nX),
						$nY - floor($nY),
						self::_IGetCol($image, floor($nX), floor($nY), $bg),
						self::_IGetCol($image, ceil($nX), floor($nY), $bg),
						self::_IGetCol($image, floor($nX), ceil($nY), $bg),
						self::_IGetCol($image, ceil($nX), ceil($nY), $bg)
					);
				} else {
					$p = self::_IGetCol($image, round($nX), round($nY), $bg);
				}

				if ($p == 0) {
					$p = $bg;
				}

				imagesetpixel($contents, $x, $y, $p);
			}
		}

		return $contents;
	}

	private static function _IGetCol($image, $x, $y, $background)
	{
		$L = imagesx($image);
		$H = imagesy($image);
		if ($x < 0 || $x >= $L || $y < 0 || $y >= $H) {
			return $background;
		}

		return imagecolorat($image, $x, $y);
	}

	private static function _IInterpolate($x, $y, $nw, $ne, $sw, $se)
	{
		list($r0, $g0, $b0) = self::_AGetRGB($nw);
		list($r1, $g1, $b1) = self::_AGetRGB($ne);
		list($r2, $g2, $b2) = self::_AGetRGB($sw);
		list($r3, $g3, $b3) = self::_AGetRGB($se);

		$cx = 1.0 - $x;
		$cy = 1.0 - $y;

		$m0 = $cx * $r0 + $x * $r1;
		$m1 = $cx * $r2 + $x * $r3;
		$r = (int)($cy * $m0 + $y * $m1);

		$m0 = $cx * $g0 + $x * $g1;
		$m1 = $cx * $g2 + $x * $g3;
		$g = (int)($cy * $m0 + $y * $m1);

		$m0 = $cx * $b0 + $x * $b1;
		$m1 = $cx * $b2 + $x * $b3;
		$b = (int)($cy * $m0 + $y * $m1);

		return ($r << 16) | ($g << 8) | $b;
	}

	private static function _AGetRGB($col)
	{
		return array(
			(int)($col >> 16) & 0xff,
			(int)($col >> 8) & 0xff,
			(int)($col) & 0xff,
		);
	}

	private static function _SGeneratePhrase($length)
	{
		$chars = str_split('abcdefghijklmnpqrstuvwxyz123456789');

		$phrase = '';
		$keys = array_rand($chars, $length);
		foreach ($keys as $key) {
			$phrase .= $chars[$key];
		}
		return $phrase;
	}
}