<?php

include_once('ko.class.php');

define('KO_SMARTY_INC', '/usr/share/php/Smarty-3.1.21/libs/Smarty.class.php');
define('KO_RUNTIME_DIR', KO_DIR . 'runtime/');          //确保这个目录可以被web用户写入
define('KO_CONFIG_SITE_INI', KO_DIR . 'conf/site.ini');

require_once('web/Bootstrap.php');
