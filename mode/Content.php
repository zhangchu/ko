<?php
/**
 * Content
 *
 * <b>数据库例表</b>
 * <pre>
 *   CREATE TABLE s_zhangchu_content(
 *     id bigint unsigned not null default 0,
 *     aid int unsigned not null default 0,
 *     content MEDIUMBLOB,
 *     mtime timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
 *     unique(aid, id)
 *   )ENGINE=InnoDB DEFAULT CHARSET=latin1;
 * </pre>
 *
 * <b>_aConf 配置</b>
 * @see Ko_Mode_Content::$_aConf
 *
 * @package ko\Mode
 * @author zhangchu
 */

/**
 * 内容中心
 */
class Ko_Mode_Content extends Ko_Busi_Api
{
	/**
	 * 配置数组
	 *
	 * <pre>
	 * array(
	 *   'contentApi' =>              目标数据库 Api 名称
	 *   'maxlength' =>               最大内容长度, optional
	 *   'app' => array(
	 *     aid => array(                        aid决定种类与格式
	 *       'type' => 'html|text',             内容格式
	 *       'maxlength' =>                     这个aid里面内容最大的长度，不大于全局的最大长度 optional
	 *     ),
	 *   ),
	 * )
	 * </pre>
	 *
	 * @var array
	 */
	protected $_aConf = array();

	const DEFAULT_MAXLENGTH = 1000000;

	public function bSet($iAid, $iId, $sContent)
	{
		assert($iId > 0 && isset($this->_aConf['app'][$iAid]));
		
		$contentApi = $this->_aConf['contentApi'];
		$aData = array(
			'aid' => $iAid,
			'id' => $iId,
		);
		if ('' === $sContent)
		{
			$this->$contentApi->iDelete($aData);
			return true;
		}
		$type = ucfirst($this->_aConf['app'][$iAid]['type']);
		if ('Html' === $type)
		{
			$sContent = $this->_sReplaceDataUrl($sContent, '"');
			$sContent = $this->_sReplaceDataUrl($sContent, "'");
		}
		$classname = 'Ko_Mode_Content_'.$type;
		$aData['content'] = $classname::S2Valid($sContent, $this->_iGetAidMaxLength($iAid));
		$aUpdate = array(
			'content' => $aData['content'],
		);
		$this->$contentApi->aInsert($aData, $aUpdate);
		return true;
	}
	
	public function sGetText($iAid, $iId, $iMaxLength = 0, $sExt = '')
	{
		assert($iId > 0 && isset($this->_aConf['app'][$iAid]));
		$type = ucfirst($this->_aConf['app'][$iAid]['type']);
		$classname = 'Ko_Mode_Content_'.$type;
		
		if ('' === ($content = $this->_aGetContent($iAid, $iId)))
		{
			return '';
		}
		return $classname::S2Text($content, $iMaxLength, $sExt);
	}
	
	public function sGetHtml($iAid, $iId, $iMaxLength = 0)
	{
		assert($iId > 0 && isset($this->_aConf['app'][$iAid]));
		$type = ucfirst($this->_aConf['app'][$iAid]['type']);
		$classname = 'Ko_Mode_Content_'.$type;
		
		if ('' === ($content = $this->_aGetContent($iAid, $iId)))
		{
			return '';
		}
		return $classname::S2Html($content, $iMaxLength);
	}
	
	public function aGetText($iAid, $aIds, $iMaxLength = 0, $sExt = '')
	{
		$aInfo = array(
			$iAid => array(
				'ids' => $aIds,
				'maxlength' => $iMaxLength,
				'ext' => $sExt
			),
		);
		$ret = $this->aGetTextEx($aInfo);
		return $ret[$iAid];
	}
	
	public function aGetHtml($iAid, $aIds, $iMaxLength = 0)
	{
		$aInfo = array(
			$iAid => array(
				'ids' => $aIds,
				'maxlength' => $iMaxLength,
			),
		);
		$ret = $this->aGetHtmlEx($aInfo);
		return $ret[$iAid];
	}
	
