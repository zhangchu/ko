<?php
/**
 * 使用说明
 *
 * <b>简介</b>
 * <pre>
 *   通用的评论数据操作
 * </pre>
 *
 * <b>数据库例表</b>
 * <pre>
 *   一个自增长值用来唯一标示内容
 *   insert into idgenerator (kind, last_id) values('zhangchut', 1);
 * </pre>
 * <pre>
 *   内容表
 *   CREATE TABLE s_zhangchu_ocmt_content_0 (
 *     oid bigint(20) NOT NULL default '0',
 *     cid bigint(20) NOT NULL default '0',
 *     thread_cid bigint(20) NOT NULL default '0',
 *     uid bigint(20) NOT NULL default '0',
 *     content blob NOT NULL,
 *     ctime timestamp NOT NULL default 0,
 *     PRIMARY KEY (oid,cid)
 *   ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
 * </pre>
 * <pre>
 *   主线索引表
 *   CREATE TABLE s_zhangchu_ocmt_index_0 (
 *     oid bigint(20) NOT NULL default '0',
 *     cid bigint(20) NOT NULL default '0',
 *     PRIMARY KEY (oid,cid)
 *   ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
 * </pre>
 * <pre>
 *   回复索引表
 *   CREATE TABLE s_zhangchu_ocmt_reply_0 (
 *     oid bigint(20) NOT NULL default '0',
 *     cid bigint(20) NOT NULL default '0',
 *     thread_cid bigint(20) NOT NULL default '0',
 *     PRIMARY KEY (oid,cid),
 *     KEY (oid,thread_cid)
 *   ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
 * </pre>
 * <pre>
 *   审核队列表
 *   CREATE TABLE s_zhangchu_ocmt_queue (
 *     oid bigint(20) NOT NULL default '0',
 *     cid bigint(20) NOT NULL default '0',
 *     thread_cid bigint(20) NOT NULL default '0',
 *     PRIMARY KEY (oid,cid),
 *     KEY (cid)
 *   ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
 * </pre>
 * <pre>
 *   缓存标记表
 *   CREATE TABLE s_zhangchu_ocmt_cacheflag_0 (
 *     oid bigint(20) NOT NULL default '0',
 *     status int not null default 0,
 *     PRIMARY KEY (oid)
 *   ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
 * </pre>
 * <pre>
 *   缓存内容表
 *   CREATE TABLE s_zhangchu_ocmt_cache_0 (
 *     oid bigint(20) NOT NULL default '0',
 *     cache mediumblob not null default '',
 *     PRIMARY KEY (oid)
 *   ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
 * </pre>
 * <pre>
 *   操作记录表
 *   CREATE TABLE s_zhangchu_ocmt_action_0 (
 *     oid bigint(20) NOT NULL default '0',
 *     cid bigint(20) NOT NULL default '0',
 *     thread_cid bigint(20) NOT NULL default '0',
 *     action tinyint(2) NOT NULL default '0',
 *     admin blob NOT NULL default '',
 *     ip varchar(60) NOT NULL default '',
 *     ctime timestamp NOT NULL default 0,
 *     KEY (oid,cid)
 *   ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
 * </pre>
 *
 * <b>_aConf 配置</b>
 * @see Ko_Mode_OCMT::$_aConf
 *
 * @package ko\mode
 * @author zhangchu
 */

//include_once('../ko.class.php');

/**
 * 通用的评论数据
 */
class Ko_Mode_OCMT extends Ko_Busi_Api
{
	const ACT_DELETE = -1;
	const ACT_INSERT = 1;

	const AUDIT_THEN_POST = -1;			// 先审后发
	const POST_DIRECT = 0;				// 无需审核
	const POST_THEN_AUDIT = 1;			// 先发后审

	const FORCE_AUDIT = -1;				// 强制审核
	const FORCE_AUTO = 0;				// 没有强制
	const FORCE_POST = 1;				// 强制发布

	const MAX_REPLY = 1000;

