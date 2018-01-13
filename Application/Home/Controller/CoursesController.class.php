<?php
namespace Home\Controller;
class CoursesController extends HomeController {
    private $_month = null;
    function _initialize() {
        parent::_initialize();
        $this->_month = session('date');
        $this->assign('from', strtolower(ACTION_NAME));
        $this->assign('controller', CONTROLLER_NAME);
        $this->assign('pageCount', 20);
    }

    public function index($id = null){
        $id = (int)$id;
        if(empty($id)){
            $this->error('请先选择学员', U('Student/index'));
        }

        $student = D('Students')->find($id);
        $this->student=$student;
        $this->assign('id'               , $id);
        $this->assign('name'             , $student['name'], 2);
        $this->assign('std_id'           , $student['std_id'], 2);
//      $this->assign('balance'          , round(D('Consumption')->getBalance($student['std_id']), 2)); 解决-0的问题；实际上是-0.0000000000000000000000000008923489这样的问题，导致的-0问题；
        $this->assign('balance'          , ((round(D('Consumption')->getBalance($student['std_id']), 2))==0?0:(round(D('Consumption')->getBalance($student['std_id']), 2))));
        $this->assign('schoolList'       , C('SCHOOL'));
//      $this->assign('gradeList'        , C('SCHOOL_GRADE'));
        $this->assign('courseList'       , C('SCHOOL_COURSE'));
        $this->assign('subjectList'      , C('SCHOOL_SUBJECT'));
        $this->assign('stdTypeList'      , C('SCHOOL_STUDENT_TYPE'));
        $this->assign('teacherLevelList' , C('SCHOOL_TEACHER_LEVEL'));
		
		if($student['grade']){
			
			$grade_lst = C('SCHOOL_GRADE');
			$temp = array_column($grade_lst,'id','name');

								 
			switch($student['grade']){
				
				case 22:
					foreach($grade_lst as $k=>$v){
						if($v['id']!=22){
							unset($grade_lst[$k]);
						}
					}
					 break;	
					 
				case 21:
					foreach($grade_lst as $k=>$v){
						if($v['id']!=21){
							unset($grade_lst[$k]);
						}
					}
					 break;	
				
				case 20:
					foreach($grade_lst as $k=>$v){
						if($v['id']!=20){
							unset($grade_lst[$k]);
						}
					}
					 break;	
				
				
				case 50:
					foreach($grade_lst as $k=>$v){
						if($v['id']!=50){
							unset($grade_lst[$k]);
						}
					}
					 break;	 
				
				case 40:
					foreach($grade_lst as $k=>$v){
						if($v['id']!=40){
							unset($grade_lst[$k]);
						}
					}
					 break;
				
					 
				case 39:
					foreach($grade_lst as $k=>$v){
						if($v['id']!=39){
							unset($grade_lst[$k]);
						}
					}
					 break;
				
				case 38:
					foreach($grade_lst as $k=>$v){
						if($v['id']!=38){
							unset($grade_lst[$k]);
						}
					}
					 break;	
					 
				case 93:
					foreach($grade_lst as $k=>$v){
						if($v['id']!=93){
							unset($grade_lst[$k]);
						}
					}
					 break;	
				
				case 100:
					foreach($grade_lst as $k=>$v){
						if($v['id']!=100){
							unset($grade_lst[$k]);
						}
					}
					 break;	
				
				case 101:
					foreach($grade_lst as $k=>$v){
						if($v['id']!=101){
							unset($grade_lst[$k]);
						}
					}
					 break;	   
				
				case 102:
					foreach($grade_lst as $k=>$v){
						if($v['id']!=102){
							unset($grade_lst[$k]);
						}
					}
					 break;	 
					 
				case 103:
					foreach($grade_lst as $k=>$v){
						if($v['id']!=103){
							unset($grade_lst[$k]);
						}
					}
					 break;	 
				default:
						$this->assign('gradeList'        , C('SCHOOL_GRADE'));  
						break;
			}
			
			array_unshift($grade_lst,array('id'=>'1','name'=>"选择年级",'remark'=>'','sort'=>'0','pid'=>'16','group'=>'16','ext'=>'','is_del'=>'0')); //增加前端年级选项变动，调动相应的js事件
			$this->assign('gradeList'        , $grade_lst);
			
		}else{
			$this->assign('gradeList'        , C('SCHOOL_GRADE'));
		}

        $this->assign('disList', D('DiscountRole')->where([
            'is_del' => ['eq', 0],
            'pid'    => ['eq', C('DISCOUNT_ID')['FACTOR']],
            ])->field(['id', 'name'])->select());

//      $course=D('UnitpriceRoleView')->where(['school'=>get_school_id($student['school']),'is_del'=>0])->select(); //增加价格方案隐藏机制
        $course=D('UnitpriceRoleView')->where(['school'=>get_school_id($student['school']),'is_del'=>0,'displays'=>'0','F4.display'=>1])->select();
        foreach ($course as &$v) {
            $v['name']=substr($v['name'],strpos($v['name'],' '));
        }
        $this->course=json_encode($course);
        $this->display();
    }

