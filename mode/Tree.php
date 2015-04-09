<?php
/**
 * 使用说明
 *
 * <b>简介</b>
 * <pre>
 *   用来实现树形数据结构，如行政区划
 * </pre>
 *
 * <b>数据库例表</b>
 * <pre>
 *   CREATE TABLE t_ko_tree (
 *     id int unsigned not null default 0,
 *     pid int unsigned not null default 0,
 *     PRIMARY KEY(id),
 *     KEY (pid)
 *   ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 * </pre>
 *
 * <b>_aConf 配置</b>
 * @see Ko_Mode_Tree::$_aConf
 *
 * @package ko\mode
 * @author zhangchu
 */

class Ko_Mode_Tree extends Ko_Busi_Api
{
	/**
	 * 配置数组
	 *
	 * <pre>
	 * array(
	 *   'treeApi' => 目标数据库 Api 名称
	 *   'mc' => 使用的 memcache Dao 名称
	 *   'maxdepth' => 并不限制总体数据的深度，但是查询父节点，最多返回这么多元素，查询子节点，最多返回这么深的子节点
	 * )
	 * </pre>
	 *
	 * @var array
	 */
	protected $_aConf = array();
	
	const DEFAULT_MAXDEPTH = 10;
	
	/**
	 * @return boolean
	 */
	public function bAdd($iId, $iPid)
	{
		$treeApi = $this->_aConf['treeApi'];
		$aData = array(
			'id' => $iId,
			'pid' => $iPid,
		);
		try
		{
			$this->$treeApi->aInsert($aData);
			$parents = $this->aGetParent($iId, 0);
			$this->_vInvalidateCache($parents);
		}
		catch (Exception $e)
		{
			return false;
		}
		return true;
	}
	
	/**
	 * @return boolean
	 */
	public function bDel($iId, $iPid)
	{
		$treeApi = $this->_aConf['treeApi'];
		$parents = $this->aGetParent($iId, 0);
		$option = new Ko_Tool_SQL;
		if ($this->$treeApi->iDelete($iId, $option->oWhere('pid = ?', $iPid)))
		{
			$this->_vInvalidateCache($parents);
			return true;
		}
		return false;
	}
	
	/**
	 * @param int $iDepth 获取父id的层数，如果为0表示一直获取到最顶层
	 * @return array
	 */
	public function aGetParent($iId, $iDepth = 1)
	{
		$iDepth = $this->_iAdjustDepth($iDepth);
		$treeApi = $this->_aConf['treeApi'];
		$ret = array();
		for ($i=0; $i<$iDepth; $i++)
		{
			$info = $this->$treeApi->aGet($iId);
			if (empty($info))
			{
				break;
			}
			$ret[] = $iId = $info['pid'];
		}
		return $ret;
	}
	
	/**
	 * @param int $iDepth 获取子id的层数，如果为0表示一直获取到最低层
	 * @return array
	 */
	public function aGetChild($iId, $iDepth = 1)
	{
		$iDepth = $this->_iAdjustDepth($iDepth);
		return $this->_aGetChildDepth($iId, $iDepth);
	}

	/**
	 * @return array
	 */
	public function aTree2Arr($aTree)
	{
		$ret = array_keys($aTree);
		foreach ($aTree as $k => $v)
		{
			$ret = array_merge($ret, $this->aTree2Arr($v));
		}
		return $ret;
	}
	
	/**
	 * @return array
	 */
	public function aTree2DepthArr($aTree, $iDepth = 1)
	{
		$ret = array();
		foreach ($aTree as $k => $v)
		{
			$ret[$k] = $iDepth;
		}
		foreach ($aTree as $k => $v)
		{
			$ret += $this->aTree2DepthArr($v, $iDepth + 1);
		}
		return $ret;
	}
	
	private function _vInvalidateCache($aParent)
	{
		$mcDao = $this->_aConf['mc'].'Dao';
		$maxDepth = $this->_iGetMaxDepth();
		foreach ($aParent as $k => $v)
		{
			for ($i=$k+1; $i<=$maxDepth; $i++)
			{
				$key = $this->_sGetMCKey($v, $i);
				$this->$mcDao->bDelete($key);
			}
		}
	}
	
