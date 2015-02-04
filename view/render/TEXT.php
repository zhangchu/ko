<?php
/**
 * TEXT
 *
 * @package ko\view\render
 * @author zhangchu
 */

class Ko_View_Render_TEXT extends Ko_View_Render_Base
{
	public function sRender()
	{
		return implode('', $this->_aData);
	}
}
