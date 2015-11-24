<?php
/**
 * HTML
 *
 * @package ko\view\render
 * @author zhangchu
 */

class Ko_View_Render_HTML extends Ko_View_Render_Base
{
	private $_oContentApi;

	/**
	 * oSetData($aid, $id);
	 * oSetData($aid, $ids);
	 * oSetData($aid, array('ids' => [, 'maxlength' => ]);
	 */
	public function __construct($api)
	{
		$this->_oContentApi = $api;
	}

	public function sRender()
	{
		$singleAid = (1 === count($this->_aData));
		$singleId = false;
		foreach ($this->_aData as $aid => &$data)
		{
			if (!is_array($data))
			{
				$data = array($data);
				if ($singleAid)
				{
					$singleId = true;
				}
			}
		}
		unset($data);
		
		$htmllist = $this->_oContentApi->aGetHtmlEx($this->_aData);
		
		if ($singleAid)
		{
			foreach ($htmllist as $aid => $htmls)
			{
				if ($singleId)
				{
					foreach ($htmls as $id => $html)
					{
						return $html;
					}
				}
				return $htmls;
			}
		}
		return $htmllist;
	}
}
