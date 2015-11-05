<?php
/**
 * 使用说明
 *
 * <b>简介</b>
 * <pre>
 *   通用的短消息/私信系统
 * </pre>
 *
 * <b>数据库例表</b>
 * <pre>
 *   一个自增长值用来生成消息ID
 *   insert into idgenerator (kind, last_id) values('message', 1);
 * </pre>
 * <pre>
 *   CREATE TABLE kotest_message_content_0(
 *     mid bigint unsigned not null default 0,
 *     uid bigint unsigned not null default 0,
 *     content TEXT,
 *     exinfo BLOB,
 *     ctime timestamp NOT NULL default 0,
 *     unique(mid)
 *   )ENGINE=InnoDB DEFAULT CHARSET=UTF8;
 *   CREATE TABLE kotest_message_list_0(
 *     threadmid bigint unsigned not null default 0,
 *     mid bigint unsigned not null default 0,
 *     ctime timestamp NOT NULL default 0,
 *     unique(threadmid, mid),
 *     index(threadmid, ctime)
 *   )ENGINE=InnoDB DEFAULT CHARSET=UTF8;
 *   CREATE TABLE kotest_message_userlist_0(
 *     uid bigint unsigned not null default 0,
 *     threadmid bigint unsigned not null default 0,
 *     mid bigint unsigned not null default 0,
 *     ctime timestamp NOT NULL default 0,
 *     unique(uid, mid),
 *     index(uid, threadmid, mid),
 *     index(uid, threadmid, ctime)
 *   )ENGINE=InnoDB DEFAULT CHARSET=UTF8;
 *   CREATE TABLE kotest_message_thread_0(
 *     mid bigint unsigned not null default 0,
 *     uids TEXT,
 *     lastinfo BLOB,
 *     unique(mid)
 *   )ENGINE=InnoDB DEFAULT CHARSET=UTF8;
 *   CREATE TABLE kotest_message_userthread_0(
 *     uid bigint unsigned not null default 0,
 *     mid bigint unsigned not null default 0,
 *     lasttime timestamp NOT NULL default 0,
 *     jointime timestamp NOT NULL default 0,
 *     unread int unsigned not null default 0,
 *     unique(uid, mid),
 *     index(uid, lasttime)
 *   )ENGINE=InnoDB DEFAULT CHARSET=UTF8;
 *   CREATE TABLE kotest_message_threaduserlog_0(
 *     mid bigint unsigned not null default 0,
 *     uid bigint unsigned not null default 0,
 *     action tinyint not null default 0,	-- 1 是 join, -1 是 quit
 *     ctime timestamp NOT NULL default 0,
 *     index(mid, ctime)
 *   )ENGINE=InnoDB DEFAULT CHARSET=UTF8;
 *   CREATE TABLE kotest_message_uidsthread_0(
 *     uids varchar(200) not null default '',
 *     mid bigint unsigned not null default 0,
 *     unique(uids)
 *   )ENGINE=InnoDB DEFAULT CHARSET=UTF8;
 * </pre>
 *
 * <b>_aConf 配置</b>
 * @see Ko_Mode_Message::$_aConf
 *
 * @package ko\mode
 * @author zhangchu
 */

/**
 * 实现
 */
class Ko_Mode_Message extends Ko_Busi_Api
{
	/**
	 * 配置数组
	 *
	 * <pre>
	 * array(
	 *   'message' => 消息内容表
	 *   'list' => 消息线的回复列表，要求db_split类型
	 *   'userlist' => 每个用户的消息线回复列表，可选，要求db_split类型
	 *   'thread' => 消息线信息表，可选
	 *   'userthread' => 用户参与的消息线表，可选，要求db_split类型
	 *   'threaduserlog' => 用户参与的消息线的变动表，可选
	 *   'uidsthread' => 通过用户列表获取消息线，可选
	 *   'maxusercount' => 一个消息线最多的参与人数，可选
	 *   'maxusercountformerge' => 参与人数小于等于这个数字的消息线根据用户进行合并，可选
	 * )
	 * </pre>
	 *
	 * @var array
	 */
	protected $_aConf = array();
	
	const DEFAULT_MAXUSERCOUNT = 30;
	const DEFAULT_MAXUSERCOUNTFORMERGE = 5;
	
