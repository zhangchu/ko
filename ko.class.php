<?php
/**
 * KO框架简介
 *
 * <b>KO在那里？</b>
 * <ul>
 * <li>svn://192.168.0.141/dev/code/php/kx/base/doc/ko框架</li>
 * <li>svn://192.168.0.141/dev/code/php/kx/base/www/include/ko</li>
 * </ul>
 *
 * <b>为什么使用KO？</b>
 * <ul>
 *   <li>
 *     数据层解决方案
 *     <ul>
 *       <li>
 *         IDMan
 *         <ul>
 *           <li>对 IDMan 进行了封装，一般不用再直接调用这个中间层的接口</li>
 *         </ul>
 *       </li>
 *       <li>
 *         DBMan
 *         <ul>
 *           <li>数据查询返回结果保存在数组而不是对象中，更方便使用</li>
 *           <li>限制了 SQL 调用的方式，不能直接拼写 SQL，禁止调用 REPLACE，完善的 INSERT 处理</li>
 *           <li>对数据库表唯一健进行了 Cache 化封装，支持进程内 Cache 和 memcache，降低了数据库不同步导致问题的概率</li>
 *           <li>改善了由于进程内缓存占用空间过大导致崩溃的情况</li>
 *           <li>支持使用字符串字段作为分表字段的数据表</li>
 *         </ul>
 *       </li>
 *       <li>
 *         UObjectMan
 *         <ul>
 *           <li>数据查询返回结果保存在数组而不是对象中，更方便使用</li>
 *           <li>与 DBMan 的 Cache 统一化</li>
 *         </ul>
 *       </li>
 *       <li>
 *         MCache
 *         <ul>
 *           <li>更好的 MCache 支持，能将某个 key 的内容设置为空串</li>
 *           <li>支持一种模块 MCache，使用模块 MCache 时，只要保证 key 值在模块内部唯一就可以了</li>
 *         </ul>
 *       </li>
 *       <li>
 *         直接连接数据库
 *         <ul>
 *           <li>更简单的使用方式</li>
 *         </ul>
 *       </li>
 *     </ul>
 *   </li>
 *   <li>
 *     逻辑层解决方案 - 模式库
 *     <ul>
 *       <li>
 *         Item
 *         <ul>
 *           <li>
 *             用一个数据表来观察另外一个数据表的变化情况，并记录
 *             一些数据表需要根据不同字段进行分表时，只需要进行简单的配置就可以完成
 *           </li>
 *         </ul>
 *       </li>
 *       <li>
 *         Limit
 *         <ul>
 *           <li>
 *             对某个对象的行为次数限制
 *           </li>
 *         </ul>
 *       </li>
 *       <li>
 *         Counter
 *         <ul>
 *           <li>
 *             可以支持增长非常快的实时计数器
 *           </li>
 *         </ul>
 *       </li>
 *       <li>
 *         User
 *         <ul>
 *           <li>
 *             通用的用户身份验证系统，用来确保用户登录过程和密码安全
 *           </li>
 *         </ul>
 *       </li>
 *       <li>
 *         OCMT
 *         <ul>
 *           <li>
 *             一种通用的评论系统
 *           </li>
 *         </ul>
 *       </li>
 *       <li>
 *         XList
 *         <ul>
 *           <li>
 *             快速生成一个数据库表的管理维护界面
 *           </li>
 *         </ul>
 *       </li>
 *       <li>
 *         更多的通用模式可添加 ......
 *       </li>
 *     </ul>
 *   </li>
 *   <li>
 *     表现层解决方案 - 视图驱动
 *     <ul>
 *       <li>模版与程序更彻底的分离</li>
 *     </ul>
 *   </li>
 *   <li>
 *     其他
 *     <ul>
 *       <li>与具体应用无关</li>
 *       <li>更简洁的文件命名方式</li>
 *       <li>UTF-8 和 GB18030 更完善的支持</li>
 *     </ul>
 *   </li>
 * </ul>
 *
 * <b>KO框架是什么？</b>
 * <ul>
 * <li>KO框架帮助应用简化和规范开发的过程，但是KO框架是一个与任何具体应用无关的框架</li>
 * <li>KO框架遵循三层结构的实现方式（数据层，逻辑层，表现层）</li>
 * </ul>
 *
 * <b>KO框架能做什么？</b>
 * <ul>
 * <li>KO框架能帮助php工程师简化每个环节的开发任务，让工程师的精力集中在处理数据库设计，业务相关和页面相关的逻辑上</li>
 * <li>KO框架能帮助新人尽快地进入开发状态</li>
 * <li>KO框架对常用的一些应用模式进行了总结，并开发了模式库，使开发更容易，更快，更少出错。</li>
 * <li>KO框架引入了视图驱动的概念，将模版与程序进行更彻底的分离。</li>
 * </ul>
 *
 * <b>KO框架的组成部分</b>
 * <ul>
 *   <li>
 *     工具模块
 *     <ul>
 *       <li>
 *         tool
 *         <ul>
 *           <li>这部分是一些公共的模块，单例处理，命名规则等函数</li>
 *         </ul>
 *       </li>
 *       <li>
 *         html
 *         <ul>
 *           <li>对 html 文本进行处理</li>
 *         </ul>
 *       </li>
 *     </ul>
 *   </li>
 *   <li>
 *     数据层模块
 *     <ul>
 *       <li>
 *         data
 *         <ul>
 *           <li>这部分实现对中间层函数的封装，完成返回数据的标准化</li>
 *           <li>中间层 DBAgent 的 SQL 简单封装</li>
 *           <li>中间层 UObject 的 MCache 和 LCache 缓存</li>
 *         </ul>
 *       </li>
 *       <li>
 *         dao
 *         <ul>
 *           <li>对 SQL 的进一步封装</li>
 *           <li>对分表字段/唯一字段/IDMan和DBMan关系的封装</li>
 *           <li>对 UObject 的进一步封装</li>
 *           <li>对直接连接数据库操作进行封装</li>
 *           <li>对数据表以外的其他类型数据操作进行封装</li>
 *         </ul>
 *       </li>
 *     </ul>
 *   </li>
 *   <li>
 *     逻辑层模块
 *     <ul>
 *       <li>
 *         busi
 *         <ul>
 *           <li>对逻辑层各层的调用限制进行封装</li>
 *         </ul>
 *       </li>
 *       <li>
 *         mode
 *         <ul>
 *           <li>对常用的一些应用逻辑进行封装，可能是逻辑层的，也可能是表现层的</li>
 *         </ul>
 *       </li>
 *     </ul>
 *   </li>
 *   <li>
 *     表现层模块
 *     <ul>
 *       <li>
 *         app
 *         <ul>
 *           <li>app基类定义了app的各处理方法及执行顺序</li>
 *           <li>封装了http协议的基本逻辑处理</li>
 *         </ul>
 *       </li>
 *       <li>
 *         view
 *         <ul>
 *           <li>支持视图驱动编程</li>
 *         </ul>
 *       </li>
 *     </ul>
 *   </li>
 * </ul>
 *
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
 * 应用模板路径
 */
