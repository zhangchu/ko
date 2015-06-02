<?php
/**
 * FilterHandle
 *
 * @package ko\html
 * @author zhangchu
 */

//include_once('../ko.class.php');

class Ko_Html_FilterHandle
{
	public static function BFilterTag($oNode, $aAllowTag)
	{
		assert($oNode instanceof Ko_Html_Node);

		$tag = $oNode->sGetTag();
		return !in_array($tag, $aAllowTag, true);
	}

	public static function BFilterStyle($oNode, $oStyle, $aAllowStyle)
	{
		assert($oNode instanceof Ko_Html_Node);
		assert($oStyle instanceof Ko_Html_Style);

		$n = $oStyle->sGetName();
		if (!isset($aAllowStyle[$n]))
		{
			return true;
		}
		$v = $oStyle->sGetValue();
		foreach ($aAllowStyle[$n] as $cond)
		{
			if (preg_match($cond, $v))
			{
				return false;
			}
		}
		return true;
	}

	public static function BFilterAttr($oNode, $oAttr, $aAllowAttr, $aCheckProtocolsAttr, $aAllowProtocols)
	{
		assert($oNode instanceof Ko_Html_Node);
		assert($oAttr instanceof Ko_Html_Attr);

		$n = $oAttr->sGetName();
		if ('data-' === substr($n, 0, 5))
		{
			return false;
		}
		$v = $oAttr->sGetValue();
		if (isset($aAllowAttr['']) && in_array($n, $aAllowAttr[''], true))
		{
			if (self::_BCheckProtocols($n, $v, $aCheckProtocolsAttr, $aAllowProtocols))
			{
				return false;
			}
			return true;
		}
		$tag = $oNode->sGetTag();
		if (isset($aAllowAttr[$tag]) && in_array($n, $aAllowAttr[$tag], true))
		{
			if (self::_BCheckProtocols($n, $v, $aCheckProtocolsAttr, $aAllowProtocols))
			{
				return false;
			}
			return true;
		}
		return true;
	}

	public static function BFilterChild($oNode, $oChild, $aForbidTag)
	{
		assert($oNode instanceof Ko_Html_Node);

		if ($oChild instanceof Ko_Html_Comment)
		{
			return true;
		}
		if ($oChild instanceof Ko_Html_Node)
		{
			$tag = $oChild->sGetTag();
			if (in_array($tag, $aForbidTag, true))
			{
				return true;
			}
		}
		return false;
	}

	public static function SGetExtraAttr($oNode, $aExtraAttr)
	{
		assert($oNode instanceof Ko_Html_Node);

		$tag = $oNode->sGetTag();
		$sAttr = '';
		if (isset($aExtraAttr[$tag]))
		{
			foreach ($aExtraAttr[$tag] as $n => $v)
			{
				$sAttr .= ' '.$n.'="'.htmlspecialchars($v).'"';
			}
		}
		return $sAttr;
	}

	public static function SFilterHtml($sHtml)
	{
		return preg_replace('/script/i', ' crip ', $sHtml);
	}

	private static function _BCheckProtocols($sName, $sValue, $aCheckProtocolsAttr, $aAllowProtocols)
	{
		if (in_array($sName, $aCheckProtocolsAttr, true))
		{
			return self::_BIsAllowProtocols($sValue, $aAllowProtocols);
		}
		return true;
	}

	private static function _BIsAllowProtocols($sUrl, $aAllowProtocols)
	{
		list($protocol, $link) = explode('://', $sUrl);
		if (is_null($link))
		{
			$link = $protocol;
			if ('/' !== substr($link, 0, 1))
			{
				return false;
			}
			$protocol = '';
		}
		$protocol = strtolower($protocol);
		return strlen($link) && in_array($protocol, $aAllowProtocols, true);
	}
}

?>