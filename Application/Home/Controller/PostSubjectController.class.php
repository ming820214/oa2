<?php
/*--------------------------------------------------------------------
 鸿文OA系统 - 让工作更轻松快乐
 --------------------------------------------------------------*/

namespace Home\Controller;

class PostSubjectController extends HomeController {

	protected $config = array('app_type' => 'master');

	public function index() {

		$this -> display();
	}
    
	public function write(){
	    array_empty_delt($_POST);
	    $mod=M('PostSubject');
	    $mod->create();
	    $post_id = $mod->post_id;
	    $subjects = $mod->subject_id;
	    
	    if(I('post.post_id')){
	        $lst = $mod->where(['post_id'=>$post_id])->select();
	        if(count($lst)>0){
	            $mod->where(['post_id'=>$post_id])->delete();
	            
	        }
	        
	        $flag = true;
	        //新增
	        for($i=0;$i<count($subjects);$i++){
	            $mod->post_id=$post_id;
	            $mod->subject_id = $subjects[$i];
	            $mod->create_time = date('Y-m-d H:i:s');
	            if(!$mod->add()){
	                $flag = false;
	            }
	        }
	        if($flag){
	            $this->ajaxReturn('添加成功');
	        }else{
	            $this->ajaxReturn('操作失败');
	        }
	    }
	    $this->ajaxReturn('操作失败');
	}
	
	public function ajax_list($post_id){
        
	    if($post_id){
	        $model = M('PostSubject');
	        $vo = $model->where(['post_id'=>$post_id])->select();
	        
	        foreach($vo as &$v){
	            $v['subject_name'] = get_config('FINANCE_SUBJECT')[$v['subject_id']];
	        }
	        if (IS_AJAX) {
	            if ($vo !== false) {// 读取成功
	                $return['data'] = $vo;
	                $return['state'] = 'ok';
	                $return['info'] = "读取成功";
	                $this -> ajaxReturn($return);
	            } else {
	                $return['state'] = 'failure';
	                $return['info'] = "读取错误";
	                $this -> ajaxReturn($return);
	            }
	        }
	    }
	}
	
	
	public function getRankInfoByPost($dept_id,$post_id){
	    $model = M('PostRank');
	    $vo = $model->where(['dept_id'=>$dept_id,'post_id'=>$post_id,'is_del'=>0])->select();
	    foreach($vo as &$v){
	        $v['post_name'] = get_config('ORG_POST')[$v['post_id']];
	        $v['rank_name'] = get_config('ORG_RANK')[$v['rank_id']];
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