if(!defined('KO_TEMPLATE_DIR'))
{
	define('KO_TEMPLATE_DIR', dirname(KO_INCLUDE_DIR).DS.'template'.DS);
}

/**
 * 模板编译路径
 */
if(!defined('KO_TEMPLATE_C_DIR'))
{
	define('KO_TEMPLATE_C_DIR', KO_TEMPLATE_DIR.'templates_c'.DS);
}

/**
 * 页面字符集，cgi 输入参数分析使用
 */
if(!defined('KO_CHARSET'))
{
	define('KO_CHARSET', 'UTF-8');
}

/**
 * 模版中使用的下面标记开头的变量可以由程序自动分析
 */
if(!defined('KO_VIEW_AUTOTAG'))
{
	define('KO_VIEW_AUTOTAG', 'koAuto');
}

/**
 * KProxy 位置的服务器
 */
if(!defined('KO_PROXY'))
{
	define('KO_PROXY', '');
}
/**
 * 配置数据库连接使用哪种引擎，如：kproxy/mysql
 */
if(!defined('KO_DB_ENGINE'))
{
	define('KO_DB_ENGINE', 'kproxy');
}
/**
 * 配置数据库主机端口，直连数据库的时候使用
 */
if(!defined('KO_DB_HOST'))
{
	define('KO_DB_HOST', '192.168.0.140');
}
/**
 * 配置数据库用户名
 */
if(!defined('KO_DB_USER'))
{
	define('KO_DB_USER', 'dev');
}
/**
 * 配置数据库密码
 */
if(!defined('KO_DB_PASS'))
{
	define('KO_DB_PASS', 'dev2008');
}
/**
 * 配置数据库库名
 */
if(!defined('KO_DB_NAME'))
{
	define('KO_DB_NAME', 'dev_config');
}

/**
 * 配置 MongoDB Host
 */
if(!defined('KO_MONGO_HOST'))
{
	define('KO_MONGO_HOST', '192.168.1.190:27017,192.168.1.190:27018,192.168.1.190:27019');
}
/**
 * 配置 MongoDB 副本集
 */
if(!defined('KO_MONGO_REPLICASET'))
{
	define('KO_MONGO_REPLICASET', 'rs0');
}
/**
 * 配置 MongoDB 用户
 */
if(!defined('KO_MONGO_USER'))
{
	define('KO_MONGO_USER', '');
}
/**
 * 配置 MongoDB 密码
 */
if(!defined('KO_MONGO_PASS'))
{
	define('KO_MONGO_PASS', '');
}
/**
 * 配置 MongoDB 数据库名称
 */
if(!defined('KO_MONGO_NAME'))
{
	define('KO_MONGO_NAME', 'testdb');
}
/**
 * 配置 MongoDB 缺省的安全模式
 */
if(!defined('KO_MONGO_SAFE'))
{
	define('KO_MONGO_SAFE', 1);
}

/**
 * 使用的序列化编码算法，Vbs/Serialize/IgBinary
 */
if(!defined('KO_ENC'))
{
	define('KO_ENC', 'IgBinary');
}

/**
 * 使用的图形库，Gd/Imagick
 */
if(!defined('KO_IMAGE'))
{
	define('KO_IMAGE', 'Imagick');
}

/**
 * 配置使用的 memcache 的连接方式，kproxy/memcache/saemc
 */
if(!defined('KO_MC_ENGINE'))
{
	define('KO_MC_ENGINE', 'kproxy');
}
/**
 * 配置使用的 memcache 服务器，KO_MC_ENGINE 为 memcache 有效
 */
if(!defined('KO_MC_HOST'))
{
	define('KO_MC_HOST', 'localhost:11211');
}
/**
 * 配置使用的 localcache 的连接方式，kproxy
 */
if(!defined('KO_LC_ENGINE'))
{
	define('KO_LC_ENGINE', 'kproxy');
}
/**
 * 配置使用的 redis 的连接方式，kproxy/redis
 */
if(!defined('KO_REDIS_ENGINE'))
{
	define('KO_REDIS_ENGINE', 'kproxy');
}
/**
 * 配置使用的 redis 服务器，KO_REDIS_ENGINE 为 redis 有效
 */
if(!defined('KO_REDIS_HOST'))
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

if(!KO_SPL_AUTOLOAD)
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