<?php

$root = \Ko_Tool_CodeCoverage::root();
if ('' == $root) {
	exit;
}

\Ko_Web_Route::VGet('index', function() {
	global $root;

	$dir = \Ko_Web_Request::SGet('dir');
	if (strncmp($root, $dir, strlen($root))) {
		$dir = $root;
	}
	$dir = rtrim($dir, '/') . '/';
	$smarty = new \Ko_View_Render_Smarty();
	$smarty->oSetTemplate('index.html')
		->oSetData('dir', $dir)
		->oSetData('basename', basename($dir))
		->oSetData('parent', dirname($dir));

	$dirlist = $filelist = array();
	if (is_dir($dir)) {
		if ($dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				if ('.' == $file[0]) {
					continue;
				}
				$filename = $dir . $file;
				if (is_dir($filename)) {
					if (false !== \Ko_Tool_CodeCoverage::ccname($filename . '/')) {
						$dirlist[$file] = $file;
					}
				} else if (is_file($filename) && 'php' === pathinfo($filename, PATHINFO_EXTENSION)) {
					if (false !== \Ko_Tool_CodeCoverage::ccname($filename)) {
						$filelist[] = $file;
					}
				}
			}
			closedir($dh);
		}
	}

	$smarty->oSetData('dirlist', $dirlist)
		->oSetData('filelist', $filelist)
		->oSend();
});

\Ko_Web_Route::VGet('file', function() {
	global $root;

	$file = \Ko_Web_Request::SGet('file');
	if (strncmp($root, $file, strlen($root))) {
		\Ko_Web_Response::VSetRedirect('../');
		\Ko_Web_Response::VSend();
		exit;
	}
	$smarty = new \Ko_View_Render_Smarty();
	$smarty->oSetTemplate('file.html')
		->oSetData('file', $file)
		->oSetData('basename', basename($file))
		->oSetData('parent', dirname($file));

	if (is_file($file)) {
		$lines = file($file);
		$filemtime = filemtime($file);
		$exec_lines = \Ko_Tool_CodeCoverage::read($file);
		$smarty->oSetData('filemtime', date('Y-m-d H:i:s', $filemtime))
			->oSetData('lines', $lines)
			->oSetData('exec_lines', $exec_lines);
	}

	$smarty->oSend();
});