	/**
	 * @param array $aInfo = array(aid => array('ids' => array(), 'maxlength' => 0, 'ext' => ''))
	 * @return array
	 */
	public function aGetTextEx($aInfo)
	{
		$this->_vNormalizeInfo($aInfo, $objs);
		
		$contentApi = $this->_aConf['contentApi'];
		$list = $this->$contentApi->aGetDetails($objs, '', '', false);
		$map = array();
		foreach ($objs as $k => $obj)
		{
			$aid = $obj['aid'];
			if (empty($list[$k]))
			{
				$map[$aid][$obj['id']] = '';
			}
			else
			{
				$map[$aid][$obj['id']] = $aInfo[$aid]['classname']::S2Text(
					strval($list[$k]['content']), $aInfo[$aid]['maxlength'], $aInfo[$aid]['ext']);
			}
		}
		return $map;
	}
	
	/**
	 * @param array $aInfo = array(aid => array('ids' => array(), 'maxlength' => 0))
	 * @return array
	 */
	public function aGetHtmlEx($aInfo)
	{
		$this->_vNormalizeInfo($aInfo, $objs);
		
		$contentApi = $this->_aConf['contentApi'];
		$list = $this->$contentApi->aGetDetails($objs, '', '', false);
		$map = array();
		foreach ($objs as $k => $obj)
		{
			$aid = $obj['aid'];
			if (empty($list[$k]))
			{
				$map[$aid][$obj['id']] = '';
			}
			else
			{
				$map[$aid][$obj['id']] = $aInfo[$aid]['classname']::S2Html(
					strval($list[$k]['content']), $aInfo[$aid]['maxlength']);
			}
		}
		return $map;
	}
	
	protected function _sDataUrl2Link($sData)
	{
		return '';
	}

	private function _sReplaceDataUrl($sContent, $quote)
	{
		while (1)
		{
			$pos1 = stripos($sContent, 'src='.$quote.'data:');
			if (false === $pos1)
			{
				break;
			}
			$pos2 = strpos($sContent, $quote, $pos1 + 10);
			if (false !== $pos2)
			{
				$dataurl = substr($sContent, $pos1 + 5, $pos2 - $pos1 - 5);
			}
			else
			{
				$dataurl = substr($sContent, $pos1 + 5);
			}
			$data = file_get_contents($dataurl);
			if (false !== $data)
			{
				$url = $this->_sDataUrl2Link($data);
			}
			else
			{
				$url = '';
			}
			$sContent = substr($sContent, 0, $pos1 + 5).$url.substr($sContent, $pos2);
		}
		return $sContent;
	}
	
	private function _vNormalizeInfo(&$aInfo, &$objs)
	{
		$objs = array();
		foreach ($aInfo as $aid => &$info)
		{
			assert(isset($this->_aConf['app'][$aid]));
			if (!isset($info['ids']))
			{
				$info = array('ids' => $info);
			}
			foreach ($info['ids'] as $id)
			{
				assert($id > 0);
				$objs[] = compact('aid', 'id');
			}
			$info['maxlength'] = intval($info['maxlength']);
			$info['ext'] = strval($info['ext']);
			$type = ucfirst($this->_aConf['app'][$aid]['type']);
			$info['classname'] = 'Ko_Mode_Content_'.$type;
		}
		unset($info);
	}
	
	private function _aGetContent($iAid, $iId)
	{
		$contentApi = $this->_aConf['contentApi'];
		$aKey = array(
			'aid' => $iAid,
			'id' => $iId,
		);
		$info = $this->$contentApi->aGet($aKey);
		if (empty($info))
		{
			return '';
		}
		return strval($info['content']);
	}

	private function _iGetAidMaxLength($aid)
	{
		if (isset($this->_aConf['app'][$aid]['maxlength']))
		{
			return min($this->_aConf['app'][$aid]['maxlength'], $this->_iGetMaxLength());
		}
		return $this->_iGetMaxLength();
	}

	private function _iGetMaxLength()
	{
		if (isset($this->_aConf['maxlength']))
		{
			return $this->_aConf['maxlength'];
		}
		return self::DEFAULT_MAXLENGTH;
	}
}
