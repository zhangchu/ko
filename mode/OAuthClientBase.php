<?php
/**
 * OAuthClientBase
 *
 * @package ko\mode
 * @author zhangchu
 */

class Ko_Mode_OAuthClientBase extends Ko_Mode_OAuthBase
{
	/**
	 * 查询用户 token 信息
	 * 
	 * @return array
	 */
	public function aGetUserToken($sSrc, $iUid, $sToken)
	{
		$usertokenDao = $this->_aConf['usertoken'].'Dao';
		return $this->$usertokenDao->aGet(array('uid' => $iUid, 'src' => $sSrc, 'token' => $sToken));
	}
	
	/**
	 * 查询用户关联信息
	 * 
	 * @return array
	 */
	public function aGetLastToken($sSrc, $iUid)
	{
		$lasttokenDao = $this->_aConf['lasttoken'].'Dao';
		return $this->$lasttokenDao->aGet(array('uid' => $iUid, 'src' => $sSrc));
	}
	
	protected function _bSaveUserToken($sSrc, $iUid, $aTokeninfo, $sTokenField, $aOtherField)
	{
		$ctime = date('Y-m-d H:i:s');
		$aData = array(
			'uid' => $iUid,
			'src' => $sSrc,
			'token' => $aTokeninfo[$sTokenField],
			'ctime' => $ctime,
			);
		$aUpdate = array(
			'ctime' => $ctime,
			);
		foreach ($aOtherField as $field)
		{
			$aData[$field] = $aUpdate[$field] = isset($aTokeninfo[$field]) ? $aTokeninfo[$field] : '';
		}
		$usertokenDao = $this->_aConf['usertoken'].'Dao';
		$this->$usertokenDao->aInsert($aData, $aUpdate);

		$aData = array(
			'uid' => $iUid,
			'src' => $sSrc,
			'token' => $aTokeninfo[$sTokenField],
			);
		$aUpdate = array(
			'token' => $aTokeninfo[$sTokenField],
			);
		$lasttokenDao = $this->_aConf['lasttoken'].'Dao';
		$this->$lasttokenDao->aInsert($aData, $aUpdate);

		return true;
	}
}

?>