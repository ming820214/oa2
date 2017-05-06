<?php
/*--------------------------------------------------------------------
 鸿文OA系统 - 让工作更轻松快乐
 --------------------------------------------------------------*/

// 后台用户模块
namespace Home\Controller;

class CampController extends HomeController {
	
	private $pageNumber=0;
	private $pageCount=1;
	
	public function class_teacher() {
		
		$pageNumber = I('get.pageNumber');
		$pageCount = I('get.pageCount');
		
		$pageNumber = empty($pageNumber)?$this->pageNumber:$pageNumber;
		$pageCount = empty($pageCount)?$this->pageCount:$pageCount;
		
		
		$w['school'] = array('like',get_school_name());
		$w['is_del'] = 0;
		
		
		$model = M("hw001.school_grade",null);
		$grade_list = $model -> where($w) -> order('timestamp asc') -> getField('id,school,name');
		$this -> assign('grade_list', $grade_list);
		
		$camp = M("hw001.camp_class_teacher",null);
	
		$grade_id = I('post.grade_id');
		$teacher_name = I('post.teacher_name');
		$keyword = I("post.keyword");
		$condition = '';
		if($grade_id){
			$condition .= " AND hw001.school_grade.name like '%" . $grade_id . "%' ";
		}
		
		if($teacher_name){
			$condition .= " AND hw001.camp_class_teacher.teacher_name like '%" . $teacher_name . "%'";
		}
		
		if($keyword){
			$condition .= " AND (hw001.school_grade.name like '%" . $keyword . "%' OR hw001.camp_class_teacher.teacher_name like '%" . $keyword . "%') ";
		}
		
		
		$maxCount = $model->join('LEFT JOIN  hw001.camp_class_teacher on hw001.school_grade.id = hw001.camp_class_teacher.grade_id')
		->where("hw001.school_grade.school='" .get_school_name() . "' AND hw001.school_grade.is_del=0 " . $condition)
		->count();
		
		
		$list = $model->join('LEFT JOIN  hw001.camp_class_teacher on hw001.school_grade.id = hw001.camp_class_teacher.grade_id')
						->where("hw001.school_grade.school='" .get_school_name() . "' AND hw001.school_grade.is_del=0 " .$condition)
						->field("hw001.school_grade.name,hw001.camp_class_teacher.id,hw001.camp_class_teacher.teacher_name,hw001.school_grade.id as idt")
						->limit($pageNumber,$pageCount)
						->select();
		
		$this->assign('list',$list);
		$this->maxCount=$maxCount?$maxCount:1;//显示全部数据
		
		if(IS_AJAX){
			// 发送给页面的数据
			$this->ajaxReturn([
					'maxCount'=>$this->maxCount,	
					'state'=>'ok',//查询结果
					'data'=>$list
						
			]);
		}
		
		$person = M("hw003.person_all",null);
		$con['state'] = 1;
		
		//集团所有员工信息列表
		$user_list = $person -> where($con)->group("school,position,name")->getField('id,school,position,name');
		
		$this->teacher_list = $user_list;

		$this -> display();
	}
	
	public function addClassTeacher(){
			if (IS_AJAX) {
				$camp = M("hw001.camp_class_teacher",null);
				$data = $_POST;
				//此处应该查重
				if ($camp->add($data)) {// 读取成功
					$return['status'] = 1;
					$return['info'] = "新增成功";
					$this -> ajaxReturn($return);
				} else {
					$return['status'] = 0;
					$return['info'] = "新增失败";
					$this -> ajaxReturn($return);
				}
			}
	}
	
	public function updateClassTeacher(){
		$camp = M("hw001.camp_class_teacher",null);
		$camp->create();
		if($camp->id){
			//此处应该查重
			if($camp->save()){
				$this -> success('信息修改成功！',U('Camp/class_teacher'));
			}else{
				$this -> error('信息修改失败！',U('Camp/class_teacher'));
			}
			
		}
		
		
	}
	
