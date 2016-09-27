<?php
/********************************
* 微信公众平台消息处理类
* NOtice： 我按照自己需求做的，部分接口未测试！
* 腾讯已不开放星标功能，星标功能无效
*
*
* BY:洋子
* wyzyok@qq.com
* purocean@gmail.com
* 2013-4-4
* 2014/1/25
********************************/

class WeChat{
    /*消息模版*/
    #文字消息
    private $_send_text_tpl =
"<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[%s]]></MsgType>
    <Content><![CDATA[%s]]></Content>
    <FuncFlag>%s</FuncFlag>
</xml>";

    #图文消息
    private $_send_news_tpl1 =
"<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[%s]]></MsgType><ArticleCount>%s</ArticleCount>
    <Articles>";
    private $_send_news_tpl2 =
        "<item>
            <Title><![CDATA[%s]]></Title>
            <Description><![CDATA[%s]]></Description>
            <PicUrl><![CDATA[%s]]></PicUrl>
            <Url><![CDATA[%s]]></Url>
        </item>";
    private $_send_news_tpl3 =
    "</Articles>
    <FuncFlag>%s</FuncFlag>
</xml>";

    #音乐消息
    private $_music_tpl =
"<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[%s]]></MsgType>
    <Music>
        <Title><![CDATA[%s]]></Title>
        <Description><![CDATA[%s]]></Description>
        <MusicUrl><![CDATA[%s]]></MusicUrl>
        <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
    </Music>
    <FuncFlag>%s</FuncFlag>
</xml>";


    #获得的
    private $_open_id;#用户
    private $_dev_id;#开发者
    private $_msg_id;#消息id
    private $_get_msg_type;#获得消息类型
    private $_get_msg_time;#获得消息时间戳

    private $_get_text;#获得的文本内容

    /***获得事件内容，数组
        $_get_event['type']#事件类型
        $_get_event['Key']#事件 Key
        More...
    ***/
    private $_get_event;#事件内容

    /***获得图片内容，数组
        $_get_image['url']#图片 URL
        $_get_image['media_id']#媒体 ID
    ***/
    private $_get_image;#图片内容

    /***获得语音内容，数组
        $_get_voice['format']#语音格式
        $_get_voice['recognition']#语音识别内容
        $_get_voice['media_id']#媒体 ID
    ***/
    private $_get_voice;#图片内容

    /**************还会继续根据需求增加************/

    #发送的
    private $_send_msg_type = 'text';#发送的消息类型
    private $_send_text = '';#发送的的文本内容
    private $_msg_flag = '0';#消息标志，为 0x0001时星标刚收到的消息

    /***音乐消息，数组
        $send_music['title']
        $send_music['description']
        $send_music['url']
        $send_music['hq_url']
    ***/
    private $_send_music = array();

    /***图文内容为数组
        $send_news[$i]['title']#一个item标题
        $send_news[$i]['description']#描述
        $send_news[$i]['pic_url']#图片url
        $send_news[$i]['url']#链接url
    ***/
    private $_send_news = array();

    /**************还会继续根据需求增加************/

    public $worked = 0;#消息处理次数
    public $menu_id = 0;#菜单id

    function __construct(){
        if(!headers_sent() && !$_GET['echostr']) $this->GetMsg();
    }

    function __destruct() {
        if(!headers_sent() && !$_GET['echostr']) $this->ResponseMsg();
    }


    #接口
    #获取消息，发送处理消息前调用
    public function GetMsg(){
        $post_str = $GLOBALS["HTTP_RAW_POST_DATA"];

        #解密微信服务器加密传送数据
        if(defined('WECHAT_CRYPT_MSG') && WECHAT_CRYPT_MSG){
            $post_str = $this->Decrypt($post_str);
        }

        if(!empty($post_str)){
            $post_obj = simplexml_load_string($post_str,'SimpleXMLElement',LIBXML_NOCDATA);

            #基础信息
            $this->_open_id = (string)$post_obj->FromUserName;
            $this->_get_msg_time = (int)$post_obj->CreateTime;
            $this->_get_msg_type = (string)$post_obj->MsgType;
            $this->_dev_id = (string)$post_obj->ToUserName;
            $this->msg_id = (int)$post_obj->MsgId;

            #文本消息
            $this->_get_text = trim($post_obj->Content);

            #事件消息
            $this->_get_event['type'] = (string)$post_obj->Event;
            $this->_get_event['key'] = (string)$post_obj->EventKey;
            # More...

            #图片消息
            $this->_get_image['url'] = (string)$post_obj->PicUrl;
            $this->_get_image['media_id'] = (string)$post_obj->MediaId;

            #语音消息
            $this->_get_voice['format'] = (string)$post_obj->Format;
            $this->_get_voice['recognition'] = (string)$post_obj->Recognition;
            $this->_get_voice['media_id'] = (string)$post_obj->MediaId;

            # More Msg Type...
        }
    }

    #返回 Openid
    public function GetOpenId(){
        return $this->_open_id;
    }

    #返回开发者用户名
    public function GetDevId(){
        return $this->_dev_id;
    }

    #返回获得消息类型
    public function GetMsgType($type = false){
        if($type !== false) $this->_get_msg_type = $type;
        return $this->_get_msg_type;
    }

    #返回消息id
    public function GetMsgId(){
        return $this->_msg_id;
    }

    #返回消息时间
    public function GetMsgTime(){
        return $this->_get_msg_time;
    }

    #返回获得文本内容
    public function GetTextContent($str = false){
        if($str !== false) $this->_get_text = $str;
        return $this->_get_text;
    }

