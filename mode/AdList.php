<?php
/**
 * 使用说明
 *
 * <b>简介</b>
 * <pre>
 *   用来实现广告轮播的效果，根据 用户ID，IP，时间，比例
 * </pre>
 *
 * <b>数据库例表</b>
 * <pre>
 *   CREATE TABLE t_ko_adlist (
 *     id int unsigned not null default 0,
 *     stime timestamp not null default 0,
 *     etime timestamp not null default 0,
 *     grp varchar(32) not null default '',
 *     regions varchar(512) not null default '',
 *     forbidregions varchar(512) not null default '',
 *     uids varchar(512) not null default '',
 *     priority int unsigned not null default 0,
 *     pub tinyint unsigned not null default 0,
 *     PRIMARY KEY(id)
 *   ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 * </pre>
 *
 * <b>_aConf 配置</b>
 * @see Ko_Mode_AdList::$_aConf
 *
 * @package ko\mode
 * @author zhangchu
 */

class Ko_Mode_AdList extends Ko_Busi_Api
{
	/**
	 * 配置数组
	 *
	 * <pre>
	 * array(
	 *   'listApi' => 广告轮播列表数据库 Api 名称
	 *   'isgb' => regions 字段使用 GB18030 编码还是 UTF-8 编码
	 *   'mc' => 使用的 memcache Dao 名称
	 * )
	 * </pre>
	 *
	 * @var array
	 */
	protected $_aConf = array();
	
	/**
	 * 重置当前的列表缓存
	 */
	public function vResetCurrentListCache()
	{
		$listApi = $this->_aConf['listApi'];
		$mcDao = $this->_aConf['mc'].'Dao';
		$key = 'koad:'.$this->$listApi->sGetTableName().':current';
		$list = $this->_aGetCurrentListFromDb();
		$this->$mcDao->bSetObj($key, $list);
	}
	
	/**
	 * 获取投放广告ID
	 *
	 * @return int
	 */
	public function iGetId($sGrp, $iUid = 0, $sIp = '')
	{
		// 获取当前可用列表
		$ids = array();
		$now = time();
		$list = $this->_aGetCurrentList();
		
		// 如果有用户ID，根据用户来决定要显示的列表
		if ($iUid)
		{
			foreach ($list as $v)
			{
				if ($sGrp == $v['grp'] && $v['stime'] <= $now && $now <= $v['etime'] && in_array($iUid, $v['uids']))
				{
					$ids[$v['id']] = $v['priority'];
				}
			}
			if (!empty($ids))
			{
				return $this->_aRandomSelectId($ids);
			}
		}
		
		//获取IP定位
		$loc = $this->_aGetLocation($sIp);
		foreach ($list as $v)
		{
			if ($sGrp == $v['grp'] && $v['stime'] <= $now && $now <= $v['etime'] && !in_array($loc, $v['forbidregions'], true) && (empty($v['regions']) || in_array($loc, $v['regions'], true)))
			{
				$ids[$v['id']] = $v['priority'];
			}
		}
		if (!empty($ids))
		{
			return $this->_aRandomSelectId($ids);
		}
		
		return 0;
	}
	
	protected function _aRandomSelectId($aIds)
	{
		$idmap = array();
		$totalpriority = 0;
		foreach ($aIds as $id => $priority)
		{
			$idmap[$id]['min'] = $totalpriority + 1;
			$idmap[$id]['max'] = $totalpriority += $priority;
		}
		$r = mt_rand(1, $totalpriority);
		foreach ($idmap as $id => $v)
		{
			if ($v['min'] <= $r && $r <= $v['max'])
			{
				return $id;
			}
		}
		return 0;
	}
	
	protected function _aGetLocation($sIp)
	{
		if (0 === strlen($sIp))
		{
			$sIp = Ko_Tool_Ip::SGetClientIP();
		}
		$loc = Ko_Data_IPLocator::OInstance()->sGetLocation($sIp);
		$loc = Ko_Tool_Str::AStr2Arr_GB18030($loc);
		return implode('', array_slice($loc, 0, 2));
	}
	
	protected function _aGetCurrentList()
	{
		$listApi = $this->_aConf['listApi'];
		$mcDao = $this->_aConf['mc'].'Dao';
		$key = 'koad:'.$this->$listApi->sGetTableName().':current';
		$list = $this->$mcDao->vGetObj($key);
		if (false === $list)
		{
			$list = $this->_aGetCurrentListFromDb();
			$this->$mcDao->bSetObj($key, $list);
		}
		return $list;
	}
	
	protected function _aGetCurrentListFromDb()
	{
		$now = time();
		$stime = ceil($now / 3600.) * 3600;
		$etime = floor($now / 3600.) * 3600;
		$option = new Ko_Tool_SQL;
		$listApi = $this->_aConf['listApi'];
		$list = $this->$listApi->aGetList($option->oSelect('id, stime, etime, grp, regions, forbidregions, uids, priority')->oWhere('stime <= ? and etime >= ? and pub != 0', date('Y-m-d H:i:s', $stime), date('Y-m-d H:i:s', $etime)));
		foreach ($list as &$v)
		{
			$v['stime'] = strtotime($v['stime']);
			$v['etime'] = strtotime($v['etime']);
			$v['uids'] = preg_split('/;|,|\s/', $v['uids']);
			$v['regions'] = $this->_aGetRegionArr($v['regions']);
			$v['forbidregions'] = $this->_aGetRegionArr($v['forbidregions']);
		}
		unset($v);
		return $list;
	}
	
	protected function _aGetRegionArr($sRegion)
	{
		$regions = preg_split('/;|,|\s/', $sRegion);
		$ret = array();
		foreach ($regions as $region)
		{
			$region = trim($region);
			if (0 === strlen($region))
			{
				continue;
			}
			$region = Ko_Tool_Str::AStr2Arr($region, $this->_aConf['isgb'] ? 'GB18030' : 'UTF-8');
			$region = implode('', array_slice($region, 0, 2));
			if (!$this->_aConf['isgb'])
			{
				$region = Ko_Tool_Str::SConvert2GB18030($region);
			}
			$ret[] = $region;
		}
		return $ret;
	}
}
