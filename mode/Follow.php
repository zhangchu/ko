<?php
/**
 * 使用说明
 *
 * <b>简介</b>
 * <pre>
 *   实现用户A和用户B之间的关注/订阅/好友等关系
 * </pre>
 *
 * <b>数据库例表</b>
 * <pre>
 *   CREATE TABLE kotest_follow_0(
 *     uida bigint unsigned not null default 0,
 *     uidb bigint unsigned not null default 0,   -- uida follow uidb
 *     sort int unsigned not null default 0,
 *     data BLOB,
 *     unique(uida, uidb),
 *     index (uida, sort)
 *   )ENGINE=InnoDB DEFAULT CHARSET=UTF8;
 *   CREATE TABLE kotest_followed_0(
 *     uidb bigint unsigned not null default 0,
 *     uida bigint unsigned not null default 0,   -- uidb is followed by uida
 *     sort int unsigned not null default 0,
 *     unique(uidb, uida),
 *     index (uidb, sort)
 *   )ENGINE=InnoDB DEFAULT CHARSET=UTF8;
 * </pre>
 *
 * <b>_aConf 配置</b>
 * @see Ko_Mode_Follow::$_aConf
 *
 * @package ko\mode
 * @author zhangchu
 */

/**
 * 实现
 */
class Ko_Mode_Follow extends Ko_Busi_Api
{
	/**
	 * 配置数组
	 *
	 * <pre>
	 * array(
	 *   'follow' => 关注表
	 *   'followed' => 被关注表
	 * )
	 * </pre>
	 *
	 * @var array
	 */
	protected $_aConf = array();

	/**
	 * 用户 A 关注用户 B
	 *
	 * @return boolean
	 */
	public function bFollow($iUida, $iUidb, $iFollowSort, $sData, $iFollowedSort)
	{
		try
		{
			$aData = array(
				'uida' => $iUida,
				'uidb' => $iUidb,
				'sort' => $iFollowSort,
				'data' => $sData,
				);
			$followDao = $this->_aConf['follow'].'Dao';
			$this->$followDao->aInsert($aData);

			$aData = array(
				'uidb' => $iUidb,
				'uida' => $iUida,
				'sort' => $iFollowedSort,
				);
			$aUpdate = array(
				'sort' => $iFollowedSort,
				);
			$followedDao = $this->_aConf['followed'].'Dao';
			$this->$followedDao->aInsert($aData, $aUpdate);
		}
		catch(Exception $e)
		{
			return false;
		}
		return true;
	}

	/**
	 * 用户 A 取消关注用户 B
	 *
	 * @return boolean
	 */
	public function bUnFollow($iUida, $iUidb)
	{
		$followedDao = $this->_aConf['followed'].'Dao';
		$this->$followedDao->iDelete(array('uidb' => $iUidb, 'uida' => $iUida));

		$followDao = $this->_aConf['follow'].'Dao';
		$this->$followDao->iDelete(array('uida' => $iUida, 'uidb' => $iUidb));
		return true;
	}

	/**
	 * 设置用户 A 关注用户 B 的数据
	 *
	 * @return boolean
	 */
	public function bSetFollowData($iUida, $iUidb, $sData)
	{
		$followDao = $this->_aConf['follow'].'Dao';
		$this->$followDao->iUpdate(array('uida' => $iUida, 'uidb' => $iUidb), array('data' => $sData));
		return true;
	}

	/**
	 * 设置用户 A 关注用户 B 的排序数据
	 *
	 * @return boolean
	 */
	public function bSetFollowSort($iUida, $iUidb, $iFollowSort)
	{
		$followDao = $this->_aConf['follow'].'Dao';
		$this->$followDao->iUpdate(array('uida' => $iUida, 'uidb' => $iUidb), array('sort' => $iFollowSort));
		return true;
	}

	/**
	 * 设置用户 B 被用户 A 关注的排序数据
	 *
	 * @return boolean
	 */
	public function bSetFollowedSort($iUidb, $iUida, $iFollowedSort)
	{
		$followedDao = $this->_aConf['followed'].'Dao';
		$this->$followedDao->iUpdate(array('uidb' => $iUidb, 'uida' => $iUida), array('sort' => $iFollowedSort));
		return true;
	}

	/**
	 * 用户 A 是否关注用户 B
	 *
	 * @return boolean
	 */
	public function bIsFollow($iUida, $iUidb)
	{
		$info = $this->aGetFollowInfo($iUida, $iUidb);
		return !empty($info);
	}
	
