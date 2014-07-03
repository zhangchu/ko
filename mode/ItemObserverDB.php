<?php
/**
 * ItemObserverDB
 *
 * @package ko\mode
 * @author zhangchu
 */

/**
 * 数据库观察者实现
 */
class Ko_Mode_ItemObserverDB implements IKo_Mode_ItemObserver
{
	const ACT_DELETE = -1;
	const ACT_UPDATE = 0;
	const ACT_INSERT = 1;

	private $_oObsDao;
	private $_sKindField;
	private $_sIdField;
	private $_sActionField;
	private $_sContentField;
	private $_sAdminField;
	private $_sIpField;

	public function __construct($oObsDao, $sKindField, $sIdField, $sActionField, $sContentField, $sAdminField, $sIpField)
	{
		$this->_oObsDao = $oObsDao;
		$this->_sIdField = $sIdField;
		$this->_sKindField = $sKindField;
		$this->_sActionField = $sActionField;
		$this->_sContentField = $sContentField;
		$this->_sAdminField = $sAdminField;
		$this->_sIpField = $sIpField;
	}

	public function vOnInsert($oDao, $sHintId, $aData, $vAdmin)
	{
		$arr = array();
		$arr[$this->_sActionField] = self::ACT_INSERT;
		$arr[$this->_sContentField] = Ko_Tool_Enc::SEncode($aData);
		$this->_vInsertInfo($arr, $oDao, $sHintId, $vAdmin);
	}

	public function vOnUpdate($oDao, $sHintId, $aUpdate, $aChange, $vAdmin)
	{
		$arr = array();
		$arr[$this->_sActionField] = self::ACT_UPDATE;
		$content = array();
		if (!empty($aUpdate))
		{
			$content['update'] = $aUpdate;
		}
		if (!empty($aChange))
		{
			$content['change'] = $aChange;
		}
		$arr[$this->_sContentField] = Ko_Tool_Enc::SEncode($content);
		$this->_vInsertInfo($arr, $oDao, $sHintId, $vAdmin);
	}

	public function vOnDelete($oDao, $sHintId, $vAdmin)
	{
		$arr = array();
		$arr[$this->_sActionField] = self::ACT_DELETE;
		$this->_vInsertInfo($arr, $oDao, $sHintId, $vAdmin);
	}

	private function _vInsertInfo(&$arr, $oDao, $sHintId, $vAdmin)
	{
		$arr[$this->_sIdField] = $sHintId;
		if (strlen($this->_sKindField))
		{
			$arr[$this->_sKindField] = $oDao->sGetTableName();
		}
		if (strlen($this->_sAdminField))
		{
			$arr[$this->_sAdminField] = is_array($vAdmin) ? Ko_Tool_Enc::SEncode($vAdmin) : $vAdmin;
		}
		if (strlen($this->_sIpField))
		{
			$arr[$this->_sIpField] = Ko_Tool_Ip::SGetClientIP();
		}
		$this->_oObsDao->aInsert($arr);
	}
}

?>