    public function indexx($id){
        $student = D('Students')->find($id);
        $this->student=$student;
        $this->grade=M('foo_info')->where('pid=16')->getField('id,name');
        $this->level=M('foo_info')->where('pid=19')->getField('id,name');
        $this->course=json_encode(D('UnitpriceRoleView')->where(['school'=>get_school_id($student['school']),'is_del'=>0])->select());
        $this->display();
    }

    /**
     * 获取课程列表,all大于0获取所有
     */
    public function getCourses($all=0) {
        $order = '`id` desc';
        $start = (int)$_GET['start'];
        $count = (int)$_GET['count'];
        $search = json_decode($_GET['search'], true);

        $listCondition = array(
            'Course.is_del' => array('eq', 0),
            'Course.std_id' => $_GET['std_id'],
        );
        $listCondition=$this->_mergeCondition($listCondition,$search);
        // var_dump($listCondition);
        if($all){
            unset($listCondition);//获取全部信息
            $listCondition[ 'Course.is_del'] = 0; //筛掉已删除数据
            $listCondition['Course.school']=$search['school']?$search['school']:session('school_id');
            if($search['state']!='_all_')$listCondition['Course.state']=$search['state'];
            if($search['name'])$listCondition['Course.name']=['like','%'.$search['name'].'%'];
        }
        $Courses = D('CourseView');
        $data = array(
            'state'    => 'ok',
            'data'     => $Courses->getList($listCondition, $start, $count, $order),
            'start'    => $start,
            'count'    => $count,
            'total'    => $Courses->where($listCondition)->count(),
        );

        $this->ajaxReturn($data);
    }

    // 修改订单的已上课时
    public function change_used($id){
        //截止今日还有未确认的排课
        if(M('hw001.class',null)->where([
                'course_id'=>$id,
                'timee'=>['lt',date('Y-m-d')],
                'cwqr'=>''
            ])->find())$this->ajaxReturn('有未确认课时，请先确认！');

        // 查询修改后预排课时是否可行
        $course=M('course')->where(['id'=>$id])->find();
        if($course){
            $un = $this->getUnconfirmedCount($id);
            if(($un + I('used')) > ($course['hour'] + $course['ext_hour'])){
                $this->ajaxReturn('请删除部分预排课程再修改！');
            }else{
                $data['used_hour']=I('used');
                $record=$course['used_hour'].','.session('user_name').','.date('Y-m-d H:i:s').','.I('used');
                $data['record']=['exp',"CONCAT_WS('|',record,'$record')"];
                M('course')->where(['id'=>$id])->save($data);
                $this->ajaxReturn('ok');
            }
        }
        $this->ajaxReturn('订单修改失败');
    }