	public function delClassTeacher(){
		$camp = M("hw001.camp_class_teacher",null);
		$camp->create();
		if($camp->id){
			$w['id'] = array('in',$camp->id);
			$camp->where($w)->delete();
		}
		
		$this -> success('信息删除成功！',U('Camp/class_teacher'));
	}
	
	public function add() {
		$w['school'] = array('like',get_school_name());
		$w['is_del'] = 0;
		
		
		$model = M("hw001.school_grade",null);
		$grade_list = $model -> where($w) -> order('timestamp asc') -> getField('id,school,name');
		$this -> assign('grade_list', $grade_list);
		
		
		$person = M("hw003.person_all",null);
		$con['state'] = 1;
		
		//集团所有员工信息列表
		$user_list = $person -> where($con)->group("school,position,name")->getField('id,school,position,name');
		
		$this->teacher_list = $user_list;
		
		$this -> display('class_teacher_add');
	}
	
	//================================================楼层管理===============================================================//
	public function floor() {
	
		$pageNumber = I('get.pageNumber');
		$pageCount = I('get.pageCount');
	
		$pageNumber = empty($pageNumber)?$this->pageNumber:$pageNumber;
		$pageCount = empty($pageCount)?$this->pageCount:$pageCount;
	
	
		$model = M("hw001.camp_floor_responsor",null);
		
		$maxCount = $model ->count();
		$floor_list = $model ->select();
	
		$camp = M("hw001.camp_class_teacher",null);
	
		$this->assign('list',$floor_list);
		$this->maxCount=$maxCount?$maxCount:1;//显示全部数据
	
		if(IS_AJAX){
			// 发送给页面的数据
			$this->ajaxReturn([
					'maxCount'=>$this->maxCount,
					'state'=>'ok',//查询结果
					'data'=>$floor_list
	
			]);
		}
	
		$person = M("hw003.person_all",null);
		$con['state'] = 1;
	
		//集团所有员工信息列表
		$user_list = $person -> where($con)->group("school,position,name")->getField('id,school,position,name');
	
		$this->teacher_list = $user_list;
	
		$this -> display();
	}
	
	
	public function openFloor() {
	
		$person = M("hw003.person_all",null);
		$con['state'] = 1;
	
		//集团所有员工信息列表
		$user_list = $person -> where($con)->group("school,position,name")->getField('id,school,position,name');
	
		$this->teacher_list = $user_list;
	
		$this -> display('floor_add');
	}
	
	public function addFloor(){
		if (IS_AJAX) {
			$camp = M("hw001.camp_floor_responsor",null);
			$data = $_POST;
			$data['school'] = get_school_name();
	
			if ($camp->add($data)) {// 读取成功
				$return['status'] = 1;
				$return['info'] = "新增成功";
				$this -> ajaxReturn($return);
			} else {
				$return['status'] = 0;
				$return['info'] = "新增失败";
				$this -> ajaxReturn($return);
			}
		}
	}
	
	
	
	public function updateFloor(){
		$camp = M("hw001.camp_floor_responsor",null);
		$camp->create();
		if($camp->id){
			if($camp->save()){
				$this -> success('信息修改成功！',U('Camp/floor'));
			}else{
				$this -> error('信息修改失败！',U('Camp/floor'));
			}
				
		}
	
	
	}
	
	public function delFloor(){
		$camp = M("hw001.camp_floor_responsor",null);
		$camp->create();
		if($camp->id){
			$w['id'] = array('in',$camp->id);
			$camp->where($w)->delete();
		}
	
		$this -> success('信息删除成功！',U('Camp/floor'));
	}
	
	
	//================================================楼层教室管理===============================================================//
	
