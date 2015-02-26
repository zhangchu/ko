<?php
/**
 * @package ko
 * @author zhangchu
 */

Ko_Web_Event::Trigger('ko.dispatch', 'before');
if (Ko_Web_Route::IDispatch())
{
	Ko_Web_Event::Trigger('ko.dispatch', '404');
}
