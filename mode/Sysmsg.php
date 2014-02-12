<?php
/**
 * 使用说明
 *
 * <b>简介</b>
 * <pre>
 *   通用的系统消息系统
 * </pre>
 *
 * <b>数据库例表</b>
 * <pre>
CREATE TABLE kotest_sysmsg_content(
  mid bigint unsigned not null auto_increment,
  uids TEXT,
  content TEXT,
  exinfo BLOB,
  conditions BLOB,
  ctime timestamp NOT NULL default 0,
  primary key (mid)
)ENGINE=InnoDB DEFAULT CHARSET=UTF8;
CREATE TABLE kotest_sysmsg_userlist(
  uid bigint unsigned not null default 0,
  mid bigint unsigned not null default 0,
  isread tinyint unsigned not null default 0,
  condition1 tinyint unsigned not null default 0,
  ...
  conditionN tinyint unsigned not null default 0,
  unique (uid, mid)
)ENGINE=InnoDB DEFAULT CHARSET=UTF8;
CREATE TABLE kotest_sysmsg_top(
  mid bigint unsigned not null default 0,
  condition1 tinyint unsigned not null default 0,
  ...
  conditionN tinyint unsigned not null default 0,
  unique (mid)
)ENGINE=InnoDB DEFAULT CHARSET=UTF8;
CREATE TABLE kotest_sysmsg_deletetop(
  uid bigint unsigned not null default 0,
  mid bigint unsigned not null default 0,
  unique (uid, mid)
)ENGINE=InnoDB DEFAULT CHARSET=UTF8;
CREATE TABLE kotest_sysmsg_massqueue(
  mid bigint unsigned not null default 0,
  status tinyint unsigned not null default 0,
  unique (mid)
)ENGINE=InnoDB DEFAULT CHARSET=UTF8;
 * </pre>
 *
 * <b>_aConf 配置</b>
 * @see Ko_Mode_Sysmsg::$_aConf
 *
 * @package ko
 * @subpackage mode
 * @author zhangchu
 */

/**
 * 接口
 */
interface IKo_Mode_Sysmsg
{
	/**
	 * 发送消息给一个用户
	 *
	 * @return int 消息ID
	 */
	public function iSendToOne($iUid, $aCond, $sContent, $sExinfo);
	/**
	 * 发送消息给所有用户，置顶
	 *
	 * @return int 消息ID
	 */
	public function iSendToAll($aCond, $sContent, $sExinfo);
	/**
	 * 发送消息给多个用户
	 *
	 * @return int 消息ID
	 */
	public function iSendToMass($aUid, $aCond, $sContent, $sExinfo);
	/**
	 * 将群发消息分发到每个用户，可能是一个耗时操作
	 *
	 * @return boolean 返回失败表示可能消息已经被分发，或正在被分发
	 */
	public function bDeliverMass($iMid);
	/**
	 * 删除消息
	 *
	 * @return boolean 是否成功
	 */
	public function bDelete($iUid, $iMid);
	/**
	 * 查询消息列表
	 *
	 * @return array
	 */
	public function aGetList($iUid, $aCond, $iStart, $iNum);
	/**
	 * 查询消息列表
	 *
	 * @return array
	 */
	public function aGetListWithTotal($iUid, $aCond, &$iTotal, $iStart, $iNum);
}

/**
 * 实现
 */
class Ko_Mode_Sysmsg extends Ko_Busi_Api implements IKo_Mode_Sysmsg
{
	/**
	 * 配置数组
	 *
	 * <pre>
	 * array(
	 *   'content' => 消息内容表
	 *   'userlist' => 用户收到的消息表，可选，要求db_split类型
	 *   'top' => 置顶消息，可选
	 *   'deletetop' => 用户删除的置顶消息，可选，要求db_split类型
	 *   'massqueue' => 群发消息的队列，可选
	 *   'mass_max' => 群发消息最多的人数，超过人数会拆分为多条数据
	 * )
	 * </pre>
	 *
	 * @var array
	 */
	protected $_aConf = array();

