<?php
/**
 * Enc_IgBinary
 *
 * @package ko\tool\enc
 * @author zhangchu
 */

class Ko_Tool_Enc_IgBinary
{
	/**
	 * @return string
	 */
	public static function SEncode($aData)
	{
		return igbinary_serialize($aData);
	}

	/**
	 * @return array
	 */
	public static function ADecode($sData)
	{
		$ret = igbinary_unserialize($sData);
		if (null === $ret)
		{
			return false;
		}
		return $ret;
	}
}

?>