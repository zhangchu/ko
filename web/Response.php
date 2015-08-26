<?php
/**
 * Response
 *
 * @package ko\web
 * @author jiangjw & zhangchu
 */

class Ko_Web_Response
{
    private static $s_sProtocol     = 'HTTP/1.1';
    private static $s_iHttpCode     = 200;
    private static $s_aCookies      = array();
    private static $s_aHeaders      = array();

    private static $s_oBody         = null;
    private static $s_bSendBody     = false;

    /**
     * http状态码
     * @param int $iCode
     */
    public static function VSetHttpCode($iCode)
    {
        self::$s_iHttpCode = intval($iCode);
    }

    /**
     * 设置Content-Type及Charset
     * @param string $sContentType content-type
     * @param string|null $sCharset 编码
     */
    public static function VSetContentType($sContentType, $sCharset = null)
    {
        static $s_aWhiteList = array(
            'application/javascript',
            'application/x-javascript',
            'application/json',
            'application/xml',
            'application/rss+xml',
        );
        
        $sContentType = ltrim(trim($sContentType), '.');
        $sContentType = (false !== strpos($sContentType, '/'))
            ? $sContentType : Ko_Tool_Mime::sGetMimeType($sContentType);
        if (null === $sCharset)
        {
            $sCharset = (in_array($sContentType, $s_aWhiteList)
                || 'text/' === substr($sContentType, 0, 5))
                ? KO_CHARSET : '';
        }
        if ('' === $sCharset)
        {
            self::_VHeader('Content-Type', $sContentType);
        }
        else
        {
            self::_VHeader('Content-Type', $sContentType.'; charset='.$sCharset);
        }
    }

    /**
     * 设置Content-Disposition
     * @param string $sFileName 文件名
     * @param bool $bIsAttachment true: 下载；false: 在浏览器中打开
     */
    public static function VSetContentDisposition($sFileName, $bIsAttachment = true)
    {
        $type = $bIsAttachment ? 'attachment' : 'inline';
        self::_VHeader('Content-Disposition', $type.'; filename='.$sFileName);
    }
    
    /**
     * 设置或删除客户端cookie
     * @param string $sName
     * @param string $sValue 当该值空串，null，或者false时表示删除cookie
     * @param int $iExpire unix timestamp
     * @param string $sPath 路径，默认为/
     * @param string $sDomain 域名
     * @param bool $bSecure 是否仅限于安全连接(HTTPS)； 默认不限
     * @param bool $bHttpOnly 是否仅限于HTTP协议（脚本不可访问)； 默认脚本不可以访问
     */
    public static function VSetCookie (
        $sName,
        $sValue = '',
        $iExpire = 0,
        $sPath = '/',
        $sDomain = '',
        $bSecure = false,
        $bHttpOnly = true
    ) {
        self::$s_aCookies[] = array(
            'name'     => $sName,
            'value'    => $sValue,
            'expire'   => $iExpire,
            'path'     => $sPath,
            'domain'   => $sDomain,
            'secure'   => $bSecure,
            'httpOnly' => $bHttpOnly
        );
    }

    /**
     * 重定向
     * @param string $sLocation 重定向地址
     * @param bool $bPermanently 是否为永久重定向
     */
    public static function VSetRedirect($sLocation, $bPermanently = false)
    {
        $statusCode = $bPermanently ? 301 : 302;
        self::$s_iHttpCode = $statusCode;
        self::_VHeader('Location', $sLocation);
    }

    /**
     * 设置或获取自定义header
     * @param string $sName
     *  如果不以`X-`开头，则自动加上`X-`;
     * @param string $sValue
     */
    public static function VSetExtraHeader($sName, $sValue)
    {
        if (0 !== strncasecmp($sName, 'X-', 2))
        {
            $sName = 'X-'.$sName;
        }
        else
        {
            $sName = ucfirst($sName);
        }
        self::_VHeader($sName, $sValue);
    }
    
    /**
     * @param Ko_View_Render_Base $oBody
     */
    public static function VAppendBody(Ko_View_Render_Base $oBody)
    {
        if (null === self::$s_oBody || !($oBody INSTANCEOF Ko_View_Render_List))
        {
            self::$s_oBody = $oBody;
            if ($oBody INSTANCEOF Ko_View_Render_TEXT)
            {
                self::VSetContentType('text/plain');
            }
            else if ($oBody INSTANCEOF Ko_View_Render_JSON)
            {
                self::VSetContentType('application/json');
            }
            else if ($oBody INSTANCEOF Ko_View_Render_FILE)
            {
                $ext = pathinfo($oBody->sFilename(), PATHINFO_EXTENSION);
                self::VSetContentType($ext);
            }
        }
        else
        {
            self::$s_oBody->oAppend($oBody);
        }
    }

    /**
     * 发送数据头和数据体
     */
    public static function VSend($oBody = null)
    {
        if($oBody instanceof Ko_View_Render_Base) 
        {
            self::VAppendBody($oBody);
        }
        if (!self::$s_bSendBody)
        {
            self::_VSendHeader();
        }
        self::_VSendBody();
    }

    private static function _VSendHeader()
    {
        if (200 != self::$s_iHttpCode)
        {
            $message = Ko_Tool_HttpCode::sGetText(self::$s_iHttpCode);
            header(self::$s_sProtocol.' '.self::$s_iHttpCode.' '.$message);
        }

        foreach (self::$s_aCookies as $c)
        {
            setcookie(
                $c['name'], $c['value'], $c['expire'], $c['path'],
                $c['domain'], $c['secure'], $c['httpOnly']
            );
        }

        if (!isset(self::$s_aHeaders['Content-Type']))
        {
            self::$s_aHeaders['Content-Type'] = 'text/html; charset='.KO_CHARSET;
        }
        foreach (self::$s_aHeaders as $name => $value)
        {
            header($name.': '.$value);
        }
    }

    private static function _VSendBody()
    {
        if (null !== self::$s_oBody)
        {
            echo self::$s_oBody->sRender();
            self::$s_oBody = null;
            self::$s_bSendBody = true;
        }
    }

    private static function _VHeader($sName, $sValue)
    {
        self::$s_aHeaders[$sName] = trim($sValue);
    }
}
