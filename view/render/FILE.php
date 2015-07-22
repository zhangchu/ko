<?php
/**
 * FILE
 *
 * @package ko\view\render
 * @author jiangjw & zhangchu
 */

class Ko_View_Render_FILE extends Ko_View_Render_Base
{
	public function sRender()
	{
		$filename = $this->_aData['filename'];
		if (is_file($filename))
		{
			return file_get_contents($filename);
		}
		return '';
	}

	public function sFilename()
	{
		return $this->_aData['filename'];
	}
}