	/**
	 * 配置数组
	 *
	 * <pre>
	 * array(
	 *   'audit' => array 不同的 aid 对应的审核模式
	 *   'audit_default' => 缺省的审核模式
	 *   'content' => 内容表 Dao 名称，要求db_split类型
	 *   'index' => 主线索引表 Dao 名称，要求db_split类型
	 *   'reply' => 回复索引表 Dao 名称，要求db_split类型
	 *   'queue' => 审核队列表 Dao 名称，要求db_split类型
	 *   'cacheflag' => 缓存标记表 Dao 名称
	 *   'cache' => 缓存内容表 Dao 名称
	 *   'action' => 操作记录表 Dao 名称
	 *   'mc' => 缓存内容 memcache Dao 名称
	 * )
	 * </pre>
	 *
	 * @var array
	 */
	protected $_aConf = array();

	/**
	 * 查询单条评论信息
	 * @return array
	 */
	public function aGet($iAid, $iBid, $iCid, $bReply=false)
	{
		$oid = $this->_iGetOid($iAid, $iBid);
		$contentDao = $this->_aConf['content'].'Dao';
		$key = array('oid' => $oid, 'cid' => $iCid);
		$aContent = $this->$contentDao->aGet($key);
		if ($bReply && !empty($aContent))
		{
			$aContent['replylist'] = array();
			$replyDao = $this->_aConf['reply'].'Dao';
			$oOption = new Ko_Tool_SQL;
			$oOption->oWhere('thread_cid = ?', $iCid)->oOrderBy('cid desc')->oLimit(self::MAX_REPLY);
			$aReply = $this->$replyDao->aGetList($oid, $oOption);
			if (!empty($aReply))
			{
				$aReplyCid = Ko_Tool_Utils::AObjs2ids($aReply, 'cid');
				$splitField = $this->$contentDao->sGetSplitField();
				if (strlen($splitField))
				{
					$aReplyContent = $this->$contentDao->aGetListByKeys($oid, $aReplyCid);
				}
				else
				{
					$aReplyContent = $this->$contentDao->aGetDetails($aReply);
				}
				$aReplyCid = array_reverse($aReplyCid);
				foreach ($aReplyCid as $v)
				{
					$aContent['replylist'][] = $aReplyContent[$v];
				}
			}
		}
		return $aContent;
	}

	/**
	 * 查询若干指定 cid 的评论列表，使用这个函数通常是应用在 OCMT 外部进行了一次封装，这样应用可以进行筛选或排序规则的开发
	 * @return array
	 */
	public function aGetListByCid($iAid, $iBid, $aCid, $bReply=false)
	{
		$oid = $this->_iGetOid($iAid, $iBid);
		return $this->_aGetContentByIndex($oid, $aCid, $bReply);
	}

	/**
	 * 查询评论列表，支持翻页
	 * @return array
	 */
	public function aGetList($iAid, $iBid, $oOption, $bAsc=false, $bReply=false)
	{
		$oid = $this->_iGetOid($iAid, $iBid);
		if (0 == $oOption->iOffset())
		{
			$info = $this->_aLoadCache($oid);
			if (!empty($info) && !empty($info['para']))
			{
				$oOption->vSetFoundRows($info['total']);
				return $info['data'];
			}
		}
		return $this->_aGetList($oid, $oOption, $bAsc, $bReply);
	}

	/**
	 * 更新所有需要更新的缓存
	 */
	public function vRebuildAllCache()
	{
		$cacheflagDao = $this->_aConf['cacheflag'].'Dao';
		$sql = 'select * from '.$this->$cacheflagDao->sGetTableName().' limit 1000';
		$this->$cacheflagDao->vDoFetchSelect($sql, array($this, 'vRebuildCache_Callback'));
	}

	public function vRebuildCache_Callback($aInfo, $iNo)
	{
		$info = $this->_aLoadCache($aInfo['oid']);
		if (!empty($info) && !empty($info['para']))
		{
			$oOption = new Ko_Tool_SQL;
			$oOption->oLimit($info['para']['num']);
			$this->_aGetList($aInfo['oid'], $oOption, $info['para']['asc'], $info['para']['reply']);
		}
		$cacheflagDao = $this->_aConf['cacheflag'].'Dao';
		$oOption = new Ko_Tool_SQL;
		$oOption->oWhere('status = ?', $aInfo['status']);
		$this->$cacheflagDao->iDelete($aInfo['oid'], $oOption);
	}

	/**
	 * 查询指定对象的审核列表
	 * @return array
	 */
	public function aGetQueueList($iAid, $iBid, $oOption, $bAsc=false)
	{
		$oid = $this->_iGetOid($iAid, $iBid);

		$queueDao = $this->_aConf['queue'].'Dao';
		$oOption->oCalcFoundRows(true)->oOrderBy('cid '.($bAsc ? 'asc' : 'desc'));
		return $this->$queueDao->aGetList($oid, $oOption);
	}

