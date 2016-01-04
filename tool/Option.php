<?php
/**
 * Option
 *
 * @package ko\tool
 * @author zhangchu
 */

/**
 * 封装 Ko_Tool_SQL | Ko_Tool_MONGO 的一些辅助接口
 */
class Ko_Tool_Option
{
	/**
	 * @param Ko_Tool_SQL|Ko_Tool_MONGO|array $oOption
	 * @return boolean
	 */
	public static function BIsWhereEmpty($oOption, $bIsMongoDB)
	{
		if ($bIsMongoDB)
		{
			if (is_array($oOption))
			{
				$aWhere = $oOption;
			}
			else
			{
				$aWhere = $oOption->aWhere();
			}
			return empty($aWhere);
		}
		else
		{
			$sWhere = $oOption->sWhere();
			return strlen(trim($sWhere)) ? false : true;
		}
	}
	
	/**
	 * @return string
	 */
	public static function SEscapeWhere($aArgs)
	{
		$iArgNum = count($aArgs);
		if (1 === $iArgNum && is_array($aArgs[0]))
		{
			$where = array();
			foreach ($aArgs[0] as $k => $v)
			{
				self::_vQuerySelector($k, $v, $where);
			}
			$where = implode(' AND ', $where);
		}
		else
		{
			assert($iArgNum && false === strpos($aArgs[0], '\'') && false === strpos($aArgs[0], '"'));

			$where = $aArgs[0];
			$pos = 0;
			for ($i=1; $i<$iArgNum; ++$i)
			{
				$pos = strpos($where, '?', $pos);
				if (false === $pos)
				{
					break;
				}
				if (is_array($aArgs[$i]))
				{
					$escapeArg = array_map(array('Ko_Data_Mysql', 'SEscape'), $aArgs[$i]);
					$sReplace = '"'.implode('", "', $escapeArg).'"';
				}
				else
				{
					$sReplace = '"'.Ko_Data_Mysql::SEscape($aArgs[$i]).'"';
				}
				$where = substr($where, 0, $pos).$sReplace.substr($where, $pos + 1);
				$pos += strlen($sReplace);
			}
		}
		return $where;
	}

	private static function _vQuerySelector($k, $v, &$where)
	{
		$field = ('`' === $k[0]) ? $k : '`'.$k.'`';
		if (is_array($v))
		{
			$valid = false;
			foreach ($v as $k2 => $v2)
			{
				switch ($k2)
				{
					case '$in':
						$escapeV = array_map(array('Ko_Data_Mysql', 'SEscape'), $v2);
						$where[] = '('.$field.' IN ("'.implode('", "', $escapeV).'"))';
						$valid = true;
						break;
					case '$nin':
						$escapeV = array_map(array('Ko_Data_Mysql', 'SEscape'), $v2);
						$where[] = '('.$field.' NOT IN ("'.implode('", "', $escapeV).'"))';
						$valid = true;
						break;
					case '$lt':
						$where[] = '('.$field.' < "'.Ko_Data_Mysql::SEscape($v2).'")';
						$valid = true;
						break;
					case '$gt':
						$where[] = '('.$field.' > "'.Ko_Data_Mysql::SEscape($v2).'")';
						$valid = true;
						break;
					case '$lte':
						$where[] = '('.$field.' <= "'.Ko_Data_Mysql::SEscape($v2).'")';
						$valid = true;
						break;
					case '$gte':
						$where[] = '('.$field.' >= "'.Ko_Data_Mysql::SEscape($v2).'")';
						$valid = true;
						break;
					case '$ne':
						$where[] = '('.$field.' <> "'.Ko_Data_Mysql::SEscape($v2).'")';
						$valid = true;
						break;
					case '$eq':
						$where[] = '('.$field.' = "'.Ko_Data_Mysql::SEscape($v2).'")';
						$valid = true;
						break;
				}
			}
			assert($valid);
		}
		else
		{
			$where[] = '('.$field.' = "'.Ko_Data_Mysql::SEscape($v).'")';
		}
	}
}
