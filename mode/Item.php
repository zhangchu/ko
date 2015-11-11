<?php
/**
 * 使用说明
 *
 * <b>简介</b>
 * <pre>
 *   用一个数据库来对另一个数据库(db_single/db_one/db_split类型)进行观察，并记录修改行为
 *   实现为一个表(db_single/db_one/db_split)与一些相关索引表的同步操作
 * </pre>
 *
 * <b>数据库例表</b>
 * <pre>
 *   观察目标
 *   CREATE TABLE s_zhangchu_test (
 *     provid int not null auto_increment,
 *     name varchar(4) not null default '',
 *     PRIMARY KEY(provid)
 *   ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 *   or
 *   CREATE TABLE s_zhangchu_testex_0 (
 *     id int not null default 0,
 *     name varchar(100) not null default '',
 *     content blob not null default '',
 *     unique KEY(id, name)
 *   ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 *
 *   观察者
 *   CREATE TABLE s_zhangchu_test_log (
 *     id int not null auto_increment,
 *     provid int not null default 0,
 *     action tinyint not null default 0,
 *     content blob not null default '',
 *     admin blob not null default '',
 *     ip varchar(100) not null default '',
 *     ctime timestamp not null default CURRENT_TIMESTAMP,
 *     PRIMARY KEY(id),
 *     KEY(provid, id)
 *   ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 * </pre>
 *
 * <b>_aConf 配置</b>
 * @see Ko_Mode_Item::$_aConf
 *
 * @package ko\mode
 * @author zhangchu
 */

/**
 * 观察者模式
 *
 * @method int   iInsert($aData, $aUpdate = array(), $aChange = array(), $oOption=null)
 * @method array aInsert($aData, $aUpdate = array(), $aChange = array(), $oOption=null)
 * @method int   iUpdate($vKey, $aUpdate, $aChange=array(), $oOption=null)
 * @method int   iUpdateByCond($oOption, $aUpdate, $aChange=array())  db_single
 * @method int   iUpdateByCond($vHintId, $oOption, $aUpdate, $aChange=array())  db_split
 * @method int   iDelete($vKey, $oOption=null)
 * @method int   iDeleteByCond($oOption)  db_single
 * @method int   iDeleteByCond($vHintId, $oOption)  db_split
 * @method array aGet($vKey)
 * @method array aGetListByKeys($aKey, $sKeyField = '')  db_single/db_one
 * @method array aGetListByKeys($vHintId, $aKey, $sKeyField = '')  db_split
 * @method array aGetList($oOption, $iCacheTime=0)  db_single
 * @method array aGetList($vHintId, $oOption, $iCacheTime=0)  db_split
 * @method array aGetDetails($oObjs, $sSplitField = '', $bRetmap = true)  db_one
 * @method array aGetDetails($oObjs, $sSplitField = '', $sKeyField = '', $bRetmap = true)  db_split
 */
class Ko_Mode_Item extends Ko_Busi_Api
{
	/**
	 * 配置数组
	 *
	 * <pre>
	 * array(
	 *   'item' => 目标数据库 Dao 名称
	 *   'itemlog' => 观察者数据库 Dao 名称
	 *   'itemlog_kindfield' => 观察者数据库用于标识观察对象类型的字段名称，如果该观察者只观察一种类型目标，可以为空，否则不能为空
	 *   'itemlog_idfield' => 观察者数据库用于标识观察对象的字段名称，缺省为观察目标的唯一键名称
	 *   'itemlog_actionfield' => 观察者数据库用于记录行为的字段名称，缺省为 action
	 *   'itemlog_contentfield' => 观察者数据库用于记录行为细节的字段名称，缺省为 content
	 *   'itemlog_adminfield' => 观察者数据库用于记录操作人员的字段名称，缺省为 admin
	 *   'itemlog_ipfield' => 观察者数据库用于记录操作IP的字段名称，缺省为 ip
	 *   'whitefield' => 观察目标被观察的字段，为空表示全部观察
	 *   'blackfield' => 观察目标不被观察的字段，优先于 whitefield
	 *   'index' => array(
	 *     array(
	 *       'dao' => 索引数据表 Dao 名称
	 *       'datafields' => 索引表保存的数据字段
	 *     ),
	 *     ...
	 *   ),
	 * )
	 * </pre>
	 *
	 * @var array
	 */
	protected $_aConf = array();

