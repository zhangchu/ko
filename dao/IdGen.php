<?php
/**
 * IdGen
 *
 * @package ko
 * @subpackage dao
 * @author zhangchu
 */

//include_once('../ko.class.php');

/**
 * 对 IDGenerator 进行封装接口
 */
interface IKo_Dao_IdGen
{
	/**
	 * @return string
	 */
	public function sNewId();
	/**
	 * @return int
	 */
	public function iNewTimeId();
}

/**
 * 对 IDGenerator 进行封装实现
 */
class Ko_Dao_IdGen implements IKo_Dao_IdGen
{
	private $_sIdKey;

	public function __construct($sIdKey)
	{
		$this->_sIdKey = $sIdKey;
	}

	/**
	 * @return string
	 */
	public function sNewId()
	{
		return Ko_Data_IDMan::OInstance()->sGetNewStringLongID($this->_sIdKey);
	}

	/**
	 * @return int
	 */
	public function iNewTimeId()
	{
		return Ko_Data_IDMan::OInstance()->iGetNewTimeID($this->_sIdKey);
	}
}

?>