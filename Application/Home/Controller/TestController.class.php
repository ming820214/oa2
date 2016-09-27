<?php
// 后台用户模块
namespace Home\Controller;
use \Think\Controller;

class TestController extends Controller{
    protected $config = array('app_type' => 'public');
    public function wechat(){
        $wx = getWechatObj(); // 获取微信对象

        var_dump(
            // 测试文字消息，文字内容，发送参数（主要是用户名，可以字符串，也可以数组），APPID
            $wx->sendTextMsg('test1    jf测试没看见122》《>:">:<LO>',
             array('touser'=>array('WW', 'WWW')),
              C('WECHAT_APP')['TZTX'])
        );

        var_dump(
            // 测试文字消息，文字内容，发送参数（主要是用户名，可以字符串，也可以数组），APPID
            $wx->sendTextMsg('test2    jf测试没看见122》《>:">:<LO>',
             array('touser'=>'WW|WWW'),
              C('WECHAT_APP')['TZTX'])
        );

        var_dump(
            // 发送图文消息,图文内容，发送参数，APPID
            $wx->sendNewsMsg(array(
                // 创建一条图文消息，标题，描述，链接，图片链接
                $wx->buildNewsItem("测试标题1",'描述1','http://baidu.com','https://www.baidu.com/img/bd_logo1.png'),
                $wx->buildNewsItem("测试标题2",'描述2','http://baidu.com','https://www.baidu.com/img/bd_logo1.png'),
            ), array('touser'=>'WW'), C('WECHAT_APP')['TZTX'])
        );
    }

    public function session(){
        var_dump($_SESSION);
    }
}
