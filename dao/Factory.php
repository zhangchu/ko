<?php
/**
 * Factory
 *
 * @package ko\dao
 * @author zhangchu
 */

/**
 * 创建Dao对象的工厂基类
 */
class Ko_Dao_Factory
{
	private static $s_aInstance = array();
	private static $s_aDefaultValue = array(
		'mcachetime' => 3600,
		'mode' => 'r',
		);
	private static $s_aTypeAlias = array(
		'usermany' => 'db_common',
		'config' => 'db_single',
		'userone' => 'db_one',
		'idgenerator' => 'idgen',
		);

	/**
	 * @var array dao 列表，可以配置的参数见函数 _oCreateDao 系列函数
	 */
	protected $_aDaoConf = array();

	private static function _OCreateDao_file($aConfig, $aParam)
	{
		return new Ko_Dao_File(self::_VGetValue($aConfig, 'file'), self::_VGetValue($aConfig, 'mode'));
	}

	private static function _OCreateDao_gdbm($aConfig, $aParam)
	{
		return new Ko_Dao_Dba(self::_VGetValue($aConfig, 'file'), self::_VGetValue($aConfig, 'mode'));
	}

	private static function _OSetAttribute_db($oDB, $aConfig, $aParam)
	{
		$oDB->vSetAttribute('issplitstring', self::_VGetValue($aConfig, 'issplitstring'));
		return $oDB;
	}

	private static function _OCreateDao_db_common($aConfig, $aParam)
	{
		$key = self::_VGetValue($aConfig, 'key');
		$mcachetime = (is_array($key) || strlen($key)) ? self::_VGetValue($aConfig, 'mcachetime') : 0;
		$db = new Ko_Dao_DB(
			self::_VGetValue($aConfig, 'kind'),
			$key,
			self::_VGetValue($aConfig, 'idkey'),
			self::_VGetValue($aConfig, 'dbagent'),
			self::_VGetValue($aConfig, 'mcachename'),
			$mcachetime,
			self::_VGetValue($aConfig, 'useuo'),
			self::_VGetValue($aConfig, 'uofields'),
			self::_VGetValue($aConfig, 'uobject'));
		return self::_OSetAttribute_db($db, $aConfig, $aParam);
	}

	private static function _OCreateDao_db_single($aConfig, $aParam)
	{
		$kind = (isset($aParam['suffix']) && strlen($aParam['suffix'])) ? self::_VGetValue($aConfig, 'kind').'_'.$aParam['suffix'] : self::_VGetValue($aConfig, 'kind');
		$db = new Ko_Dao_Config(
			$kind,
			self::_VGetValue($aConfig, 'key'),
			self::_VGetValue($aConfig, 'idkey'),
			self::_VGetValue($aConfig, 'dbagent'),
			self::_VGetValue($aConfig, 'mcachename'),
			self::_VGetValue($aConfig, 'mcachetime'));
		return self::_OSetAttribute_db($db, $aConfig, $aParam);
	}

	private static function _OCreateDao_db_mongo($aConfig, $aParam)
	{
		$kind = (isset($aParam['suffix']) && strlen($aParam['suffix'])) ? self::_VGetValue($aConfig, 'kind').'_'.$aParam['suffix'] : self::_VGetValue($aConfig, 'kind');
		$db = new Ko_Dao_Config(
			$kind,
			self::_VGetValue($aConfig, 'key'),
			self::_VGetValue($aConfig, 'idkey'),
			self::_VGetValue($aConfig, 'dbagent'),
			self::_VGetValue($aConfig, 'mcachename'),
			self::_VGetValue($aConfig, 'mcachetime'));
		$db->vSetAttribute('ismongodb', true);
		return self::_OSetAttribute_db($db, $aConfig, $aParam);
	}

	private static function _OCreateDao_db_one($aConfig, $aParam)
	{
		$db = new Ko_Dao_UserOne(
			self::_VGetValue($aConfig, 'kind'),
			self::_VGetValue($aConfig, 'idkey'),
			self::_VGetValue($aConfig, 'dbagent'),
			self::_VGetValue($aConfig, 'mcachename'),
			self::_VGetValue($aConfig, 'mcachetime'),
			self::_VGetValue($aConfig, 'useuo'),
			self::_VGetValue($aConfig, 'uofields'),
			self::_VGetValue($aConfig, 'uobject'));
		return self::_OSetAttribute_db($db, $aConfig, $aParam);
	}