    #返回获得事件内容
    public function GetEvent(){
        return $this->_get_event;
    }

    #返回获得图片内容
    public function GetImage(){
        return $this->_get_image;
    }

    #返回获得语音内容
    public function GetVoice(){
        return $this->_get_voice;
    }


    #返回或设置发送消息类型
    public function SetMsgType($msg_type = ''){
        if($msg_type) $this->_send_msg_type = $msg_type;
        return $this->_send_msg_type;
    }

    #返回或设置发送文本
    public function SetTextContent($text = ''){
        if($text) $this->_send_text = $text;
        return $this->_send_text;
    }

    #返回或设置发送图文内容
    public function SetNewsContent($news = array()){
        if($news) $this->_send_news = $news;
        return $this->_send_news;
    }

    #添加/修改（index不为 -1）一条图文内容，失败返回false——添加条目超过 10 条
    public function SetNewsItem($title, $description, $pic_url, $url, $index = -1){
        if($index == -1){
            if(count($this->_send_news) < 10){
                $temp['title'] = $title;
                $temp['description'] = $description;
                $temp['pic_url'] = $pic_url;
                $temp['url'] = $url;

                $this->_send_news[] = $temp;

                return true;
            }else{
                return false;
            }
        }else{
            if(isset($this->_send_news[$index])){
                $this->_send_news[$index]['title'] = $title;
                $this->_send_news[$index]['description'] = $description;
                $this->_send_news[$index]['pic_url'] = $pic_url;
                $this->_send_news[$index]['url'] = $url;

                return true;
            }else{
                return false;
            }
        }
    }

    #删除一条图文消息
    public function RemoveNewsItem($index){
        unset($this->_send_news[$index]);
    }

    #返回或设置音乐内容
    public function SetMusicContent($music = array()){
        if($music) $this->_send_music = $music;
            return $this->_send_music;
    }

    #返回或设置消息flag，已失效
    public function SetMsgFlag($flag = 0){
        if($flag) $this->_msg_flag = $flag;
        return $this->_msg_flag;
    }

    /*回复消息*/
    public function ResponseMsg(){
        switch($this->_send_msg_type){
            case 'text':
                // $this->_send_text .= "\n执行时间: ".round(microtime(true) - $GLOBALS['exe_time'], 2).' 秒';
                $result = sprintf($this->_send_text_tpl, $this->_open_id, $this->_dev_id, time(), 'text', $this->_send_text, $this->_msg_flag);
                break;
            case 'news':
                $result = sprintf($this->_send_news_tpl1, $this->_open_id, $this->_dev_id, time(), 'news', count($this->_send_news));
                foreach ($this->_send_news as $news_item) {
                    $result .= sprintf($this->_send_news_tpl2, $news_item['title'], $news_item['description'], $news_item['pic_url'], $news_item['url']);
                }
                $result .= sprintf($this->_send_news_tpl3, $this->_msg_flag);
                break;
            case 'music':
                $result = sprintf($this->_music_tpl, $this->_open_id, $this->_dev_id, time(), 'music', $this->_send_music['title'], $this->_send_music['description'], $this->_send_music['url'], $this->_send_music['hq_url'], $this->_msg_flag);
                break;
            default:
                break;
        }

        #解密微信服务器加密传送数据
        if(defined('WECHAT_CRYPT_MSG') && WECHAT_CRYPT_MSG){
            $result = $this->Encrypt($result);
        }
        die($result);
    }

    #微信端显示错误信息，调试
    public function ShowError($e){
        $this->SetMsgType('text');
        $this->SetTextContent($e);
        $this->ResponseMsg();
        $this->worked++;
    }

    #验证API URL
    public function Valid($token){
        $echoStr = $_GET["echostr"];
        //valid signature , option
        if($this->checkSignature($token)){
            die($echoStr);
        }else{
            die('Token Error!');
        }
    }

    #校验签名
    public function CheckSignature($token){
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if($tmpStr == $signature){
            return true;
        }else{
            return false;
        }
    }

    #解密微信服务器加密传送数据
    public function Decrypt($xml){
        if(empty($xml)){
            return false;
        }

        $token = WECHAT_TOKEN;
        $encode_key = WECHAT_ENCODING_AESKEY;
        $appid = WECHAT_APPID;

        $signature = $_GET['signature'];
        $timestamp = $_GET['timestamp'];
        $nonce = $_GET['nonce'];
        $encrypt_type = $_GET['encrypt_type'];
        $msg_signature = $_GET['msg_signature'];

        $pc = new WXBizMsgCrypt($token, $encode_key, $appid);

        $msg = '';
        $errCode = $pc->decryptMsg($msg_signature, $timestamp, $nonce, $xml, $msg);
        if($errCode == 0){
            return $msg;
        }else{
            return false;
        }
    }

    public function Encrypt($xml){
        if(empty($xml)){
            return false;
        }

        $token = WECHAT_TOKEN;
        $encode_key = WECHAT_ENCODING_AESKEY;
        $appid = WECHAT_APPID;

        $signature = $_GET['signature'];
        $timestamp = $_GET['timestamp'];
        $nonce = $_GET['nonce'];
        $encrypt_type = $_GET['encrypt_type'];
        $msg_signature = $_GET['msg_signature'];

        $pc = new WXBizMsgCrypt($token, $encode_key, $appid);

        $msg = '';
        $errCode = $pc->encryptMsg($xml, $timestamp, $nonce, $msg);
        if($errCode == 0){
            return $msg;
        }else{
            return false;
        }
    }
}