	private $_aObserver = array();
	private $_aBefore = array();
	private $_allowMethods = array(
		'aInsertMulti',
		'aGet',
		'aGetListByKeys',
		'aGetDetails',
		'aGetList',
		'vDeleteCache',

		'sGetTableName',
		'sGetSplitField',
		'aGetKeyField',
		'aGetIndexField',
		'aGetIndexValue',
		'sGetIdKey',
		'vGetAttribute',
		'vSetAttribute',

		'iTableCount',
		'oConnectDB',
		'sGetRealTableName',
		'vDoFetchSelect',
		);

	public function __construct()
	{
		assert(strlen($this->_aConf['item']));
		if (isset($this->_aConf['itemlog']) && strlen($this->_aConf['itemlog']))
		{
			$kindField = $this->_aConf['itemlog_kindfield'];
			$idField = $this->_sGetIdField();
			$actionField = strlen($this->_aConf['itemlog_actionfield']) ? $this->_aConf['itemlog_actionfield'] : 'action';
			$contentField = strlen($this->_aConf['itemlog_contentfield']) ? $this->_aConf['itemlog_contentfield'] : 'content';
			$adminField = strlen($this->_aConf['itemlog_adminfield']) ? $this->_aConf['itemlog_adminfield'] : 'admin';
			$ipField = strlen($this->_aConf['itemlog_ipfield']) ? $this->_aConf['itemlog_ipfield'] : 'ip';

			$logDao = $this->_aConf['itemlog'].'Dao';
			$oObs = new Ko_Mode_ItemObserverDB($this->$logDao, $kindField, $idField, $actionField, $contentField, $adminField, $ipField);
			$this->vAttach($oObs);
		}
	}

	/**
	 * @return mixed
	 */
	public function __call($sName, $aArgs)
	{
		if (in_array($sName, $this->_allowMethods, true))
		{
			$itemDao = $this->_aConf['item'].'Dao';
			return call_user_func_array(array($this->$itemDao, $sName), $aArgs);
		}
		assert(0);
	}

	/**
	 * @return int
	 */
	public function iInsert($aData, $aUpdate = array(), $aChange = array(), $oOption = null, $vAdmin='')
	{
		$info = $this->aInsert($aData, $aUpdate, $aChange, $oOption, $vAdmin);
		return $info['insertid'];
	}

	/**
	 * @return array
	 */
	public function aInsert($aData, $aUpdate = array(), $aChange = array(), $oOption = null, $vAdmin='')
	{
		if (!$this->_bIsFullItemIndex($aData))
		{
			assert(empty($aUpdate) && empty($aChange));
		}
		else if ($bUpdateIndex = $this->_bIsFieldIndexData($aUpdate, $aChange))
		{
			$aOldInfo = $this->aGet($aData);
		}
		
		$itemDao = $this->_aConf['item'].'Dao';
		$this->_vFireBeforeInsert($this->$itemDao, $aData, $aUpdate, $aChange, $vAdmin);
		$aInfo = $this->$itemDao->aInsert($aData, $aUpdate, $aChange, $oOption);
		if (1 == $aInfo['affectedrows'])
		{
			$this->_vInsert_Index($aInfo['data']);
			$this->_vFireInsert($this->$itemDao, $aInfo['data'], $vAdmin);
		}
		else if (2 == $aInfo['affectedrows'])
		{
			if ($bUpdateIndex && !empty($aOldInfo))
			{
				$aNewInfo = array_merge($aOldInfo, $aUpdate);
				$this->_vUpdate_Index($aOldInfo, $aNewInfo);
			}
			$this->_vFireUpdate($this->$itemDao, $aData, $aUpdate, $aChange, $vAdmin);
		}
		return $aInfo;
	}

