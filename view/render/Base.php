<?php
/**
 * Base
 *
 * @package ko\view\render
 * @author jiangjw & zhangchu
 */

class Ko_View_Render_Base
{
	protected $_aData = array();

	public function oSetData($vName, $vValue = null)
	{
		if (!is_array($vName))
		{
			$vName = array($vName => $vValue);
		}
		foreach ($vName as $k => $v)
		{
			$this->_aData[$k] = $v;
		}
		return $this;
	}

	public function sRender()
	{
		return '';
	}
}