    /**
    计算价格
     */
    public function getPrice() {

        if(I('unit_plan')){
            $result=D('Course')->plan_price(I('unit_plan'),$_GET['hour']);
            $result['state']='ok';
            //获取课程关联的老师信息
            if ((int)$_GET['subject_id']) {
                $map = [
                    'UnitpriceRole.id' => I('unit_plan'),
                    'TeachRole.subject'    => I('get.subject_id'),
                    'TeachRole.is_del'     => 0,
                    'User.is_del'          => 0,
                ];
                $result['data']= D('TeachPriceView')->where($map)->select();
            }

            $this->ajaxReturn($result);
        }
    }






    /**
订购课程
     */
    public function choose() {
        $returnData = ['state' => 'error', 'info'  => '选课失败'];

        $Students    = D('Students');
        $Course      = D('Course');
        $Consumption = D('Consumption');

        $data = [];
        foreach ($_POST as $key => $value) {
            if(stripos($key, 'apply_form') !== false){
                $data[] = json_decode($value, true);
            }
        }

        $payInfo  = json_decode($_POST['pay_info'], true);
        $payInfo['student'] = $payInfo['name'];

        if (round($Consumption->getBalance($payInfo['std_id']),2) < $payInfo['pay_due']) {
            $returnData = ['state' => 'error', 'info'  => '充值金额不足'];
        } elseif ($Course->book($payInfo, $data)){
            $returnData = ['state' => 'ok', 'info'  => '选课成功'];
        }

        $this->ajaxReturn($returnData);
    }

    /**
退课页面
     */
    public function drop($id) {
        $course = D('CourseView')->where(['Course.id' => $id])->find();
        $Realreturn = D('Course') -> plan_price($course['unit_plan'],$course['used_hour'],$course['create_time'],1);
        // var_dump($Realreturn);
        $unitpriceReal = $Realreturn['unitprice'];
        $returnValue = formatPrice($course['price']-$Realreturn['price']);

        $this->assign('id'             , $id);
        $this->assign('name'           , $course['name']);
        $this->assign('std_id'         , $course['std_id']);
        $this->assign('hour'           , $course['hour']);
        $this->assign('ext_hour'       , $course['ext_hour']);
        $this->assign('used_hour'      , $course['used_hour']);
        $this->assign('plan_name'      , $course['plan_name']);
        $this->assign('factor'         , $course['factor']);
        $this->assign('unitprice'      , formatPrice($course['unitprice']));
        $this->assign('unitprice_real' , formatPrice($unitpriceReal));
        $this->assign('forext_hour'    , $Realreturn['ext_hour']);
        $this->assign('return_value'   , $returnValue);
        $this->assign('return_info'    , $course['price'].' - ('.$course['used_hour'].' - '.$Realreturn['return_ext_hour'].") X {$unitpriceReal} = {$returnValue}");
        $this->assign('return_exthour' , $Realreturn['return_ext_hour_last']);
        $this->display();
    }

    /**
     * 执行退课
     */
    public function dropCourse() {
        $returnData = ['state' => 'error', 'info'  => '退课失败'];

        $id = (int)$_POST['id'];
        // print_r(I('post.'));

        if($this->getUnconfirmedCount($id)){
            $returnData = ['state' => 'error', 'info'  => '有未确认排课，请先确认或删除'];
        }else{
            if(D('Course')->drop($id, I('post.'))){
                $returnData = ['state' => 'ok', 'info'  => '退课成功'];
            }
        }

        $this->ajaxReturn($returnData);
    }

