<?php
/**
 * 使用说明
 *
 * <b>简介</b>
 * <pre>
 *   自动生成一个数据库表的管理界面
 *   db_single -- 列表页 --> 最终页
 *   db_one    -- 搜索页 --> 最终页
 *   db_split  -- 搜索页 --> 列表页 --> 最终页
 *   增 改 删 明细 列表 条件查询 排序 翻页 外键
 * </pre>
 *
 * <b>_aConf 配置</b>
 * @see Ko_Mode_XList::$_aConf
 *
 * @package ko\mode
 * @author zhangchu
 */

/**
 * 自动生成一个数据库表的管理界面实现
 */
class Ko_Mode_XList extends Ko_Busi_Api
{
	/**
	 * 配置数组
	 *
	 * <pre>
	 * array(
	 *   'gb' => false, 数据库保存的字符集，utf-8 或者其他(latin1, gb等)
	 *   'pageisgb' => false, 页面使用的字符集，缺省同数据库字符集
	 *   'titlewidth' => 添加/编辑/删除/详细页面的左侧标题栏的宽度, 单位：px
	 *   'itemApi' => 使用 Ko_Mode_Item 生成的 Api 或 Func
	 *   'field' => array(
	 *     'uid' => array(
	 *       'title' => '用户ID',
	 *       'exkey' => array(
	 *         'dao' => 'user',
	 *         'field' => 'real_name',
	 *         'option' => array(),
	 *         'order' => ''
	 *       ),
	 *       'cellinfo' => array(
	 *         'width' => 100,
	 *         'brief' => '',
	 *       ),
	 *       'editinfo' => array(
	 *         'type' => 'text', // text|checkbox|radio|select|textarea|file|image
	 *         'size' => 10,
	 *         'rows' => 5,
	 *         'cols' => 60,
	 *         'values' => array(...),
	 *         'brief' => '',
	 *       ),
	 *       'queryinfo' => array(
	 *         'type' => 'text', // text|checkbox|radio|select|textarea
	 *         'size' => 10,
	 *         'rows' => 5,
	 *         'cols' => 60,
	 *         'values' => array(...),
	 *       ),
	 *     ),
	 *     ...
	 *   ),
	 *   'list' => array(
	 *     'orderfield' => array('uid', ...),
	 *     'pagenum' => 10,  每页数据条目数
	 *     'paging' => 0,    显示翻页数量
	 *     'show' => array('uid', 'content', ...),
	 *     'hide' => array('uid', ...),
	 *     'condition' => array(
	 *       'uid' => array(
	 *         array(
	 *           'operate' => 'eq', // eq|lt|gt|le|ge|like
	 *         ),
	 *         ...
	 *       ),
	 *       ...
	 *     ),
	 *   ),
	 *   'detail' => array(
	 *     'enable' => true,
	 *     'show' => array('uid', 'content', ...),
	 *     'hide' => array('uid', ...),
	 *   ),
	 *   'insert' => array(
	 *     'enable' => true,
	 *     'show' => array('uid', 'content', ...),
	 *     'hide' => array('uid', ...),
	 *     'link' => '',         // 插入数据使用的 url
	 *   ),
	 *   'update' => array(
	 *     'enable' => true,
	 *     'show' => array('uid', 'content', ...),
	 *     'hide' => array('uid', ...),
	 *     'link' => '',         // 编辑数据使用的 url
	 *   ),
	 *   'delete' => array(
	 *     'enable' => true,
	 *     'confirm' => true,
	 *     'show' => array('uid', 'content', ...),
	 *     'hide' => array('uid', ...),
	 *   ),
	 * )
	 * </pre>
	 *
	 * @var array
	 */
	protected $_aConf = array();

	private $_sSplitValue;
	private $_sHtml;
	private $_aDescCache;
	private $_vAdmin;

	private $_aAuth = array();
	private $_oUI;
	private $_oStorage;
	private $_aData = array();

	public function vMain($aReq, $vAdmin='')
	{
		if ($this->_bPageIsGb() != $this->_bIsGb())
		{
			if ($this->_bIsGb())
			{
				Ko_Tool_Str::VConvert2GB18030($aReq);
			}
			else
			{
				Ko_Tool_Str::VConvert2UTF8($aReq);
			}
		}
		
		if (!isset($aReq['sXSAction']))      $aReq['sXSAction']    = '';
		if (!isset($aReq['sXSOrder']))       $aReq['sXSOrder']     = '';
		if (!isset($aReq['iXSOrder']))       $aReq['iXSOrder']     = 0;
		if (!isset($aReq['iXSPage']))        $aReq['iXSPage']      = 1;
		
		$this->_vAdmin = $vAdmin;
		if (is_null($this->_oUI))
		{
			$this->vAttachUI(new Ko_Mode_XIUI);
		}

		if (!$this->_bIsSingleDB())
		{
			$cginame = $this->_sGetFieldCginame($this->_sGetSplitField_Item());
			if (isset($aReq[$cginame])) $this->_sSplitValue = $aReq[$cginame];
		}

		if ('POST' === Ko_Web_Request::SRequestMethod())
		{
			$this->_vMain_Post($aReq);
		}
		else
		{
			$this->_vMain_Get($aReq);
		}
	}

	public function vOutputHttp()
	{
		Header('Content-Type: text/html; charset='.($this->_bPageIsGb() ? 'GB2312' : 'UTF-8'));
	}

	/**
	 * @param boolean $bOutput 输出或者返回页面内容
	 */
	public function vOutputHead($bOutput = true)
	{
		$html = $this->_sGetPageHead($this->_bPageIsGb());
		if (!$bOutput)
		{
			return $html;
		}
		echo $html;
	}

	/**
	 * @param boolean $bOutput 输出或者返回页面内容
	 */
	public function vOutputBody($bOutput = true)
	{
		$html = $this->_sGetNaviLink().$this->_sHtml;
		if ($this->_bPageIsGb() != $this->_bIsGb())
		{
			if ($this->_bIsGb())
			{
				$html = Ko_Tool_Str::SConvert2UTF8($html);
			}
			else
			{
				$html = Ko_Tool_Str::SConvert2GB18030($html);
			}
		}
		if (!$bOutput)
		{
			return $html;
		}
		echo $html;
	}

	/**
	 * @param boolean $bOutput 输出或者返回页面内容
	 */
	public function vOutputTail($bOutput = true)
	{
		$html = $this->_sGetPageTail();
		if (!$bOutput)
		{
			return $html;
		}
		echo $html;
	}

