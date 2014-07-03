<?php
/**
 * Ajax
 *
 * @package ko\app
 * @author zhangchu
 */

class Ko_App_Ajax extends Ko_App_Safe
{
	protected $_iJsonErrno = 0;			//负数为系统定义错误，正数为应用自定义错误
	protected $_sJsonError = '';
	protected $_aJsonData = array();
	protected $_bNoData = false;
	protected $_bNoHtml = false;
	
	protected function vMain()
	{
		$methodname = 'vAction_'.$this->_aReq['sAction'];
		if (method_exists($this, $methodname))
		{
			try
			{
				$this->$methodname();
			}
			catch (Exception $e)
			{
				$this->vReportError($e->getCode() ? $e->getCode() : -2, $e->getMessage());
			}
		}
		else
		{
			$this->vReportError(-1, $this->_aReq['sAction'].' 方法未定义');
		}
	}
	
	protected function vOutputHttp()
	{
		header('Content-Type: application/json; charset=UTF-8');
	}
	
	protected function vOutputPage()
	{
		$data = array(
			'errno' => intval($this->_iJsonErrno),
			'error' => Ko_View_Escape::VEscapeHtml($this->_sJsonError),
		);
		if (!$this->_bNoHtml)
		{
			$data['html'] = Ko_View_Escape::VEscapeHtml($this->_aJsonData);
		}
		if (!$this->_bNoData)
		{
			$data['data'] = $this->_aJsonData;
		}
		echo json_encode($data);
	}
	
	protected function vReportError($errno, $error)
	{
		$this->_iJsonErrno = $errno;
		$this->_sJsonError = $error;
	}
}
