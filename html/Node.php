<?php
/**
 * Node
 *
 * @package ko\html
 * @author zhangchu
 */

//include_once('../ko.class.php');

class Ko_Html_Node implements IKo_Html_Item
{
	static private $s_aSingleTag = array('br', 'hr', 'img', 'embed');

	private $_oParent;
	private $_sTag = '';
	private $_bClosed = false;
	private $_aAttr = array();
	private $_oStyles;
	private $_aChild = array();
	private $_aFilter = array();

	public function __construct($oParent = null, $sTag = '')
	{
		assert(($oParent instanceof Ko_Html_Node) || is_null($oParent));

		$this->_oParent = $oParent;
		$this->_sTag = $sTag;
	}

	public function bParse($oHtmlStr)
	{
		assert($oHtmlStr instanceof Ko_Html_Str);

		$this->_aChild = array();

		$this->_vParseText($oHtmlStr);

		while (!$oHtmlStr->bEnd())
		{
			$tag = $oHtmlStr->sGetTagStr();
			if ('/' === $tag[0])
			{
				$tag = substr($tag, 1);
				$oHtmlStr->vFind('>');
				$oHtmlStr->vNext();
				$this->_vCloseTag($tag);
			}
			else
			{
				$child = new Ko_Html_Node($this, $tag);
				if (!$child->_bParseAllAttr($oHtmlStr) || '!' === $tag[0] || in_array($tag, self::$s_aSingleTag, true))
				{
					$child->_bClosed = true;
				}
				else
				{
					$child->_vParseChild($oHtmlStr);
				}
				$this->_aChild[] = $child;
			}

			if ($this->_bClosed)
			{
				break;
			}

			$this->_vParseText($oHtmlStr);
		}
		return true;
	}

	public function sHtml()
	{
		return $this->sHtmlEx();
	}

	/**
	 * 释放对 parent 的引用，防止内存无法释放
	 */
	public function vFreeParent()
	{
		if (!is_null($this->_oStyles))
		{
			$this->_oStyles->vFreeParent();
		}
		foreach ($this->_aChild as $child)
		{
			if ($child instanceof Ko_Html_Node)
			{
				$child->vFreeParent();
			}
		}
		$this->_oParent = null;
	}

	/**
	 * 添加过滤器
	 */
	public function vAddFilter($oFilter)
	{
		assert($oFilter instanceof IKo_Html_Filter);

		$this->_aFilter[] = $oFilter;
	}

	/**
	 * 限制返回最大长度
	 */
	public function sHtmlEx($iMaxLength = 0, $sCharset = '')
	{
		$iLen = 0;
		return $this->_sHtmlEx($iLen, $iMaxLength, $sCharset);
	}

	public function sGetTag()
	{
		return $this->_sTag;
	}

	public function vSetTag($sTag)
	{
		$this->_sTag = $sTag;
	}

	private function _sHtmlEx(&$iLen, $iMaxLength, $sCharset)
	{
		$sRet = '';
		if (!$this->_bFilterTag())
		{
			$sBeginTag = $this->_sGetBeginTag();
			$iLen += strlen($sBeginTag) + (empty($this->_aChild) ? 0 : (strlen($this->_sTag) + 3));
			if ($iMaxLength && $iLen > $iMaxLength)
			{
				return $sRet;
			}
			$sRet .= $sBeginTag;
		}
		foreach ($this->_aChild as $child)
		{
			if ($this->_bFilterChild($child))
			{
				continue;
			}
			$sRet .= $this->_sChildHtml($child, $iLen, $iMaxLength, $sCharset);
			if ($iMaxLength && $iLen > $iMaxLength)
			{
				break;
			}
		}
		if (!$this->_bFilterTag() && !empty($this->_aChild))
		{
			$sRet .= '</'.$this->_sTag.'>';
		}
		if ('' === $this->_sTag)
		{
			$sRet = $this->_sFilterHtml($sRet);
		}
		return $sRet;
	}

	private function _sChildHtml($oChild, &$iLen, $iMaxLength, $sCharset)
	{
		if ($oChild instanceof Ko_Html_Node)
		{
			return $oChild->_sHtmlEx($iLen, $iMaxLength, $sCharset);
		}
		$sChildHtml = $oChild->sHtml();
		$iChildLen = strlen($sChildHtml);
		$iLen += $iChildLen;
		if (!($iMaxLength && $iLen > $iMaxLength))
		{
			return $sChildHtml;
		}
		if ($oChild instanceof Ko_Html_Text)
		{
			return $this->_sGetAbstractHtml($sChildHtml, $iMaxLength - $iLen + $iChildLen, $sCharset);
		}
		return '';
	}

	private function _sGetAbstractHtml($sHtml, $iMaxLength, $sCharset)
	{
		if ($iMaxLength <= 0)
		{
			return '';
		}
		$sExt = '...';
		$iExtLen = strlen($sExt);
		if ($iExtLen >= $iMaxLength)
		{
			return substr($sExt, 0, $iMaxLength);
		}
		$sRet = Ko_Tool_Str::SSubStr($sHtml, $iMaxLength, $sExt, $sCharset);
		$sConvert = htmlspecialchars(htmlspecialchars_decode($sRet));
		if ($sRet === $sConvert)
		{
			return $sRet;
		}
		return $this->_sGetAbstractHtml($sHtml, $iMaxLength - 1, $sCharset);
	}

