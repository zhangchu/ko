<?php

namespace koApps\def;

\Ko_Web_Route::VGet('index', function() {
	$render = new \Ko_View_Render_Smarty();
	$render->oSetTemplate('hello.html');

	\Ko_Web_Response::VSend($render);
});

