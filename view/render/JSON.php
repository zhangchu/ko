<?php
/**
 * JSON
 *
 * @package ko\view\render
 * @author jiangjw & zhangchu
 */

class Ko_View_Render_JSON extends Ko_View_Render_Base
{
	public function sRender()
	{
		return json_encode($this->_aData);
	}

	public function oSend()
	{
		Ko_Web_Response::VSend($this);
		return $this;
	}
}
