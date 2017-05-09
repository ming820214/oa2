<?php
/*--------------------------------------------------------------------
 鸿文OA系统 - 让工作更轻松快乐
 --------------------------------------------------------------*/

namespace Home\Controller;
use Think\Controller;

class HomeController extends Controller {
	protected $config = array('app_type' => 'asst');

	function _initialize() {
		$auth_id = session(C('USER_AUTH_KEY'));
		if (!isset($auth_id) || !session('?school_id')) {
			// 跳转到认证网关
			redirect(U(C('USER_AUTH_GATEWAY')));
		}
		$this -> _assign_menu();

		$typeList = M("FinanceType")->order('sort asc')
                            ->select();
        C('PURCHASE_TYPE', list_to_tree($typeList, C('PURCHASE_TYPE_ROOT_ID')));
        C('COST_TYPE', list_to_tree($typeList, C('COST_TYPE_ROOT_ID')));
        $fooInfo = M("FooInfo");
        $schoolList = $fooInfo->where('pid='.C('SCHOOL_ROOT_ID').' AND is_del=0')
//         										->order('sort asc,id asc')->select();
        										->order('name desc')->select();
		//针对年级按照给你的顺序进行排列
        $gradeList = $fooInfo->where('pid='.C('SCHOOL_GRADE_ROOT_ID').' AND is_del=0')
        										->order('cast(sort as signed) DESC,id asc')->select();
        $courseList = $fooInfo->where('pid='.C('SCHOOL_COURSE_ROOT_ID').' AND is_del=0')
        										->order('sort asc,id asc')->select();
        $subjectList = $fooInfo->where('pid='.C('SCHOOL_SUBJECT_ROOT_ID').' AND is_del=0')
        										->order('sort asc,id asc')->select();
        $teacherLevelList = $fooInfo->where('pid='.C('SCHOOL_TEACHER_LEVEL_ROOT_ID').' AND is_del=0')
        										->order('sort asc,id asc')->select();
        
        //获取集团所有部门信息列表
        $deptInfo = M("dept");
        $deptList = $deptInfo->where("is_del=0")->select();
        
        C('SCHOOL', array_merge(C('SCHOOL_DEFAULT'), $schoolList));
        C('SCHOOL_GRADE', $gradeList);
        C('SCHOOL_COURSE', $courseList);
        C('SCHOOL_SUBJECT', $subjectList);
        C('SCHOOL_TEACHER_LEVEL', $teacherLevelList);
        C('ORG_DEPT',$deptList);
	}

	protected function _assign_menu() {
		$user_id = get_user_id();

		$model = D("Node");
		$top_menu_list = $model -> get_top_menu($user_id);
		if (empty($top_menu_list)) {
			$this -> assign('jumpUrl', U("Public/logout"));
			$this -> error("没有权限");
		}

		$this -> assign('top_menu', $top_menu_list);

		//读取数据库模块列表生成菜单项
		$menu = D("Node") -> access_list();
		$system_folder_menu = D("SystemFolder") -> get_folder_menu();

		$menu = array_merge($menu, $system_folder_menu);
		$menu = sort_by($menu, 'sort');

		$top_menu = cookie('top_menu');
		if (!empty($top_menu)) {
			$top_menu_name = $model -> where("id=$top_menu") -> getField('name');
			$this -> assign("top_menu_name", $top_menu_name);
			$this -> assign("title", get_system_config("SYSTEM_NAME") . "-" . $top_menu_name);

			$left_menu = list_to_tree($menu, $top_menu);
			$this -> assign('left_menu', $left_menu);
		} else {
			$this -> assign("title", get_system_config("SYSTEM_NAME"));
		}
	}

	/**列表页面 **/
	function index() {
		$this -> _index();
	}

	/**查看页面 **/
	function read($id) {
		$this -> _edit($id);
	}

	/**编辑页面 **/
	function edit($id) {
		$this -> _edit($id);
	}

	/** 保存操作  **/
	function save() {
		$this -> _save();
	}

	/**列表页面 **/
	protected function _index($name = CONTROLLER_NAME) {
		$map = $this -> _search();
		if (method_exists($this, '_search_filter')) {
			$this -> _search_filter($map);
		}
		$model = D($name);
		if (!empty($model)) {
			$this -> _list($model, $map);
		}
		$this -> display();
	}

