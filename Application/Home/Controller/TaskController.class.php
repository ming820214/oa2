<?php
namespace Home\Controller;

class TaskController extends HomeController {

    public function task(){
    	
        $this->show("<extend name='Layout/ins_page' /><block name='content'><iframe src='/oa/weixin.php/task/index' width='900px' height='500px' frameborder='0' scrolling='auto'/></iframe></block>");
    }
    
    public function reply(){
        $this->show("<extend name='Layout/ins_page' /><block name='content'><iframe src='/oa/weixin.php/task/reply' width='900px' height='500px' frameborder='0' scrolling='auto'/></iframe></block>");
    }

	public function index() {
		$node = M("hw003.task_user",null);
		$menu = array();
		$menu = $node -> field('id,pid,name') -> select();
		$tree = list_to_tree($menu);
		$this -> assign('menu', popup_tree_menu($tree));

		$model = M("hw003.task_user",null);
		$list = $model  -> getField('id,name');
		$this -> assign('dept_list', $list);

		$this -> display();
	}

	public function winpop() {
		$node = M("hw003.task_user",null);
		$menu = array();
		$menu = $node -> field('id,pid,name') -> select();

		$tree = list_to_tree($menu);
		$this -> assign('menu', popup_tree_menu($tree));
		$pid = array();
		$this -> assign('pid', $pid);
		$this -> display();
	}

	public function winpop2() {
		$this -> winpop();
	}

	//新增或修改
	public function addx() {
		$node = M("hw003.task_user",null);
		$if = $node->where(['name'=>I('post.name')])->find();
		$node -> Create($_POST);
		if($if){
			$node->id=$if['id'];
			$node->save();
        	M('hw003.task',null)->where(['uid'=>$if['id'],'state'=>0])->setField('pid',I('post.pid'));//把未关闭的任务也同步归属到新上级下
		}else{
			$node->add();
		}
		$this -> redirect('index');
	}

	public function read($id){
		$user=M('hw003.task_user',null);
		$info=$user->find($id);
		$info['parent']=$user->where(['id'=>$info['pid']])->getField('name');
		$this->ajaxReturn($info);
	}

}
?>