	public function vAttachAuth($oAuth)
	{
		assert($oAuth instanceof Ko_Mode_XIAuth);
		$this->_aAuth[] = $oAuth;
	}

	public function vAttachUI($oUI)
	{
		assert($oUI instanceof Ko_Mode_XIUI);
		$this->_oUI = $oUI;
		if (!is_null($this->_oStorage))
		{
			$this->_oUI->vAttachStorage($this->_oStorage);
		}
	}
	
	public function vAttachStorage($oStorage)
	{
		assert($oStorage instanceof Ko_Data_Storage);
		$this->_oStorage = $oStorage;
		if (!is_null($this->_oUI))
		{
			$this->_oUI->vAttachStorage($this->_oStorage);
		}
	}
	
	public function vAttachData($oData)
	{
		assert($oData instanceof Ko_Mode_XIData);
		$this->_aData[] = $oData;
	}

	private function _vMain_Get($aReq)
	{
		switch ($aReq['sXSAction'])
		{
		case 'insert':
			$this->_sHtml = $this->_sGetXInsert($aReq);
			break;
		case 'update':
			$this->_sHtml = $this->_sGetXUpdate($aReq);
			break;
		case 'delete':
			$this->_sHtml = $this->_sGetXDelete($aReq);
			break;
		case 'detail':
			$this->_sHtml = $this->_sGetXDetail($aReq);
			break;
		case 'download':
			if ($this->_bGetXDownload($aReq, $this->_sHtml, $sDownloadFilename))
			{
				if ($this->_bPageIsGb() != $this->_bIsGb())
				{
					if ($this->_bIsGb())
					{
						$sDownloadFilename = Ko_Tool_Str::SConvert2UTF8($sDownloadFilename);
					}
					else
					{
						$sDownloadFilename = Ko_Tool_Str::SConvert2GB18030($sDownloadFilename);
					}
				}
				header('Content-Length: '.strlen($this->_sHtml));
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment;filename="'.$sDownloadFilename.'"');
				echo $this->_sHtml;
				exit;
			}
			break;
		default:
			$this->_sHtml = $this->_sGetXList($aReq);
			break;
		}
	}

	private function _vMain_Post($aReq)
	{
		switch ($aReq['sXSAction'])
		{
		case 'insert':
			$this->_sHtml = $this->_vPostXInsert($aReq, $aKeyInfo);
			break;
		case 'update':
			$this->_sHtml = $this->_vPostXUpdate($aReq, $aKeyInfo);
			break;
		case 'delete':
			$this->_sHtml = $this->_vPostXDelete($aReq);
			break;
		default:
			$this->_sHtml = $this->_sGetXList($aReq);
			break;
		}
		if (true === $this->_sHtml)
		{
			$para = $this->_aGetReqCommonPara($aReq);
			if (empty($aKeyInfo) || !$this->_bIsActionEnable('detail'))
			{
				header('Location: ?'.$this->_aHttpBuildQuery($para));
			}
			else
			{
				header('Location: ?sXSAction=detail&'.$this->_aHttpBuildQuery($para).'&'.$this->_aHttpBuildQuery($aKeyInfo));
			}
			exit;
		}
	}

	private function _bIsSplitDB()
	{
		return !$this->_bIsSingleDB() && !$this->_bIsOneDB();
	}

	private function _bIsOneDB()
	{
		$keyField = $this->_aGetKeyField_Item();
		return 0 == count($keyField);
	}

	private function _bIsSingleDB()
	{
		$splitField = $this->_sGetSplitField_Item();
		return 0 == strlen($splitField);
	}

	private function _bIsGb()
	{
		return isset($this->_aConf['gb']) ? $this->_aConf['gb'] : false;
	}
	
	private function _bPageIsGb()
	{
		return isset($this->_aConf['pageisgb']) ? $this->_aConf['pageisgb'] : $this->_bIsGb();
	}
	
	private function _iGetTitleWidth()
	{
		return isset($this->_aConf['titlewidth']) ? $this->_aConf['titlewidth'] : 200;
	}

	private function _iGetListPageNum()
	{
		if (isset($this->_aConf['list']['pagenum']))
		{
			return $this->_aConf['list']['pagenum'];
		}
		return 10;
	}

	private function _iGetListPaging()
	{
		return intval($this->_aConf['list']['paging']);
	}

	private function _aGetListOrderField()
	{
		if (isset($this->_aConf['list']['orderfield']))
		{
			$aOrder = $this->_aConf['list']['orderfield'];
		}
		if (empty($aOrder))
		{
			$aOrder = array();
			$aFieldInfo = $this->_aDesc_Item();
			foreach ($aFieldInfo as $field => $info)
			{
				if (Ko_Mode_XTool::BIsDBIndexTag($info['Key']))
				{
					$aOrder[] = $field;
				}
			}
		}
		$splitField = $this->_sGetSplitField_Item();
		return array_diff($aOrder, array($splitField));
	}
	
	private function _sGetInsertLink()
	{
		$link = isset($this->_aConf['insert']['link']) ? $this->_aConf['insert']['link'] : '';
		if (false === strpos($link, '?'))
		{
			return $link.'?';
		}
		return $link.'&';
	}

	private function _sGetUpdateLink()
	{
		$link = isset($this->_aConf['update']['link']) ? $this->_aConf['update']['link'] : '';
		if (false === strpos($link, '?'))
		{
			return $link.'?';
		}
		return $link.'&';
	}

	private function _bGetDeleteConfirm()
	{
		if (isset($this->_aConf['delete']['confirm']))
		{
			return $this->_aConf['delete']['confirm'];
		}
		return true;
	}

	private function _bIsActionEnable($sItem)
	{
		foreach ($this->_aAuth as $oAuth)
		{
			if (!$oAuth->bIsActionEnable($sItem, $this->_vAdmin))
			{
				return false;
			}
		}
		if (isset($this->_aConf[$sItem]['enable']))
		{
			return $this->_aConf[$sItem]['enable'];
		}
		return true;
	}

	private function _aGetShowField($sItem)
	{
		if (isset($this->_aConf[$sItem]['show']))
		{
			$aShow = $this->_aConf[$sItem]['show'];
		}
		if (empty($aShow))
		{
			$aShow = array_keys($this->_aDesc_Item());
		}

		if (isset($this->_aConf[$sItem]['hide']))
		{
			$aHide = $this->_aConf[$sItem]['hide'];
		}
		else
		{
			$aHide = array();
		}

		$aShow = array_diff($aShow, $aHide);
		foreach ($this->_aAuth as $oAuth)
		{
			$aHide = $oAuth->aGetHideField($sItem, $this->_vAdmin);
			$aShow = array_diff($aShow, $aHide);
		}
		return $aShow;
	}