	/**
	 * @return int
	 */
	public function iUpdate($vKey, $aUpdate, $aChange=array(), $oOption=null, $vAdmin='')
	{
		if ($bUpdateIndex = $this->_bIsFieldIndexData($aUpdate, $aChange))
		{
			$aOldInfo = $this->aGet($vKey);
		}
		$itemDao = $this->_aConf['item'].'Dao';
		$this->_vFireBeforeUpdate($this->$itemDao, $vKey, $aUpdate, $aChange, $oOption, $vAdmin);
		$iInfo = $this->$itemDao->iUpdate($vKey, $aUpdate, $aChange, $oOption);
		if ($iInfo)
		{
			if ($bUpdateIndex && !empty($aOldInfo))
			{
				$aNewInfo = array_merge($aOldInfo, $aUpdate);
				$this->_vUpdate_Index($aOldInfo, $aNewInfo);
			}
			$this->_vFireUpdate($this->$itemDao, $vKey, $aUpdate, $aChange, $vAdmin);
		}
		return $iInfo;
	}

	/**
	 * 对于 db_single 或 db_split 类型，分别使用下面两种方式调用
	 * public function iUpdateByCond($oOption, $aUpdate, $aChange=array(), $vAdmin='')
	 * public function iUpdateByCond($vHintId, $oOption, $aUpdate, $aChange=array(), $vAdmin='')
	 *
	 * @return int
	 */
	public function iUpdateByCond()
	{
		$aArgv = func_get_args();
		$splitField = $this->sGetSplitField();
		$isSplit = strlen($splitField);
		if ($isSplit)
		{
			assert(count($aArgv) >= 3);
			$vHintId = $aArgv[0];
			$oOption = $aArgv[1];
			$aUpdate = $aArgv[2];
			$aChange = isset($aArgv[3]) ? $aArgv[3] : array();
			$vAdmin = isset($aArgv[4]) ? $aArgv[4] : '';
		}
		else
		{
			assert(count($aArgv) >= 2);
			$oOption = $aArgv[0];
			$aUpdate = $aArgv[1];
			$aChange = isset($aArgv[2]) ? $aArgv[2] : array();
			$vAdmin = isset($aArgv[3]) ? $aArgv[3] : '';
		}
		assert(!Ko_Tool_Option::BIsWhereEmpty($oOption, $this->vGetAttribute('ismongodb')));
		if ($isSplit)
		{
			$list = $this->aGetList($vHintId, $oOption);
		}
		else
		{
			$list = $this->aGetList($oOption);
		}
		$iRet = 0;
		foreach ($list as $info)
		{
			$oOptionNew = $oOption->oClone();
			$iRet += $this->iUpdate($info, $aUpdate, $aChange, $oOptionNew, $vAdmin);
		}
		return $iRet;
	}
	
	/**
	 * @return int
	 */
	public function iDelete($vKey, $oOption=null, $vAdmin='')
	{
		$aInfo = $this->_aGetDeleteIndexInfo($vKey);
		$itemDao = $this->_aConf['item'].'Dao';
		$this->_vFireBeforeDelete($this->$itemDao, $vKey, $oOption, $vAdmin);
		$iInfo = $this->$itemDao->iDelete($vKey, $oOption);
		if ($iInfo)
		{
			$this->_vDelete_Index($aInfo);
			$this->_vFireDelete($this->$itemDao, $vKey, $vAdmin);
		}
		return $iInfo;
	}

