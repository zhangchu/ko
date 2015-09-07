<?php
/**
 * Sysmsg
 *
 * CREATE TABLE `sysmsg_content` (
 *   `msgid` bigint(20) unsigned NOT NULL auto_increment,
 *   `msgtype` int(11) unsigned NOT NULL DEFAULT '0',
 *   `content` blob,
 *   `ctime` timestamp NOT NULL DEFAULT 0,
 *   `uid` int(11) unsigned NOT NULL DEFAULT '0',
 *   UNIQUE KEY `msgid` (`msgid`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 *
 * CREATE TABLE `sysmsg_user` (
 *   `uid` int(11) unsigned NOT NULL DEFAULT '0',
 *   `msgid` bigint(20) unsigned NOT NULL DEFAULT '0',
 *   `msgtype` int(11) unsigned NOT NULL DEFAULT '0',
 *   `stime` timestamp NOT NULL DEFAULT 0,
 *   `unread` int(11) unsigned NOT NULL DEFAULT '1',
 *   UNIQUE KEY `uid` (`uid`,`msgid`),
 *   KEY `uid_msgtype_stime` (`uid`,`msgtype`,`stime`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 *
 * CREATE TABLE `sysmsg_merge` (
 *   `uid` int(11) unsigned NOT NULL DEFAULT '0',
 *   `msgtype` int(11) unsigned NOT NULL DEFAULT '0',
 *   `mergeid` int(11) unsigned NOT NULL DEFAULT '0',
 *   `msgid` bigint(20) unsigned NOT NULL DEFAULT '0',
 *   UNIQUE KEY `uid` (`uid`,`msgtype`,`mergeid`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 *
 * CREATE TABLE sysmsg_masstag (
 *   masstag varchar(100) not null default '',
 *   msgids varchar(500) not null default '',
 *   unique (masstag)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 *
 * CREATE TABLE sysmsg_masstag_user (
 *   `uid` int(11) unsigned NOT NULL DEFAULT '0',
 *   masstags text not null default '',
 *   unique (uid)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 *
 * @package ko\Mode
 * @author zhangchu
 */

class Ko_Mode_Sysmsg extends Ko_Busi_Api
{
	/**
	 * 配置数组
	 *
	 * <pre>
	 * array(
	 *   'content' =>        内容表
	 *   'user' =>           用户索引表
	 *   'merge' =>          合并消息表，可选
	 *   'masstag' =>        指定群发消息列表，记录指定群发集合对应的消息ID列表，可选
	 *   ‘usermasstag' =>    用户接收群发消息状态，记录用户订阅了那些群发消息，以及接收到的最大ID，可选
	 *   'kind' => array(    消息类型组配置
	 *     kindname => array(typeid1, typeid2, ...),
	 *     ...
	 *   ),
	 * )
	 * </pre>
	 *
	 * @var array
	 */
	protected $_aConf = array();

	public function iSend($uid, $msgtype, array $content, $mergeid = null)
	{
		$msgid = 0;
		if (null !== $mergeid)
		{
			$mergekey = compact('uid', 'msgtype', 'mergeid');
			$mergeDao = $this->_aConf['merge'].'Dao';
			$mergeinfo = $this->$mergeDao->aGet($mergekey);
			$msgid = $mergeinfo['msgid'];
		}

		$ctime = date('Y-m-d H:i:s');
		$content = Ko_Tool_Enc::SEncode($content);
		$data = compact('msgtype', 'content', 'ctime', 'uid');
		$update = array();
		if ($msgid)
		{
			$data['msgid'] = $msgid;
			$update['content'] = $content;
			$update['ctime'] = $ctime;
		}
		$contentDao = $this->_aConf['content'].'Dao';
		$id = $this->$contentDao->iInsert($data, $update);
		$msgid = $id ? $id : $msgid;

		if (null !== $mergeid && empty($mergeinfo))
		{
			$data = compact('uid', 'msgtype', 'mergeid', 'msgid');
			$update = compact('msgid');
			$this->$mergeDao->aInsert($data, $update);
		}

		$data = compact('uid', 'msgid', 'msgtype');
		$data['stime'] = $ctime;
		$update = array(
			'stime' => $ctime,
		);
		$change = array(
			'unread' => 1,
		);
		$userDao = $this->_aConf['user'].'Dao';
		$this->$userDao->aInsert($data, $update, $change);

		return $msgid;
	}

	public function aGet($uid, $msgid)
	{
		$msgkey = compact('uid', 'msgid');
		$userDao = $this->_aConf['user'].'Dao';
		$info = $this->$userDao->aGet($msgkey);
		if (!empty($info))
		{
			$contentDao = $this->_aConf['content'].'Dao';
			$msginfo = $this->$contentDao->aGet($msgid);
			$info = array_merge($msginfo, $info);
			$info['content'] = Ko_Tool_Enc::ADecode($info['content']);
		}
		return $info;
	}

