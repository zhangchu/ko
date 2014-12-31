<?php
/**
 * Domain
 *
 * @package ko\tool
 * @author zhangchu
 */

class Ko_Tool_Domain
{
	private static $s_aGenericTopLevelDomain = array(
		'com', 'net', 'org', 'gov', 'edu', 'mil', 'biz', 'name', 'info', 'mobi', 'pro',
		'travel', 'museum', 'int', 'aero', 'post', 'asia', 'cat', 'coop', 'jobs', 'tel', 'xxx',
	);

	/**
	 * 获取域名中的关键词
	 * 如：www.sina.com.cn 返回 sina
	 *     www.baidu.com 返回 baidu
	 *     www.mafengwo.cn 返回 mafengwo
	 *
	 * @param string $sDomain
	 * @return string
	 */
	public static function SGetTag($sDomain)
	{
		$tags = strtolower(rtrim($sDomain, '.'));
		$tags = explode('.', $tags);
		for ($i=count($tags)-2; $i>=0; $i--)
		{
			if (in_array($tags[$i], self::$s_aGenericTopLevelDomain, true))
			{
				continue;
			}
			return $tags[$i];
		}
		return '';
	}
}