	private function _vParseChild($oHtmlStr)
	{
		assert($oHtmlStr instanceof Ko_Html_Str);
		if ('script' === $this->_sTag)
		{
			$script = $oHtmlStr->sGetScriptStr();
			$text = new Ko_Html_Text;
			$text->vSetText($script);
			$this->_aChild[] = $text;
		}
		else
		{
			$this->bParse($oHtmlStr);
		}
	}

	private function _oGetRootNode()
	{
		$oNode = $this;
		while (!is_null($oNode->_oParent))
		{
			$oNode = $oNode->_oParent;
		}
		return $oNode;
	}

	private function _bFilterTag()
	{
		foreach ($this->_oGetRootNode()->_aFilter as $oFilter)
		{
			if ($oFilter->bFilterTag($this))
			{
				return true;
			}
		}
		return false;
	}

	private function _bFilterAttr($oAttr)
	{
		foreach ($this->_oGetRootNode()->_aFilter as $oFilter)
		{
			if ($oFilter->bFilterAttr($this, $oAttr))
			{
				return true;
			}
		}
		return false;
	}

	private function _bFilterChild($oChild)
	{
		foreach ($this->_oGetRootNode()->_aFilter as $oFilter)
		{
			if ($oFilter->bFilterChild($this, $oChild))
			{
				return true;
			}
		}
		return false;
	}

	private function _sGetExtraAttr()
	{
		$sAttr = '';
		foreach ($this->_oGetRootNode()->_aFilter as $oFilter)
		{
			$sAttr .= $oFilter->sGetExtraAttr($this);
		}
		return $sAttr;
	}

	private function _sFilterHtml($sHtml)
	{
		foreach ($this->_oGetRootNode()->_aFilter as $oFilter)
		{
			$sHtml = $oFilter->sFilterHtml($sHtml);
		}
		return $sHtml;
	}

	private function _vParseText($oHtmlStr)
	{
		assert($oHtmlStr instanceof Ko_Html_Str);

		$text = new Ko_Html_Text;
		if ($text->bParse($oHtmlStr))
		{
			$this->_aChild[] = $text;
		}
		if ($oHtmlStr->bIsCommentStart())
		{
			$comment = new Ko_Html_Comment;
			if ($comment->bParse($oHtmlStr))
			{
				$this->_aChild[] = $comment;
			}
			$this->_vParseText($oHtmlStr);
		}
	}

	private function _bParseAttr($oHtmlStr)
	{
		assert($oHtmlStr instanceof Ko_Html_Str);

		$attr = new Ko_Html_Attr($this);
		if ($attr->bParse($oHtmlStr))
		{
			if ('style' === $attr->sGetName())
			{
				if (is_null($this->_oStyles))
				{
					$this->_oStyles = new Ko_Html_Styles($this);
				}
				$this->_oStyles->bParse($attr->sGetValue());
			}
			else
			{
				$this->_aAttr[] = $attr;
			}
			return true;
		}
		return false;
	}

	private function _bParseAllAttr($oHtmlStr)
	{
		assert($oHtmlStr instanceof Ko_Html_Str);

		while (!$oHtmlStr->bEnd() && !$oHtmlStr->bIsEndTag())
		{
			$this->_bParseAttr($oHtmlStr);
		}
		if ('>' === $oHtmlStr->sChar())
		{
			$oHtmlStr->vNext();
			return true;
		}
		$oHtmlStr->vNext(2);
		return false;
	}

	private function _vCloseTag($sTag)
	{
		if ('' !== $sTag)
		{
			$oNode = $this;
			while (!is_null($oNode))
			{
				$oNode->_bClosed = true;
				if ($oNode->_sTag === $sTag)
				{
					break;
				}
				$oNode = $oNode->_oParent;
			}
		}
	}

	private function _sGetBeginTag()
	{
		$sRet = '<'.$this->_sTag;
		foreach ($this->_aAttr as $attr)
		{
			if ($this->_bFilterAttr($attr))
			{
				continue;
			}
			$sRet .= ' '.$attr->sHtml();
		}
		if (!is_null($this->_oStyles))
		{
			$sRet .= ' style="'.$this->_oStyles->sHtml($this->_oGetRootNode()->_aFilter).'"';
		}
		$sRet .= $this->_sGetExtraAttr();
		if (empty($this->_aChild))
		{
			$sRet .= ' />';
		}
		else
		{
			$sRet .= '>';
		}
		return $sRet;
	}
}

/*

if ($argc != 2)
{
	echo "Usage: ".$argv[0]." <filename>\n";
	exit;
}
$filename = $argv[1];

$content = file_get_contents($filename);

$str = new Ko_Html_Str($content);

$node = new Ko_Html_Node;
$ret = $node->bParse($str);

$ret = $node->sHtml();
echo $ret;

*/
?>