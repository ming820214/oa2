<?php
namespace Wechat\Controller;
use Think\Controller;

class CommController extends Controller {
    protected $needOAth = false; // 控制器是否需要验证用户信息

    public $wx = null; // 微信单件
    public $userId = null;
    public $userInfo = null;
	function _initialize() {
        $this->wx = new \Wechat\Hongwen\Wechat(array(
            'corpid' => C('WECHAT_CORPID'),
            'secret' => C('WECHAT_SECRET'),
        ));

        if(!$this->needOAth){
            return;
        }

        if(isset($_GET['state']) && $_GET['state'] == 'WechatOAuth'){ // 验证回调
            $this->userId = $this->wx->getUserId($_GET['code']);

            // $this->userInfo = D('User')->getUserInfo($this->userId, $wx); // 数据会定期从微信服务器更新
            // var_dump($this->userInfo);

            session('wechat_userid', $this->userid);
            return;
        }

        if(!session('wechat_userid')){
            $this->wx->jumpOAuth('http://'.$_SERVER['HTTP_HOST'].U());
            return;
        }

        $this->userId = session('wechat_userid');
        $this->userInfo = M('User')->getUserInfo($this->userId, $wx);
	}

    public function getUserId(){
        return $this->userId;
    }

    public function getuserinfo(){
        return $this->userInfo;
    }

    public function getUserName(){
        return $this->userInfo['name'];
    }
}