	/**
	 * 发起会话
	 *
	 * @return int 消息线id，返回0表示失败
	 */
	public function iCreateThread($iUid, $aTo, $sContent, $sExinfo, $sLastinfo, $bForceCreate = false)
	{
		$sUids = $this->_sUidsToString($iUid, $aTo);
		$aUids = $this->_aUidsToArray($sUids);
		$uc = count($aUids);
		if ($uc > $this->_iGetMaxUserCount())
		{	//用户数量超限
			return 0;
		}

		if (!$bForceCreate && $uc <= $this->_iGetMaxUserCountForMerge())
		{	//用户数量满足合并消息的条件
			$iThread = $this->_iGetMergeThread($sUids);
			if ($iThread)
			{	//如果有可以合并的历史消息线，改为回复操作
				return $this->iReplyThread($iUid, $iThread, $sContent, $sExinfo, $sLastinfo) ? $iThread : 0;
			}
		}
		$ctime = date('Y-m-d H:i:s');
		$mid = $this->_iInsertMessage($iUid, $sContent, $sExinfo, $ctime);	//添加消息
		$this->_vInsertList($mid, $mid, $ctime);	//添加到消息线消息列表
		$this->_vInsertUserList($aUids, $mid, $mid, $ctime);	//添加到用户消息线消息列表
		$this->_vInsertThread($mid, $sUids, $sLastinfo);	//添加消息线信息
		
		foreach ($aUids as $uid)
		{	//依次绑定用户与消息线关系
			$this->_bInsertUserThread($uid, $mid, $ctime, ($uid == $iUid) ? 0 : 1);
		}
		
		if ($uc <= $this->_iGetMaxUserCountForMerge())
		{	//记录用户列表和消息线关系
			$this->_vInsertUidsThread($sUids, $mid);
		}
		return $mid;
	}
	
	/**
	 * 回复会话
	 *
	 * @return int 消息id，返回0表示失败
	 */
	public function iReplyThread($iUid, $iThread, $sContent, $sExinfo, $sLastinfo)
	{
		if (isset($this->_aConf['thread']) && isset($this->_aConf['userthread']))
		{
			$threadDao = $this->_aConf['thread'].'Dao';
			$info = $this->$threadDao->aGet($iThread);
			$aUids = $this->_aUidsToArray($info['uids']);
			if (!in_array($iUid, $aUids))
			{	//不是消息线相关用户，不能回复
				return 0;
			}
		}
		$ctime = date('Y-m-d H:i:s');
		$mid = $this->_iInsertMessage($iUid, $sContent, $sExinfo, $ctime);	//添加消息
		$this->_vInsertList($iThread, $mid, $ctime);	//添加到消息线消息列表
		if (isset($this->_aConf['thread']) && isset($this->_aConf['userthread']))
		{
			$this->_vInsertUserList($aUids, $iThread, $mid, $ctime);	//添加到用户消息线消息列表
			$aUpdate = array(
				'lastinfo' => $sLastinfo,
				);
			$this->$threadDao->iUpdate($iThread, $aUpdate);	//更新消息线信息
			foreach ($aUids as $uid)
			{	//依次更新用户与消息线关系的时间戳
				$this->_iUpdateUserThread($uid, $iThread, $ctime, ($uid == $iUid) ? 0 : 1);
			}
		}
		return $mid;
	}
	
	/**
	 * 获取用户之间的历史消息线id
	 *
	 * @return int 消息线id，返回0表示无历史记录
	 */
	public function iGetThread($iUid, $aTo)
	{
		$sUids = $this->_sUidsToString($iUid, $aTo);
		$aUids = $this->_aUidsToArray($sUids);
		$uc = count($aUids);
		if ($uc > $this->_iGetMaxUserCountForMerge()
			|| $uc > $this->_iGetMaxUserCount())
		{	//用户数量超限
			return 0;
		}
		return $this->_iGetMergeThread($sUids);
	}

	/**
	 * 判断用户是否在某个消息线里面
	 *
	 * @return boolean
	 */
	public function bIsUserInThread($iUid, $iThread)
	{
		assert(isset($this->_aConf['thread']));
		
		$threadDao = $this->_aConf['thread'].'Dao';
		$info = $this->$threadDao->aGet($iThread);
		$aUids = $this->_aUidsToArray($info['uids']);
		return in_array($iUid, $aUids);
	}
	
