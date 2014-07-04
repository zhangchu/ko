<?php
/**
 * Enc
 *
 * @package ko\tool
 * @author zhangchu
 */

/**
 * 编码相关函数实现
 */
class Ko_Tool_Enc
{
	/**
	 * @return string
	 */
	public static function SEncode($aData)
	{
		return call_user_func(array('Ko_Tool_Enc_'.KO_ENC, 'SEncode'), $aData);
	}

	/**
	 * @return array
	 */
	public static function ADecode($sData)
	{
		return call_user_func(array('Ko_Tool_Enc_'.KO_ENC, 'ADecode'), $sData);
	}
}

?>