<?php
/**
 * WebFilter
 *
 * @package ko\html
 * @author zhangchu
 */

//include_once('../ko.class.php');

class Ko_Html_WebFilter implements IKo_Html_Filter
{
	private static $s_aAllowTag = array(
		'table', 'tbody', 'th', 'tr', 'td',
		'b', 'strike', 'i', 'u', 'font',
		'sub', 'sup', 'em', 's', 'strong',
		'ol', 'ul', 'blockquote', 'a',
		'p', 'div', 'center', 'span',
		'br', 'hr', 'li', 'img', 'embed',
		);
	private static $s_aForbidTag = array(
		'style', 'script',
		);
	private static $s_aAllowAttr = array(
		'' => array('title', 'width', 'height', 'align', 'valign', 'border', 'bgcolor', 'clear', 'noshade', 'size', 'face', 'color', 'cellspacing', 'cellpadding', 'span', 'class'),
		'a' => array('href'),
		'img' => array('src', 'hspace', 'vspace'),
		'embed' => array('src', 'loop', 'autostart', 'wmode', 'quality', 'scale', 'allowfullscreen', 'pluginspage'),
		);
	private static $s_aExtraAttr = array(
		'a' => array(
			'target' => '_blank',
			),
		'embed' => array(
			'type' => 'application/x-shockwave-flash',
			'allowscriptaccess' => 'never',
			'allownetworking' => 'none',
			),
		);
	private static $s_aCheckProtocolsAttr = array(
		'src', 'href',
		);
	private static $s_aAllowProtocols = array(
		'', 'mms', 'http', 'https', 'ftp',
		);
	private static $s_aAllowStyle = array(
		'display' => array('/^(block|inline|none)$/i'),
		'color' => array('/^#\\w{3,6}$/i', '/^rgb\(\\d{1,3}, ?\\d{1,3}, ?\\d{1,3}\)$/i'),
		'background-color' => array('/^#\\w{3,6}$/i', '/^rgb\(\\d{1,3}, ?\\d{1,3}, ?\\d{1,3}\)$/i'),
		'text-align' => array('/^(left|center|right)$/i'),
		'text-decoration' => array('/^\\w*$/i'),
		'font-weight' => array('/^\\w*$/i'),
		'font-style' => array('/^\\w*$/i'),
		'font-size' => array('/^\\w*$/i'),
		'font-family' => array('/^[^\\"]*$/i'),
		'padding-right' => array('/^\\w*$/i'),
		'padding-left' => array('/^\\w*$/i'),
		'padding-bottom' => array('/^\\w*$/i'),
		'padding-top' => array('/^\\w*$/i'),
		'width' => array('/^\\w*$/i'),
		'height' => array('/^\\w*$/i'),
		);

	public function bFilterTag($oNode)
	{
		return Ko_Html_FilterHandle::BFilterTag($oNode, self::$s_aAllowTag);
	}

	public function bFilterStyle($oNode, $oStyle)
	{
		return Ko_Html_FilterHandle::BFilterStyle($oNode, $oStyle, self::$s_aAllowStyle);
	}

	public function bFilterAttr($oNode, $oAttr)
	{
		return Ko_Html_FilterHandle::BFilterAttr($oNode, $oAttr, self::$s_aAllowAttr,
			self::$s_aCheckProtocolsAttr, self::$s_aAllowProtocols);
	}

	public function bFilterChild($oNode, $oChild)
	{
		return Ko_Html_FilterHandle::BFilterChild($oNode, $oChild, self::$s_aForbidTag);
	}

	public function sGetExtraAttr($oNode)
	{
		return Ko_Html_FilterHandle::SGetExtraAttr($oNode, self::$s_aExtraAttr);
	}

	public function sFilterHtml($sHtml)
	{
		return Ko_Html_FilterHandle::SFilterHtml($sHtml);
	}
}
