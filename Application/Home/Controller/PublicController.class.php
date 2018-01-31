<?php

namespace Home\Controller;
use Think\Controller;

class PublicController extends Controller {
	protected $config = array('app_type' => 'public');
	/**
	 * 后台用户登录
	 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
	 */
	public function login() {

		$this -> assign("is_verify_code", get_system_config("IS_VERIFY_CODE"));
		$auth_id = session(C('USER_AUTH_KEY'));
		if (!isset($auth_id) ||!session('?school_id')) {
			$this -> display();
		} else {
			header('Location: ' . __APP__);
		}
	}

	// 检测输入的验证码是否正确，$code为用户输入的验证码字符串
	function check_verify($code, $id = '') {
		$verify = new \Think\Verify();
		return $verify -> check($code, $id);
	}

	// 登录检测
	public function check_login() {
		$is_verify_code = get_system_config("IS_VERIFY_CODE");
		if (!empty($is_verify_code)) {
			$check = $this -> check_verify($_POST['verify'], 1);
			if (!$check) {
				$this -> error('验证码错误！');
			}
		}

		if (empty($_POST['emp_no'])) {
			$this -> error('帐号必须！');
		} elseif (empty($_POST['password'])) {
			$this -> error('密码必须！');
		}

		if ($_POST['emp_no'] == 'admin') {
			$is_admin = true;
			session(C('ADMIN_AUTH_KEY'), true);
		}

		$map = array();
		// 支持使用绑定帐号登录
		$map['emp_no'] = $_POST['emp_no'];
		$map["is_del"] = array('eq', 0);
		$map['password'] = array('eq', md5($_POST['password']));
		$model = M("User");
		$auth_info = $model -> where($map) -> find();

		//使用用户名、密码和状态的方式进行认证
		if (false == $auth_info) {
			$this -> error('帐号或密码错误！');
		} else {


			//微信通知账号主人
	        $wx= getWechatObj();
	        $wx->sendNewsMsg(
	            [$wx->buildNewsItem("OA登陆提醒",'你的OA账号于【'.date('Y-m-d H:i:s').'】在电脑端登录成功，如非本人操作请及时修改密码！','','')],
	            ['touser'=>$auth_info['wechat_userid']],
	            C('WECHAT_APP')['TZTX']);
	        	session(array('name'=>'session_id','expire'=>43200));
				session(C('USER_AUTH_KEY'), $auth_info['id']);
				session('emp_no', $auth_info['emp_no']);
				session('user_name', $auth_info['name']);
				session('user_pic', $auth_info['pic']);
				session('dept_id', $auth_info['dept_id']);
				session('position_id', $auth_info['position_id']);
				session('school_id', $auth_info['school']);
				session('region_id',get_school_region($auth_info['school']));
				session('date', date('Y-m'));
				session('last_login_time', $auth_info['last_login_time']);
				
				//用户数据cookie设置；
				cookie('user_id',$auth_info['id']);
				cookie('user_name',$auth_info['name']);
				cookie('school_id',$auth_info['school']);
				
				//保存登录信息
				$User = M('User');
				$ip = get_client_ip();
				$time = time();
				$data = array();
				$data['id'] = $auth_info['id'];
				$data['last_login_time'] = $time;
				$data['login_count'] = array('exp', 'login_count+1');
				$data['last_login_ip'] = $ip;
				$User -> save($data);
				$this -> assign('jumpUrl', U("index/index"));
			
				
				//每月去统计各个校区各个学管的统计数据每个月仅执行一次，登陆后就执行一次
				
				$weihu = A('Weihu');
			
				$weihu->month_static();	
				
			
				$_POST['emp_no']==$_POST['password']?$this->success('您的密码为初始密码，请修改！',U('Profile/password'),15):header('Location: ' . U("index/index"));
				
				
				
		}
	}

	/* 退出登录 */
	public function logout() {
		$auth_id = session(C('USER_AUTH_KEY'));
		if (isset($auth_id)) {
			session(null);
			session('[destroy]'); // 销毁session
			$this -> assign("jumpUrl", __APP__);
			$this -> success('退出成功！');
		} else {
			$this -> assign("jumpUrl", __APP__);
			$this -> error('退出成功！');
		}
	}

	public function register() {
		$this -> display();
	}