	/**编辑页面 **/
	protected function _edit($id, $name = CONTROLLER_NAME) {
		$model = M($name);
		$vo = $model -> find($id);
		if (IS_AJAX) {
			if ($vo !== false) {// 读取成功
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

	protected function _save($name = CONTROLLER_NAME) {
		$opmode = I('opmode');
		switch($opmode) {
			case "add" :
				$this -> _insert($name);
				break;
			case "edit" :
				$this -> _update($name);
				break;
			default :
				$this -> error("非法操作");
		}
	}

	/** 插入新新数据  **/
	protected function _insert($name = CONTROLLER_NAME) {

		$model = D($name);
		if (false === $model -> create()) {
			$this -> error($model -> getError());
		}

		/*保存当前数据对象 */
		$list = $model -> add();
		if ($list !== false) {//保存成功
			$this -> assign('jumpUrl', get_return_url());
			$this -> success('新增成功!');
		} else {
			$this -> error('新增失败!');
			//失败提示
		}
	}

	/* 更新数据  */
	protected function _update($name = CONTROLLER_NAME) {
		$model = D($name);
		if (false === $model -> create()) {
			$this -> error($model -> getError());
		}
		$list = $model -> save();
		if (false !== $list) {
			$this -> assign('jumpUrl', get_return_url());
			$this -> success('编辑成功!');
			//成功提示
		} else {
			$this -> error('编辑失败!');
			//错误提示
		}
	}

	/** 删除标记  **/
	protected function _del($id, $name = CONTROLLER_NAME, $return_flag = false) {
		$model = M($name);
		if (!empty($model)) {
			if (isset($id)) {
				if (is_array($id)) {
					$where['id'] = array("in", array_filter($id));
				} else {
					$where['id'] = array('in', array_filter(explode(',', $id)));
				}
				$result = $model -> where($where) -> setField("is_del", 1);
				if ($return_flag) {
					return $result;
				}
				if ($result !== false) {
					$this -> assign('jumpUrl', get_return_url());
					$this -> success("成功删除{$result}条!");
				} else {
					$this -> error('删除失败!');
				}
			} else {
				$this -> error('没有可删除的数据!');
			}
		} else {
			$this -> error('没有可删除的数据!');
		}
	}

	/** 永久删除数据  **/
	protected function _destory($id, $name = CONTROLLER_NAME, $return_flag = false) {

		$model = M($name);
		if (is_array($id)) {
			$where['id'] = array("in", array_filter($id));
		} else {
			$where['id'] = array('in', array_filter(explode(',', $id)));
		}

		$app_type = $this -> config['app_type'];

		if ($app_type == "personal") {
			$where['user_id'] = get_user_id();
		}

		if (in_array('add_file', $model -> getDbFields())) {
			$file_list = $model -> where($where) -> getField("add_file", true);
			$file_list = array_filter(explode(";", implode(';', $file_list)));
			if (!empty($file_list)) {
				$this -> _destory_file($file_list);
			}
		};

		$result = $model -> where($where) -> delete();
		if ($return_flag) {
			return $result;
		}
		if ($result !== false) {
			$this -> assign('jumpUrl', get_return_url());
			$this -> success("彻底删除{$result}条!");
		} else {
			$this -> error('删除失败!');
		}
	}

	public function del_file($sid) {
		$this -> _destory_file($sid);
	}

	protected function _destory_file($file_list) {
		if (isset($file_list)) {
			if (is_array($file_list)) {
				$files = array_map(think_decrypt, $file_list);
				$where['id'] = array('in', $files);
			} else {
				$files = array_filter(explode(';', $file_list));

				$files = array_map(think_decrypt, $files);

				$where['id'] = array('in', $files);
			}
		} else {
			exit();
		}

		$model = M("File");
		$admin = $this -> config['auth']['admin'];

		if (!$admin) {
			$where['user_id'] = array('eq', get_user_id());
		};

		$list = $model -> where($where) -> select();

		foreach ($list as $file) {

			if (file_exists(__ROOT__ . substr(C('DOWNLOAD_UPLOAD.rootPath'), 2) . $file['savepath'] . $file['savename'])) {
				unlink(__ROOT__ . substr(C('DOWNLOAD_UPLOAD.rootPath'), 2) . $file['savepath'] . $file['savename']);
			}
		}

		$result = $model -> where($where) -> delete();
		if ($result !== false) {
			return true;
		} else {
			return false;
		}
	}

	protected function _upload() {
		$return = array('status' => 1, 'info' => '上传成功', 'data' => '');
		/* 调用文件上传组件上传文件 */
		$File = D('File');
		$file_driver = C('DOWNLOAD_UPLOAD_DRIVER');
		$info = $File -> upload($_FILES, C('DOWNLOAD_UPLOAD'), C('DOWNLOAD_UPLOAD_DRIVER'), C("UPLOAD_{$file_driver}_CONFIG"));

		/* 记录附件信息 */
		if ($info) {
			if (!empty($info['file'])) {
				$return = $info['file'];
			}
			if (!empty($info['imgFile'])) {
				$return = $info['imgFile'];
				$return['url'] = $return['path'];
			}
			$return['sid'] = think_encrypt($info['file']['id']);
			$return['status'] = 1;
			$return['error'] = 0;
		} else {
			$return['status'] = 0;
			$return['info'] = $File -> getError();
		}
		/* 返回JSON数据 */
		$this -> ajaxReturn($return);
	}

	protected function _grab_img($pic_list) {
		$pic_list = explode("|", $pic_list);
		$path = C('EDITOR_UPLOAD.rootPath');
		$return = "";

		foreach ($pic_list as $val) {

			$file_name = $path . md5($val);
			//echo $file_name;
			$return .= get_remote_img($val, $file_name) . "|";
		}
		//dump($return);
		$this -> ajaxReturn($return);
	}

	protected function _down($attach_id) {
		$file_id = think_decrypt($attach_id);
		$File = D('File');
		$root = C('DOWNLOAD_UPLOAD.rootPath');
		if (false === $File -> download($root, $file_id)) {
			$this -> error = $File -> getError();
		}
	}

	//生成查询条件
	protected function _search($model = null) {
		$map = array();
		//过滤非查询条件
		$request = array_filter(array_keys(array_filter($_REQUEST)), "filter_search_field");
		if (empty($model)) {
			$model = D(CONTROLLER_NAME);
		}
		$fields = get_model_fields($model);

		foreach ($request as $val) {
			$field = substr($val, 3);
			$prefix = substr($val, 0, 3);
			if (in_array($field, $fields)) {
				if ($prefix == "be_") {
					if (isset($_REQUEST["en_" . $field])) {
						if (strpos($field, "time") != false) {
							$start_time = date_to_int(trim($_REQUEST[$val]));
							$end_time = date_to_int(trim($_REQUEST["en_" . $field])) + 86400;
							$map[$field] = array( array('egt', $start_time), array('elt', $end_time));
						}
						if (strpos($field, "date") != false) {
							$start_date = trim($_REQUEST[$val]);
							$end_date = trim($_REQUEST["en_" . substr($val, 3)]);
							$map[$field] = array( array('egt', $start_date), array('elt', $end_date));
						}
					}
				}

				if ($prefix == "li_") {
					$map[$field] = array('like', '%' . trim($_REQUEST[$val]) . '%');
				}
				if ($prefix == "eq_") {
					$map[$field] = array('eq', trim($_REQUEST[$val]));
				}
				if ($prefix == "gt_") {
					$map[$field] = array('egt', trim($_REQUEST[$val]));
				}
				if ($prefix == "lt_") {
					$map[$field] = array('elt', trim($_REQUEST[$val]));
				}
			}
		}
		return $map;
	}

	protected function _list($model, $map, $sort = '') {
		//排序字段 默认为主键名
		if (isset($_REQUEST['_sort'])) {
			$sort = $_REQUEST['_sort'];
		} else if (in_array('sort', get_model_fields($model))) {
			$sort = "sort asc";
		} else if (empty($sort)) {
			$sort = "id desc";
		}

		//取得满足条件的记录数
		$count_model = clone $model;
		//取得满足条件的记录数
		$count = $count_model -> where($map) -> count();

		if ($count > 0) {
			//创建分页对象
			if (!empty($_REQUEST['list_rows'])) {
				$list_rows = $_REQUEST['list_rows'];
			} else {
				$list_rows = 20;
			}
			// import("@.ORG.Util.Page");
			$p = new \Home\Hongwen\Page($count, $list_rows);
			//分页查询数据
			$vo_list = $model -> where($map) -> order($sort) -> limit($p -> firstRow . ',' . $p -> listRows) -> select();

			//echo $model->getlastSql();
			// $p -> parameter = $this -> _search($model);

			//分页显示
			$page = $p -> show();
			if ($vo_list) {
				$this -> assign('list', $vo_list);
				$this -> assign('sort', $sort);
				$this -> assign("page", $page);
				return $vo_list;
			}
		}
		return FALSE;
	}

	protected function _system_folder_manage($folder_name, $has_pid = false) {
		$this -> assign('folder_name', $folder_name);
		$this -> assign('has_pid', $has_pid);
		R('SystemFolder/index');
	}

	protected function _assign_folder_list() {
		$model = D("SystemFolder");

		$list = $model -> get_folder_list();
		$tree = list_to_tree($list);
		$this -> assign('folder_list', dropdown_menu($tree));
	}

//发送text消息，应用ID，接收人姓名（可以是数组多人），信息内容。
    public function text($app,$name,$message){
    	if(is_array($name)){
	        foreach ($name as $val) {
	            $n[]=userid($val);
	        }
	        $name=implode("|",$n);
	        $msg['touser']=$name;
    	}else{
			$msg['touser']=userid($name);
    	}
        $msg['msgtype']='text';
        $msg['agentid']=$app;
        $msg['text']['content']=$message;
        if($msg['touser'])
        if(send($msg))return true;
    }

//发送单图文消息
    public function news($agentid,$name,$title=null,$description=null,$url=null){
        $message=array('title'=>$title,'description'=>$description,'url'=>$url);
    	if(is_array($name)){
	        foreach ($name as $val) {
	            $n[]=userid($val);
	        }
	        $name=implode("|",$n);
	        $msg['touser']=$name;
    	}else{
			$msg['touser']=userid($name);
    	}
        $msg['msgtype']='news';
        $msg['agentid']=$agentid;
        $msg['news']['articles'][]=$message;
        if(send($msg))return true;
    }



}