	private function _aGetShowFieldWithoutKey($sItem)
	{
		$showField = $this->_aGetShowField($sItem);
		$indexField = $this->_aGetIndexField_Item();
		return array_diff($showField, $indexField);
	}

	private function _sGetNaviLink()
	{
		$html = '<div style="float:left;">'.$this->_sGetLinkHtml('javascript: window.history.back();', 'Back').' | '.$this->_sGetLinkHtml('?', 'Home');
		if (!is_null($this->_sSplitValue))
		{
			$aPara = array($this->_sGetFieldCginame($this->_sGetSplitField_Item()) => $this->_sSplitValue);
			$html .= ' | '.$this->_sGetLinkHtml('?'.$this->_aHttpBuildQuery($aPara), $this->_sGetFieldTitle($this->_sGetSplitField_Item()).' = '.$this->_sSplitValue);
		}
		$html .= ' | '.htmlspecialchars($this->_sGetTableName_Item()).'</div><div style="clear:both;"></div><hr />'."\n";
		return $html;
	}

	private function _sGetListLink($aPara)
	{
		return $this->_sGetLinkHtml('?'.$this->_aHttpBuildQuery($aPara), 'List').' ';
	}

	private function _sGetDetailLink($aPara)
	{
		$html = '';
		if ($this->_bIsActionEnable('detail'))
		{
			$html .= $this->_sGetLinkHtml('?sXSAction=detail&'.$this->_aHttpBuildQuery($aPara), 'Detail').' ';
		}
		return $html;
	}

	private function _sGetEditLink($aPara)
	{
		$html = '';
		if ($this->_bIsActionEnable('update'))
		{
			$html .= $this->_sGetLinkHtml($this->_sGetUpdateLink().'sXSAction=update&'.$this->_aHttpBuildQuery($aPara), 'Edit').' ';
		}
		if ($this->_bIsActionEnable('delete'))
		{
			$html .= $this->_sGetLinkHtml('?sXSAction=delete&'.$this->_aHttpBuildQuery($aPara), 'Delete').' ';
		}
		return $html;
	}

	private function _bIsFieldAutoIncrement($sField)
	{
		$aFieldInfo = $this->_aDesc_Item();
		if (isset($aFieldInfo[$sField]))
		{
			if ('auto_increment' == $aFieldInfo[$sField]['Extra'])
			{
				return true;
			}
			$keyField = $this->_aGetKeyField_Item();
			if (count($keyField))
			{
				$idgenField = $keyField[0];
			}
			else
			{
				$idgenField = $this->_sGetSplitField_Item();
			}
			return ($sField == $idgenField) && strlen($this->_sGetIdKey_Item());
		}
		return false;
	}
	
	private function _bIsFieldFile($sField)
	{
		return 'file' == $this->_aConf['field'][$sField]['editinfo']['type'];
	}

	private function _bIsFieldImage($sField)
	{
		return 'image' == $this->_aConf['field'][$sField]['editinfo']['type'];
	}

	private function _bIsFieldFileOrImage($sField)
	{
		return $this->_bIsFieldFile($sField) || $this->_bIsFieldImage($sField);
	}

	private function _sGetFieldCginame($sField)
	{
		if ($this->_bIsFieldFileOrImage($sField))
		{
			return 'fXF'.$sField;
		}
		return 'sXF'.$sField;
	}
	
	private function _sGetFieldRemoveCginame($sField)
	{
		return 'iXR'.$sField;
	}

	private function _sGetFieldTitle($sField)
	{
		if (isset($this->_aConf['field'][$sField]['title']))
		{
			return $this->_aConf['field'][$sField]['title'];
		}
		return $sField;
	}

	private function _aGetFieldTypeInfo($sField)
	{
		$aFieldInfo = $this->_aDesc_Item();
		return array_merge(Ko_Mode_XTool::AGetDBTypeInfo($aFieldInfo[$sField]['Type']), isset($this->_aConf['field'][$sField]) ? $this->_aConf['field'][$sField] : array());
	}
	
	private function _sGetDownloadLink($sField, $aValue)
	{
		$aPara = array();
		$this->_vFillKeyParaInfo($aValue, $aPara);
		return '<a href="?sXSAction=download&sXSField='.urlencode($sField).'&'.$this->_aHttpBuildQuery($aPara).'">Download</a>';
	}

	private function _sGetDetailLineHtml($sField, $aValue)
	{
		$typeinfo = $this->_aGetFieldTypeInfo($sField);
		if (isset($typeinfo['exkey']))
		{
			$info = $this->_aGetListByKeys_Exkey($typeinfo['exkey'], $this->_sSplitValue, array($aValue[$sField]));
			if (!empty($info[$aValue[$sField]]))
			{
				$aValue[$sField] .= '-'.$info[$aValue[$sField]][$typeinfo['exkey']['field']];
			}
		}
		$this->_oUI->vSetItemTypeinfo($typeinfo);
		$html = $this->_oUI->sDetail_LineHtml($sField, $aValue);
		if ($this->_bIsFieldFile($sField) && strlen($aValue[$sField]))
		{
			$html .= '<br>'.$this->_sGetDownloadLink($sField, $aValue);
		}
		return $this->_sGetDetailLine($sField, $html);
	}

	private function _sGetEditLineHtml($sField, $sValue)
	{
		$typeinfo = $this->_aGetFieldTypeInfo($sField);
		if (isset($typeinfo['exkey']))
		{
			$typeinfo['editinfo'] = array(
				'type' => 'select',
				'values' => $this->_aGetExkeyValues($typeinfo['exkey'], false),
				);
		}
		return $this->_sGetEditLine($sField, $this->_sGetFieldCginame($sField), $sValue, $typeinfo['editinfo']);
	}

	private function _sGetQueryEleHtml($sField, $sOperator, $sName, $sValue)
	{
		$typeinfo = $this->_aGetFieldTypeInfo($sField);
		if (isset($typeinfo['exkey']))
		{
			$sOperator = 'eq';
			$typeinfo['queryinfo'] = array(
				'type' => 'select',
				'values' => $this->_aGetExkeyValues($typeinfo['exkey'], true),
				);
		}
		return $this->_sGetQueryEle($sOperator, $sField, $sName, $sValue, $typeinfo['queryinfo']);
	}

	private function _sGetListHeadCellHtml($sField, $sLink, $iSort = 0)
	{
		$typeinfo = $this->_aGetFieldTypeInfo($sField);
		$title = $this->_sGetFieldTitle($sField);
		if ($iSort > 0)
		{
			$title .= ' v';
		}
		else if ($iSort < 0)
		{
			$title .= ' ^';
		}
		return $this->_sGetListHeadCell($sLink, $title, $typeinfo['cellinfo']);
	}

