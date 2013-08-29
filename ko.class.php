<?php
/**
 * @package ko
 * @author zhangchu
 */

/**
 * 目录分隔符
 */
if (!defined('DS'))
{
	define('DS', DIRECTORY_SEPARATOR);
}

/**
 * 临时文件目录
 */
if (!defined('KO_TEMPDIR'))
{
	define('KO_TEMPDIR', '/tmp/');
}

/**
 * 取值0-9，为0为生产状态，其他为调试状态，调试状态输出调试内容，调试信息输出到 KO_LOG_FILE 定义的文件，取值越大，信息越多
 * 1: SQL 语句，cache 统计
 * 2: 其他中间层请求
 */
if (!defined('KO_DEBUG'))
{
	define('KO_DEBUG', 0);
}

/**
 * 保存调试信息的文件名
 */
if (!defined('KO_LOG_FILE'))
{
	define('KO_LOG_FILE', '/tmp/ko.log');
}

/**
 * KO 部署的路径
 */
if (!defined('KO_DIR'))
{
	define('KO_DIR', dirname(__FILE__).DS);
}

/**
 * 应用 include 路径
 */
if (!defined('KO_INCLUDE_DIR'))
{
	define('KO_INCLUDE_DIR', dirname(KO_DIR).DS);
}

/**
 * Smarty 类文件定义
 */
if (!defined('KO_SMARTY_INC'))
{
	define('KO_SMARTY_INC', dirname(KO_DIR).'/vendor/Smarty-3.1.14/libs/Smarty.class.php');
}

/**
 * 应用模板路径
 */
if (!defined('KO_TEMPLATE_DIR'))
{
	define('KO_TEMPLATE_DIR', dirname(KO_INCLUDE_DIR).DS.'template'.DS);
}

/**
 * 模板编译路径
 */
if (!defined('KO_TEMPLATE_C_DIR'))
{
	define('KO_TEMPLATE_C_DIR', KO_TEMPLATE_DIR.'templates_c'.DS);
}

/**
 * 页面字符集，cgi 输入参数分析使用
 */
if (!defined('KO_CHARSET'))
{
	define('KO_CHARSET', 'UTF-8');
}

/**
 * 模版中使用的下面标记开头的变量可以由程序自动分析
 */
if (!defined('KO_VIEW_AUTOTAG'))
{
	define('KO_VIEW_AUTOTAG', 'koAuto');
}

/**
 * KProxy 位置的服务器
 */
if (!defined('KO_PROXY'))
{
	define('KO_PROXY', '');
}
/**
 * 配置数据库连接使用哪种引擎，如：kproxy/mysql
 */
if (!defined('KO_DB_ENGINE'))
{
	define('KO_DB_ENGINE', 'mysql');
}
/**
 * 配置数据库主机端口，直连数据库的时候使用
 */
if (!defined('KO_DB_HOST'))
{
	define('KO_DB_HOST', '192.168.0.140');
}
/**
 * 配置数据库用户名
 */
if (!defined('KO_DB_USER'))
{
	define('KO_DB_USER', 'dev');
}
/**
 * 配置数据库密码
 */
if (!defined('KO_DB_PASS'))
{
	define('KO_DB_PASS', 'dev2008');
}
/**
 * 配置数据库库名
 */
if (!defined('KO_DB_NAME'))
{
	define('KO_DB_NAME', 'dev_config');
}

/**
 * 配置 MongoDB Host
 */
if (!defined('KO_MONGO_HOST'))
{
	define('KO_MONGO_HOST', '192.168.1.190:27017,192.168.1.190:27018,192.168.1.190:27019');
}
/**
 * 配置 MongoDB 副本集
 */
if (!defined('KO_MONGO_REPLICASET'))
{
	define('KO_MONGO_REPLICASET', 'rs0');
}
/**
 * 配置 MongoDB 用户
 */
if (!defined('KO_MONGO_USER'))
{
	define('KO_MONGO_USER', '');
}
/**
 * 配置 MongoDB 密码
 */
if (!defined('KO_MONGO_PASS'))
{
	define('KO_MONGO_PASS', '');
}
/**
 * 配置 MongoDB 数据库名称
 */
if (!defined('KO_MONGO_NAME'))
{
	define('KO_MONGO_NAME', 'testdb');
}
/**
 * 配置 MongoDB 缺省的安全模式
 */
if (!defined('KO_MONGO_SAFE'))
{
	define('KO_MONGO_SAFE', 1);
}

/**
 * 使用的序列化编码算法，Vbs/Serialize/IgBinary
 */
if (!defined('KO_ENC'))
{
	define('KO_ENC', 'Serialize');
}

/**
 * 使用的图形库，Gd/Imagick
 */
if (!defined('KO_IMAGE'))
{
	define('KO_IMAGE', 'Imagick');
}

