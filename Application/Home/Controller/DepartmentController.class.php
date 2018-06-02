<?php
/*--------------------------------------------------------------------
 鸿文OA系统 - 让工作更轻松快乐
 --------------------------------------------------------------*/

namespace Home\Controller;

class DepartmentController extends HomeController {

	protected $config = array('app_type' => 'master');

	public function index() {

		$node = M("Department");
		$menu = array();
		$menu = $node->where(["is_del"=>0]) -> field('id,pid,name,is_del,dept_grade') -> order('sort asc') -> select();
		$tree = list_to_tree($menu);
		$this -> assign('menu', popup_tree_menu($tree));

		$model = M("Department");
		$list = $model -> order('sort asc') -> getField('id,name');
		$this -> assign('dept_list', $list);

		$this -> display();
	}

	public function del($id) {
		$this -> _destory($id);
	}

	public function winpop() {
		$node = M("Department");
		$menu = array();
		$menu = $node -> where('is_del=0') -> field('id,pid,name') -> order('sort asc') -> select();

		$tree = list_to_tree($menu);
		$this -> assign('menu', popup_tree_menu($tree));
		$pid = array();
		$this -> assign('pid', $pid);
		$this -> display();
	}

	public function winpop2() {
		$this -> winpop();
	}

}