	public function classroom() {
	
		$pageNumber = I('get.pageNumber');
		$pageCount = I('get.pageCount');
	
		$pageNumber = empty($pageNumber)?$this->pageNumber:$pageNumber;
		$pageCount = empty($pageCount)?$this->pageCount:$pageCount;
	
	
		$model = M("hw001.camp_classroom",null);
		$maxCount = $model->join(' LEFT JOIN hw001.school_grade on hw001.camp_classroom.grade_id = hw001.school_grade.id')
							->field(' hw001.camp_classroom.id, hw001.camp_classroom.floor, hw001.camp_classroom.class_name, hw001.camp_classroom.class_responsor, hw001.camp_classroom.state as state_id, hw001.camp_classroom.grade_id,hw001.school_grade.name as grade_name,case state when 1 then "正常" when 2 then "上课" end ')
							->where('hw001.school_grade.is_del = 0')
							->count();
		
		$classroom_list = $model->join(' LEFT JOIN hw001.school_grade on hw001.camp_classroom.grade_id = hw001.school_grade.id')
								->field(' hw001.camp_classroom.id, hw001.camp_classroom.floor, hw001.camp_classroom.class_name, hw001.camp_classroom.class_responsor, hw001.camp_classroom.state as state_id, hw001.camp_classroom.grade_id,hw001.school_grade.name as grade_name,case state when 1 then "正常" when 2 then "上课" end as state') 
								->where('hw001.school_grade.is_del = 0')
								->select();
	
		
	
		$this->assign('list',$classroom_list);
		$this->maxCount=$maxCount?$maxCount:1;//显示全部数据
	
		if(IS_AJAX){
			// 发送给页面的数据
			$this->ajaxReturn([
					'maxCount'=>$this->maxCount,
					'state'=>'ok',//查询结果
					'data'=>$classroom_list
	
			]);
		}
	
		$person = M("hw003.person_all",null);
		$con['state'] = 1;
	
		//集团所有员工信息列表
		$user_list = $person -> where($con)->group("school,position,name")->getField('id,school,position,name');
	
		$this->teacher_list = $user_list;
		
		
		$grade = M("hw001.school_grade",null);
		$w['school'] = get_school_name();
		$w['is_del'] = 0;
		
		$grade_list = $grade->where($w)->select();
		
		$this->grade_list = $grade_list;
	
		$this -> display();
	}
	
	public function openClassRoom() {
	
		$person = M("hw003.person_all",null);
		$con['state'] = 1;
	
		//集团所有员工信息列表
		$user_list = $person -> where($con)->group("school,position,name")->getField('id,school,position,name');
	
		$this->teacher_list = $user_list;
		
		$grade = M("hw001.school_grade",null);
		$w['school'] = get_school_name();
		$w['is_del'] = 0;
		
		$grade_list = $grade->where($w)->select();
		
		$this->grade_list = $grade_list;
		
		$this -> display('classroom_add');
	}
	
	public function addClassRoom(){
		if (IS_AJAX) {
			$camp = M("hw001.camp_classroom",null);
			$data = $_POST;
			$data['school'] = get_school_name();
			if ($camp->add($data)) {// 读取成功
				$return['status'] = 1;
				$return['info'] = "新增成功";
				$this -> ajaxReturn($return);
			} else {
				$return['status'] = 0;
				$return['info'] = "新增失败";
				$this -> ajaxReturn($return);
			}
		}
	}
	
	public function updateClassRoom(){
		$camp = M("hw001.camp_classroom",null);
		$camp->create();
		if($camp->id){
			if($camp->save()){
				$this -> success('信息修改成功！',U('Camp/classroom'));
			}else{
				$this -> error('信息修改失败！',U('Camp/classroom'));
			}
	
		}
	
	
	}
	
	public function delClassRoom(){
		$camp = M("hw001.camp_classroom",null);
		$camp->create();
		if($camp->id){
			$w['id'] = array('in',$camp->id);
			$camp->where($w)->delete();
		}
	
		$this -> success('信息删除成功！',U('Camp/classroom'));
	}
	
}