	public function bChange($uid, $msgid, array $content)
	{
		$msgkey = compact('uid', 'msgid');
		$userDao = $this->_aConf['user'].'Dao';
		$info = $this->$userDao->aGet($msgkey);
		if (!empty($info))
		{
			$contentDao = $this->_aConf['content'].'Dao';
			$msginfo = $this->$contentDao->aGet($msgid);
			if ($uid == $msginfo['uid'])
			{
				$update = array(
					'content' => Ko_Tool_Enc::SEncode($content),
				);
				$this->$contentDao->iUpdate($msgid, $update);
				return true;
			}
		}
		return false;
	}

	public function vRead($uid, $msgids)
	{
		if (!is_array($msgids))
		{
			$msgids = array($msgids);
		}
		$userDao = $this->_aConf['user'].'Dao';
		foreach ($msgids as $msgid)
		{
			$msgkey = compact('uid', 'msgid');
			$info = $this->$userDao->aGet($msgkey);
			if ($info['unread'])
			{
				$update = array(
					'unread' => 0,
				);
				$this->$userDao->iUpdate($msgkey, $update);
			}
		}
	}

	public function vDelete($uid, $msgid)
	{
		$msgkey = compact('uid', 'msgid');
		$userDao = $this->_aConf['user'].'Dao';
		$this->$userDao->iDelete($msgkey);
	}

	public function aGetList($uid, $kind, $start = 0, $num = 10)
	{
		$msgtypes = $this->_aKind2MsgTypes($kind);
		$userDao = $this->_aConf['user'].'Dao';
		$option = new Ko_Tool_SQL();
		$option->oWhere('msgtype in (?)', $msgtypes)->oOrderBy('stime desc')->oOffset($start)->oLimit($num);
		$splitField = $this->$userDao->sGetSplitField();
		if (strlen($splitField))
		{
			$list = $this->$userDao->aGetList($uid, $option);
		}
		else
		{
			$option->oAnd('uid = ?', $uid);
			$list = $this->$userDao->aGetList($option);
		}
		$contentDao = $this->_aConf['content'].'Dao';
		$msginfos = $this->$contentDao->aGetListByKeys($list);
		foreach ($list as &$v)
		{
			$v = array_merge($msginfos[$v['msgid']], $v);
			$v['content'] = Ko_Tool_Enc::ADecode($v['content']);
		}
		unset($v);
		return $list;
	}

	public function aGetListSeq($uid, $kind, $boundary, $num, &$next, &$next_boundary)
	{
		$msgtypes = $this->_aKind2MsgTypes($kind);
		$userDao = $this->_aConf['user'].'Dao';
		$option = new Ko_Tool_SQL();
		$option->oWhere('msgtype in (?)', $msgtypes)->oOrderBy('stime desc, msgid desc')->oLimit($num+1);
		list($boundary_stime, $boundary_msgid) = explode('_', $boundary);
		if ($boundary_msgid)
		{
			$option->oAnd('stime < ? or (stime = ? and msgid < ?)', $boundary_stime, $boundary_stime, $boundary_msgid);
		}
		$splitField = $this->$userDao->sGetSplitField();
		if (strlen($splitField))
		{
			$list = $this->$userDao->aGetList($uid, $option);
		}
		else
		{
			$option->oAnd('uid = ?', $uid);
			$list = $this->$userDao->aGetList($option);
		}
		$next = 0;
		if ($count = count($list)) {
			if (count($list) > $num) {
				$next = array_pop($list);
				$count --;
				$next = $next['msgid'];
			}
		}
		$contentDao = $this->_aConf['content'].'Dao';
		$msginfos = $this->$contentDao->aGetListByKeys($list);
		foreach ($list as $k => &$v)
		{
			$v = array_merge($msginfos[$v['msgid']], $v);
			$v['content'] = Ko_Tool_Enc::ADecode($v['content']);
			if ($k == $count - 1) {
				$next_boundary = $v['stime'].'_'.$v['msgid'];
			}
		}
		unset($v);
		return $list;
	}

	public function vDeleteByKind($uid, $kind)
	{
		$msgtypes = $this->_aKind2MsgTypes($kind);
		$userDao = $this->_aConf['user'].'Dao';
		$option = new Ko_Tool_SQL();
		$option->oWhere('msgtype in (?)', $msgtypes);
		$splitField = $this->$userDao->sGetSplitField();
		if (strlen($splitField))
		{
			$this->$userDao->iDeleteByCond($uid, $option);
		}
		else
		{
			$option->oAnd('uid = ?', $uid);
			$this->$userDao->iDeleteByCond($option);
		}
	}

