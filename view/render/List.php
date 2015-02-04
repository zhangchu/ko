<?php
/**
 * List
 *
 * @package ko\view\render
 * @author jiangjw & zhangchu
 */

class Ko_View_Render_List extends Ko_View_Render_Base
{
	private $_oNext = null;

	public function oAppend(Ko_View_Render_List $oNext)
	{
		if (null === $this->_oNext)
		{
			$this->_oNext = $oNext;
		}
		else
		{
			$this->_oNext->oAppend($oNext);
		}
	}

	public function sRender()
	{
		if (null !== $this->_oNext)
		{
			return $this->_sRender().$this->_oNext->sRender();
		}
		return $this->_sRender();
	}

	protected function _sRender()
	{
		return parent::sRender();
	}
}
