<?php
/**
 * Utils
 *
 * @package ko\web
 * @author zhangchu
 */

class Ko_Web_Utils
{
	/**
	 * 判断是否是ajax请求
	 *
	 * @return boolean
	 */
	public static function BIsAjax()
	{
		return 'XMLHttpRequest' === Ko_Web_Request::SHttpXRequestedWith();
	}
	
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