    /**
调课页面
     */
    public function renewal($id) {
        $course = D('CourseView')->where(['Course.id' => $id])->find();
        // if($course['count']!=1)$this->error('该订单不支持调整课时');
        $courseSbt = D('CourseSbtView')->getSbt($course['id']);
        $sbt_str = '';
        if (is_array($courseSbt)) {
            foreach ($courseSbt as $val) {
                $sbt_str .= $val['subject_name'].' > '.$val['teacher_name'].'<br />';
            }
        }
        $course['subject_teacher'] = trim($sbt_str, '<br />');
        $return = $this->_getReturn($course['used_hour'], $course['school']);
        $unitpriceReal = $course['unitprice']*$course['factor'];
        $returnValue = formatPrice($unitpriceReal*$course['hour'] - $return['hour']*$course['unitprice']);

        $this->course=$course;
        $this->unit_plan=D('UnitpriceRoleView')->find($course['unit_plan']);
        $this->assign('unitprice_real' , $unitpriceReal);
        $this->assign('forext_hour'    , $this->_getExtHour($course['used_hour'], $course['school']));
        $this->assign('return_value'   , $returnValue);
        $this->assign('return_info'    , "({$course['hour']} - {$return['hour']}) X {$unitpriceReal} = {$returnValue}");
        $this->assign('return_exthour' , $return['extHour']);
        $this->assign('title'          , '学员调课-'.get_school_name(session('school_id')));
        $this->assign('stdTypeList'    , C('SCHOOL_STUDENT_TYPE'));
        $this->assign('balance'        , round(D('Consumption')->getBalance($course['std_id']), 2));

        $this->display();
    }

    /**
     * 计算调课价格
     */
    public function getPriceRenewal() {
        $course = D('Course')->find($_GET['id']);
        $newcourse = D('Course')->plan_price($course['unit_plan'],$_GET['hour'],$course['create_time'],0);
        $unitprice = $newcourse['unitprice'];
		$rd_count = $newcourse['count']; //周、月课时量
        $price = $course['price'];
        $factor = $course['factor'];
        //针对周月走规则的按is_join==2 流程走，否则的话，按else走
        if($newcourse['is_join'] == 2){
        	$priceNew = $_GET['hour'] * $rd_count * $unitprice*$factor;
        }else{
        	$priceNew = $_GET['hour']*$unitprice*$factor;
        	$unitprice = $newcourse['unitprice']/$newcourse['count'];
        }
// 		$priceNew = $_GET['hour']*$unitprice*$factor; //缺少对按周、月计算的情况 edit by zhangxm at 2016-03-12 16:02
//         $priceNew = $_GET['hour'] * $rd_count * $unitprice*$factor;

        $result = [
            'state'    => 'ok',
            'data'     => D('TeachPriceView')->where([
                'UnitpriceRole.id'  => ['eq', (int)$_GET['plan_id']],
                'TeachRole.subject' => ['eq', (int)$_GET['subject']],
                ])->select(),
            'price'    => round($priceNew, 2),
            'ext_hour' => $newcourse['ext_hour'],
            'pay'      => round($priceNew - $price, 2),
            'factor'   => $factor,
            'unitprice'   => $unitprice,
        ];

        $this->ajaxReturn($result);
    }

    /**
     * 执行调课
     */
    public function renewalCourse() {
        $returnData = ['state' => 'error', 'info'  => '调课失败'];

        $Course      = D('Course');
        $Consumption = D('Consumption');
        $payInfo     = json_decode($_POST['pay_info'], true);
        $data        = json_decode($_POST['new'], true);
		$foo = M("FooInfo");
		
		$foo_ids = $foo->where("pid=17 and `group` in (2,3) and is_del=0")->getField('id',true);
		
        $oldCourse = $Course->field(array('operate_area','area'),true)->find((int)$data['id']);
        
        $unitprice = M("UnitpriceRole");
        
        $plan_course = $unitprice->where(["id"=>$oldCourse['unit_plan']])->getField('course');
        
		if($data['add_hour']>0 && ((time()-$oldCourse['create_time'])>15*24*60*60) && !(in_array($plan_course,$foo_ids))){
			$returnData = ['state' => 'error', 'info'  => '课时订购已超过15天！'];
		}else{
			$payInfo['student'] = $payInfo['name'];
			
			if ($data['hour'] < 0 || ($data['hour']*$data['count']+$data['ext_hour'])<($oldCourse['used_hour']+$this->getUnconfirmedCount($data['id']))) {
				$returnData = ['state' => 'error', 'info'  => '课时错误！'];
			} elseif (round($Consumption->getBalance($payInfo['std_id']),2) < round($payInfo['pay_due'],2)) {
				$returnData = ['state' => 'error', 'info'  => '充值金额不足'];
			} elseif ($Course->renewal($payInfo, $data, $oldCourse)) {
				$returnData = ['state' => 'ok', 'info'  => '调课成功'];
			}	
		}
        

        $this->ajaxReturn($returnData);
    }

