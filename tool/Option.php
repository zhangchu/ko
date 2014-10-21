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
		return $where;
	}
}
