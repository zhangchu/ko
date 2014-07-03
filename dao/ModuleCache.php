<?php
/**
 * ModuleCache
 *
 * @package ko\dao
 * @author zhangchu
 */

/**
 * 封装模块内部 Cache 实现的基类
 */
class Ko_Dao_ModuleCache
{
	private $_sModuleName;

	protected function __construct ($sModuleName)
	{
		$this->_sModuleName = $sModuleName;
	}

	/**
	 * @return string
	 */
	protected function _sModuleName($vKey)
	{
		if (is_array($vKey))
		{
			$aRet = array();
			foreach ($vKey as $key)
			{
				$aRet[] = $this->_sModuleName.':'.$key;
			}
			return $aRet;
		}
		return $this->_sModuleName.':'.$vKey;
	}

	/**
	 * @return array
	 */
	protected function _aDeleteModuleName($aRet)
	{
		$len = strlen($this->_sModuleName) + 1;
		$ret = array();
		foreach ($aRet as $k => $v)
		{
			$ret[substr($k, $len)] = $v;
		}
		return $ret;
	}
}

?>