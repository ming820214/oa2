<?php
/*--------------------------------------------------------------------
 鸿文OA系统 - 让工作更轻松快乐
 --------------------------------------------------------------*/

namespace Home\Controller;

class SystemController extends HomeController {
	//过滤查询字段
	protected $config = array('app_type' => 'asst');
	function _search_filter(&$map) {
		$keyword = I('keyword');
		if (!empty($keyword)) {
			$map['type|name|code'] = array('like', "%" . $keyword . "%");
		}
	}

	function index() {
		$where_user['is_del'] = array('eq', 0);
		$user_count = M("User") -> where($where_user) -> count();
		$this -> assign('user_count', $user_count);

		$file_count = M("File") -> count();
		$this -> assign('file_count', $file_count);

		$file_spage = M("File") -> sum('size');
		$this -> assign('file_spage', $file_spage);

		$this -> display();
	}
}