	/**
	 * 用户加入会话
	 *
	 * @return array 真正添加进去的用户列表，有些用户可能已经在消息里面，或者人数达到上限
	 */
	public function aJoinThread($aTo, $iThread)
	{
		assert(isset($this->_aConf['thread']));
		
		$threadDao = $this->_aConf['thread'].'Dao';
		$info = $this->$threadDao->aGet($iThread);
		$aUids = $this->_aUidsToArray($info['uids']);
		$aTo = array_values(array_diff($aTo, $aUids));
		$uc = count($aUids) + count($aTo);
		if ($uc > $this->_iGetMaxUserCount())
		{
			return array();
		}
		
		$ctime = date('Y-m-d H:i:s');
		foreach ($aTo as $uid)
		{
			$this->_bInsertUserThread($uid, $iThread, $ctime, 0);
		}
		if (!empty($aTo))
		{
			$aUpdate = array(
				'uids' => $this->_sUidsToString(0, array_merge($aUids, $aTo)),
				);
			$this->$threadDao->iUpdate($iThread, $aUpdate);
		}
		return $aTo;
	}
	
	/**
	 * 用户删除会话，与离开会话不同，删除会话后，有新消息会导致回话重新出现
	 *
	 * @return boolean
	 */
	public function bDeleteThread($iUid, $iThread)
	{
		$this->_iDeleteUserThread($iUid, $iThread);
		return true;
	}
	
	/**
	 * 用户离开会话
	 *
	 * @return boolean
	 */
	public function bQuitThread($iUid, $iThread)
	{
		assert(isset($this->_aConf['thread']));
		
		$threadDao = $this->_aConf['thread'].'Dao';
		$info = $this->$threadDao->aGet($iThread);
		$aUids = $this->_aUidsToArray($info['uids']);
		$aUpdate = array(
			'uids' => $this->_sUidsToString(0, array_diff($aUids, array($iUid))),
			);
		$this->$threadDao->iUpdate($iThread, $aUpdate);
		
		$this->_iDeleteUserThread($iUid, $iThread);
		return true;
	}

	/**
	 * 用户删除消息，用户看不见了，但是其他参与人还可见
	 *
	 * @return boolean
	 */
	public function bDeleteMessage($iUid, $iMid)
	{
		assert(isset($this->_aConf['userlist']));
		
		$userlistDao = $this->_aConf['userlist'].'Dao';
		$this->$userlistDao->iDelete(array('uid' => $iUid, 'mid' => $iMid));
		return true;
	}
	
	/**
	 * 用户查看会话列表
	 *
	 * @return array
	 */
	public function aGetThreadList($iUid, $iStart, $iNum)
	{
		$oOption = new Ko_Tool_SQL;
		$oOption->oOrderBy('lasttime desc')->oOffset($iStart)->oLimit($iNum);
		return $this->_aGetThreadList($iUid, $oOption);
	}

	/**
	 * 用户查看会话列表
	 *
	 * @return array
	 */
	public function aGetThreadListWithTotal($iUid, &$iTotal, $iStart, $iNum)
	{
		$oOption = new Ko_Tool_SQL;
		$oOption->oOrderBy('lasttime desc')->oOffset($iStart)->oLimit($iNum)->oCalcFoundRows(true);
		$aRet = $this->_aGetThreadList($iUid, $oOption);
		$iTotal = $oOption->iGetFoundRows();
		return $aRet;
	}
	
	/**
	 * 用户查看会话详情
	 *
	 * @return array
	 */
	public function aGetThreadInfo($iUid, $iThread)
	{
		assert(isset($this->_aConf['userthread']) && isset($this->_aConf['thread']));
		
		$userthreadDao = $this->_aConf['userthread'].'Dao';
		$info = $this->$userthreadDao->aGet(array('uid' => $iUid, 'mid' => $iThread));
		if (empty($info))
		{
			return array();
		}
		
		$threadDao = $this->_aConf['thread'].'Dao';
		$thread = $this->$threadDao->aGet($iThread);
		$thread['uids'] = $this->_aUidsToArray($thread['uids']);
		return array_merge($info, $thread);
	}
	
