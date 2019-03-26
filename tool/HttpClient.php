<?php
/**
 * HttpClient
 *
 * @package ko\tool
 * @author zhouping
 * @date 2015-08-06
 */

class Ko_Tool_HttpClient
{
    /**
     * 慢请求时间，默认 3 秒
     */
    const SLOW_REQUEST_TIME = 3;

    /**
     * 请求的url地址
     * @var type
     */
    private $_srequest_url = '';

    /**
     * 请求的port端口
     * @var type
     */
    private $_srequest_port = '';

    /**
     * 请求内容，请将请求的内容以 key=value&key2=value2 的方式提供
     * @var type
     */
    private $_srequest_data = '';

    /**
     * 应答内容
     * @var type
     */
    private $_sresponse_data = '';

    /**
     * 请求方法，默认是POST 方法
     * @var type
     */
    private $_smethod = 'GET';

    /**
     * 证书文件
     */
    private $_scert_file = '';

    /**
     * 证书密码
     * @var type
     */
    private $_cert_passwd = '';

    /**
     * 证书类型PEM
     * @var type
     */
    private $_scert_type = 'PEM';

    /**
     * CA文件
     * @var type
     */
    private $_sca_file = '';

    /**
     * 错误信息
     * @var type
     */
    private $_serr_info = '';

    /**
     * 超时时间
     * @var type
     */
    private $_itimeout = 30;

    /**
     * HTTPHEADER
     * @var array
     */
    private $_aheaders = array();

    /**
     * http状态码
     * @var type
     */
    private $_iresponse_code = 0;
    /**
     * UserAgent 信息
     * @var string
     */
    private $_suser_agent= '';
    /**
     * curl 句柄资源信息
     * @var array
     */
    private $_scurl_info = array();

    /**
     * 构造方法
     */
    public function __construct($url = '', $method = 'GET', $timeout = 30)
    {
        $this->bSetRequestUrl($url);
        $this->vSetMethod($method);
        $this->vSetTimeout($timeout);
    }

    /**
     * 设置请求地址
     * @param type $url
     * @return boolean
     */
    public function bSetRequestUrl($url)
    {
        if (empty($url))
        {
            return false;
        }

        $this->_srequest_url = $url;
        return true;
    }

    public function bSetRequestPort($port)
    {
        if (empty($port))
        {
            return false;
        }

        $this->_srequest_port = $port;
        return true;
    }

    /**
     * 设置请求的内容
     * @param type $data
     */
    public function bSetRequestData($data)
    {
        if (empty($data))
        {
            return false;
        }

        $this->_srequest_data = $data;
        return true;
    }

    /**
     * 获取响应结果信息
     * @return type
     */
    public function sGetResponseData()
    {
        return $this->_sresponse_data;
    }

    /**
     * 设置请求方法
     * @param string $method
     */
    public function vSetMethod($method)
    {
        if (!in_array(strtoupper($method), array('POST', 'GET')))
        {
            $method = 'GET';
        }

        $this->_smethod = strtoupper($method);
    }

    /**
     * 获取错误信息
     * @return type
     */
    public function sGetErrInfo()
    {
        return $this->_serr_info;
    }

    /**
     * 设置证书信息
     * @param type $cert_file
     * @param type $cert_passwd
     * @param type $cert_type
     * @return boolean
     */
    public function bSetCertInfo($cert_file, $cert_passwd = '', $cert_type="PEM")
    {
        if (!is_readable($cert_file) || empty($cert_type))
        {
            return false;
        }
        $this->_scert_file   = $cert_file;
        $this->_cert_passwd = $cert_passwd;
        $this->_scert_type = $cert_type;
        return true;
    }

    /**
     * 设置CA
     * @param type $ca_file
     * @return boolean
     */
    public function bSetCaInfo($ca_file)
    {
        if (!is_readable($ca_file))
        {
            return false;
        }
        $this->_sca_file = $ca_file;
        return true;
    }

    /**
     * 设置超时时间,单位秒
     * @param type $timeout
     */
    public function vSetTimeout($timeout)
    {
        $this->_itimeout = (int)$timeout;
    }

    /**
     * 设置HttpHeader
     * @param type $headers
     */
    public function vSetHttpHeader($headers)
    {
        $this->_aheaders = $headers;
    }

    /**
     * 获取http响应状态码
     * @return type
     */
    public function iGetResponseCode()
    {
        return $this->_iresponse_code;
    }

    /**
     * 设置UserAgent
     * @param string $user_agent
     * @return boolean
     */
    public function bSetUserAgent($user_agent)
    {
        if (!empty($user_agent))
        {
            $this->_suser_agent= $user_agent;
        }

        return true;
    }

    /**
     * 获取UserAgent
     * @return string
     */
    public function sGetUserAgent()
    {
        return $this->_suser_agent;
    }

    /**
     * 获取curl句柄信息
     * @return array
     */
    public function sGetCurlInfo()
    {
        return $this->_scurl_info;
    }

    /**
     * 执行远程请求
     * @return boolean
     */
    public function bCall()
    {
        if (empty($this->_srequest_url)) return false;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->_itimeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
//        curl_setopt($ch, CURLOPT_VERBOSE, true);
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4'))
        {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }
        if ($this->_suser_agent) {
            curl_setopt($ch, CURLOPT_USERAGENT, $this->_suser_agent);
        }
        if ($this->_smethod == 'GET') {
            curl_setopt($ch, CURLOPT_URL, $this->_srequest_url . '?' . $this->_srequest_data);
        } else {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_URL, $this->_srequest_url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_srequest_data);
        }
        if ($this->_srequest_port != "") {
            curl_setopt($ch, CURLOPT_PORT, $this->_srequest_port);
        }
        if($this->_scert_file != "") {
            curl_setopt($ch, CURLOPT_SSLCERT, $this->_scert_file);
            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $this->_cert_passwd);
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, $this->_scert_type);
        }
        if($this->_sca_file != "") {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_CAINFO, $this->_sca_file);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }
        if (!empty($this->_aheaders)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->_aheaders);
        }
        $_start_time = microtime(true);
        $res = curl_exec($ch);
        $_request_time = microtime(true) - $_start_time;
        $this->_scurl_info = curl_getinfo($ch);
        if ($_request_time > self::SLOW_REQUEST_TIME){}
        $this->_iresponse_code = isset($this->_scurl_info['http_code']) ? $this->_scurl_info['http_code'] : 0;
        if ($res == NULL) {
            $_ip = Ko_Tool_Ip::SGetServerIp();
            $this->_serr_info = 'Http 请求失败.' . curl_errno($ch) . ',' . curl_error($ch).",{$_ip}";
            curl_close($ch);
            return false;
        } else if($this->_iresponse_code != '200') {
            $this->_serr_info = "Http 请求异常. Http code:" . $this->_iresponse_code;
            curl_close($ch);
            return false;
        }
        curl_close($ch);
        $this->_sresponse_data = $res;
        return true;
    }
}
?>