	/**
	 * 查询全部审核列表
	 * @return array
	 */
	public function aGetAllQueueList($oOption, $bAsc=false)
	{
		$queueDao = $this->_aConf['queue'].'Dao';
		$oOption->oCalcFoundRows(true)->oOrderBy('cid '.($bAsc ? 'asc' : 'desc'));
		return $this->$queueDao->aGetList($oOption);
	}

	/**
	 * 发表评论
	 * @return int
	 */
	public function iComment($iAid, $iBid, $iUid, $sContent, $vAdmin='', $iForceFlag=Ko_Mode_OCMT::FORCE_AUTO)
	{
		return $this->_iInsertContentEx($iAid, $iBid, 0, $iUid, $sContent, $vAdmin, $iForceFlag);
	}

	/**
	 * 发表回复
	 * @return int
	 */
	public function iReply($iAid, $iBid, $iThread, $iUid, $sContent, $vAdmin='', $iForceFlag=Ko_Mode_OCMT::FORCE_AUTO)
	{
		return $this->_iInsertContentEx($iAid, $iBid, $iThread, $iUid, $sContent, $vAdmin, $iForceFlag);
	}

	/**
	 * 删除评论
	 */
	public function vDeleteComment($iAid, $iBid, $iCid, $vAdmin='')
	{
		$oid = $this->_iGetOid($iAid, $iBid);
		$this->vDenyComment($oid, $iCid, $vAdmin);
	}

	/**
	 * 删除回复
	 */
	public function vDeleteReply($iAid, $iBid, $iCid, $vAdmin='')
	{
		$oid = $this->_iGetOid($iAid, $iBid);
		$this->vDenyReply($oid, $iCid, $vAdmin);
	}

	/**
	 * 审核通过评论
	 */
	public function vPassComment($iOid, $iCid, $vAdmin='')
	{
		$this->_vInsertIndex($iOid, $iCid, $vAdmin);
		$this->_iDeleteIndexEx($iOid, $iCid, 'queue');
	}

	/**
	 * 审核通过回复
	 */
	public function vPassReply($iOid, $iCid, $iThread, $vAdmin='')
	{
		$this->_vInsertReply($iOid, $iCid, $iThread, $vAdmin);
		$this->_iDeleteIndexEx($iOid, $iCid, 'queue');
	}

	/**
	 * 审核删除评论
	 */
	public function vDenyComment($iOid, $iCid, $vAdmin='')
	{
		$this->_vDeleteIndex($iOid, $iCid, $vAdmin);
		$this->_iDeleteIndexEx($iOid, $iCid, 'queue');
	}

	/**
	 * 审核删除回复
	 */
	public function vDenyReply($iOid, $iCid, $vAdmin='')
	{
		$this->_vDeleteReply($iOid, $iCid, $vAdmin);
		$this->_iDeleteIndexEx($iOid, $iCid, 'queue');
	}

	/**
	 * 通过 Aid 和 Bid 获取 Oid
	 * @return int
	 */
	public function iGetOid($iAid, $iBid)
	{
		return $this->_iGetOid($iAid, $iBid);
	}

	private function _aGetList($iOid, $oOption, $bAsc, $bReply)
	{
		$indexDao = $this->_aConf['index'].'Dao';
		$oOption->oCalcFoundRows(true)->oOrderBy('cid '.($bAsc ? 'asc' : 'desc'));
		$aIndex = $this->$indexDao->aGetList($iOid, $oOption);
		$aIndexCid = Ko_Tool_Utils::AObjs2ids($aIndex, 'cid');
		$info = $this->_aGetContentByIndex($iOid, $aIndexCid, $bReply);
		if (0 == $oOption->iOffset())
		{
			$data = array(
				'total' => $oOption->iGetFoundRows(),
				'data' => $info,
				'para' => array(
					'asc' => $bAsc,
					'num' => $oOption->iLimit(),
					'reply' => $bReply,
					),
				);
			$this->_vSaveCache($iOid, $data);
		}
		return $info;
	}