/**
 * 配置使用的 memcache 的连接方式，kproxy/memcache/saemc
 */
if (!defined('KO_MC_ENGINE'))
{
	define('KO_MC_ENGINE', 'memcache');
}
/**
 * 配置使用的 memcache 服务器，KO_MC_ENGINE 为 memcache 有效
 */
if (!defined('KO_MC_HOST'))
{
	define('KO_MC_HOST', 'localhost:11211');
}
/**
 * 配置使用的 localcache 的连接方式，kproxy
 */
if (!defined('KO_LC_ENGINE'))
{
	define('KO_LC_ENGINE', 'kproxy');
}
/**
 * 配置使用的 redis 的连接方式，kproxy/redis
 */
if (!defined('KO_REDIS_ENGINE'))
{
	define('KO_REDIS_ENGINE', 'redis');
}
/**
 * 配置使用的 redis 服务器，KO_REDIS_ENGINE 为 redis 有效
 */
if (!defined('KO_REDIS_HOST'))
{
	define('KO_REDIS_HOST', 'localhost:6379');
}

/**
 * KO对于 K 和 IK 开头的接口和类定义了 AutoLoad 的处理方式，如果应用需要自己处理 AutoLoad 可以设置 KO_SPL_AUTOLOAD 为 1
 */
if (!defined('KO_SPL_AUTOLOAD'))
{
	define('KO_SPL_AUTOLOAD', 0);
}

if (!KO_SPL_AUTOLOAD)
{
	spl_autoload_register('koAutoload');
}

/**
 * KO定义了断言操作的处理方式，如果应用需要自己处理，可以设置 KO_ASSERT 为 1
 */
if (!defined('KO_ASSERT'))
{
	define('KO_ASSERT', 0);
}

if (!KO_ASSERT)
{
	assert_options(ASSERT_ACTIVE, 1);
	assert_options(ASSERT_WARNING, 0);
	assert_options(ASSERT_BAIL, KO_DEBUG ? 1 : 0);
	assert_options(ASSERT_QUIET_EVAL, 1);
	assert_options(ASSERT_CALLBACK, 'koAssertCallback');
}

/**
 * K 和 IK 开头的类自动加载
 *
 * <ul>
 * <li>使用 K 或 IK 开头</li>
 * <li>应用的 include 目录下分级目录使用小写字母</li>
 * <li>多级目录使用下划线分隔，并且在类名中，每个目录的首字母大写</li>
 * <li>最后在跟上文件名，如：类 KO2o_Tools_geoApi 的文件应该是 include/o2o/tools/geoApi.php 或 include/o2o/tools/KO2o_Tools_geoApi.php</li>
 * </ul>
 */
function koAutoload($sClassName)
{
	if (substr($sClassName, 0, 1) !== 'K')
	{
		if (substr($sClassName, 0, 2) === 'IK')
		{
			koAutoload(substr($sClassName, 1));
		}
		return;
	}
	$pos = strrpos($sClassName, '_');
	if (false === $pos)
	{
		return;
	}
	if (substr($sClassName, 0, 3) === 'Ko_')
	{
		$modulename = substr($sClassName, 3, $pos - 3);
		$includeRoot = KO_DIR;
	}
	else
	{
		$modulename = substr($sClassName, 1, $pos - 1);
		$includeRoot = KO_INCLUDE_DIR;
	}
	$modulename = strtolower($modulename);
	$modulepath = str_replace('_', DS, $modulename);

	$filename = substr($sClassName, 1 + $pos);
	$classfile = $includeRoot.$modulepath.DS.$filename.'.php';
	if (is_file($classfile))
	{
		require_once($classfile);
		return;
	}

	$classfile = $includeRoot.$modulepath.DS.$sClassName.'.php';
	if (is_file($classfile))
	{
		require_once($classfile);
		return;
	}
}

/**
 * 断言处理回调函数
 *
 * <ul>
 * <li>断言，通常应该是在开发测试过程中解决的问题</li>
 * <li>调试状态，输出断言的文件名/行号等相关信息</li>
 * <li>生产状态，抛出异常，没有详细信息</li>
 * </ul>
 */
function koAssertCallback($file, $line, $code)
{
	if (KO_DEBUG)
	{
		echo 'Assertion Failed:'."\n\t".'File '.$file."\n\t".'Line '.$line."\n\t".'Code '.$code."\n";
		debug_print_backtrace();
	}
	else
	{
		$error = 'Assertion Failed!';
		throw new Exception($error);
	}
}

/*

class test implements IKo_Tool_Singleton
{
	public static function OInstance($sClassName)
	{
	}
}

$a = new Ko_Tool_Module();
var_dump($a);

assert_options(ASSERT_BAIL, 0);
assert(0);

$a = new KA_B_C();

*/

?>