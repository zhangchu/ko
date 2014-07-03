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
interface IKo_Tool_Option
{
	/**
	 * @param Ko_Tool_SQL|Ko_Tool_MONGO|array $oOption
	 * @return boolean
	 */
	public static function BIsWhereEmpty($oOption, $bIsMongoDB);
}

class Ko_Tool_Option implements IKo_Tool_Option
{
	/**
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
}