	private function _aGetContentByIndex($iOid, $aCid, $bReply=false)
	{
		$aReplyCid = $bReply ? $this->_aGetReplyCids($iOid, $aCid) : array();
		$aAllCid = array_merge($aCid, $aReplyCid);
		$aContent = $this->_aGetContents($iOid, $aAllCid);

		$aRet = $aIndexMap = array();
		foreach ($aCid as $k => $cid)
		{
			$aRet[$k] = $aContent[$cid];
			if ($bReply)
			{
				$aIndexMap[$cid] = $k;
				$aRet[$k]['replylist'] = array();
			}
		}
		if ($bReply)
		{
			sort($aAllCid, SORT_NUMERIC);
			foreach ($aAllCid as $cid)
			{
				$iThread = $aContent[$cid]['thread_cid'];
				if ($iThread)
				{
					$aRet[$aIndexMap[$iThread]]['replylist'][] = $aContent[$cid];
				}
			}
		}
		return $aRet;
	}

	private function _aGetReplyCids($iOid, $aIndexCid)
	{
		if (empty($aIndexCid))
		{
			return array();
		}
		$replyDao = $this->_aConf['reply'].'Dao';
		$oOption = new Ko_Tool_SQL;
		$oOption->oWhere('thread_cid in (?)', $aIndexCid)->oOrderBy('cid desc')->oLimit(self::MAX_REPLY);
		$aReply = $this->$replyDao->aGetList($iOid, $oOption);
		return Ko_Tool_Utils::AObjs2ids($aReply, 'cid');
	}

	private function _aGetContents($iOid, $aCids)
	{
		if (empty($aCids))
		{
			return array();
		}
		$contentDao = $this->_aConf['content'].'Dao';
		$splitField = $this->$contentDao->sGetSplitField();
		if (strlen($splitField))
		{
			return $this->$contentDao->aGetListByKeys($iOid, $aCids);
		}
		else
		{
			foreach ($aCids as &$cid)
			{
				if (is_array($cid))
				{
					$cid['oid'] = $iOid;
				}
				else
				{
					$cid = array(
						'oid' => $iOid,
						'cid' => $cid,
					);
				}
			}
			unset($cid);
			return $this->$contentDao->aGetDetails($aCids);
		}
	}

	private function _iInsertContentEx($iAid, $iBid, $iThread, $iUid, $sContent, $vAdmin, $iForceFlag)
	{
		$oid = $this->_iGetOid($iAid, $iBid);

		$cid = $this->_iInsertContent($oid, $iUid, $iThread, $sContent);
		if ($cid)
		{
			if ($iForceFlag == Ko_Mode_OCMT::FORCE_AUDIT)
			{
				$audit = Ko_Mode_OCMT::AUDIT_THEN_POST;
			}
			else if ($iForceFlag == Ko_Mode_OCMT::FORCE_POST)
			{
				$audit = Ko_Mode_OCMT::POST_DIRECT;
			}
			else
			{
				if (isset($this->_aConf['audit'][$iAid]))
				{
					$audit = $this->_aConf['audit'][$iAid];
				}
				else
				{
					$audit = $this->_aConf['audit_default'];
				}
			}

			if ($audit)
			{
				$this->_vInsertQueue($oid, $cid, $iThread);
			}

			if ($audit >= self::POST_DIRECT)
			{
				if ($iThread)
				{
					$this->_vInsertReply($oid, $cid, $iThread, $vAdmin);
				}
				else
				{
					$this->_vInsertIndex($oid, $cid, $vAdmin);
				}
			}
		}
		return $cid;
	}

	private function _iInsertContent($iOid, $iUid, $iThread, $sContent)
	{
		$contentDao = $this->_aConf['content'].'Dao';
		$arr = array(
			'oid' => $iOid,
			'uid' => $iUid,
			'thread_cid' => $iThread,
			'content' => $sContent,
			'ctime' => date('Y-m-d H:i:s'),
		);
		return $this->$contentDao->iInsert($arr);
	}

	private function _vInsertAction($iOid, $iCid, $iThread, $iAction, $vAdmin)
	{
		if (strlen($this->_aConf['action']))
		{
			$actionDao = $this->_aConf['action'].'Dao';
			$arr = array(
				'oid' => $iOid,
				'cid' => $iCid,
				'thread_cid' => $iThread,
				'action' => $iAction,
				'admin' => is_array($vAdmin) ? Ko_Tool_Enc::SEncode($vAdmin) : $vAdmin,
				'ip' => Ko_Tool_Ip::SGetClientIP(),
				'ctime' => date('Y-m-d H:i:s'),
			);
			$this->$actionDao->aInsert($arr);
		}
	}

