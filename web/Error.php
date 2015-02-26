<?php
/**
 * Error
 *
 * @package ko\web
 * @author jiangjw & zhangchu
 */

class Ko_Web_Error
{
	private static $_vPrevErrorHandler = null;
	private static $_vPrevExceptionHandler = null;

	public static function VHandle()
	{
		self::$_vPrevErrorHandler = set_error_handler('Ko_Web_Error::BHandleError', error_reporting());
		self::$_vPrevExceptionHandler = set_exception_handler('Ko_Web_Error::VHandleException');
		register_shutdown_function('Ko_Web_Error::VHandleShutdown');
	}

	public static function BHandleError($errno, $errstr, $errfile, $errline, $errcontext)
	{
		static $s_a500Errors = array(
			E_ERROR,
			E_CORE_ERROR,
			E_COMPILE_ERROR,
			E_USER_ERROR,
		);
		
		Ko_Web_Event::Trigger('ko.error', 'error',
			$errno, $errstr, $errfile, $errline, $errcontext);
		if (null !== self::$_vPrevErrorHandler)
		{
			call_user_func(self::$_vPrevErrorHandler,
				$errno, $errstr, $errfile, $errline, $errcontext);
		}
		if (in_array($errno, $s_a500Errors))
		{
			Ko_Web_Event::Trigger('ko.error', '500',
				$errno, $errstr, $errfile, $errline, $errcontext);
		}
		return false;
	}

	public static function VHandleException(Exception $ex)
	{
		$errno = E_USER_ERROR;
		$errstr = 'Caught Exception: '.$ex->getMessage();
		$errfile = $ex->getFile();
		$errline = $ex->getLine();
		$errcontext = $ex->getTrace();
		
		Ko_Web_Event::Trigger('ko.error', 'error',
			$errno, $errstr, $errfile, $errline, $errcontext);
		Ko_Web_Event::Trigger('ko.error', 'exception', $ex);
		if (null !== self::$_vPrevExceptionHandler)
		{
			call_user_func(self::$_vPrevExceptionHandler, $ex);
		}
		Ko_Web_Event::Trigger('ko.error', '500',
			$errno, $errstr, $errfile, $errline, $errcontext);
	}

	public static function VHandleShutdown()
	{
		static $s_aFatalErrors = array(
			E_ERROR,
			E_CORE_ERROR,
			E_COMPILE_ERROR,
		);
		
		$error = error_get_last();
		if (null !== $error && in_array($error['type'], $s_aFatalErrors))
		{
			Ko_Web_Event::Trigger('ko.error', 'error',
				$error['type'], 
				$error['message'],
				$error['file'], 
				$error['line'], 
				array()
			);
		}
		Ko_Web_Event::Trigger('ko.error', 'shutdown');
	}
	
	public static function V500($errno, $errstr, $errfile, $errline, $errcontext)
	{
		$error = self::SFormatError($errno, $errstr, $errfile, $errline, $errcontext);
		$render = new Ko_View_Render_TEXT;
		$render->oSetData('error', $error);
		
		Ko_Web_Response::VSetHttpCode(500);
		Ko_Web_Response::VSetContentType('text/plain');
		Ko_Web_Response::VAppendBody($render);
		Ko_Web_Response::VSend();
	}
	
	public static function SFormatError($errno, $errstr, $errfile, $errline, $errcontext)
	{
		return 'Errno: '.$errno."\n"
			.'Error: '.$errstr."\n"
			.'File: '.$errfile."\n"
			.'Line: '.$errline."\n"
			.'Context: '.Ko_Tool_Stringify::SConvAny($errcontext);
	}
}
