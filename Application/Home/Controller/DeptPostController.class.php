<?php
/*--------------------------------------------------------------------
 鸿文OA系统 - 让工作更轻松快乐
 --------------------------------------------------------------*/

namespace Home\Controller;

class DeptPostController extends HomeController {

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
    
	public function edit(){
	    $this->display();
	}
	public function read($id){
	    $model = M('DeptPost');
	    $vo = $model->where(['dept_id'=>$id])->select();
	    
	    foreach($vo as &$v){
	        $v['post_name'] = get_config('ORG_POST')[$v['post_id']];
	        $v['remark'] = $v['remark'];
	        $v['is_del_name'] = $v['is_del']==1?'禁用':'启用';
	    }
	    if (IS_AJAX) {
	        if ($vo !== false) {// 读取成功
	            $return['data'] = $vo;
	            $return['state'] = 'ok';
	            $return['info'] = "读取成功";
	            $return['total'] = count($vo);
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
