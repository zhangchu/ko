<?php
/**
 * Url
 *
 * @package ko\tool
 * @author zhangchu
 */

class Ko_Tool_Url
{
	/**
	 * 获取url中的域名关键词
	 * 如：http://www.sina.com.cn/xxx 返回 sina
	 *     http://www.baidu.com/xxx 返回 baidu
	 *
	 * @param string $sUrl
	 * @return string
	 */
	public static function SGetDomainTag($sUrl)
	{
		$urlinfo = parse_url($sUrl);
		return Ko_Tool_Domain::SGetTag($urlinfo['host']);
	}
}