	private function _sGetListCellHtml($sField, $aValue, $aExkeyInfos)
	{
		$typeinfo = $this->_aGetFieldTypeInfo($sField);
		if (isset($typeinfo['exkey']) && isset($aExkeyInfos[$sField][$aValue[$sField]]))
		{
			$aValue[$sField] .= '-'.$aExkeyInfos[$sField][$aValue[$sField]][$typeinfo['exkey']['field']];
		}
		$this->_oUI->vSetItemTypeinfo($typeinfo);
		$html = $this->_oUI->sList_CellHtml($sField, $aValue);
		if ($this->_bIsFieldFile($sField) && strlen($aValue[$sField]))
		{
			$html .= '<br>'.$this->_sGetDownloadLink($sField, $aValue);
		}
		return $this->_sGetListCell($html, $typeinfo['cellinfo']);
	}

	private function _aGetExkeyInfos($aField, $aData)
	{
		$aExkeyInfos = array();
		foreach ($aField as $field)
		{
			$typeinfo = $this->_aGetFieldTypeInfo($field);
			if (isset($typeinfo['exkey']))
			{
				$keys = Ko_Tool_Utils::AObjs2ids($aData, $field);
				$aExkeyInfos[$field] = $this->_aGetListByKeys_Exkey($typeinfo['exkey'], $this->_sSplitValue, $keys);
			}
		}
		return $aExkeyInfos;
	}

	private function _aGetExkeyValues($aExkeyConf, $bAll)
	{
		$list = $this->_aGetAll_Exkey($aExkeyConf, $this->_sSplitValue);
		$keyField = $this->_aGetKeyField_Exkey($aExkeyConf);
		$aValues = $bAll ? array('' => '---- All ----') : array();
		foreach ($list as $item)
		{
			$aValues[$item[$keyField[0]]] = $item[$keyField[0]].'-'.$item[$aExkeyConf['field']];
		}
		return $aValues;
	}

	private function _sGetKeyShowInfo($aInfo)
	{
		$indexField = $this->_aGetIndexField_Item();
		$html = '';
		foreach ($indexField as $field)
		{
			$html .= $this->_sGetDetailLineHtml($field, $aInfo);
		}
		return $html;
	}

	private function _vFillKeyParaInfo($aInfo, &$aPara)
	{
		$indexField = $this->_aGetIndexField_Item();
		foreach ($indexField as $field)
		{
			$aPara[$this->_sGetFieldCginame($field)] = $aInfo[$field];
		}
	}

	private function _aGetResultKey($aReq, $iNewId)
	{
		$indexField = $this->_aGetIndexField_Item();
		$data = array();
		foreach ($indexField as $field)
		{
			$cginame = $this->_sGetFieldCginame($field);
			$data[$cginame] = ($iNewId && $this->_bIsFieldAutoIncrement($field)) ? $iNewId : $aReq[$cginame];
		}
		return $data;
	}

	private function _aGetReqKey($aReq)
	{
		$indexField = $this->_aGetIndexField_Item();
		$aKey = array();
		foreach ($indexField as $field)
		{
			$aKey[$field] = $aReq[$this->_sGetFieldCginame($field)];
		}
		return $aKey;
	}

	private function _sGetReqOrder($aReq)
	{
		$orderfield = $this->_aGetListOrderField();
		if (in_array($aReq['sXSOrder'], $orderfield, true))
		{
			return $aReq['sXSOrder'].' '.($aReq['iXSOrder'] ? 'desc' : 'asc');
		}
		return '';
	}

	private function _aGetReqCommonPara_Callback($sField, $sOperator, $sName, $sValue, &$vData)
	{
		$vData[$sName] = $sValue;
	}

	private function _aGetReqCommonPara($aReq)
	{
		$para = $this->_bIsSplitDB() ? array($this->_sGetFieldCginame($this->_sGetSplitField_Item()) => $this->_sSplitValue) : array();
		$this->_vDoCondition($aReq, array($this, '_aGetReqCommonPara_Callback'), $para);
		$para['sXSOrder'] = $aReq['sXSOrder'];
		$para['iXSOrder'] = $aReq['iXSOrder'];
		$para['iXSPage'] = $aReq['iXSPage'];
		return $para;
	}

	private function _vGetReqCondition_Callback($sField, $sOperator, $sName, $sValue, &$vData)
	{
		if (0 == strlen($sValue))
		{
			return;
		}
		Ko_Mode_XTool::VGetOperatorSql($sOperator, $sField, $sValue, $vData);
	}

	private function _vGetReqCondition($aReq, $oOption)
	{
		$this->_vDoCondition($aReq, array($this, '_vGetReqCondition_Callback'), $oOption);
		foreach ($this->_aAuth as $oAuth)
		{
			$oAuth->vGetListEx($this->_sSplitValue, $this->_vAdmin, $oOption);
		}
	}

	private function _sGetReqQueryHtml_Callback($sField, $sOperator, $sName, $sValue, &$vData)
	{
		$vData .= $this->_sGetQueryEleHtml($sField, $sOperator, $sName, $sValue);
	}

	private function _sGetReqQueryHtml($aReq)
	{
		$html = $form = '';
		$this->_vDoCondition($aReq, array($this, '_sGetReqQueryHtml_Callback'), $form);
		if (strlen($form))
		{
			$html .= '<form action="?">';
			$html .= '<div style="float:left;">';
			if ($this->_bIsSplitDB())
			{
				$html .= $this->_sGetHidden($this->_sGetFieldCginame($this->_sGetSplitField_Item()), $this->_sSplitValue);
			}
			$html .= $form;
			$html .= '<input type=submit value="Query"></div>';
			$html .= '<div style="clear:both;"></div>';
			$html .= '</form><hr />'."\n";
		}
		return $html;
	}

	private function _vDoCondition($aReq, $fnCallback, &$vData)
	{
		if (isset($this->_aConf['list']['condition']))
		{
			foreach ($this->_aConf['list']['condition'] as $field => $v)
			{
				foreach ($v as $k2 => $v2)
				{
					$cginame = $this->_sGetFieldCginame($field).'_'.$k2;
					call_user_func_array($fnCallback, array($field, $v2['operate'], $cginame, $aReq[$cginame], &$vData));
				}
			}
		}
	}

