<?php
/**
 * Enc_Vbs
 *
 * @package ko\tool\enc
 * @author zhangchu
 */

class Ko_Tool_Enc_Vbs
{
	/**
	 * @return string
	 */
	public static function SEncode($aData)
	{
		return vbs_encode($aData);
	}

	/**
	 * @return array
	 */
	public static function ADecode($sData)
	{
		$o = vbs_decode($sData, $used);
		if ($used > 0 && is_array($o))
		{
			return $o;
		}
		return false;
	}
}

?>