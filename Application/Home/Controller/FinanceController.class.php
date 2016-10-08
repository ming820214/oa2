<?php
/*--------------------------------------------------------------------
 鸿文OA系统 - 让工作更轻松快乐
 --------------------------------------------------------------*/

// 后台用户模块
namespace Home\Controller;

class FinanceController extends HomeController {

    public function index() {
        $this->display();
    }

    public function changeMonth(){
        /* if($_POST['date']){
            session('date', $_POST['date']);
            $this->success('所在期次设置成功！');
			
        } */
    	
    	if($_POST['info']){
    		session('info', $_POST['info']);
    	}
    	
    	if($_POST['date']){
    		session('date', $_POST['date']);
    		$this->success('所在期次设置成功！');
    	}
    	
    	
    }

}
