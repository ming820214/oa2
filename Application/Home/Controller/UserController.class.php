<?php
/*--------------------------------------------------------------------
 鸿文OA系统 - 让工作更轻松快乐
 --------------------------------------------------------------*/

// 后台用户模块
namespace Home\Controller;

class UserController extends HomeController {
	protected $config = array('app_type' => 'master');

	function _search_filter(&$map) {
		$keyword = I('keyword');
		if (!empty($keyword)) {
			$map['name|emp_no'] = array('like', "%" . $keyword . "%");
		}
	}

	public function index() {
		$plugin['date'] = true;
		$this -> assign("plugin", $plugin);

		$model = M("Position");
		$list = $model -> where('is_del=0') -> order('sort asc') -> getField('id,name');
		$this -> assign('position_list', $list);

		$model = M("Dept");
		$list = $model -> where('is_del=0') -> order('sort asc') -> getField('id,name');
		$this -> assign('dept_list', $list);

		if (I('param.eq_is_del')) {
			$eq_is_del = I('param.eq_is_del');
		} else {
			$eq_is_del = "0";
		}
		//die;
		$this -> assign('eq_is_del', $eq_is_del);
		$this -> assign('school', I('param.school'));
		$this -> assign('position_id', I('param.position_id'));

		$map = $this -> _search();
		if(!$eq_is_del){
			$map['is_del'] = array('eq',0);
		}
		if (method_exists($this, '_search_filter')) {
			$this -> _search_filter($map);
		}
		// $map['is_del'] = array('eq', $eq_is_del);
		if(I('param.school')||I('param.school')==='0')$map['school']= I('param.school');
		if(I('param.position_id'))$map['position_id']= I('param.position_id');
		
		if(I('param.dept_id')){
			$map['dept_id']= I('param.dept_id'); 
		}

		$model = D("User");

		if (!empty($model)) {
			$this -> _list($model, $map, "emp_no");
		}

		$this -> assign('school_list', C('SCHOOL'));

		$this -> display();
	}

	public function add() {
		$plugin['date'] = true;
		$this -> assign("plugin", $plugin);

		$model = M("Position");
		$list = $model -> where('is_del=0') -> order('sort asc') -> getField('id,name');
		$this -> assign('position_list', $list);

		$model = M("Dept");
		$list = $model -> where('is_del=0') -> order('sort asc') -> getField('id,name');
		$this -> assign('dept_list', $list);

		$this -> assign('school_list', C('SCHOOL'));

		$this -> display();
	}

	// 检查帐号
	public function check_account() {
		if (!preg_match('/^[a-z]\w{4,}$/i', $_POST['emp_no'])) {
			$this -> error('用户名必须是字母，且5位以上！');
		}
		$User = M("User");
		// 检测用户名是否冲突
		$name = I('emp_no'); ;
		$result = $User -> getByAccount($name);
		if ($result) {
			$this -> error('该编码已经存在！');
		} else {
			$this -> assign('jumpUrl', get_return_url());
			$this -> success('该编码可以使用！');
		}
	}
    
	/**查看页面 **/
	function read($id) {
	    $this -> _edit($id);
	}
	
	/**编辑页面 **/
	protected function _edit($id, $name = CONTROLLER_NAME) {
	    $model = M($name);
	    $vo = $model -> find($id);
	    if (IS_AJAX) {
	        if ($vo !== false) {// 读取成功
	            
                $vo['new_dept_name'] = M('Department')->where(['id'=>$vo['new_dept_id']])->getField('name');
                $vo['new_position_name'] = get_config('ORG_POST')[$vo['new_position_id']];
                $vo['new_rank_name'] = get_config('ORG_RANK')[$vo['new_rank_id']];
	            
	            $return['data'] = $vo;
	            $return['status'] = 1;
	            $return['info'] = "读取成功";
	            $this -> ajaxReturn($return);
	        } else {
	            $return['status'] = 0;
	            $return['info'] = "读取错误";
	            $this -> ajaxReturn($return);
	        }
	    }
	    
	    $this -> assign('vo', $vo);
	    $this -> display();
	    return $vo;
	}
	
	// 插入数据
	protected function _insert($name = "User") {
		// 创建数据对象
		$model = D("User");
		if (!$model -> create()) {
			$this -> error($model -> getError());
		} else {
			// 写入帐号数据
			$model -> letter = get_letter($model -> name);
			//$model -> password = md5($model -> emp_no); 由原来的账户名变成6个1；
			$model -> password = md5('111111');
			$emp_no = $model -> emp_no;
			$name = $model -> name;
			$mobile_tel = $model -> mobile_tel;
			$model -> open_id = $model -> emp_no;
			$model -> westatus = 1;
			if ($result = $model -> add()) {

				$this->add_default_role($result);

				$this -> assign('jumpUrl', get_return_url());
				$this -> success('用户添加成功！');
			} else {
				$this -> error('用户添加失败！');
			}
		}
	}