	/**
	 * 用户查看会话消息列表详情，根据消息线id查询
	 *
	 * @return array
	 */
	public function aGetMessageList($iUid, $iThread, $iStart, $iNum, $bAutoRead = true)
	{
		if (isset($this->_aConf['userthread']))
		{
			$userthreadDao = $this->_aConf['userthread'].'Dao';
			$info = $this->$userthreadDao->aGet(array('uid' => $iUid, 'mid' => $iThread));
			if (empty($info))
			{
				return array();
			}
			if ($bAutoRead && $info['unread'])
			{
				$this->$userthreadDao->iUpdate(array('uid' => $iUid, 'mid' => $iThread), array('unread' => 0));
			}
		}
		
		$oOption = new Ko_Tool_SQL;
		if (isset($this->_aConf['userthread']))
		{
			$oOption->oWhere('ctime >= ?', $info['jointime']);
		}
		$oOption->oOrderBy('mid desc')->oOffset($iStart)->oLimit($iNum);
		return $this->_aGetMessageList($iUid, $iThread, $oOption);
	}

	/**
	 * 用户查看会话消息列表详情，根据消息线id查询
	 *
	 * @return array
	 */
	public function aGetMessageListWithTotal($iUid, $iThread, &$iTotal, $iStart, $iNum, $bAutoRead = true)
	{
		if (isset($this->_aConf['userthread']))
		{
			$userthreadDao = $this->_aConf['userthread'].'Dao';
			$info = $this->$userthreadDao->aGet(array('uid' => $iUid, 'mid' => $iThread));
			if (empty($info))
			{
				return array();
			}
			if ($bAutoRead && $info['unread'])
			{
				$this->$userthreadDao->iUpdate(array('uid' => $iUid, 'mid' => $iThread), array('unread' => 0));
			}
		}

		$oOption = new Ko_Tool_SQL;
		if (isset($this->_aConf['userthread']))
		{
			$oOption->oWhere('ctime >= ?', $info['jointime']);
		}
		$oOption->oOrderBy('mid desc')->oOffset($iStart)->oLimit($iNum)->oCalcFoundRows(true);
		$aRet = $this->_aGetMessageList($iUid, $iThread, $oOption);
		$iTotal = $oOption->iGetFoundRows();
		return $aRet;
	}
	
	/**
	 * 用户查看会话消息列表详情，根据参与者查询
	 *
	 * @return array
	 */
	public function aGetMessageListByUsers($iUid, $aTo, $iStart, $iNum, $bAutoRead = true)
	{
		$iThread = $this->iGetThread($iUid, $aTo);
		if (empty($iThread))
		{
			return array();
		}
		return $this->aGetMessageList($iUid, $iThread, $iStart, $iNum, $bAutoRead);
	}
	
	/**
	 * 用户查看会话消息列表详情，根据参与者查询
	 *
	 * @return array
	 */
	public function aGetMessageListByUsersWithTotal($iUid, $aTo, &$iTotal, $iStart, $iNum, $bAutoRead = true)
	{
		$iThread = $this->iGetThread($iUid, $aTo);
		if (empty($iThread))
		{
			return array();
		}
		return $this->aGetMessageListWithTotal($iUid, $iThread, $iTotal, $iStart, $iNum, $bAutoRead);
	}
	
	/**
	 * 用户查看会话消息列表详情，根据最大id查询
	 *
	 * @return array
	 */
	public function aGetMessageListByMaxmid($iUid, $iThread, $iMaxmid, $iNum, $bAutoRead = false)
	{
		if (isset($this->_aConf['userthread']))
		{
			$userthreadDao = $this->_aConf['userthread'].'Dao';
			$info = $this->$userthreadDao->aGet(array('uid' => $iUid, 'mid' => $iThread));
			if (empty($info))
			{
				return array();
			}
			//和 ByMinmid 不同，这个接口获取的是历史消息，所以默认不需要清除未读计数
			if ($bAutoRead && $info['unread'])
			{
				$this->$userthreadDao->iUpdate(array('uid' => $iUid, 'mid' => $iThread), array('unread' => 0));
			}
		}
		
		$oOption = new Ko_Tool_SQL;
		if (isset($this->_aConf['userthread']))
		{
			$oOption->oWhere('ctime >= ?', $info['jointime']);
		}
		$oOption->oAnd('mid < ?', $iMaxmid)->oOrderBy('mid desc')->oLimit($iNum);
		return $this->_aGetMessageList($iUid, $iThread, $oOption);
	}
	
