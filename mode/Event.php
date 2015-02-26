<?php
/**
 * Event
 *
 * @brief: 给对象绑定事件
 *
 * @package ko\Mode
 * @author: jiangjw & zhangchu
 */

class Ko_Mode_Event
{
	private $_aEvents = array();

	/**
	 * 给对象绑定单个事件：
	 *   $event->oOn('event_name', array($obj, 'function_name'));
	 * 或者
	 *   $event->oOn('event_name', function() {});
	 * 或者
	 *   $event->oOn('event_name', 'global_function_name');
	 *
	 * @param string $name 事件名称
	 * @param array|closure|string $callback 事件被触发后的回调函数
	 * @return $this
	 * @api
	 */
	public function oOn($name, $callback = null)
	{
		if (!isset($this->_aEvents[$name]))
		{
			$this->_aEvents[$name] = array();
		}
		array_push($this->_aEvents[$name], array('once' => false, 'event' => $callback));
		return $this;
	}

	/**
	 * 绑定事件, 触发后解除绑定
	 *
	 * @param string $name 事件名称
	 * @param array|closure|string $callback 事件被触发后的回调函数
	 * @return $this
	 * @api
	 */
	public function oOnce($name, $callback = null)
	{
		if (!isset($this->_aEvents[$name]))
		{
			$this->_aEvents[$name] = array();
		}
		array_push($this->_aEvents[$name], array('once' => true, 'event' => $callback));
		return $this;
	}

	/**
	 * 解除绑定
	 * 解除单个事件
	 *   $callback = function() {};
	 *   $event->oOn('event_name', $callback);
	 *   $event->oOff('event_name', $callback);
	 * 解除事件名称下的全部事件
	 *   $event->oOff('event_name');
	 * 解除对象的全部事件
	 *   $event->oOff();
	 *
	 * @param string $name 事件名称
	 * @param array|closure|string $callback 事件被触发后的回调函数
	 * @return $this
	 * @api
	 */
	public function oOff($name = null, $callback = null)
	{
		if (null === $name)
		{
			$this->_aEvents = array();
		}
		else if (null === $callback)
		{
			unset($this->_aEvents[$name]);
		}
		else if (isset($this->_aEvents[$name]))
		{
			foreach ($this->_aEvents[$name] as $k => $ev)
			{
				if ($ev['event'] === $callback)
				{
					unset($this->_aEvents[$name][$k]);
					break;
				}
			}
		}
		return $this;
	}

	/**
	 * 触发单个事件
	 *   $event->oTrigger('event_name', $arg1, $arg2);
	 *
	 * @param string $name 事件名称
	 * @return $this
	 * @api
	 */
	public function oTrigger($name)
	{
		if (isset($this->_aEvents[$name]))
		{
			$args = func_get_args();
			array_shift($args);
			foreach ($this->_aEvents[$name] as $k => $ev)
			{
				call_user_func_array($ev['event'], $args);
				if ($ev['once'])
				{
					unset($this->_aEvents[$name][$k]);
				}
			}
		}
		return $this;
	}
}
