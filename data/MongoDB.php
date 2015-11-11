<?php
/**
 * MongoDB
 *
 * @package ko\data
 * @author zhangchu
 */

/**
 * 封装 MongoDB 实现
 */
class Ko_Data_MongoDB
{
	const SAFE = KO_MONGO_SAFE;

	private static $s_aInstance = array();

	private $_sTag;
	private $_oEngine;

	protected function __construct($sTag, $oEngine)
	{
		$this->_sTag = $sTag;
		$this->_oEngine = $oEngine;
	}

	public static function OInstance($sTag, $oEngine = null)
	{
		if (empty(self::$s_aInstance[$sTag]))
		{
			self::$s_aInstance[$sTag] = new self($sTag, $oEngine);
		}
		return self::$s_aInstance[$sTag];
	}

	public function aInsert($sKind, $iHintId, $aData, $aUpdate, $aChange, $oOption)
	{
		assert(empty($aUpdate) && empty($aChange));
		$options = array('safe' => self::SAFE);
		$ret = $this->_oGetEngine()->oSelectCollection($sKind)->insert($aData, $options);
		return array('data' => array(), 'rownum' => 0, 'insertid' => $aData['_id'], 'affectedrows' => 1);
	}
	
	/**
	 * @param array $aUpdate 支持两种格式，一种是 array('field1' => 'value1', ...)，另一种是 array('$set' => array(...), '$inc' => array(...), ...)
	 * @param array $oOption 支持两种格式，一种是 array('field1' => 'value1', ...)，另一种是 Ko_Tool_MONGO
	 */
	public function iUpdate($sKind, $iHintId, $aUpdate, $aChange, $oOption)
	{
		$operator = $field = 0;
		foreach ($aUpdate as $k => $v)
		{
			('$' == substr($k, 0, 1)) ? $operator++ : $field++;
		}
		if (0 == $operator)
		{	//没有操作符
			$new_object = array();
			if (!empty($aUpdate))
			{
				$new_object['$set'] = $aUpdate;
			}
			if (!empty($aChange))
			{
				$this->_vValue2Numeric($aChange);
				$new_object['$inc'] = $aChange;
			}
		}
		else if (0 == $field)
		{	//全是操作符
			assert(empty($aChange));
			$new_object = $aUpdate;
			if (isset($new_object['$inc']))
			{
				$this->_vValue2Numeric($new_object['$inc']);
			}
		}
		else
		{
			assert(0);
		}
		$options = array('safe' => self::SAFE, 'multiple' => true);
		if (is_array($oOption))
		{
			$ret = $this->_oGetEngine()->oSelectCollection($sKind)->update($oOption, $new_object, $options);
		}
		else
		{
			$options['upsert'] = $oOption->bUpsert();
			$ret = $this->_oGetEngine()->oSelectCollection($sKind)->update($oOption->aWhere(), $new_object, $options);
		}
		return $ret['n'];
	}
	
	/**
	 * @param array $oOption 支持两种格式，一种是 array('field1' => 'value1', ...)，另一种是 Ko_Tool_MONGO
	 */
	public function iDelete($sKind, $iHintId, $oOption)
	{
		$options = array('safe' => self::SAFE);
		if (is_array($oOption))
		{
			$ret = $this->_oGetEngine()->oSelectCollection($sKind)->remove($oOption, $options);
		}
		else
		{
			$ret = $this->_oGetEngine()->oSelectCollection($sKind)->remove($oOption->aWhere(), $options);
		}
		return $ret['n'];
	}

	/**
	 * @param array $oOption 支持两种格式，一种是 array('field1' => 'value1', ...)，另一种是 Ko_Tool_MONGO
	 */
	public function aSelect($sKind, $iHintId, $oOption, $iCacheTime, $bMaster)
	{
		if (is_array($oOption))
		{
			$col = $this->_oGetEngine()->oSelectCollection($sKind);
			$col->setReadPreference($bMaster ? MongoClient::RP_PRIMARY : MongoClient::RP_SECONDARY_PREFERRED);
			$cursor = $col->find($oOption);
		}
		else
		{
			$aCommand = $oOption->aCommand();
			if (!empty($aCommand))
			{
				return $this->_oGetEngine()->oSelectDB()->command($aCommand);
			}
			$col = $this->_oGetEngine()->oSelectCollection($sKind);
			$col->setReadPreference($bMaster ? MongoClient::RP_PRIMARY : MongoClient::RP_SECONDARY_PREFERRED);
			$cursor = $col->find($oOption->aWhere(), $oOption->aFields());
			$aOrderBy = $oOption->aOrderBy();
			if (!empty($aOrderBy))
			{
				$cursor->sort($aOrderBy);
			}
			$iLimit = $oOption->iLimit();
			if ($oOption->bCalcFoundRows())
			{
				$oOption->vSetFoundRows($cursor->count());
				$iLimit = max(1, $iLimit);
			}
			if ($iLimit)
			{
				$iSkip = $oOption->iOffset();
				if ($iSkip)
				{
					$cursor->skip($iSkip);
				}
				$cursor->limit($iLimit);
			}
		}
		$ret = array();
		foreach ($cursor as $doc)
		{
			$ret[] = $doc;
		}
		return $ret;
	}
	
	/**
	 * @return array
	 */
	public function aGetIndexes($sKind)
	{
		$col = $this->_oGetEngine()->oSelectCollection($sKind);
		return $col->getIndexInfo();
	}
	
	private function _oGetEngine()
	{
		if (is_null($this->_oEngine))
		{
			$this->_oEngine = Ko_Data_Mongo::OInstance(KO_MONGO_HOST, KO_MONGO_REPLICASET, KO_MONGO_USER, KO_MONGO_PASS, KO_MONGO_NAME);
		}
		return $this->_oEngine;
	}
	
	private function _vValue2Numeric(&$aData)
	{
		foreach ($aData as $k => &$v)
		{
			$v += 0;
		}
		unset($v);
	}
}
