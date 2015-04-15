<?php
/**
 * Uuid
 *
 * <b>数据库例表</b>
 * <pre>
 *   CREATE TABLE s_zhangchu_uuid(
 *     id bigint unsigned not null auto_increment,
 *     uuid char(36) not null default '',
 *     ctime timestamp NOT NULL default CURRENT_TIMESTAMP,
 *     primary key (id),
 *     unique (uuid)
 *   )ENGINE=InnoDB DEFAULT CHARSET=latin1;
 * </pre>
 *
 * <b>_aConf 配置</b>
 * @see Ko_Mode_Uuid::$_aConf
 *
 * @package ko\Mode
 * @author zhangchu
 */

class Ko_Mode_Uuid extends Ko_Busi_Api
{
	/**
	 * 配置数组
	 *
	 * <pre>
	 * array(
	 *   'cookiename' =>        cookie 名称
	 *   'domain' =>            cookie 使用的域
	 *   'uuid' =>              目标数据库 Dao 名称，不是所有数据都进入数据库，只有需要id的uuid才进入数据库
	 * )
	 * </pre>
	 *
	 * @var array
	 */
	protected $_aConf = array();
	
	private $_sUuid;
	
	public function __construct()
	{
		if (empty($this->_sUuid) && strlen($this->_aConf['cookiename']))
		{
			$this->_sUuid = $_COOKIE[$this->_aConf['cookiename']];
			if (empty($this->_sUuid))
			{
				$this->_sUuid = self::_genUuid();
				setcookie($this->_aConf['cookiename'], $this->_sUuid, time() + 31536000, '/',
					strlen($this->_aConf['domain']) ? $this->_aConf['domain'] : null, false, true);
			}
		}
	}
	
	public function sGet()
	{
		return $this->_sUuid;
	}
	
	public function iGetId($bForce = false)
	{
		$uuidDao = $this->_aConf['uuid'].'Dao';
		$info = $this->$uuidDao->aGet($this->_sUuid);
		if (empty($info))
		{
			if ($bForce)
			{
				$data = array('uuid' => $this->_sUuid);
				return $this->$uuidDao->iInsert($data);
			}
			return 0;
		}
		return $info['id'];
	}
	
	private static function _genUuid()
	{
		$chars = md5(uniqid(mt_rand(), true));
		$header = sprintf('%08x', time());
		$uuid = $header.'-'
			.substr($chars, 8, 4).'-'
			.substr($chars, 12, 4).'-'
			.substr($chars, 16, 4).'-'
			.substr($chars, 20, 12);
		return $uuid;
	}
}