    /**
变更科目页面
     */
    public function tosbt($id) {
        $course = D('CourseView')->where(['Course.id' => $id])->find();
        $courseSbt = D('CourseSbtView')->getSbt($course['id']);
        $sbt_str = '';
        if (is_array($courseSbt)) {
            foreach ($courseSbt as $val) {
                $sbt_str .= $val['subject_name'].' > '.$val['teacher_name'].'<br />';
            }
        }
        $course['subject_teacher'] = trim($sbt_str, '<br />');
        $return = $this->_getReturn($course['used_hour'], $course['school']);
        $unitpriceReal = $course['unitprice']*$course['factor'];
        $returnValue = round($unitpriceReal*$course['hour'] - $return['hour']*$course['unitprice'], 2);

        $this->assign('id'               , $id);
        $this->assign('name'             , $course['name']);
        $this->assign('std_id'           , $course['std_id']);
        $this->assign('hour'             , $course['hour']);
        $this->assign('ext_hour'         , $course['ext_hour']);
        $this->assign('used_hour'        , $course['used_hour']);
        $this->assign('plan_name'        , $course['plan_name']);
        $this->assign('plan_id'          , $course['plan_id']);
        $this->assign('subject'          , $course['subject']);
        $this->assign('subject_name'     , $course['subject_name']);
        $this->assign('teacher_name'     , $course['teacher']);
        $this->assign('subject_teacher'  , $course['subject_teacher']);
        $this->assign('teacher_id'       , $course['teacher_id']);
        $this->assign('director'         , $course['director']);
        $this->assign('manager'          , $course['manager']);
        $this->assign('remark'           , $course['remark']);
        $this->assign('price'            , formatPrice($course['price']));
        $this->assign('factor'           , formatPrice($course['factor']));
        $this->assign('unitprice'        , formatPrice($course['unitprice']));
        $this->assign('unitprice_real'   , $unitpriceReal);
        $this->assign('forext_hour'      , $this->_getExtHour($course['used_hour'], $course['school']));
        $this->assign('return_value'     , $returnValue);
        $this->assign('return_info'      , "({$course['hour']} - {$return['hour']}) X {$unitpriceReal} = {$returnValue}");
        $this->assign('return_exthour'   , $return['extHour']);
        $this->assign('title'            , '变更科目教师-'.get_school_name(session('school_id')));
        $this->assign('stdTypeList'      , C('SCHOOL_STUDENT_TYPE'));
        $this->assign('balance'          , round(D('Consumption')->getBalance($course['std_id']), 2));
        $this->assign('schoolList'       , C('SCHOOL'));
        $this->assign('gradeList'        , C('SCHOOL_GRADE'));
        $this->assign('courseList'       , C('SCHOOL_COURSE'));
        $this->assign('subjectList'      , C('SCHOOL_SUBJECT'));
        $this->assign('stdTypeList'      , C('SCHOOL_STUDENT_TYPE'));
        $this->assign('teacherLevelList' , C('SCHOOL_TEACHER_LEVEL'));

        $this->assign('disList', D('DiscountRole')->where([
            'is_del' => ['eq', 0],
            'pid'    => ['eq', C('DISCOUNT_ID')['FACTOR']],
            ])->field(['id', 'name'])->select());


        $this->display();
    }

