<?php

namespace koApps\def;

class MRest_xxx
{
    const ERR_UNKNOWN           = 1;

	public static $s_aConf = array(
        'unique' => 'int',                  // 可以是一个组合键，在uri中用某种方式编码，使用str2key函数进行解码
        'stylelist' => array(               // 返回主要数据样式列表
        ),
        'exstylelist' => array(             // 返回扩展数据样式列表
        ),
        'filterstylelist' => array(         // 筛选数据参数样式列表
        ),
		'poststylelist' => array(           // 添加数据参数样式列表
        ),
        'putstylelist' => array(            // 修改数据参数样式列表
        ),
		'errorlist' => array(               // 错误代码和文案列表
			self::ERR_UNKNOWN => array(
				'message' => '未知错误',
			),
        ),
    );

    /**
     * unique 是组合键时，用来对uri中的唯一健进行解码
     */
    public function str2key($str)
    {
    }

    /**
     * @param $id
     * @param $style['style']           req里的data_style参数
     * @param $style['decorate']        req里的data_decorate参数
     * 
     * @return $data
     */
    public function get($id, $style)
    {
    }

    /**
     * @param $style['style']           req里的data_style参数
     * @param $style['decorate']        req里的data_decorate参数
     * @param $page['mode']             req里的page[mode]参数
     * @param $page['num']              req里的page[num]参数
     * @param $page['no']               req里的page[no]参数
     * @param $page['boundary']         req里的page[boundary]参数
     * @param $filter                   req里的filter参数，由filter_style参数和filterstylelist配置决定数据类型
     * @param $ex['style']              req里的ex_style参数
     * @param $ex['decorate']           req里的ex_decorate参数
     * @param $filter_style             req里的filter_style参数
     * 
     * @return $data['list']
     * @return $data['page']['mode']
     * @return $data['page']['num']
     * @return $data['page']['no']
     * @return $data['page']['data_total']
     * @return $data['page']['next']
     * @return $data['page']['next_boundary']
     * @return $data['ex']
     */
    public function getMulti($style, $page, $filter, $ex, $filter_style)
    {
    }

    /**
     * @param $update                   req里的update参数，由post_style参数和poststylelist配置决定数据类型
     * @param $after['style']           req里的after_style参数
     * @param $after['decorate']        req里的after_decorate参数
     * @param $post_style               req里的post_style参数
     * 
     * @return $data['key']
     * @return $data['after']
     */
    public function post($update, $after, $post_style)
    {
    }

    /**
     * @param $list[]['update']         req里的list参数，由post_style参数和poststylelist配置决定数据类型
     * @param $post_style               req里的post_style参数
     * 
     * @return null
     */
    public function postMulti($list, $post_style)
    {
    }

    /**
     * @param $id
     * @param $update                   req里的update参数，由put_style参数和putstylelist配置决定数据类型
     * @param $before['style']          req里的before_style参数
     * @param $before['decorate']       req里的before_decorate参数
     * @param $after['style']           req里的after_style参数
     * @param $after['decorate']        req里的after_decorate参数
     * @param $put_style                req里的put_style参数
     * 
     * @return $data['key']
     * @return $data['before']
     * @return $data['after']
     */
    public function put($id, $update, $before, $after, $put_style)
    {
    }

    /**
     * @param $list[]['key]             req里的list参数，唯一健
     * @param $list[]['update']         req里的list参数，由put_style参数和putstylelist配置决定数据类型
     * @param $put_style                req里的put_style参数
     * 
     * @return null
     */
    public function putMulti($list, $put_style)
    {
    }

    /**
     * @param $id
     * @param $before['style']          req里的before_style参数
     * @param $before['decorate']       req里的before_decorate参数
     * 
     * @return $data['key']
     * @return $data['before']
     */
    public function delete($id, $before)
    {
    }

    /**
     * @param $list[]['key]             req里的list参数，唯一健
     * 
     * @return null
     */
    public function deleteMulti($list)
    {
    }
}