	/**
	 * 对于 db_single 或 db_split 类型，分别使用下面两种方式调用
	 * public function iDeleteByCond($oOption, $vAdmin='')
	 * public function iDeleteByCond($vHintId, $oOption, $vAdmin='')
	 *
	 * @return int
	 */
	public function iDeleteByCond()
	{
		$aArgv = func_get_args();
		$splitField = $this->sGetSplitField();
		$isSplit = strlen($splitField);
		if ($isSplit)
		{
			assert(count($aArgv) >= 2);
			$vHintId = $aArgv[0];
			$oOption = $aArgv[1];
			$vAdmin = isset($aArgv[2]) ? $aArgv[2] : '';
		}
		else
		{
			assert(count($aArgv) >= 1);
			$oOption = $aArgv[0];
			$vAdmin = isset($aArgv[1]) ? $aArgv[1] : '';
		}
		assert(!Ko_Tool_Option::BIsWhereEmpty($oOption, $this->vGetAttribute('ismongodb')));
		if ($isSplit)
		{
			$list = $this->aGetList($vHintId, $oOption);
		}
		else
		{
			$list = $this->aGetList($oOption);
		}
		$iRet = 0;
		foreach ($list as $info)
		{
			$oOptionNew = $oOption->oClone();
			$iRet += $this->iDelete($info, $oOptionNew, $vAdmin);
		}
		return $iRet;
	}
	
	public function vAttach($oObserver)
	{
		assert($oObserver instanceof IKo_Mode_ItemObserver);
		$this->_aObserver[] = $oObserver;
	}

	public function vAttachBefore($oBefore)
	{
		assert($oBefore instanceof IKo_Mode_ItemBefore);
		$this->_aBefore[] = $oBefore;
	}

	/**
	 * @return string
	 */
	public function sGetHintId($vKey)
	{
		$data = $this->aGetIndexValue($vKey);
		return implode(':', array_map('urlencode', $data));
	}

	private function _vFireBeforeInsert($oDao, $aData, $aUpdate, $aChange, $vAdmin)
	{
		foreach ($this->_aBefore as $oBefore)
		{
			$oBefore->vBeforeInsert($oDao, $aData, $aUpdate, $aChange, $vAdmin);
		}
	}

	private function _vFireBeforeUpdate($oDao, $vKey, $aUpdate, $aChange, $oOption, $vAdmin)
	{
		foreach ($this->_aBefore as $oBefore)
		{
			$oBefore->vBeforeUpdate($oDao, $vKey, $aUpdate, $aChange, $oOption, $vAdmin);
		}
	}

	private function _vFireBeforeDelete($oDao, $vKey, $oOption, $vAdmin)
	{
		foreach ($this->_aBefore as $oBefore)
		{
			$oBefore->vBeforeDelete($oDao, $vKey, $oOption, $vAdmin);
		}
	}

	private function _vFireInsert($oDao, $aData, $vAdmin)
	{
		$sHintId = $this->sGetHintId($aData);
		foreach ($this->_aObserver as $observer)
		{
			$observer->vOnInsert($oDao, $sHintId, $aData, $vAdmin);
		}
	}

	private function _vFireUpdate($oDao, $vKey, $aUpdate, $aChange, $vAdmin)
	{
		if (!$this->_bFieldFilter($aUpdate) && !$this->_bFieldFilter($aChange))
		{
			return;
		}

		$sHintId = $this->sGetHintId($vKey);
		foreach ($this->_aObserver as $observer)
		{
			$observer->vOnUpdate($oDao, $sHintId, $aUpdate, $aChange, $vAdmin);
		}
	}

	private function _vFireDelete($oDao, $vKey, $vAdmin)
	{
		$sHintId = $this->sGetHintId($vKey);
		foreach ($this->_aObserver as $observer)
		{
			$observer->vOnDelete($oDao, $sHintId, $vAdmin);
		}
	}

	private function _bFieldFilter($aData)
	{
		if (!empty($this->_aConf['whitefield']))
		{
			$aRet = array();
			foreach ($this->_aConf['whitefield'] as $field)
			{
				if (array_key_exists($field, $aData))
				{
					$aRet[$field] = $aData[$field];
				}
			}
		}
		else
		{
			$aRet = $aData;
		}
		if (!empty($this->_aConf['blackfield']))
		{
			foreach ($this->_aConf['blackfield'] as $field)
			{
				unset($aRet[$field]);
			}
		}
		return !empty($aRet);
	}

	private function _sGetIdField()
	{
		if (strlen($this->_aConf['itemlog_idfield']))
		{
			$idField = $this->_aConf['itemlog_idfield'];
		}
		else
		{
			$indexField = $this->aGetIndexField();
			assert(count($indexField));
			$idField = $indexField[0];
		}
		return $idField;
	}
	