    /**
     * 变更讲师
     */
    public function dosbt() {
        $returnData = ['state' => 'error', 'info'  => '更改失败'];

        $sbt = [];
        foreach ($_POST['subject'] as $key => $value) {
            $sbt[$key] = ['subject_id' => $value, 'teacher_id' => $_POST['teacher'][$key]];
        }

        if(D('CourseSbt')->write((int)$_POST['id'], $sbt)){
            $returnData = ['state' => 'ok', 'info'  => '更改成功'];
        }

        $this->ajaxReturn($returnData);
    }


    /**
暂停恢复排课
     */
    public function pauseOrRecover() {
        $returnData = ['state' => 'error', 'info'  => '操作失败'];

        $id = (int)$_POST['id'];
        $flag = (int)$_POST['flag'];
        $reason = $_POST['reason'];

        if ($flag) { // 暂停排课
            $state = C('COURSE_STATES')['PAUSE']['id'];
        } else { // 正常排课
            $state = C('COURSE_STATES')['NORMAL']['id'];
        }

        if (D('Course')->save([
            'id' => $id,
            'state' => $state,
            'reason' => $reason,
            ])) {
            $returnData = ['state' => 'ok', 'info'  => '操作成功'];
        }

        $this->ajaxReturn($returnData);
    }

/**
其他以及调用
*/
    // 订单详情的页面
    public function fororder($order_id = null) {
        $this->assign('order_id', $order_id);
        $this->display();
    }

    /**
     * 退课详情
     */
    public function fordrop($id = null) {
        $consumption = D('Consumption')->find($id);
        $this->assign('reason', $consumption['reason']);
        $this->assign('remark', $consumption['remark']);
        $this->assign('drop_id', $consumption['ext_id']);
        $this->display();
    }

    /**
     * 调课详情
     */
    public function forrenwal($id = null) {
        $this->assign('order_id', $id);
        $consumption = D('Consumption')->find($id);
        $this->assign('reason', $consumption['reason']);
        $this->assign('remark', $consumption['remark']);
        $this->assign('drop_id', $consumption['ext_id']);
        $this->display();
    }

    /**
     * 具体上课情况
     */
    public function forarrangedcourse($id = null) {
        $ArrangedCourse = D('ArrangedCourse');
        $this->assign('id', $id);
        $this->assign('course', D('Course')->find($id));
        $this->assign('arrangedCourseList', $ArrangedCourse->getListByCourseId($id));
        $this->assign('unconfirmedHour', $ArrangedCourse->getUnconfirmedHour($id));
        $this->assign('confirmedHour', $ArrangedCourse->getConfirmedHour($id));
        $this->assign('absentHour', $ArrangedCourse->getAbsentHour($id));
        $this->display();
    }

    /**
     * 获取赠送课时
     * @param  float $classHour 选课的课时
     * @return float            赠送的课时
     */
    private function _getExtHour($classHour, $school = -1) {
        $DiscountRole = D('DiscountRole');
        $roles = $DiscountRole->where([
            'is_del' => ['eq', 0],
            'pid'    => ['eq', C('DISCOUNT_ID')['HOUR']],
            'school' => ['in', [-1, $school]],
            ])->select();
        $discountHour = 0;
        foreach ($roles as $role) {
            if ($role['bottom'] <= $classHour && $classHour < $role['top']) {
                $discountHour = $role['value'];
            }
        }

        return $discountHour;
    }

    /**
     * 获取折扣系数
     * @param  int $discountId 折扣规则的ID
     * @return float           折扣系数
     */
    private function _getExtFactor($discountId) {
        $DiscountRole = D('DiscountRole');
        $role = $DiscountRole->find((int)$discountId);

        $factor['factor'] = 1.0;
        if(!empty($role['role'])){
            eval($role['role']);
        }

        return $factor;
    }

