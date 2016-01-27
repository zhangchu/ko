<?php

namespace apps\def;

$render = new \Ko_View_Render_Smarty();
$render->oSetData('IP', \Ko_Tool_Ip::SGetClientIP())
	->oSetTemplate('index.html');

\Ko_Web_Response::VSend($render);