	/**
	 * 用户查看会话消息列表详情，根据最小id查询
	 *
	 * @return array
	 */
	public function aGetMessageListByMinmid($iUid, $iThread, $iMinmid, $iNum, $bAutoRead = true)
	{
		if (isset($this->_aConf['userthread']))
		{
			$userthreadDao = $this->_aConf['userthread'].'Dao';
			$info = $this->$userthreadDao->aGet(array('uid' => $iUid, 'mid' => $iThread));
			if (empty($info))
			{
				return array();
			}
			if ($bAutoRead && $info['unread'])
			{
				$this->$userthreadDao->iUpdate(array('uid' => $iUid, 'mid' => $iThread), array('unread' => 0));
			}
		}
		
		$oOption = new Ko_Tool_SQL;
		if (isset($this->_aConf['userthread']))
		{
			$oOption->oWhere('ctime >= ?', $info['jointime']);
		}
		$oOption->oAnd('mid > ?', $iMinmid)->oOrderBy('mid desc')->oLimit($iNum);
		return $this->_aGetMessageList($iUid, $iThread, $oOption);
	}
	
	private function _aGetThreadList($iUid, $oOption)
	{
		$list = $this->_aGetUserThreadList($iUid, $oOption);
		if (!empty($list))
		{
			$threaddetail = $this->_aGetThreadDetails($list);
			foreach ($list as &$item)
			{
				$item = array_merge($item, $threaddetail[$item['mid']]);
				$item['uids'] = $this->_aUidsToArray($item['uids']);
			}
			unset($item);
		}
		return $list;
	}
	
	private function _aGetUserThreadList($iUid, $oOption)
	{
		assert(isset($this->_aConf['userthread']));
		
		$userthreadDao = $this->_aConf['userthread'].'Dao';
		return $this->$userthreadDao->aGetList($iUid, $oOption);
	}
	
	private function _aGetThreadDetails($aList)
	{
		assert(isset($this->_aConf['thread']));
		
		$threadDao = $this->_aConf['thread'].'Dao';
		return $this->$threadDao->aGetListByKeys($aList);
	}
	
	private function _aGetMessageList($iUid, $iThread, $oOption)
	{
		$list = $this->_aGetListList($iUid, $iThread, $oOption);
		if (!empty($list))
		{
			$messageDetail = $this->_aGetMessageDetails($list);
			foreach ($list as &$item)
			{
				$item = array_merge($item, $messageDetail[$item['mid']]);
			}
			unset($item);
		}
		return $list;
	}
	
	private function _aGetListList($iUid, $iThread, $oOption)
	{
		if (isset($this->_aConf['userlist']))
		{
			$userlistDao = $this->_aConf['userlist'].'Dao';
			$oOption->oAnd('threadmid = ?', $iThread);
			return $this->$userlistDao->aGetList($iUid, $oOption);
		}
		
		$listDao = $this->_aConf['list'].'Dao';
		return $this->$listDao->aGetList($iThread, $oOption);
	}
	
	private function _aGetMessageDetails($aList)
	{
		$messageDao = $this->_aConf['message'].'Dao';
		return $this->$messageDao->aGetListByKeys($aList);
	}
	
	private function _iInsertMessage($iUid, $sContent, $sExinfo, $sCtime)
	{
		$messageDao = $this->_aConf['message'].'Dao';
		$aData = array(
			'uid' => $iUid,
			'content' => $sContent,
			'exinfo' => $sExinfo,
			'ctime' => $sCtime,
			);
		return $this->$messageDao->iInsert($aData);
	}
	
	private function _vInsertList($iThread, $iMid, $sCtime)
	{
		$listDao = $this->_aConf['list'].'Dao';
		$aData = array(
			'threadmid' => $iThread,
			'mid' => $iMid,
			'ctime' => $sCtime,
			);
		$this->$listDao->aInsert($aData);
	}
	
	private function _vInsertUserList($aUids, $iThread, $iMid, $sCtime)
	{
		if (isset($this->_aConf['userlist']))
		{
			$userlistDao = $this->_aConf['userlist'].'Dao';
			foreach ($aUids as $iUid)
			{
				$aData = array(
					'uid' => $iUid,
					'threadmid' => $iThread,
					'mid' => $iMid,
					'ctime' => $sCtime,
					);
				$this->$userlistDao->aInsert($aData);
			}
		}
	}
	
	private function _vInsertThread($iThread, $sUids, $sLastinfo)
	{
		assert(isset($this->_aConf['thread']));
		
		$threadDao = $this->_aConf['thread'].'Dao';
		$aData = array(
			'mid' => $iThread,
			'uids' => $sUids,
			'lastinfo' => $sLastinfo,
			);
		$this->$threadDao->aInsert($aData);
	}
	
