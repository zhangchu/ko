<?php
/**
 * Array
 *
 * @package ko\tool
 * @author jiangjw
 */

class Ko_Tool_Array
{
    /**
     * 挑选一个数组中特定的键值对，过滤掉其余字段
     *   1. 如果源数组中不存在该字段，则返回的数组中也不包含该字段
     *   2. 支持简单映射，名称映射，自定义映射，用法见example
     * 补充说明：
     *   我们经常将数据库中的字段直接透传到前端，有时候会造成不必要的字段传递，
     *   有时候需要dump出来才知道字段名，所以建议有时候在业务层指定返回字段
     *
     * @example
     *   $primary = array('key1' => 'val1', 'key2' => 'val2', 'key3' => 'val3');
     *
     *   $reserved = Ko_Tool_Array::APick(
     *     $primary, 
     *     'key1',                                     //简单映射
     *     'key4',
     *     array('newkey3', 'key3'),                   //名称映射（提取数据时修改键名）
     *     array('newkey2', function($data){return 'new_'.$data['key2'];})  //自定义映射（提取数据时修改键名和值）
     *   );
     *   // $reserved : array('key1' => 'val1', 'newkey3' => 'val3', 'newkey2' => 'new_val2');
     *
     * @param $aData array
     * @return array
     * @api
     */
    public static function APick(array $aData)
    {
        $reserved = array();
        $args = func_get_args();
        $reserveKeys = array_slice($args, 1);
        foreach ($reserveKeys as $k)
        {
            if (is_array($k))
            {
                if (!isset($k[0]))
                {
                    continue;
                }
                $newkey = $k[0];
                $srckey = isset($k[1]) ? $k[1] : $newkey;
                if (is_object($srckey) || is_array($srckey))
                {
                    $val = call_user_func($srckey, $aData);
                    if (!is_null($val))
                    {
                        $reserved[$newkey] = $val;
                    }
                }
                elseif (isset ($aData[$srckey]))
                {
                    $reserved[$newkey] = $aData[$srckey];
                }
            }
            elseif (isset($aData[$k]))
            {
                $reserved[$k] = $aData[$k];
            }
        }
        return $reserved;
    }

    /**
     * 按照数组key的路径查找 可以避免递归判断isset
     *
     * @example 
     *   $input = array('a' => array('b' => 'c'));
     *   Ko_Tool_Array::VOffsetGet($input, 'a.b');
     *   等同于：
     *   Ko_Tool_Array::VOffsetGet($input, array('a', 'b'));
     *   output : 'c'
     *
     *   $input = array('a' => array('c'));
     *   echo Ko_Tool_VOffsetGet($input, 'a.0'); // output : 'c'
     *
     * @param $aData array 数组
     * @param $vPath string|array 路径，可以由分隔符分割，或者由数组表示
     * @param $sDelimiter string 路径中key的分隔符，当路径为数组时该值无效
     * @param $vDefault mixed 如果查找不到，返回的默认值
     * @return mixed
     * @api
     */
    public static function VOffsetGet(array $aData, $vPath, $sDelimiter = '.', $vDefault = null)
    {
        $tokens = is_array($vPath) ? $vPath : explode($sDelimiter, $vPath);
        foreach ($tokens as $tk)
        {
            if (is_array($aData) && isset($aData[$tk]))
            {
                $aData = &$aData[$tk];
            }
            else
            {
                return $vDefault;
            }
        }
        return $aData;
    }

    /**
     * 按照数组key组成的路径赋值, 可以避免频繁判断isset
     * 
     * @example
     *   $array = array();
     *   Ko_Tool_Array::VOffsetSet($array, 'a.b', 'c');
     *   等同于:
     *   Ko_Tool_Array::VOffsetSet($array, array('a', 'b'), 'c');
     *   // $array : array('a' => array('b' => c));
     *
     *   Ko_Tool_VOffsetSet($array, 'a.b.0', 'd');
     *   $array : array('a' => array('b' => array(d)));
     *
     * @param $aData array 数组
     * @param $vPath string|array 路径，可以由分隔符分割，或者由数组表示
     * @param $vValue mixed 待设置的值
     * @param $sDelimiter string 路径中key的分隔符，当路径为数组时该值无效
     * @return void
     * @api
     */
    public static function VOffsetSet(&$aData, $vPath, $vValue, $sDelimiter = '.')
    {
        $tokens = is_array($vPath) ? array_values($vPath) : explode($sDelimiter, $vPath);
        $last = count($tokens) - 1;
        $p = &$aData;
        foreach ($tokens as $i => $tk)
        {
            if ($last === $i)
            {
                $p[$tk] = $vValue;
            }
            else
            {
                if (!isset($p[$tk]) || !is_array($p[$tk]))
                {
                    $p[$tk] = array();
                }
                $p = &$p[$tk];
            }
        }
    }

    /**
     * 按照路径删除数组中的值
     *
     * @param $aData array 数组
     * @param $vPath string|array 路径，可以由分隔符分割，或者由数组表示
     * @param $sDelimiter string 路径中key的分隔符，当路径为数组时该值无效
     * @return void
     * @api
     */
    public static function VOffsetRemove(&$aData, $vPath, $sDelimiter = '.')
    {
        $tokens = is_array($vPath) ? array_values($vPath) : explode($sDelimiter, $vPath);
        $last = count($tokens) - 1;
        $p = &$aData;
        foreach ($tokens as $i => $tk)
        {
            if (!is_array($p) || !isset($p[$tk]))
            {
                break;
            }
            if ($i === $last)
            {
                unset($p[$tk]);
                break;
            }
            $p = &$p[$tk];
        }
    }

    /**
     * 按照路径检查是否存在相应的值
     *
     * @param $aData array 数组
     * @param $vPath string|array 路径，可以由分隔符分割，或者由数组表示
     * @param $sDelimiter string 路径中key的分隔符，当路径为数组时该值无效
     * @return boolean
     * @api
     */
    public static function BOffsetContains($aData, $vPath, $sDelimiter = '.')
    {
        $tokens = is_array($vPath) ? $vPath : explode($sDelimiter, $vPath);
        foreach ($tokens as $tk)
        {
            if (!isset($aData[$tk]))
            {
                return false;
            }
            $aData = &$aData[$tk];
        }
        return true;
    }
}