	const UIDS_SPLIT = '|';
	const DEFAULT_MASS_MAX = 2000;
	
	/**
	 * @return int
	 */
	public function iSendToOne($iUid, $aCond, $sContent, $sExinfo)
	{
		$mid = $this->_iInsertContent($iUid, $aCond, $sContent, $sExinfo);
		if ($mid)
		{
			$this->_vInsertUserlist($iUid, $mid, $aCond);
		}
		return $mid;
	}
	
	/**
	 * @return int
	 */
	public function iSendToAll($aCond, $sContent, $sExinfo)
	{
		$mid = $this->_iInsertContent('', $aCond, $sContent, $sExinfo);
		if ($mid)
		{
			$this->_vInsertTop($mid, $aCond);
		}
		return $mid;
	}

	/**
	 * @return int
	 */
	public function iSendToMass($aUid, $aCond, $sContent, $sExinfo)
	{
		$max = $this->_iGetMassMax();
		$len = count($aUid);
		for ($i=0; $i<$len; $i+=$max)
		{
			$subuids = array_slice($aUid, $i, $max);
			$mid = $this->_iInsertContent(implode(self::UIDS_SPLIT, $subuids), $aCond, $sContent, $sExinfo);
			if ($mid)
			{
				$this->_vInsertMassQueue($mid);
			}
		}
		return $mid;
	}
	
	/**
	 * @return boolean
	 */
	public function bDeliverMass($iMid)
	{
		if (!$this->_bLockMass($iMid))
		{
			return false;
		}
		$contentDao = $this->_aConf['content'].'Dao';
		$info = $this->$contentDao->aGet($iMid);
		$uids = explode(self::UIDS_SPLIT, $info['uids']);
		$aCond = Ko_Tool_Enc::ADecode($info['conditions']);
		foreach ($uids as $uid)
		{
			$this->_vInsertUserlist($uid, $iMid, $aCond);
		}
		return true;
	}
	
	/**
	 * @return boolean
	 */
	public function bDelete($iUid, $iMid)
	{
		$contentDao = $this->_aConf['content'].'Dao';
		$info = $this->$contentDao->aGet($iMid);
		if (empty($info['uids']))
		{	//删除置顶消息
			$deletetopDao = $this->_aConf['deletetop'].'Dao';
			$this->$deletetopDao->aInsert(array('uid' => $iUid, 'mid' => $iMid), array('mid' => $iMid));
			return true;
		}
		//删除普通消息
		$userlistDao = $this->_aConf['userlist'].'Dao';
		return $this->$userlistDao->iDelete(array('uid' => $iUid, 'mid' => $iMid)) ? true : false;
	}
	
	/**
	 * @return array
	 */
	public function aGetList($iUid, $aCond, $iStart, $iNum)
	{
		$list = $iStart ? array() : $this->_aGetTop($iUid, $aCond);
		
		if (isset($this->_aConf['userlist']))
		{
			$option = new Ko_Tool_SQL;
			$this->_vBuildCondOption($aCond, $option);
			$option->oOrderBy('mid desc')->oOffset($iStart)->oLimit($iNum);
			$ulist = $this->_aGetUserList($iUid, $option);
			$list = array_merge($list, $ulist);
		}
		
		$this->vFillContent($list);
		return $list;
	}
	
	/**
	 * @return array
	 */
	public function aGetListWithTotal($iUid, $aCond, &$iTotal, $iStart, $iNum)
	{
		$list = $iStart ? array() : $this->_aGetTop($iUid, $aCond);
		
		if (isset($this->_aConf['userlist']))
		{
			$option = new Ko_Tool_SQL;
			$this->_vBuildCondOption($aCond, $option);
			$option->oOrderBy('mid desc')->oOffset($iStart)->oLimit($iNum)->oCalcFoundRows(true);
			$ulist = $this->_aGetUserList($iUid, $option);
			$list = array_merge($list, $ulist);
			$iTotal = $option->iGetFoundRows();
		}
		
		$this->vFillContent($list);
		return $list;
	}
	