	/**
	 * 用户 A 关注了用户列表中的那些用户
	 *
	 * @return array
	 */
	public function aIsFollow($iUida, $aUidb, $sKeyField = '')
	{
		$list = $this->aGetFollowInfos($iUida, $aUidb, $sKeyField);
		return array_keys($list);
	}
	
	/**
	 * 用户 B 被用户列表中的那些用户关注了
	 *
	 * @return array
	 */
	public function aIsFollowed($iUidb, $aUida, $sKeyField = '')
	{
		$followedDao = $this->_aConf['followed'].'Dao';
		$splitField = $this->$followedDao->sGetSplitField();
		if (strlen($splitField))
		{
			$list = $this->$followedDao->aGetListByKeys($iUidb, $aUida, $sKeyField);
		}
		else
		{
			foreach ($aUida as &$uida)
			{
				if (is_array($uida))
				{
					$uida['uidb'] = $iUidb;
				}
				else
				{
					$uida = array(
						'uidb' => $iUidb,
						'uida' => $uida,
					);
					$sKeyField = '';
				}
			}
			unset($uida);
			$list = $this->$followedDao->aGetDetails($aUida, '', $sKeyField);
		}
		return array_keys($list);
	}
	
	/**
	 * 查询用户 A 关注用户 B 信息
	 *
	 * @return array
	 */
	public function aGetFollowInfo($iUida, $iUidb)
	{
		$followDao = $this->_aConf['follow'].'Dao';
		return $this->$followDao->aGet(array('uida' => $iUida, 'uidb' => $iUidb));
	}

	/**
	 * 查询用户 A 关注用户列表的信息
	 *
	 * @return array
	 */
	public function aGetFollowInfos($iUida, $aUidb, $sKeyField = '')
	{
		$followDao = $this->_aConf['follow'].'Dao';
		$splitField = $this->$followDao->sGetSplitField();
		if (strlen($splitField))
		{
			return $this->$followDao->aGetListByKeys($iUida, $aUidb, $sKeyField);
		}
		else
		{
			foreach ($aUidb as &$uidb)
			{
				if (is_array($uidb))
				{
					$uidb['uida'] = $iUida;
				}
				else
				{
					$uidb = array(
						'uida' => $iUida,
						'uidb' => $uidb,
					);
					$sKeyField = '';
				}
			}
			unset($uidb);
			return $this->$followDao->aGetDetails($aUidb, '', $sKeyField);
		}
	}

	/**
	 * 查询用户 A 关注的用户列表
	 *
	 * @return array
	 */
	public function aGetFollowList($iUida, $iStart, $iNum)
	{
		$oOption = new Ko_Tool_SQL;
		$oOption->oOrderBy('sort desc')->oOffset($iStart)->oLimit($iNum);
		return $this->_aGetFollowList($iUida, $oOption);
	}

	/**
	 * 查询用户 A 关注的用户列表
	 *
	 * @return array
	 */
	public function aGetFollowListWithTotal($iUida, $iStart, $iNum, &$iTotal)
	{
		$oOption = new Ko_Tool_SQL;
		$oOption->oOrderBy('sort desc')->oOffset($iStart)->oLimit($iNum)->oCalcFoundRows(true);
		$info = $this->_aGetFollowList($iUida, $oOption);
		$iTotal = $oOption->iGetFoundRows();
		return $info;
	}
	
	/**
	 * 查询关注用户 B 的用户列表
	 *
	 * @return array
	 */
	public function aGetFollowedList($iUidb, $iStart, $iNum)
	{
		$oOption = new Ko_Tool_SQL;
		$oOption->oOrderBy('sort desc')->oOffset($iStart)->oLimit($iNum);
		return $this->_aGetFollowedList($iUidb, $oOption);
	}
	
	/**
	 * 查询关注用户 B 的用户列表
	 *
	 * @return array
	 */
	public function aGetFollowedListWithTotal($iUidb, $iStart, $iNum, &$iTotal)
	{
		$oOption = new Ko_Tool_SQL;
		$oOption->oOrderBy('sort desc')->oOffset($iStart)->oLimit($iNum)->oCalcFoundRows(true);
		$info = $this->_aGetFollowedList($iUidb, $oOption);
		$iTotal = $oOption->iGetFoundRows();
		return $info;
	}
	
	private function _aGetFollowList($iUida, $oOption)
	{
		$followDao = $this->_aConf['follow'].'Dao';
		return $this->$followDao->aGetList($iUida, $oOption);
	}

	private function _aGetFollowedList($iUidb, $oOption)
	{
		$followedDao = $this->_aConf['followed'].'Dao';
		return $this->$followedDao->aGetList($iUidb, $oOption);
	}
}

?>