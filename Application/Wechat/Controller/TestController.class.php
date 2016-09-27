<?php
namespace Wechat\Controller;

class TestController extends CommController {
    public function index() {
        $wx = new \Wechat\Hongwen\Wechat(array(
            'corpid' => C('WECHAT_CORPID'),
            'secret' => C('WECHAT_SECRET'),
        ));

        // $wx->getAccessToken();

        // var_dump($wx->sendTextMsg('test2jfk还是结婚122》《>:">:<LO>', array('touser'=>'WW|WWW'), C('WECHAT_APP_TZTX')));
        var_dump($wx->sendNewsMsg(array(
            $wx->buildNewsItem("测试标题1",'描述1','http://baidu.com','https://www.baidu.com/img/bd_logo1.png'),
        ), array('touser'=>'WW'), C('WECHAT_APP')['TZTX']));

        // var_dump($wx->getDepartmentMember(C('WECHAT_DEPARTMENT_HWJY'), true, 0));

        // var_dump($wx->getuserinfo('WW'));

    }
}
