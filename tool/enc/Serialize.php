<?php
/**
 * Enc_Serialize
 *
 * @package ko
 * @subpackage tool
 * @author zhangchu
 */

class Ko_Tool_Enc_Serialize implements IKo_Tool_Enc
{
	/**
	 * @return string
	 */
	public static function SEncode($aData)
	{
		return serialize($aData);
	}

	/**
	 * @return array
	 */
	public static function ADecode($sData)
	{
		return unserialize($sData);
	}
}

?>