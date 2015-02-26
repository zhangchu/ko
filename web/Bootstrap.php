<?php
/**
 * Bootstrap
 *
 * @package ko/Web
 * @author zhangchu
 */

class Ko_Web_Bootstrap
{
	public static function VInit()
	{
		Ko_Web_Error::VHandle();

		Ko_Web_Event::Trigger('ko.rewrite', 'before');
		Ko_Web_Rewrite::VHandle();

		Ko_Web_Event::Trigger('ko.dispatch', 'before');
		if (Ko_Web_Route::IDispatch())
		{
			Ko_Web_Event::Trigger('ko.dispatch', '404');
		}
	}
}
