<?php
/**
 * Smarty
 *
 * @package ko\view\render
 * @author jiangjw & zhangchu
 */

class Ko_View_Render_Smarty extends Ko_View_Render_List
{
	private $_oSmarty;

	private $_sTemplate;

	/**
	 * @param Smarty|Ko_View_Smarty $oSmarty 
	 */
	public function __construct($oSmarty = null)
	{
		if (null === $oSmarty)
		{
			$this->_oSmarty = Ko_Tool_Singleton::OInstance('Ko_View_Smarty');
		}
		else
		{
			$this->_oSmarty = $oSmarty;
		}
	}

	public function oSetTemplate($sTemplate)
	{
		$this->_sTemplate = $sTemplate;
		return $this;
	}

	public function oSend()
	{
		Ko_Web_Response::VSend($this);
		return $this;
	}

	protected function _sRender()
	{
		$this->_vEscapeData($this->_aData);
		if ($this->_oSmarty instanceof Ko_View_Smarty)
		{
			$this->_oSmarty->vAssignRaw($this->_aData);
			return $this->_oSmarty->sFetch($this->_sTemplate);
		}
		$this->_oSmarty->assgin($this->_aData);
		return $this->_oSmarty->fetch($this->_sTemplate);
	}

	private function _vEscapeData(array &$data)
	{
		foreach ($data as $k => &$v)
		{
			if ($v instanceof Ko_View_Render_Base)
			{
				$v = $v->sRender();
			}
			else if (is_array($v))
			{
				$this->_vEscapeData($v);
			}
			else
			{
				$v = Ko_View_Escape::VEscapeHtml($v);
			}
		}
		unset($v);
	}
}