	protected function _update($name = "User") {
		$model = D($name);
		if (false === $model -> create()) {
			$this -> error($model -> getError());
		}
		if($model->emp_no=='admin'&&session('emp_no')!='admin')admin('管理员信息修改');//限制其他人修改管理员账号
		// 更新数据
		$model -> __set('letter', get_letter($model -> __get('name')));
		$emp_no = $model -> emp_no;
		$name = $model -> name;
		$mobile_tel = $model -> mobile_tel;
		$list = $model -> save();
		if (false !== $list) {
			//成功提示
			$this -> assign('jumpUrl', get_return_url());
			$this -> success('编辑成功!');
		} else {
			//错误提示
			$this -> error('编辑失败!');
		}
	}

	protected function add_default_role($user_id) {
		//新增用户自动加入相应权限组
		$RoleUser = M("RoleUser");
		$RoleUser -> user_id = $user_id;
		// 默认加入权限
		$RoleUser -> role_id = C('DEFAULT_ROLE');
		$RoleUser -> add();
	}

	//重置密码
	public function reset_pwd() {
		$id = $_POST['user_id'];
		$password = $_POST['password'];
		if ('' == trim($password)) {
			$this -> error('密码不能为空!');
		}
		$User = M('User');
		$User -> password = md5($password);
		$User -> id = $id;
		$result = $User -> save();
		if (false !== $result) {
			$this -> assign('jumpUrl', get_return_url());
			$this -> success("密码修改成功");
		} else {
			$this -> error('重置密码失败！');
		}
	}

	public function password() {
		$this -> assign("id", I('id'));
		$this -> display();
	}

	function json() {
		header("Content-Type:text/html; charset=utf-8");
		$key = $_REQUEST['key'];

		$model = M("User");
		$where['name'] = array('like', "%" . $key . "%");
		$where['emp_no'] = array('like', "%" . $key . "%");
		$where['_logic'] = 'or';
		$map['_complex'] = $where;
		$list = $model -> where($map) -> field('id,name') -> select();
		exit(json_encode($list));
	}

	function del() {
		$id = I('user_id');
		$this -> _destory($id);
	}

	public function import() {
		$opmode = $_POST["opmode"];
		if ($opmode == "import") {
			$File = D('File');
			$file_driver = C('DOWNLOAD_UPLOAD_DRIVER');
			$info = $File -> upload($_FILES, C('DOWNLOAD_UPLOAD'), C('DOWNLOAD_UPLOAD_DRIVER'), C("UPLOAD_{$file_driver}_CONFIG"));
			if (!$info) {
				$this -> error();
			} else {
				//取得成功上传的文件信息
				//$uploadList = $upload -> getUploadFileInfo();
				Vendor('Excel.PHPExcel');
				//导入thinkphp第三方类库

				$import_file = $info['uploadfile']["path"];
				$import_file = substr($import_file, 1);

				$objPHPExcel = \PHPExcel_IOFactory::load($import_file);
				//$objPHPExcel = \PHPExcel_IOFactory::load('Uploads/Download/Org/2014-12/547e87ac4b0bf.xls');
				$dept = M("Dept") -> getField('name,id');
				$position = M("Position") -> getField('name,id');
				$role = M("Role") -> getField('name,id');
				$sheetData = $objPHPExcel -> getActiveSheet() -> toArray(null, true, true, true);
				$model = D("User");
				for ($i = 2; $i <= count($sheetData); $i++) {
					$data = array();

					$data_user['emp_no'] = $sheetData[$i]["A"];
					$data_user['name'] = $sheetData[$i]["B"];

					$data_user['dept_id'] = $dept[$sheetData[$i]["C"]];
					$data_user['position_id'] = $position[$sheetData[$i]["D"]];

					$data_user['duty'] = $sheetData[$i]["J"];
					$data_user['office_tel'] = $sheetData[$i]["F"];
					$data_user['mobile_tel'] = $sheetData[$i]["G"];
					$data_user['sex'] = $sheetData[$i]["H"];
					$data_user['birthday'] = $sheetData[$i]["I"];
					$data_user['open_id'] = $sheetData[$i]["A"];
					$data_user['westatus'] = 1;

					$role_list = explode($sheetData[$i]["E"]);
					foreach ($role_list as $key => $val) {
						$data_role[] = $role[$val];
					}
					$user_id = M("User") -> add($data_user);

					$this -> add_role($user_id, $data_role);
				}

				$this -> assign('jumpUrl', get_return_url());
				$this -> success('导入成功！');
			}
		} else {
			$this -> display();
		}
	}

	function add_role($user_id, $role_list) {
		$role_list = explode(",", $role_list);
		$role_list = array_filter($role_list);
		$RoleUser = M("RoleUser");
		$RoleUser -> user_id = $user_id;
		foreach ($role_list as $role_id) {
			$RoleUser -> role_id = $role_id;
			$RoleUser -> add();
		}
	}

}