	private function _sGetReqHeadHtml($aReq)
	{
		$showfield = $this->_aGetShowField('list');
		$orderfield = $this->_aGetListOrderField();
		$para = $this->_aGetReqCommonPara($aReq);
		$para['iXSPage'] = 1;

		$html = '';
		foreach ($showfield as $field)
		{
			if (in_array($field, $orderfield, true))
			{
				$para['sXSOrder'] = $field;
				if ($field == $aReq['sXSOrder'])
				{
					$para['iXSOrder'] = !$aReq['iXSOrder'];
					$html .= $this->_sGetListHeadCellHtml($field, '?'.$this->_aHttpBuildQuery($para), $aReq['iXSOrder'] ? -1 : 1);
				}
				else
				{
					$para['iXSOrder'] = 0;
					$html .= $this->_sGetListHeadCellHtml($field, '?'.$this->_aHttpBuildQuery($para));
				}
			}
			else
			{
				$html .= $this->_sGetListHeadCellHtml($field, '');
			}
		}
		$html .= '<div style="float:left;">Action</div>';
		$html .= '<div style="clear:both;"></div>'."\n";
		return $html;
	}

	private function _sGetReqListHtml($aReq, $oOption, &$iCount)
	{
		$showfield = $this->_aGetShowField('list');
		$this->_vGetReqCondition($aReq, $oOption);
		$para = $this->_aGetReqCommonPara($aReq);

		$pagenum = $this->_iGetListPageNum();
		$curpage = max(1, $aReq['iXSPage']);
		$oOption->oCalcFoundRows(true)->oOrderBy($this->_sGetReqOrder($aReq))->oOffset(($curpage - 1) * $pagenum)->oLimit($pagenum);
		$info = $this->_aGetList_Item($aReq, $oOption);
		$iCount = count($info);
		$aExkeyInfos = $this->_aGetExkeyInfos($showfield, $info);

		$html = '';
		foreach ($info as $v)
		{
			foreach ($showfield as $field)
			{
				$html .= $this->_sGetListCellHtml($field, $v, $aExkeyInfos);
			}
			$html .= '<div style="float:left;">';
			$this->_vFillKeyParaInfo($v, $para);
			$html .= $this->_sGetDetailLink($para);
			$html .= $this->_sGetEditLink($para);
			$html .= '</div><div style="clear:both;"></div>'."\n";
		}
		return $html;
	}

	private function _sGetReqPageHtml($aReq, $iTotal, $iCount)
	{
		$para = $this->_aGetReqCommonPara($aReq);
		$pagenum = $this->_iGetListPageNum();
		$totalpage = ceil($iTotal / $pagenum);
		$curpage = max(1, $aReq['iXSPage']);
		$startNum = ($curpage - 1) * $pagenum + 1;
		$endNum = $startNum + $iCount - 1;

		if ($totalpage)
		{
			$para['iXSPage'] = $curpage - 1;
			$prevLink = ($curpage > 1) ? $this->_sGetLinkHtml('?'.$this->_aHttpBuildQuery($para), 'Prev') : 'Prev';
			$para['iXSPage'] = $curpage + 1;
			$nextLink = ($curpage < $totalpage) ? $this->_sGetLinkHtml('?'.$this->_aHttpBuildQuery($para), 'Next') : 'Next';
			
			$paging = $this->_iGetListPaging();
			if ($paging)
			{
				$halfpaging = ceil(($paging - 1) / 2);
				$startpage = max(1, $curpage - $halfpaging);
				$endpage = min($totalpage, max($startpage + $paging - 1, $curpage + $halfpaging));
				$startpage = max(1, $endpage - $paging + 1);
				$subflag = false;
				while ($endpage - $startpage >= $paging)
				{
					$subflag ? $endpage-- : $startpage++;
					$subflag = !$subflag;
				}
				$pagingLink = array();
				for ($i=$startpage; $i<=$endpage; ++$i)
				{
					$para['iXSPage'] = $i;
					$pagingLink[] = ($i != $curpage) ? $this->_sGetLinkHtml('?'.$this->_aHttpBuildQuery($para), $i) : $i;
				}
				$pagingLink = $prevLink.' '.((1 == $startpage) ? '' : '.. ').implode(' ', $pagingLink).' '.(($totalpage == $endpage) ? '' : '.. ').$nextLink;
			}
			else
			{
				$pagingLink = $prevLink.' '.$nextLink;
			}
			unset($para['iXSPage']);
			$pagingLink .= '</div><div style="float:left;margin-right:10px;"><form action="?'.$this->_aHttpBuildQuery($para).'" method="GET"><input type="text" name="iXSPage" value="" size="4"><input type="submit" value="go"></form></div><div style="float:left;margin-right:10px;">';
			$pagingLink .= '</div><div style="float:left;margin-right:10px;">TotalPage: '.$curpage.'/'.$totalpage;
		}
		
		$html = '<div style="float:left;margin-right:10px;">';
		if ($this->_bIsActionEnable('insert'))
		{
			$newLink = $this->_sGetLinkHtml($this->_sGetInsertLink().'sXSAction=insert&'.$this->_aHttpBuildQuery($para), 'Insert');
			$html .= $newLink.'</div><div style="float:left;margin-right:10px;">';
		}
		$html .= 'Total: '.htmlspecialchars($iTotal).($iCount ? ', '.htmlspecialchars($startNum).' - '.htmlspecialchars($endNum) : '').'</div><div style="float:left;margin-right:10px;">'.$pagingLink.'</div><div style="clear:both;"></div>'."\n";

		return $html;
	}

	private function _sGetSplitQueryHtml($aReq)
	{
		$splitField = $this->_sGetSplitField_Item();
		$html = '<form action="?">';
		$html .= '<div style="float:left;">';
		$cginame = $this->_sGetFieldCginame($splitField);
		$typeinfo = $this->_aGetFieldTypeInfo($splitField);
		$html .= $this->_sGetQueryEle('eq', $splitField, $cginame, '', $typeinfo['queryinfo']);
		$html .= '<input type=submit value="Query"></div>';
		$html .= '<div style="clear:both;"></div>';
		$html .= '</form><hr />'."\n";

		if ($this->_bIsOneDB() && $this->_bIsActionEnable('insert'))
		{
			$html .= '<div style="float:left;">'.$this->_sGetLinkHtml($this->_sGetInsertLink().'sXSAction=insert', 'Insert').'</div><div style="clear:both;"></div>'."\n";
		}
		return $html;
	}