	// 登录检测
	public function check_register() {
		$is_verify_code = get_system_config("IS_VERIFY_CODE");
		if (!empty($is_verify_code)) {
			if (session('verify') != md5($_POST['verify'])) {
				$this -> error('验证码错误！');
			}
		}

		if (empty($_POST['emp_no'])) {
			$this -> error('帐号必须！');
		} elseif (empty($_POST['password'])) {
			$this -> error('密码必须！');
		} elseif ($_POST['password'] !== $_POST['check_password']) {
			$this -> error('密码不一致');
		}

		$map = array();
		// 支持使用绑定帐号登录
		$map['emp_no'] = $_POST['emp_no'];
		$count = M("User") -> where($map) -> count();

		if ($count) {
			$this -> error('该账户已注册');
		} else {
			$model = D("User");
			if (false === $model -> create()) {
				$this -> error($model -> getError());
			}
			$list = $model -> add();
			if ($list !== false) {//保存成功
				$this -> assign('jumpUrl', get_return_url());
				$this -> success('注册成功!');
			} else {
				$this -> error('注册失败!');
				//失败提示
			}

		}
	}

	public function verify() {
		$config = array('fontSize' => 15, // 验证码字体大小
		'length' => 4, // 验证码位数
		'useNoise' => false, // 关闭验证码杂点
		);
		$verify = new \Think\Verify($config);
		$verify -> entry(1);
	}


	//通过微信页面认证方式登陆
	public function log_wx($urll='index/index'){
		// var_dump($urll);die;
    	if (isset($_GET['code'])&&$_GET['code']!=''){
            //==获取code和tokon
            $code=$_GET['code'];
            //获取并判断access_tokon是否过期获取tokon
            $tk=M('hw003.access',null)->find(2);
            if((time()-$tk['timestamp'])>7000){
                $access_token=accesstokon();
            }else{
                $access_token=$tk['tokon'];
            }

            //====通过code换取获取员工id信息
            $url = "https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo?access_token=$access_token&code=$code&agentid=2";//$agentid=0调用的应用id
            $info=url_get($url);
            $user_id=$info['UserId'];

            //====通过id换取获取员工资料信息$user_info
            $url = "https://qyapi.weixin.qq.com/cgi-bin/user/get?access_token=$access_token&userid=$user_id";
            $user_info=url_get($url);
			// 将获取到的值存储到seccion
			$auth_info=M('user')->where(['name'=>$user_info['name'],'wechat_userid'=>$user_info['userid'],'is_del'=>0,'wx_flag'=>1])->find();
			// var_dump($auth_info);die;
	    	
			if(!$auth_info){
				// var_dump($user_info);
				$this->error('没有相关权限','login');
				die;
			}
			session('name',$user_info['name']);
			session(C('USER_AUTH_KEY'), $auth_info['id']);
			session('emp_no', $auth_info['emp_no']);
			session('user_name', $auth_info['name']);
			session('user_pic', $auth_info['pic']);
			session('dept_id', $auth_info['dept_id']);
			session('position_id', $auth_info['position_id']);
			session('school_id', $auth_info['school']);
			session('date', date('Y-m'));
			$this -> redirect($urll);
	    }else{
	    	$this->error('没有相关权限',U('login'));
	    }
	}

	//外部借用发送微信消息通知的接口
	public function SendText($name,$msg){
		$user_id=M('user')->where(['name'=>$name])->getField('wechat_userid');
        $wx= getWechatObj();
        $cc=$wx->sendTextMsg($msg,['touser'=>$user_id],C('WECHAT_APP')['TZTX']);
        if($cc['errmsg']=='ok'){
        	M('wechat_msg',null)->add(['name'=>$name,'msg'=>$msg]);
        	echo '<meta charset=\'utf-8\'><script type="text/javascript">alert("您已经成功预约，会有鸿文教育工作人员处理您的信息，请稍候接听工作人员的来电，感谢您的支持！\n点击\'确定\'返回。");window.history.go(-1);</script>';
        }
	}

	//开机通知的接口
	public function Start(){
        $wx= getWechatObj();
        $cc=$wx->sendTextMsg(date('Y-m-d H:i:s'),['touser'=>'WW'],C('WECHAT_APP')['XWZX']);
        if($cc['errmsg']=='ok')header('location:/');
	}

	public function send_to_me(){
        $wx= getWechatObj();
        $cc=$wx->sendTextMsg(date('Y-m-d H:i:s'),['touser'=>'WW'],C('WECHAT_APP')['XWZX']);
		
	}

}