<?php
/**
 * MsgParse
 *
 * @package ko\html
 * @author zhangchu
 */

//include_once('../../../htdocs/global.php');
//include_once('../ko.class.php');

class Ko_Html_MsgParse
{
	public static function sParse($sHtml, $iMaxLength = 0, $sCharset = '')
	{
		$filter = new Ko_Html_MsgFilter;
		return Ko_Html_Parse::sParse($filter, $sHtml, $iMaxLength, $sCharset);
	}
}

/*

function getMicro()
{
	list($msec, $sec) = explode(" ", microtime());
	return ((float)$msec + (float)$sec);
}

if ($argc != 2)
{
	echo "Usage: ".$argv[0]." <filename>\n";
	exit;
}
$filename = $argv[1];

$content = file_get_contents($filename);

$start = getMicro();
for ($i=0; $i<1; $i++)
{
	$old = new CHtmlParse;
	$ret = $old->parse($content);
	$ret = $old->getAbstract($ret, 6000);
	echo $i." ".memory_get_usage()."\n";
}
$end = getMicro();
echo ($end - $start)."\n";
$start = getMicro();
for ($i=0; $i<1; $i++)
{
	$ret = Ko_Html_MsgParse::sParse($content, 65535);
	echo $i." ".memory_get_usage()."\n";
}
$end = getMicro();
echo ($end - $start)."\n";

echo strlen($ret)."\n";
echo $ret;
*/
?>