	private function _sGetXList($aReq)
	{
		$html = '';
		if ($this->_bIsOneDB() && !is_null($this->_sSplitValue))
		{
			$html .= $this->_sGetXDetail($aReq);
		}
		else if (!$this->_bIsSingleDB() && is_null($this->_sSplitValue))
		{
			$html .= $this->_sGetSplitQueryHtml($aReq);
		}
		else
		{
			$html .= $this->_sGetReqQueryHtml($aReq);
			$html .= $this->_sGetReqHeadHtml($aReq);
			$oOption = new Ko_Tool_SQL;
			$html .= $this->_sGetReqListHtml($aReq, $oOption, $iCount);
			$html .= $this->_sGetReqPageHtml($aReq, $oOption->iGetFoundRows(), $iCount);
		}
		return $html;
	}

	private function _sGetXDetail($aReq)
	{
		assert($this->_bIsActionEnable('detail'));

		$info = $this->_aGetDetailData($aReq, $sError);

		$html = '';
		if (empty($info))
		{
			$html .= $this->_sGetErrorHtml($sError);
		}
		else
		{
			$para = $this->_aGetReqCommonPara($aReq);
			$html .= '<div style="float:left;">';
			if (!$this->_bIsOneDB())
			{
				$html .= $this->_sGetListLink($para);
			}
			$this->_vFillKeyParaInfo($info, $para);
			$html .= $this->_sGetEditLink($para);
			$html .= '</div><div style="clear:both;"></div>'."\n";

			$showfield = $this->_aGetShowField('detail');
			foreach ($showfield as $field)
			{
				$html .= $this->_sGetDetailLineHtml($field, $info);
			}
		}
		return $html;
	}

	private function _sGetXUpdate($aReq)
	{
		assert($this->_bIsActionEnable('update'));

		$info = $this->_aGetDetailData($aReq, $sError);

		$html = '';
		if (empty($info))
		{
			$html .= $this->_sGetErrorHtml($sError);
		}
		else
		{
			$html .= '<form action="?" method="POST" enctype="multipart/form-data">';

			$para = $this->_aGetReqCommonPara($aReq);
			$this->_vFillKeyParaInfo($info, $para);
			$para['sXSAction'] = 'update';
			$html .= $this->_sGetHiddenList($para);

			$html .= $this->_sGetKeyShowInfo($info);
			$showfield = $this->_aGetShowFieldWithoutKey('update');
			foreach ($showfield as $field)
			{
				$html .= $this->_sGetEditLineHtml($field, $info[$field]);
			}

			$html .= '<div style="float:left;"><input type=submit value="Update"></div><div style="clear:both;"></div>';
			$html .= '</form>'."\n";
		}
		return $html;
	}
	
	private function _bGetXDownload($aReq, &$sContent, &$sFilename)
	{
		$info = $this->_aGetDetailData($aReq, $sError);

		if (empty($info))
		{
			$sContent = $this->_sGetErrorHtml($sError);
			return false;
		}
		$sContent = $this->_oStorage->sRead($info[$aReq['sXSField']]);
		return false !== $sContent;
	}

	private function _vPostXUpdate($aReq, &$aKeyInfo)
	{
		assert($this->_bIsActionEnable('update'));

		$aData = array();
		$showfield = $this->_aGetShowFieldWithoutKey('update');
		foreach ($showfield as $field)
		{
			$ret = $this->_vGetPostData($field, $aReq);
			if (false !== $ret)
			{
				$aData[$field] = $ret;
			}
		}
		try
		{
			$aKey = $this->_aGetReqKey($aReq);
			foreach ($this->_aAuth as $oAuth)
			{
				if (!$oAuth->bBeforeUpdate($aKey, $aData, $this->_vAdmin, $sError))
				{
					return $this->_sGetErrorHtml($sError);
				}
			}
			$itemApi = $this->_aConf['itemApi'];
			$this->$itemApi->iUpdate($aKey, $aData, array(), '', $this->_vAdmin);
		}
		catch(Exception $e)
		{
			return $this->_sGetErrorHtml('Fail!');
		}
		$aKeyInfo = $this->_aGetResultKey($aReq, 0);
		return true;
	}

	private function _sGetXDelete($aReq)
	{
		assert($this->_bIsActionEnable('delete'));

		$info = $this->_aGetDetailData($aReq, $sError);

		$html = '';
		if (empty($info))
		{
			$html .= $this->_sGetErrorHtml($sError);
		}
		else
		{
			$html .= '<form action="?" method="POST"'.($this->_bGetDeleteConfirm() ? ' onsubmit="javascript: return confirm(\'Do you want to delete it?\');"' : '').'>';

			$para = $this->_aGetReqCommonPara($aReq);
			$this->_vFillKeyParaInfo($info, $para);
			$para['sXSAction'] = 'delete';
			$html .= $this->_sGetHiddenList($para);

			$html .= $this->_sGetKeyShowInfo($info);
			$showfield = $this->_aGetShowFieldWithoutKey('delete');
			foreach ($showfield as $field)
			{
				$html .= $this->_sGetDetailLineHtml($field, $info);
			}

			$html .= '<div style="float:left;"><input type=submit value="Delete"></div><div style="clear:both;"></div>';
			$html .= '</form>'."\n";
		}
		return $html;
	}

	private function _vPostXDelete($aReq)
	{
		assert($this->_bIsActionEnable('delete'));

		$aKey = $this->_aGetReqKey($aReq);
		foreach ($this->_aAuth as $oAuth)
		{
			if (!$oAuth->bBeforeDelete($aKey, $this->_vAdmin, $sError))
			{
				return $this->_sGetErrorHtml($sError);
			}
		}
		$itemApi = $this->_aConf['itemApi'];
		$this->$itemApi->iDelete($aKey, '', $this->_vAdmin);
		return true;
	}

	private function _sGetXInsert($aReq)
	{
		assert($this->_bIsActionEnable('insert'));

		$html = '';
		$html .= '<form action="?" method="POST" enctype="multipart/form-data">';

		$para = $this->_aGetReqCommonPara($aReq);
		$para['sXSAction'] = 'insert';
		$html .= $this->_sGetHiddenList($para);

		$showfield = $this->_aGetShowField('insert');
		if ($this->_bIsSplitDB())
		{
			$showfield = array_diff($showfield, array($this->_sGetSplitField_Item()));
		}
		foreach ($showfield as $field)
		{
			if ($this->_bIsFieldAutoIncrement($field))
			{
				continue;
			}
			$html .= $this->_sGetEditLineHtml($field, '');
		}

		$html .= '<div style="float:left;"><input type=submit value="Insert"></div><div style="clear:both;"></div>';
		$html .= '</form>'."\n";
		return $html;
	}