	public function iSend2Mass($msgtype, array $content, $masstag = 'all')
	{
		$ctime = date('Y-m-d H:i:s');
		$content = Ko_Tool_Enc::SEncode($content);
		$data = compact('msgtype', 'content', 'ctime');
		$contentDao = $this->_aConf['content'].'Dao';
		$msgid = $this->$contentDao->iInsert($data);

		$masstagDao = $this->_aConf['masstag'].'Dao';
		$masstaginfo = $this->$masstagDao->aGet($masstag);
		if (false === ($masstagmsgids = unserialize($masstaginfo['msgids'])))
		{
			$masstagmsgids = array();
		}
		$masstagmsgids[] = $msgid;
		$masstagmsgids = array_slice($masstagmsgids, -5);
		$update = array(
			'msgids' => serialize($masstagmsgids),
		);
		$this->$masstagDao->iUpdate($masstag, $update);
		return $msgid;
	}

	public function vCancelMass($msgid, $masstag = 'all')
	{
		$masstagDao = $this->_aConf['masstag'].'Dao';
		$masstaginfo = $this->$masstagDao->aGet($masstag);
		if (false === ($masstagmsgids = unserialize($masstaginfo['msgids'])))
		{
			return;
		}
		$masstagmsgids = array_values(array_diff($masstagmsgids, array($msgid)));
		$update = array(
			'msgids' => serialize($masstagmsgids),
		);
		$this->$masstagDao->iUpdate($masstag, $update);
	}

	public function vPull($uid)
	{
		$usermasstagDao = $this->_aConf['usermasstag'].'Dao';
		$usermasstaginfo = $this->$usermasstagDao->aGet($uid);
		if (false === ($usermasstag = unserialize($usermasstaginfo['masstags'])))
		{
			$usermasstag = array();
		}
		if (!isset($usermasstag['all']))
		{
			$usermasstag['all'] = 0;
		}
		$masstags = array_keys($usermasstag);
		$masstagDao = $this->_aConf['masstag'].'Dao';
		$masstaginfos = $this->$masstagDao->aGetListByKeys($masstags);
		$usermsgids = $new_usermasstag = array();
		foreach ($masstaginfos as $masstaginfo)
		{
			if (false === ($masstagmsgids = unserialize($masstaginfo['msgids'])))
			{
				$masstagmsgids = array();
			}
			foreach ($masstagmsgids as $msgid)
			{
				if ($msgid > $usermasstag[$masstaginfo['masstag']])
				{
					$usermsgids[] = $msgid;
					$new_usermasstag[$masstaginfo['masstag']] = $msgid;
				}
			}
		}
		if (!empty($usermsgids))
		{
			$contentDao = $this->_aConf['content'].'Dao';
			$msginfos = $this->$contentDao->aGetListByKeys($usermsgids);
			$userDao = $this->_aConf['user'].'Dao';
			foreach ($msginfos as $msginfo)
			{
				$data = array(
					'uid' => $uid,
					'msgid' => $msginfo['msgid'],
					'msgtype' => $msginfo['msgtype'],
					'stime' => date('Y-m-d H:i:s'),
				);
				$update = array(
					'stime' => date('Y-m-d H:i:s'),
				);
				$this->$userDao->aInsert($data, $update);
			}
			foreach ($new_usermasstag as $masstag => $msgid)
			{
				$usermasstag[$masstag] = $msgid;
				$update = array(
					'masstags' => serialize($usermasstag),
				);
				$this->$usermasstagDao->iUpdate($uid, $update);
			}
		}
	}

	public function vBindUserMassTag($uid, $masstag)
	{
		$usermasstagDao = $this->_aConf['usermasstag'].'Dao';
		$usermasstaginfo = $this->$usermasstagDao->aGet($uid);
		if (false === ($usermasstag = unserialize($usermasstaginfo['masstags'])))
		{
			$usermasstag = array();
		}
		if (!isset($usermasstag[$masstag]))
		{
			$masstagDao = $this->_aConf['masstag'].'Dao';
			$masstaginfo = $this->$masstagDao->aGet($masstag);
			if (false === ($masstagmsgids = unserialize($masstaginfo['msgids'])))
			{
				$masstagmsgids = array(0);
			}
			$usermasstag[$masstag] = max($masstagmsgids);
			$update = array(
				'masstags' => serialize($usermasstag),
			);
			$this->$usermasstagDao->iUpdate($uid, $update);
		}
	}

	public function vUnbindUserMassTag($uid, $masstag)
	{
		$usermasstagDao = $this->_aConf['usermasstag'].'Dao';
		$usermasstaginfo = $this->$usermasstagDao->aGet($uid);
		if (false === ($usermasstag = unserialize($usermasstaginfo['masstags'])))
		{
			$usermasstag = array();
		}
		if (isset($usermasstag[$masstag]))
		{
			unset($usermasstag[$masstag]);
			$update = array(
				'masstags' => serialize($usermasstag),
			);
			$this->$usermasstagDao->iUpdate($uid, $update);
		}
	}

	private function _aKind2MsgTypes($kind)
	{
		return isset($this->_aConf['kind'][$kind]) ? $this->_aConf['kind'][$kind] : array();
	}
}
