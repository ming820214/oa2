<?php
/*****
***********************
 *
 *	HTTP通讯类(Curl)
 *  By：洋子
 *  purocean@gmail.com
 *
**********************

$http = new HttpLib();

$http->open([网址[,是否自动跳转[,是否只获取Header]]]); // 设置或返回打开的一个网址，支持跳转

$http->setPost([名，值]); // 设置或返回(数组)要 POST 的内容

$http->setReferer([地址]); // 设置或返回 Header 中的来源页信息

$http->setCookie([名, 值]); // 设置或返回(数组)要发送的 cookie

$http->setHeader([值]); // 设置或返回(数组)要发送的 header

$http->send(); // 在这之前设置 Header 之类信息，这之后才实际发出请求并接收文件

$http->getContent(); // 获得内容，不包括 Header

$http->getHeader([名]); // 返回 Header 数组,名都大写

$http->getCookie(); // 返回 Cookie 数组

$http->getCookie(名); // 返回指定 Key 的 Cookie 值
******************************/

class HttpLib{
    private $_url = ''; // 打开的 URL
    private $_jump = 0; // 自动跳转
    private $_only_header = 0; // 只获取 Header
    private $_content = ''; // 获得的内容，不包括 Header
    private $_set_post = array(); // 发送的内容
    private $_set_post_str = ''; // 发送的内容转换后的文字
    private $_set_cookie = array(); // 设置或返回的 Cookie
    private $_set_header = array(); // 设置或返回的 Header
    private $_get_cookie = array(); // 获得的 Cookie
    private $_get_header = array(); // 获得的 Header
    private $_get_header_arr = array(); // 获得的 Header Key => Value Key都大写
    private $_referer = ''; // 来源页
    private $_http_code = null; // HTTP 状态码

    private $_curl; // CURL 句柄


    // 设置或返回打开 URL，自动跳转未实现
    public function open($url = '', $auto_jump = 0, $only_header = 0){
        if($url){
            $this->_url = $url;
            $this->_jump = $auto_jump;
            $this->_only_header = $only_header;
        }

        return $this->_url;
    }

    // 设置或返回要 POST 的内容
    public function setPost($name = '', $value = null){
        if($name && $value !== null){
            $this->_set_post[urlencode($name)] = urlencode($value);
        }

        return $this->_set_post;
    }

    // 设置或返回要 POST 的内容，直接用字符串的形式
    public function SetPostStr($str = null){
        if($str !== null){
            $this->_set_post = $str.'&';
        }

        return $this->_set_post;
    }

    // 设置或返回 Header 中的来源页信息
    public function setReferer($ref = ''){
        if($ref){
            $this->_referer = $ref;
        }

        return $this->_referer;
    }

    // 设置或返回要发送的 Cookie
    public function setCookie($name = '', $value = null){
        if($name && $value !== null){
            $this->_set_cookie[$name] = urlencode($value);
        }

        return $this->_set_cookie;
    }

    // 设置或返回要发送的 Header
    public function setHeader($header = ''){
        if($header){
            $this->_set_header[] = $header;
        }

        return $this->_set_header;
    }

    // 发送请求，返回 HTTP 状态码
    public function send(){
        // 把 Cookie 加入 Header
        $cookie = $this->_ArrayImplode('=', '; ', $this->_set_cookie);
        $this->setHeader('Cookie: '.$cookie);

        $count = 1;
        if(is_array($this->_set_post)){
            $count = (int)count($this->_set_post);
            $this->_set_post_str .= $this->_ArrayImplode('=', '&', $this->_set_post);
        }
        $this->_set_post_str = rtrim($this->_set_post_str, '&');

        $this->_curl = curl_init();

        curl_setopt($this->_curl, CURLOPT_URL, $this->_url);
        curl_setopt($this->_curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->_curl, CURLOPT_NOBODY, (int)$this->_only_header);
        curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, 1); // 返回结果保存在变量
        curl_setopt($this->_curl, CURLOPT_HEADER, 1); // 返回中包含 Header 信息
        curl_setopt($this->_curl, CURLOPT_REFERER, $this->_referer);// 设置 来源页
        curl_setopt($this->_curl, CURLOPT_POST, $count);
        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $this->_set_post_str); // Post 数据
        curl_setopt($this->_curl, CURLOPT_HTTPHEADER, $this->_set_header);

        $response = curl_exec($this->_curl);

        // 分离 Header 和 Content，提取 Cookie
        $temp_1 = explode("\r\n\r\n", $response, 2);
        $this->_get_header = explode("\r\n", $temp_1[0]);
        foreach($this->_get_header as $value) {
            $temp_2 = explode(":", trim($value), 2);
            if(!isset($temp_2[1])){
                $temp_2[1] = $temp_2[0];
            }
            $this->_get_header_arr[strtoupper($temp_2[0])] = trim($temp_2[1]);

            if(strcasecmp($temp_2[0], 'Set-Cookie') == 0){
                $t = explode(";", trim($temp_2[1]));
                $t1 = explode('=', trim($t[0]));
                $this->_get_cookie[trim($t1[0])]['value'] = trim($t1[1]);
                foreach($t as $t2){
                    $t3 = explode('=', trim($t2));
                    $this->_get_cookie[trim($t1[0])][trim($t3[0])] = trim($t3[1]);
                }
            }
        }
        $this->_content = $temp_1[1];
        $this->_http_code = (int)preg_replace('/.*?(\d{3}).*?/is', '$1', $this->_get_header[0]);

        return $this->_http_code;
    }

    // 获得 HTTP 状态码
    public function getHttpCode(){
        return $this->_http_code;
    }

    // 转换内容编码
    public function encodeTo($inChatset = 'GBK', $outCharset = 'UTF-8//IGNORE'){
        $this->_content = iconv($inChatset, $outCharset, $this->_content);
    }

    // 获得的内容，不包括 Header
    public function getContent(){
        return $this->_content;
    }

    // 返回获得的 Cookie
    public function getCookie($name = ''){
        if($name){
            return $this->_get_cookie[$name];
        }else{
            return $this->_get_cookie;
        }
    }

    // 返回获得的 Header
    public function getHeader($name = ''){
        if($name){
            return $this->_get_header_arr[$name];
        }else{
            return $this->_get_header_arr;
        }
    }

    private function _ArrayImplode($glue, $separator, $array){
        if(!is_array($array)){
            return false;
        }

        $string = array();
        foreach($array as $key => $val){
            if(is_array($val))
                $val = implode(',', $val);
                $string[] = $key.$glue.$val;
            }

        return implode($separator, $string);
    }
}