	private function _vInsertQueue($iOid, $iCid, $iThread)
	{
		$this->_bInsertIndexEx($iOid, $iCid, $iThread, 'queue');
	}

	private function _vInsertIndex($iOid, $iCid, $vAdmin)
	{
		$this->_vInsertAction($iOid, $iCid, 0, self::ACT_INSERT, $vAdmin);
		if ($this->_bInsertIndexEx($iOid, $iCid, 0, 'index'))
		{
			$this->_vTouchOid($iOid);
		}
	}

	private function _vDeleteIndex($iOid, $iCid, $vAdmin)
	{
		$this->_vInsertAction($iOid, $iCid, 0, self::ACT_DELETE, $vAdmin);
		if ($this->_iDeleteIndexEx($iOid, $iCid, 'index'))
		{
			$this->_vTouchOid($iOid);
		}
	}

	private function _vInsertReply($iOid, $iCid, $iThread, $vAdmin)
	{
		if ($this->_bInsertIndexEx($iOid, $iCid, $iThread, 'reply'))
		{
			$this->_vInsertAction($iOid, $iCid, $iThread, self::ACT_INSERT, $vAdmin);
			$this->_vTouchOid($iOid);
		}
	}

	private function _vDeleteReply($iOid, $iCid, $vAdmin)
	{
		$this->_vInsertAction($iOid, $iCid, 0, self::ACT_DELETE, $vAdmin);
		if ($this->_iDeleteIndexEx($iOid, $iCid, 'reply'))
		{
			$this->_vTouchOid($iOid);
		}
	}

	private function _bInsertIndexEx($iOid, $iCid, $iThread, $sIndex)
	{
		try
		{
			$dao = $this->_aConf[$sIndex].'Dao';
			$arr = array(
				'oid' => $iOid,
				'cid' => $iCid,
			);
			if ($iThread)
			{
				$arr['thread_cid'] = $iThread;
			}
			$this->$dao->aInsert($arr);
			return true;
		}
		catch(Exception $ex)
		{
		}
		return false;
	}

	private function _iDeleteIndexEx($iOid, $iCid, $sIndex)
	{
		$dao = $this->_aConf[$sIndex].'Dao';
		$key = array('oid' => $iOid, 'cid' => $iCid);
		return $this->$dao->iDelete($key);
	}

	private function _vTouchOid($iOid)
	{
		if (strlen($this->_aConf['cacheflag']))
		{
			$cacheflagDao = $this->_aConf['cacheflag'].'Dao';
			$arr = array(
				'oid' => $iOid,
				'status' => 1,
				);
			$change = array(
				'status' => 1,
				);
			$this->$cacheflagDao->aInsert($arr, array(), $change);
		}
	}

	private function _aLoadCache($iOid)
	{
		if (strlen($this->_aConf['mc']))
		{
			$key = $this->_sGetMCKey($iOid);
			$mcDao = $this->_aConf['mc'].'Dao';
			$cache = $this->$mcDao->vGet($key);
			$info = Ko_Tool_Enc::ADecode($cache);
			if (!empty($info) && !empty($info['para']))
			{
				return $info;
			}
		}
		if (strlen($this->_aConf['cache']))
		{
			$cacheDao = $this->_aConf['cache'].'Dao';
			$info = $this->$cacheDao->aGet($iOid);
			if (!empty($info))
			{
				$info = Ko_Tool_Enc::ADecode($info['cache']);
				if (!empty($info) && !empty($info['para']))
				{
					return $info;
				}
			}
		}
		return false;
	}

	private function _vSaveCache($iOid, $aInfo)
	{
		if (strlen($this->_aConf['cache']) || strlen($this->_aConf['mc']))
		{
			$cache = Ko_Tool_Enc::SEncode($aInfo);
			$aData = array(
				'oid' => $iOid,
				'cache' => $cache,
				);
			$aUpdate = array(
				'cache' => $cache,
				);
		}
		if (strlen($this->_aConf['cache']))
		{
			$cacheDao = $this->_aConf['cache'].'Dao';
			$this->$cacheDao->aInsert($aData, $aUpdate);
		}
		if (strlen($this->_aConf['mc']))
		{
			$key = $this->_sGetMCKey($iOid);
			$mcDao = $this->_aConf['mc'].'Dao';
			$this->$mcDao->bSet($key, $cache);
		}
	}