	private function _vPostXInsert($aReq, &$aKeyInfo)
	{
		assert($this->_bIsActionEnable('insert'));

		$aData = $this->_bIsSplitDB() ? array($this->_sGetSplitField_Item() => $this->_sSplitValue) : array();
		$showfield = $this->_aGetShowField('insert');
		foreach ($showfield as $field)
		{
			if ($this->_bIsFieldAutoIncrement($field))
			{
				continue;
			}
			$ret = $this->_vGetPostData($field, $aReq);
			if (false !== $ret)
			{
				$aData[$field] = $ret;
			}
		}
		try
		{
			foreach ($this->_aAuth as $oAuth)
			{
				if (!$oAuth->bBeforeInsert($aData, $this->_vAdmin, $sError))
				{
					return $this->_sGetErrorHtml($sError);
				}
			}
			$itemApi = $this->_aConf['itemApi'];
			$newid = $this->$itemApi->iInsert($aData, array(), array(), null, $this->_vAdmin);
		}
		catch(Exception $e)
		{
			return $this->_sGetErrorHtml('Fail!');
		}
		$aKeyInfo = $this->_aGetResultKey($aReq, $newid);
		return true;
	}

	private function _aGetDetailData($aReq, &$sError)
	{
		$sError = 'No Data!';
		$aKey = $this->_aGetReqKey($aReq);
		foreach ($this->_aAuth as $oAuth)
		{
			if (!$oAuth->bBeforeGet($aKey, $this->_vAdmin, $sError))
			{
				return array();
			}
		}
		$itemApi = $this->_aConf['itemApi'];
		return $this->$itemApi->aGet($aKey);
	}
	
	private function _vGetPostData($sField, $aReq)
	{
		$cginame = $this->_sGetFieldCginame($sField);
		if ($this->_bIsFieldFileOrImage($sField))
		{
			if ($this->_oStorage->bUpload2Storage($aReq[$cginame], $dest, $this->_bIsFieldImage($sField)))
			{
				return $dest;
			}
			$removeCginame = $this->_sGetFieldRemoveCginame($sField);
			if ($aReq[$removeCginame])
			{
				return '';
			}
		}
		else
		{
			return $aReq[$cginame];
		}
		return false;
	}

	private function _aGetList_Item($aReq, $oOption)
	{
		foreach ($this->_aData as $oData)
		{
			$list = $oData->aGetList($aReq, $oOption);
			if (!empty($list))
			{
				break;
			}
		}
		if (empty($list))
		{
			$itemApi = $this->_aConf['itemApi'];
			if ($this->_bIsSplitDB())
			{
				$list = $this->$itemApi->aGetList($this->_sSplitValue, $oOption);
			}
			else
			{
				$list = $this->$itemApi->aGetList($oOption);
			}
		}
		foreach ($this->_aData as $oData)
		{
			$oData->vAfterGetList($list);
		}
		return $list;
	}

	private function _sGetTableName_Item()
	{
		$itemApi = $this->_aConf['itemApi'];
		return $this->$itemApi->sGetTableName();
	}

	private function _sGetSplitField_Item()
	{
		$itemApi = $this->_aConf['itemApi'];
		return $this->$itemApi->sGetSplitField();
	}

	private function _aGetKeyField_Item()
	{
		$itemApi = $this->_aConf['itemApi'];
		return $this->$itemApi->aGetKeyField();
	}

	private function _aGetIndexField_Item()
	{
		$itemApi = $this->_aConf['itemApi'];
		return $this->$itemApi->aGetIndexField();
	}

	private function _sGetIdKey_Item()
	{
		$itemApi = $this->_aConf['itemApi'];
		return $this->$itemApi->sGetIdKey();
	}

	private function _aDesc_Item()
	{
		if (empty($this->_aDescCache))
		{
			$itemApi = $this->_aConf['itemApi'];
			$oMysql = $this->$itemApi->oConnectDB(0);
			$oMysql->bQuery('DESC '.$this->$itemApi->sGetRealTableName(0));
			$this->_aDescCache = array();
			while ($info = $oMysql->aFetchAssoc())
			{
				$this->_aDescCache[$info['Field']] = $info;
			}
		}
		return $this->_aDescCache;
	}

	private function _aGetListByKeys_Exkey($aExkeyConf, $vHintId, $aKey)
	{
		$exkeyDao = $aExkeyConf['dao'].'Dao';
		if (strlen($this->$exkeyDao->sGetSplitField()))
		{
			return $this->$exkeyDao->aGetListByKeys($vHintId, $aKey);
		}
		return $this->$exkeyDao->aGetListByKeys($aKey);
	}

	private function _aGetAll_Exkey($aExkeyConf, $vHintId)
	{
		$exkeyDao = $aExkeyConf['dao'].'Dao';
		$oOption = new Ko_Tool_SQL;
		$oOption->oOrderBy($aExkeyConf['order']);
		if (!empty($aExkeyConf['option']))
		{
			call_user_func_array(array($oOption, 'oAnd'), $aExkeyConf['option']);
		}
		if (strlen($this->$exkeyDao->sGetSplitField()))
		{
			return $this->$exkeyDao->aGetList($vHintId, $oOption);
		}
		return $this->$exkeyDao->aGetList($oOption);
	}

	private function _aGetKeyField_Exkey($aExkeyConf)
	{
		$exkeyDao = $aExkeyConf['dao'].'Dao';
		return $this->$exkeyDao->aGetKeyField();
	}
	
	private function _aHttpBuildQuery($aPara)
	{
		if ($this->_bPageIsGb() != $this->_bIsGb())
		{
			if ($this->_bIsGb())
			{
				Ko_Tool_Str::VConvert2UTF8($aPara);
			}
			else
			{
				Ko_Tool_Str::VConvert2GB18030($aPara);
			}
		}
		return http_build_query($aPara);
	}
	
	private function _sGetDetailLine($sField, $sHtmlValue)
	{
		$html = '<div style="float:left;width:'.$this->_iGetTitleWidth().'px;">'.htmlspecialchars($this->_sGetFieldTitle($sField)).'</div><div style="float:left;">';
		$html .= $sHtmlValue;
		$html .= '</div><div style="clear:both;"></div>'."\n";
		return $html;
	}
	
	private function _sGetEditLine($sField, $sCginame, $sValue, $aPara)
	{
		$html = '<div style="float:left;width:'.$this->_iGetTitleWidth().'px;">'.htmlspecialchars($this->_sGetFieldTitle($sField)).'</div><div style="float:left;">';
		$html .= $this->_sGetInput($sField, $sCginame, $sValue, $aPara);
		$html .= '</div><div style="clear:both;"></div>'."\n";
		return $html;
	}
	
