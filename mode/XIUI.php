<?php
/**
 * XIUI
 *
 * @package ko\mode
 * @author zhangchu
 */

/**
 * 自定义界面的缺省实现
 */
class Ko_Mode_XIUI extends Ko_Busi_Api
{
	protected $_oStorage;
	protected $_aTypeinfo = array();
	
	public function vAttachStorage($oStorage)
	{
		$this->_oStorage = $oStorage;
	}
	
	public function vSetItemTypeinfo($aTypeinfo)
	{
		$this->_aTypeinfo = $aTypeinfo;
	}
	
	/**
	 * @return string
	 */
	public function sList_CellHtml($sField, $aValue)
	{
		$html = '&nbsp;';
		if (strlen($aValue[$sField]))
		{
			switch ($this->_aTypeinfo['editinfo']['type'])
			{
			case 'file':
				list($sDomain, $sDest, $iSize, $sMimetype, $sFilename) = $this->_oStorage->aParseUniqStr($aValue[$sField]);
				$html = htmlspecialchars($sFilename.'('.$iSize.')');
				break;
			case 'image':
				list($sDomain, $sDest, $iSize, $sMimetype, $sFilename) = $this->_oStorage->aParseUniqStr($aValue[$sField]);
				$big = $this->_oStorage->sGetUrl($sDomain, $sDest, '');
				$image = $this->_oStorage->sGetUrl($sDomain, $sDest, $this->_aTypeinfo['cellinfo']['brief']);
				$html = '<a href="'.htmlspecialchars($big).'" target="_blank"><img src="'.htmlspecialchars($image).'"></a>';
				break;
			default:
				$html = htmlspecialchars($aValue[$sField]);
				break;
			}
		}
		return $html;
	}

	/**
	 * @return string
	 */
	public function sDetail_LineHtml($sField, $aValue)
	{
		$html = '&nbsp;';
		if (strlen($aValue[$sField]))
		{
			switch ($this->_aTypeinfo['editinfo']['type'])
			{
			case 'file':
				list($sDomain, $sDest, $iSize, $sMimetype, $sFilename) = $this->_oStorage->aParseUniqStr($aValue[$sField]);
				$html = htmlspecialchars($sFilename.'('.$iSize.')').'<br>'.htmlspecialchars($aValue[$sField]);
				break;
			case 'image':
				list($sDomain, $sDest, $iSize, $sMimetype, $sFilename) = $this->_oStorage->aParseUniqStr($aValue[$sField]);
				$big = $this->_oStorage->sGetUrl($sDomain, $sDest, '');
				$image = $this->_oStorage->sGetUrl($sDomain, $sDest, $this->_aTypeinfo['editinfo']['brief']);
				$html = '<a href="'.htmlspecialchars($big).'" target="_blank"><img src="'.htmlspecialchars($image).'"></a><br>'.htmlspecialchars($aValue[$sField]);
				break;
			case 'textarea':
				$html = nl2br(htmlspecialchars($aValue[$sField]));
				break;
			default:
				$html = htmlspecialchars($aValue[$sField]);
				break;
			}
		}
		return $html;
	}
}

?>