	private function _vBuildCondOption($aCond, &$oOption)
	{
		foreach ($aCond as $k => $v)
		{
			if (is_array($v))
			{
				$oOption->oAnd($k.' in (?)', $v);
			}
			else
			{
				$oOption->oAnd($k.' = ?', $v);
			}
		}
	}
	
	private function _iInsertContent($sUids, $aCond, $sContent, $sExinfo)
	{
		$aData = array(
			'uids' => $sUids,
			'content' => $sContent,
			'exinfo' => $sExinfo,
			'conditions' => Ko_Tool_Enc::SEncode($aCond),
			'ctime' => date('Y-m-d H:i:s'),
		);
		$contentDao = $this->_aConf['content'].'Dao';
		return $this->$contentDao->iInsert($aData);
	}
	
	private function vFillContent(&$list)
	{
		$contentDao = $this->_aConf['content'].'Dao';
		$infos = $this->$contentDao->aGetListByKeys($list);
		foreach ($list as &$v)
		{
			$v['content'] = $infos[$v['mid']]['content'];
			$v['exinfo'] = $infos[$v['mid']]['exinfo'];
			$v['ctime'] = $infos[$v['mid']]['ctime'];
		}
		unset($v);
	}
	
	private function _vInsertUserlist($iUid, $iMid, $aCond)
	{
		$aCond['uid'] = $iUid;
		$aCond['mid'] = $iMid;
		$userlistDao = $this->_aConf['userlist'].'Dao';
		$this->$userlistDao->aInsert($aCond);
	}
	
	private function _aGetUserList($iUid, &$option)
	{
		$userlistDao = $this->_aConf['userlist'].'Dao';
		$ulist = $this->$userlistDao->aGetList($iUid, $option);
		$unreadmids = array();
		foreach ($ulist as $v)
		{
			if (!$v['isread'])
			{
				$unreadmids[] = $v['mid'];
			}
		}
		if (!empty($unreadmids))
		{
			$option = new Ko_Tool_SQL;
			$this->$userlistDao->iUpdateByCond($iUid, $option->oWhere('mid in (?)', $unreadmids), array('isread' => 1));
		}
		return $ulist;
	}
	
	private function _vInsertTop($iMid, $aCond)
	{
		$aCond['mid'] = $iMid;
		$topDao = $this->_aConf['top'].'Dao';
		$this->$topDao->aInsert($aCond);
	}
	
	private function _aGetTop($iUid, $aCond)
	{
		$list = array();
		if (isset($this->_aConf['top']))
		{
			$option = new Ko_Tool_SQL;
			$this->_vBuildCondOption($aCond, $option);
			$option->oOrderBy('mid desc');
			$topDao = $this->_aConf['top'].'Dao';
			$list = $this->$topDao->aGetList($option);
			if (!empty($list) && isset($this->_aConf['deletetop']))
			{
				$mids = Ko_Tool_Utils::AObjs2ids($list, 'mid');
				$deletetopDao = $this->_aConf['deletetop'].'Dao';
				$deletelist = $deletetopDao->aGetListByKeys($iUid, $mids);
				$newlist = array();
				foreach ($list as $v)
				{
					if (empty($deletelist[$v['mid']]))
					{
						$newlist[] = $v;
					}
				}
				$list = $newlist;
			}
		}
		return $list;
	}
	
	private function _vInsertMassQueue($iMid)
	{
		$massqueueDao = $this->_aConf['massqueue'].'Dao';
		$this->$massqueueDao->aInsert(array('mid' => $iMid));
	}
	
	private function _bLockMass($iMid)
	{
		$option = new Ko_Tool_SQL;
		$massqueueDao = $this->_aConf['massqueue'].'Dao';
		return $this->$massqueueDao->iUpdate($iMid, array('status' => 1), $option->oWhere('status = ?', 0)) ? true : false;
	}
	
	private function _iGetMassMax()
	{
		if (isset($this->_aConf['mass_max']))
		{
			return $this->_aConf['mass_max'];
		}
		return self::DEFAULT_MASS_MAX;
	}
}