	private function _sGetQueryEle($sOperator, $sField, $sCginame, $sValue, $aPara)
	{
		$html = htmlspecialchars($this->_sGetFieldTitle($sField)).' '.htmlspecialchars(Ko_Mode_XTool::SGetOperatorText($sOperator)).' ';
		$html .= $this->_sGetInput($sField, $sCginame, $sValue, $aPara);
		return $html;
	}

	private function _sGetInput($sField, $sCginame, $sValue, $aPara)
	{
		$func = '_sGet'.ucfirst($aPara['type']).'Input';
		if (method_exists($this, $func))
		{
			return $this->$func($sField, $sCginame, $sValue, $aPara);
		}
		return '';
	}
	
	private function _sGetTextInput($sField, $sCginame, $sValue, $aPara)
	{
		$attr = '';
		if ($aPara['size'])
		{
			$attr .= ' size="'.intval($aPara['size']).'"';
		}
		return '<input type=text name="'.$sCginame.'" value="'.htmlspecialchars($sValue).'"'.$attr.'>'."\n";
	}
	
	private function _sGetCheckboxInput($sField, $sCginame, $sValue, $aPara)
	{
		$attr = '';
		if ($sValue)
		{
			$attr .= ' checked';
		}
		return '<input type=checkbox name="'.$sCginame.'" value="1"'.$attr.'>'."\n";
	}
	
	private function _sGetRadioInput($sField, $sCginame, $sValue, $aPara)
	{
		$html = '';
		if (is_array($aPara['values']))
		{
			foreach ($aPara['values'] as $k => $v)
			{
				$attr = '';
				if ($k.'' === $sValue)
				{
					$attr .= ' checked';
				}
				$html .= '<input type=radio name="'.$sCginame.'" value="'.htmlspecialchars($k).'"'.$attr.'> '.htmlspecialchars($v)."\n";
			}
		}
		return $html;
	}
	
	private function _sGetSelectInput($sField, $sCginame, $sValue, $aPara)
	{
		$html = '';
		if (is_array($aPara['values']))
		{
			$html .= '<select name="'.$sCginame.'">'."\n";
			foreach ($aPara['values'] as $k => $v)
			{
				$attr = '';
				if ($k.'' === $sValue)
				{
					$attr .= ' selected';
				}
				$html .= '<option value="'.htmlspecialchars($k).'"'.$attr.'> '.htmlspecialchars($v)."\n";
			}
			$html .= '</select>'."\n";
		}
		return $html;
	}
	
	private function _sGetTextareaInput($sField, $sCginame, $sValue, $aPara)
	{
		$attr = '';
		if ($aPara['rows'])
		{
			$attr .= ' rows="'.intval($aPara['rows']).'"';
		}
		if ($aPara['cols'])
		{
			$attr .= ' cols="'.intval($aPara['cols']).'"';
		}
		return '<textarea name="'.$sCginame.'"'.$attr.'>'.htmlspecialchars($sValue).'</textarea>'."\n";
	}
	
	private function _sGetFileInput($sField, $sCginame, $sValue, $aPara)
	{
		$html = '';
		if (strlen($sValue))
		{
			$removeCginame = $this->_sGetFieldRemoveCginame($sField);
			$fileinfo = $this->_oStorage->aGetFileInfo($sValue);
			$html .= '<label><input type=radio name="'.$removeCginame.'" value="1">remove file</label> <label><input type=radio name="'.$removeCginame.'" value="0">'.htmlspecialchars($fileinfo['filename'].'('.$fileinfo['size'].')').'</label><br>';
		}
		$attr = '';
		if ($aPara['size'])
		{
			$attr .= ' size="'.intval($aPara['size']).'"';
		}
		$html .= '<input type=file name="'.$sCginame.'" value=""'.$attr.'>'."\n";
		return $html;
	}
	
	private function _sGetImageInput($sField, $sCginame, $sValue, $aPara)
	{
		$html = '';
		if (strlen($sValue))
		{
			$removeCginame = $this->_sGetFieldRemoveCginame($sField);
			$image = $this->_oStorage->sGetUrl($sValue, $aPara['brief']);
			$html .= '<label><input type=radio name="'.$removeCginame.'" value="1">remove image</label> <label><input type=radio name="'.$removeCginame.'" value="0"><img src="'.htmlspecialchars($image).'"></label><br>';
		}
		$attr = '';
		if ($aPara['size'])
		{
			$attr .= ' size="'.intval($aPara['size']).'"';
		}
		$html .= '<input type=file name="'.$sCginame.'" value=""'.$attr.'>'."\n";
		return $html;
	}

	private function _sGetPageHead($bGb)
	{
		return '<html><head><meta http-equiv="Content-Type" content="text/html; charset='.($bGb ? 'GB2312' : 'UTF-8').'" /></head><body>'."\n";
	}

	private function _sGetPageTail()
	{
		return '</body></html>'."\n";
	}

	private function _sGetLinkHtml($sLink, $sTitle)
	{
		return '<a href="'.htmlspecialchars($sLink).'">'.htmlspecialchars($sTitle).'</a>';
	}

	private function _sGetErrorHtml($sError)
	{
		return '<div style="float:left;"><font color="red">'.htmlspecialchars($sError).'</font></div><div style="clear:both;"></div>'."\n";
	}

	private function _sGetListHeadCell($sLink, $sValue, $aPara = array())
	{
		$style = '';
		if ($aPara['width'])
		{
			$style .= 'width:'.intval($aPara['width']).'px;';
		}
		$html = '<div style="float:left;'.$style.'">';
		if (strlen($sLink))
		{
			$html .= '<a href="'.htmlspecialchars($sLink).'">';
		}
		$html .= htmlspecialchars($sValue);
		if (strlen($sLink))
		{
			$html .= '</a>';
		}
		$html .= '</div>'."\n";
		return $html;
	}

	private function _sGetListCell($sHtmlValue, $aPara = array())
	{
		$style = '';
		if ($aPara['width'])
		{
			$style .= 'width:'.intval($aPara['width']).'px;';
		}
		return '<div style="float:left;'.$style.'">'.$sHtmlValue.'</div>'."\n";
	}

	private function _sGetHiddenList($aList)
	{
		$html = '';
		foreach ($aList as $k => $v)
		{
			$html .= $this->_sGetHidden($k, $v);
		}
		return $html;
	}

	private function _sGetHidden($sName, $sValue)
	{
		return '<input type=hidden name="'.$sName.'" value="'.htmlspecialchars($sValue).'">'."\n";
	}
}
