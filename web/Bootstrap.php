<?php
/**
 * Bootstrap
 *
 * @package ko/Web
 * @author zhangchu
 */

if (!defined('KO_WEB_BOOTSTRAP'))
{
	define('KO_WEB_BOOTSTRAP', 1);
	Ko_Tool_Xhprof::VStart();
	
	Ko_Web_Event::Trigger('ko.bootstrap', 'before');

	Ko_Web_Config::VLoad();
	Ko_Web_Event::Trigger('ko.config', 'after');

	Ko_Web_Error::VHandle();
	Ko_Web_Rewrite::VHandle();

	Ko_Web_Event::Trigger('ko.dispatch', 'before');
	if (Ko_Web_Route::IDispatch($phpFilename))
	{
		Ko_Web_Event::Trigger('ko.dispatch', '404');
	}
	else if ('' !== $phpFilename)
	{
		$cwd = getcwd();
		chdir(dirname($phpFilename));
		require_once($phpFilename);
		chdir($cwd);
		Ko_Web_Route::VCallIndex();
	}
}