    /**
     * 获取退课相关信息
     * @param  float $usedHour 已经上过的课时数
     * @return array           参考 D('Course')->getReturn
     */
    private function _getReturn($usedHour, $school = -1) {
        return D('Course')->getReturn($usedHour, $school);
    }

    /**
     * 合并查询条件
     */
    private function _mergeCondition($conditionCom, $condition){
        foreach($condition as $key => $value){
            if($value === ''){
                unset($condition[$key]);
            }
        }

        if(empty($condition)){
            return $conditionCom;
        }

        $condition = array_merge($conditionCom, $condition);
        foreach($condition as $key => $value){
            if($value == '_all_' || $value == ''){
                unset($condition[$key]);
            }
        }

        return $condition;
    }

    public function print_course ($id){
        $info=M('hw001.student',null)->find($id);
        $condition['std_id']=$info['std_id'];
        $condition['is_del']=0;
        $condition['state']=['neq',C('CONSUME_STATES')['CANCEL']['id']];
        $data=D('Consumption')->getList($condition, 0, 100,'create_time desc',true);
        foreach ($data as &$v) {
            $v['type']=getConsumeTypeById($v['type'])['name'];
            $v['order_std_type']=getStudentTypeById($v['order_std_type'])['name'];
            $v['order_plan']=str_replace(get_school_name(),'', $v['order_plan']);
        }
		
		$this->assign('gradeList', C('SCHOOL_GRADE'));
		$grade_lst = C('SCHOOL_GRADE');
		$gradelst = array_column($grade_lst,'name','id');
		$this->gradelst = $gradelst;
		
        $this->info=$info;
        $this->data=$data;
        $this->display();
    }
	
	
	 public function print_course_new($id){
        $st=M('hw001.student',null);
		$info = $st->alias('st')->join('left join hongwen_oa.oa_yewu_students as ywst on st.oa_stuid = ywst.id')->field('st.std_id,st.name,st.sex,st.schoolx,st.grade,st.wl,st.jiaoxue,st.xueguan,st.school,st.other,ywst.parents,ywst.parent_type,ywst.tel1,ywst.tel2')->where(['st.id'=>$id])->select();
        $condition['std_id']=$info[0]['std_id'];
        $condition['is_del']=0;
        $condition['state']=['neq',C('CONSUME_STATES')['CANCEL']['id']];
        $data=D('Consumption')->getList($condition, 0, 100,'create_time desc',true);
        foreach ($data as &$v) {
            $v['type']=getConsumeTypeById($v['type'])['name'];
            $v['order_std_type']=getStudentTypeById($v['order_std_type'])['name'];
            $v['order_plan']=str_replace(get_school_name(),'', $v['order_plan']);
			$v['order_course_type'] = getSchoolCourseById($v['order_course_type'])['name'];
        }
		
		$this->assign('gradeList', C('SCHOOL_GRADE'));
		$grade_lst = C('SCHOOL_GRADE');
		$gradelst = array_column($grade_lst,'name','id');
		$this->gradelst = $gradelst;
		
        $this->info=$info[0];
        $this->data=$data;
        $this->display();
    }
	

    //设置老师课程关联问题
    public function teacher(){
        $this->list=M('user')->where(['school'=>session('school_id')])->where('is_del=0')->select();
        $this->display();
    }

    /**
    * 获取未确认课程课时
    * @param  int   $courseId 订单中课程的id
    * @param  int   $stdId    学号
    * @return float           未被确认的课时数
    */
    public function getUnconfirmedCount($courseId) {
        return (float)(M('hw001.class',null)->where([
                'course_id' => $courseId,
                'cwqr'  => '', // 待确认的课
                ])->sum('count'));
    }

}