	private function _bIsFullItemIndex($aData)
	{
		$indexField = $this->aGetIndexField();
		foreach ($indexField as $field)
		{
			if (!isset($aData[$field]))
			{
				return false;
			}
		}
		return true;
	}
	
	private function _bIsFieldIndexData($aUpdate, $aChange)
	{
		if (isset($this->_aConf['index']) && (!empty($aUpdate) || !empty($aChange)))
		{
			foreach ($this->_aConf['index'] as $v)
			{
				$indexDao = $v['dao'].'Dao';
				$indexField = $this->$indexDao->aGetIndexField();
				foreach ($indexField as $field)
				{
					if (array_key_exists($field, $aUpdate))
					{
						return true;
					}
					assert(!array_key_exists($field, $aChange));
				}
				if (isset($v['datafields']))
				{
					foreach ($v['datafields'] as $field)
					{
						if (array_key_exists($field, $aUpdate))
						{
							return true;
						}
						assert(!array_key_exists($field, $aChange));
					}
				}
			}
		}
		return false;
	}

	private function _aGetDeleteIndexInfo($vKey)
	{
		$fields = array();
		if (isset($this->_aConf['index']))
		{
			foreach ($this->_aConf['index'] as $v)
			{
				$indexDao = $v['dao'].'Dao';
				$fields = array_merge($fields, $this->$indexDao->aGetIndexField());
			}
		}
		$fields = array_diff($fields, $this->aGetIndexField());
		if (empty($fields))
		{
			return $vKey;
		}
		return $this->aGet($vKey);
	}

	private function _vInsert_Index($aData)
	{
		if (isset($this->_aConf['index']))
		{
			foreach ($this->_aConf['index'] as $v)
			{
				$this->_vInsert_Index_One($aData, $v);
			}
		}
	}

	private function _vInsert_Index_One($aData, $aIndexConf)
	{
		$indexDao = $aIndexConf['dao'].'Dao';
		$aIndexData = $this->$indexDao->aGetIndexValue($aData);
		if (isset($aIndexConf['datafields']))
		{
			foreach ($aIndexConf['datafields'] as $field)
			{
				if (array_key_exists($field, $aData))
				{
					$aIndexData[$field] = $aData[$field];
				}
			}
		}
		$this->$indexDao->aInsert($aIndexData);
	}
	
	private function _vUpdate_Index($aOldInfo, $aNewInfo)
	{
		if (isset($this->_aConf['index']))
		{
			foreach ($this->_aConf['index'] as $v)
			{
				$this->_vUpdate_Index_One($aOldInfo, $aNewInfo, $v);
			}
		}
	}
	
	private function _vUpdate_Index_One($aOldInfo, $aNewInfo, $aIndexConf)
	{
		$indexDao = $aIndexConf['dao'].'Dao';
		$indexField = $this->$indexDao->aGetIndexField();
		foreach ($indexField as $field)
		{
			if ($aOldInfo[$field] != $aNewInfo[$field])
			{
				$this->_vDelete_Index_One($aOldInfo, $aIndexConf);
				$this->_vInsert_Index_One($aNewInfo, $aIndexConf);
				return;
			}
		}
		$updateData = array();
		if (isset($aIndexConf['datafields']))
		{
			foreach ($aIndexConf['datafields'] as $field)
			{
				if ($aOldInfo[$field] != $aNewInfo[$field])
				{
					$updateData[$field] = $aNewInfo[$field];
				}
			}
		}
		if (!empty($updateData))
		{
			$this->$indexDao->iUpdate($aOldInfo, $updateData);
		}
	}

	private function _vDelete_Index($aInfo)
	{
		if (isset($this->_aConf['index']))
		{
			foreach ($this->_aConf['index'] as $v)
			{
				$this->_vDelete_Index_One($aInfo, $v);
			}
		}
	}
	
	private function _vDelete_Index_One($aInfo, $aIndexConf)
	{
		$indexDao = $aIndexConf['dao'].'Dao';
		$this->$indexDao->iDelete($aInfo);
	}
}
