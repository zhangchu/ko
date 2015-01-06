<?php
/**
 * Response
 *
 * @package ko\web
 * @author jiangjw & zhangchu
 */

class Ko_Web_Response
{
    private static $_ORes;

    private $_sProtocol     = 'HTTP/1.1';
    private $_iHttpCode     = 200;
    private $_aCookies      = array();
    private $_aHeaders      = array();

    private $_oBody         = null;
    private $_bSendBody     = false;

    private function __construct()
    {
        $this->_oHeader('Content-Type', 'text/html; charset='.KO_CHARSET);
    }

    public final static function OInstance()
    {
        if (null === self::$_ORes)
        {
            self::$_ORes = new static();
        }
        return self::$_ORes;
    }

    /**
     * http状态码
     * @param int $iCode
     * @return $this
     */
    public function oSetHttpCode($iCode)
    {
        $this->_iHttpCode = intval($iCode);
        return $this;
    }

    /**
     * Content-Type值
     * @param string $sContentType e.g. text/html
     * @return $this
     */
    public function oSetContentType($sContentType, $sCharset = null)
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
            ?: Ko_Tool_Mime::sGetMimeType($sContentType);
        if (null === $sCharset)
        {
            $sCharset = (in_array($sContentType, $s_aWhiteList)
                || 'text/' === substr($sContentType, 0, 5))
                ? KO_CHARSET : '';
        }
        if ('' === $sCharset)
        {
            return $this->_oHeader('Content-Type', $sContentType);
        }
        return $this->_oHeader('Content-Type', $sContentType.'; charset='.$sCharset);
    }

    /**
     * 设置Content-Disposition
     * @param string $sFileName 文件名
     * @param bool $bIsAttachment true: 下载；false: 在浏览器中打开
     * @return $this
     */
    public function oSetContentDisposition($sFileName, $bIsAttachment = true)
    {
        $type = $bIsAttachment ? 'attachment' : 'inline';
        return $this->_oHeader('Content-Disposition', $type.'; filename='.$sFileName);
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
     * @return $this
     */
    public function oSetCookie (
        $sName,
        $sValue = '',
        $iExpire = 0,
        $sPath = '/',
        $sDomain = '',
        $bSecure = false,
        $bHttpOnly = true
    ) {
        $this->_aCookies[] = array(
            'name'     => $sName,
            'value'    => $sValue,
            'expire'   => $iExpire,
            'path'     => $sPath,
            'domain'   => $sDomain,
            'secure'   => $bSecure,
            'httpOnly' => $bHttpOnly
        );
        return $this;
    }

    /**
     * 重定向
     * @param string $sLocation 重定向地址
     * @param bool $bPermanently 是否为永久重定向
     * @return $this
     */
    public function oRedirect($sLocation, $bPermanently = false)
    {
        $statusCode = $bPermanently ? 301 : 302;
        $this->vHttpCode($statusCode);
        return $this->_oHeader('Location', $sLocation);
    }

    /**
     * 设置或获取自定义header
     * @param string $sName
     *  如果不以`X-`开头，则自动加上`X-`;
     * @param string $sValue
     * @return $this|array|null
     */
    public function oSetExtraHeader($sName, $sValue)
    {
        if (0 !== strncasecmp($sName, 'X-', 2))
        {
            $sName = 'X-'.$sName;
        }
        else
        {
            $sName = ucfirst($sName);
        }
        return $this->_oHeader($sName, $sValue);
    }
    
    /**
     * @param Ko_View_Render_Base $oBody
     * @return Ko_View_Render_Base
     */
    public function oGetBody()
    {
        if (null === $this->_oBody)
        {
            $this->_oBody = new Ko_View_Render_Base;
        }
        return $this->_oBody;
    }
    
    /**
     * @param Ko_View_Render_Base $oBody
     * @return $this|Ko_View_Render_Base
     */
    public function oAppendBody(Ko_View_Render_Base $oBody)
    {
        if (null === $this->_oBody)
        {
            $this->_oBody = $oBody;
        }
        else
        {
            $this->_oBody->oAppend($oBody);
        }
        return $this;
    }

    /**
     * 发送数据头和数据体
     * @return $this
     */
    public function oSend()
    {
        if (!$this->_bSendBody)
        {
            $this->_oSendHeader();
        }
        return $this->_oSendBody();
    }

    /**
     * 发送数据头
     * @return $this
     */
    private function _oSendHeader()
    {
        if (200 != $this->_iHttpCode)
        {
            $message = Ko_Tool_HttpCode::sGetText($this->_iHttpCode);
            header($this->_sProtocol.' '.$this->_iHttpCode.' '.$message);
        }
        $this->_vSendCookies();
        $this->_vSendHeaders();
        return $this;
    }

    /**
     * 发送数据体
     * @return $this
     */
    private function _oSendBody()
    {
        if (null !== $this->_oBody)
        {
            echo $this->_oBody->sRender();
            $this->_oBody = null;
            $this->_bSendBody = true;
        }
        return $this;
    }

    private function _oHeader($sName, $sValue)
    {
        $this->_aHeaders[$sName] = trim($sValue);
        return $this;
    }

    private function _vSendCookies()
    {
        foreach ($this->_aCookies as $c)
        {
            setcookie(
                $c['name'], $c['value'], $c['expire'], $c['path'],
                $c['domain'], $c['secure'], $c['httpOnly']
            );
        }
    }
    
    private function _vSendHeaders()
    {
        foreach ($this->_aHeaders as $name => $value)
        {
            header($name.': '.$value);
        }
    }
}