	private function _aGetChildDepth($iId, $iDepth)
	{
		if (1 == $iDepth)
		{
			$ret = $this->_aGetMultiChild(array($iId));
			return $ret[$iId];
		}

		$mcDao = $this->_aConf['mc'].'Dao';
		$key = $this->_sGetMCKey($iId, $iDepth);
		$ids = $this->$mcDao->vGetObj($key);
		if (false === $ids)
		{
			$ids = $this->_aGetChildDepth($iId, $iDepth - 1);
			$bottomids = $this->_aGetBottomIds($ids, $iDepth - 1);
			$bottomdata = $this->_aGetMultiChild($bottomids);
			$this->_vFillBottomIds($ids, $iDepth - 1, $bottomdata);
			$this->$mcDao->bSetObj($key, $ids);
		}
		return $ids;
	}
	
	private function _aGetMultiChild($aIds)
	{
		$ret = $nocacheids = $keymap = $keys = array();
		foreach ($aIds as $id)
		{
			$key = $this->_sGetMCKey($id, 1);
			$keymap[$key] = $id;
			$keys[] = $key;
		}
		$mcDao = $this->_aConf['mc'].'Dao';
		$data = $this->$mcDao->vGetObj($keys);
		$nocacheids = array();
		foreach ($keymap as $k => $v)
		{
			if (!isset($data[$k]))
			{
				$nocacheids[] = $v;
			}
		}
		$nocachedata = $this->_aGetChildList($nocacheids);
		foreach ($keymap as $k => $v)
		{
			if (isset($data[$k]))
			{
				$ret[$v] = $data[$k];
			}
			else
			{
				$ret[$v] = isset($nocachedata[$v]) ? $nocachedata[$v] : array();
				$this->$mcDao->bSetObj($k, $ret[$v]);
			}
		}
		return $ret;
	}
	
	private function _aGetChildList($aId)
	{
		$ret = array();
		if (!empty($aId))
		{
			$treeApi = $this->_aConf['treeApi'];
			$option = new Ko_Tool_SQL;
			$list = $this->$treeApi->aGetList($option->oWhere('pid in (?)', $aId)->oForceMaster(true));
			foreach ($list as $v)
			{
				$ret[$v['pid']][$v['id']] = array();
			}
		}
		return $ret;
	}
	
	private function _sGetMCKey($iId, $iDepth)
	{
		$treeApi = $this->_aConf['treeApi'];
		return 'kotr:'.$this->$treeApi->sGetTableName().':'.$iId.':'.$iDepth;
	}
	
	private function _iAdjustDepth($iDepth)
	{
		if ($iDepth < 1 || $this->_iGetMaxDepth() < $iDepth)
		{
			return $this->_iGetMaxDepth();
		}
		return $iDepth;
	}

	private function _iGetMaxDepth()
	{
		if (isset($this->_aConf['maxdepth']))
		{
			return $this->_aConf['maxdepth'];
		}
		return self::DEFAULT_MAXDEPTH;
	}
	
	private function _vFillBottomIds(&$aTree, $iDepth, $aBottomData)
	{
		if (1 == $iDepth)
		{
			foreach ($aTree as $id => &$v)
			{
				$v = $aBottomData[$id];
			}
			unset($v);
		}
		else
		{
			foreach ($aTree as $id => &$v)
			{
				$this->_vFillBottomIds($v, $iDepth - 1, $aBottomData);
			}
			unset($v);
		}
	}
	
	private function _aGetBottomIds($aTree, $iDepth)
	{
		if (1 == $iDepth)
		{
			return array_keys($aTree);
		}
		$ids = array();
		foreach ($aTree as $k => $v)
		{
			$ids = array_merge($ids, $this->_aGetBottomIds($v, $iDepth - 1));
		}
		return $ids;
	}
}