	private function _bInsertUserThread($iUid, $iThread, $sLasttime, $iUnread)
	{
		assert(isset($this->_aConf['userthread']));
		
		try
		{
			$aData = array(
				'uid' => $iUid,
				'mid' => $iThread,
				'lasttime' => $sLasttime,
				'jointime' => $sLasttime,
				'unread' => $iUnread,
				);
			$userthreadDao = $this->_aConf['userthread'].'Dao';
			$this->$userthreadDao->aInsert($aData);
			$this->_vInsertThreadUserLog($iThread, $iUid, 1, $sLasttime);
		}
		catch(Exception $e)
		{
			return false;
		}
		return true;
	}
	
	private function _iUpdateUserThread($iUid, $iThread, $sLasttime, $iUnread)
	{
		assert(isset($this->_aConf['userthread']));
		
		$aData = array(
			'uid' => $iUid,
			'mid' => $iThread,
			'lasttime' => $sLasttime,
			'jointime' => $sLasttime,
			'unread' => $iUnread,
			);
		$aUpdate = array(
			'lasttime' => $sLasttime,
			);
		$aChange = $iUnread ? array('unread' => $iUnread) : array();
		$userthreadDao = $this->_aConf['userthread'].'Dao';
		$this->$userthreadDao->aInsert($aData, $aUpdate, $aChange);
		return 1;
	}
	
	private function _iDeleteUserThread($iUid, $iThread)
	{
		assert(isset($this->_aConf['userthread']));
		
		$userthreadDao = $this->_aConf['userthread'].'Dao';
		$ret = $this->$userthreadDao->iDelete(array('uid' => $iUid, 'mid' => $iThread));
		if ($ret)
		{
			$this->_vInsertThreadUserLog($iThread, $iUid, -1, date('Y-m-d H:i:s'));
		}
		return $ret;
	}
	
	private function _vInsertThreadUserLog($iThread, $iUid, $iAction, $sCtime)
	{
		if (isset($this->_aConf['threaduserlog']))
		{
			$aData = array(
				'mid' => $iThread,
				'uid' => $iUid,
				'action' => $iAction,
				'ctime' => $sCtime,
				);
			$threaduserlogDao = $this->_aConf['threaduserlog'].'Dao';
			$this->$threaduserlogDao->aInsert($aData);
		}
	}
	
	private function _iGetMergeThread($sUids)
	{
		if (isset($this->_aConf['uidsthread']) && isset($this->_aConf['thread']))
		{
			$uidsthreadDao = $this->_aConf['uidsthread'].'Dao';
			$info = $this->$uidsthreadDao->aGet($sUids);
			if (!empty($info))
			{
				$threadDao = $this->_aConf['thread'].'Dao';
				$threadinfo = $this->$threadDao->aGet($info['mid']);
				if ($threadinfo['uids'] == $sUids)
				{
					return $info['mid'];
				}
			}
		}
		return 0;
	}
	
	private function _vInsertUidsThread($sUids, $iThread)
	{
		if (isset($this->_aConf['uidsthread']))
		{
			$uidsthreadDao = $this->_aConf['uidsthread'].'Dao';
			$aData = array(
				'uids' => $sUids,
				'mid' => $iThread,
				);
			$aUpdate = array(
				'mid' => $iThread,
				);
			$this->$uidsthreadDao->aInsert($aData, $aUpdate);
		}
	}
	
	private function _sUidsToString($iUid, $aTo)
	{
		$aTo[] = $iUid;
		$aTo = array_unique($aTo);
		$aTo = array_diff($aTo, array(0, ''));
		sort($aTo, SORT_NUMERIC);
		return implode(' ', $aTo);
	}
	
	private function _aUidsToArray($sUids)
	{
		if ('' == $sUids)
		{
			return array();
		}
		return explode(' ', $sUids);
	}
	
	private function _iGetMaxUserCount()
	{
		if (isset($this->_aConf['maxusercount']))
		{
			return $this->_aConf['maxusercount'];
		}
		return self::DEFAULT_MAXUSERCOUNT;
	}
	
	private function _iGetMaxUserCountForMerge()
	{
		if (isset($this->_aConf['maxusercountformerge']))
		{
			return $this->_aConf['maxusercountformerge'];
		}
		return self::DEFAULT_MAXUSERCOUNTFORMERGE;
	}
}

?>