	private function _sGetMCKey($iOid)
	{
		$contentDao = $this->_aConf['content'].'Dao';
		return 'koocmt:'.$this->$contentDao->sGetTableName().':'.$iOid;
	}

	private function _iGetOid($iAid, $iBid)
	{
		assert($iAid < 128);
		assert(0 != $iBid);
		return ($iAid << 56) + $iBid;
	}
}

/*

class KKo_Mode_Dao extends Ko_Dao_Factory
{
	protected $_aDaoConf = array(
		'index' => array(
			'type' => 'db_split',
			'kind' => 's_zhangchu_ocmt_index',
			'split' => 'oid',
			'key' => 'cid',
		),
		'reply' => array(
			'type' => 'db_split',
			'kind' => 's_zhangchu_ocmt_reply',
			'split' => 'oid',
			'key' => 'cid',
		),
		'queue' => array(
			'type' => 'db_single',
			'kind' => 's_zhangchu_ocmt_queue',
			'key' => array('oid', 'cid'),
		),
		'content' => array(
			'type' => 'db_split',
			'kind' => 's_zhangchu_ocmt_content',
			'split' => 'oid',
			'key' => 'cid',
			'idkey' => 'zhangchut',
		),
		'action' => array(
			'type' => 'db_split',
			'kind' => 's_zhangchu_ocmt_action',
			'split' => 'oid',
		),
		'cacheflag' => array(
			'type' => 'db_one',
			'kind' => 's_zhangchu_ocmt_cacheflag',
			'split' => 'oid',
		),
		'cache' => array(
			'type' => 'db_one',
			'kind' => 's_zhangchu_ocmt_cache',
			'split' => 'oid',
		),
		'localmcache' => array(
			'type' => 'mcache',
		),
		);
}

class A extends Ko_Mode_OCMT
{
	protected $_aConf = array(
		'audit_default' => Ko_Mode_OCMT::AUDIT_THEN_POST,
		'audit' => array(),
		'index' => 'index',				// db_single or db_split
		'reply' => 'reply',				// optional, db_single or db_split
		'queue' => 'queue',				// optional, db_single or db_split
		'content' => 'content',			// db_single or db_split
		'action' => 'action',			// optional, db_single or db_split
		'cacheflag' => 'cacheflag',		// optional
		'cache' => 'cache',				// optional
		'mc' => 'localmcache',			// optional
		);
}

$aid = 0;
$bid = 1;
$cid = 1374841590214973;
$uid = 337;

$cids = array(1374841590214973, 1374843751315195);

$a = new A;
$a->vRebuildAllCache();

$ret = $a->aGet($aid, $bid, $cid, true);
var_dump($ret);

$ret = $a->aGetListByCid($aid, $bid, $cids, true);
var_dump($ret);

$ret = $a->aGetListWithTotal($aid, $bid, $iTotal, false, 0, 10, true);
var_dump($ret);
var_dump($iTotal);

$ret = $a->aGetQueueListWithTotal($aid, $bid, $iTotal);
var_dump($ret);
var_dump($iTotal);

$ret = $a->aGetAllQueueListWithTotal($iTotal);
var_dump($ret);
var_dump($iTotal);

exit;

$cid = $a->iComment($aid, $bid, $uid, '222 222', '', Ko_Mode_OCMT::FORCE_AUTO);
var_dump($cid);
$a->vPassComment($bid, $cid, array('system pass'));

$rid = $a->iReply($aid, $bid, $cid, $uid, 'rrr rrr', '', Ko_Mode_OCMT::FORCE_AUTO);
var_dump($rid);
$a->vPassReply($bid, $rid, $cid, array('system pass reply'));

$rid = $a->iReply($aid, $bid, $cid, $uid, 'ddd ddd', '', Ko_Mode_OCMT::FORCE_AUTO);
var_dump($rid);
$a->vDenyReply($bid, $rid, array('system deny reply'));

$cid = $a->iComment($aid, $bid, $uid, '111 111', '', Ko_Mode_OCMT::FORCE_AUTO);
var_dump($cid);
$a->vDenyComment($bid, $cid, array('system deny'));

*/
?>