	private static function _OCreateDao_db_split($aConfig, $aParam)
	{
		$db = new Ko_Dao_DBSplit(
			self::_VGetValue($aConfig, 'kind'),
			self::_VGetValue($aConfig, 'key'),
			self::_VGetValue($aConfig, 'idkey'),
			self::_VGetValue($aConfig, 'dbagent'),
			self::_VGetValue($aConfig, 'mcachename'),
			self::_VGetValue($aConfig, 'mcachetime'),
			self::_VGetValue($aConfig, 'useuo'),
			self::_VGetValue($aConfig, 'uofields'),
			self::_VGetValue($aConfig, 'uobject'));
		return self::_OSetAttribute_db($db, $aConfig, $aParam);
	}

	private static function _OCreateDao_redis($aConfig, $aParam)
	{
		return Ko_Data_RedisAgent::OInstance(self::_VGetValue($aConfig, 'name'), self::_VGetValue($aConfig, 'host'));
	}

	private static function _OCreateDao_mcache($aConfig, $aParam)
	{
		if ($aConfig['global'])
		{
			$dao = Ko_Data_MCAgent::OInstance(self::_VGetValue($aConfig, 'name'));
		}
		else
		{
			$dao = new Ko_Dao_MCacheEx($aParam['module'], self::_VGetValue($aConfig, 'name'));
		}
		return $dao;
	}

	private static function _OCreateDao_lcache($aConfig, $aParam)
	{
		if ($aConfig['global'])
		{
			$dao = Ko_Data_LCAgent::OInstance();
		}
		else
		{
			$dao = new Ko_Dao_LCacheEx($aParam['module']);
		}
		return $dao;
	}

	private static function _OCreateDao_idgen($aConfig, $aParam)
	{
		return new Ko_Dao_IdGen(self::_VGetValue($aConfig, 'idkey'));
	}

	private static function _SGetRealType($type)
	{
		return isset(self::$s_aTypeAlias[$type]) ? self::$s_aTypeAlias[$type] : $type;
	}

	private static function _VGetValue($aConfig, $sKey)
	{
		return isset($aConfig[$sKey]) ? $aConfig[$sKey] : (isset(self::$s_aDefaultValue[$sKey]) ? self::$s_aDefaultValue[$sKey] : null);
	}

	/**
	 * @return object
	 */
	public static function OCreateDao($aConfig, $aParam)
	{
		foreach ($aConfig as $k => $v)
		{
			$aConfig[strtolower($k)] = $v;
		}

		if (isset($aConfig['factory']))
		{
			assert(method_exists($aConfig['factory'], 'OCreateDao'));
			return call_user_func(array($aConfig['factory'], 'OCreateDao'), $aConfig, $aParam);
		}

		$sType = self::_SGetRealType($aConfig['type']);
		$funcname = '_OCreateDao_'.$sType;
		assert(method_exists('Ko_Dao_Factory', $funcname));
		return self::$funcname($aConfig, $aParam);
	}

	/**
	 * @param string $sName 例如 goodsDao,userCafeDao
	 * @return object
	 */
	public function oGetDao($sName)
	{
		$tag = substr($sName, -3);
		assert($tag === 'Dao');
		$modulename = Ko_Tool_Module::SGetObjectModuleName($this);
		$confkey = $modulekey = substr($sName, 0, -3);

		if(!isset(self::$s_aInstance[$modulename][$modulekey]))
		{
			$aParam = array('module' => $modulename);
			if(!isset($this->_aDaoConf[$confkey]))
			{
				list($confkey, $aParam['suffix']) = explode('_', $confkey, 2);
				assert(isset($this->_aDaoConf[$confkey]));
			}
			self::$s_aInstance[$modulename][$modulekey] = self::OCreateDao($this->_aDaoConf[$confkey], $aParam);
		}
		return self::$s_aInstance[$modulename][$modulekey];
	}
}

?>