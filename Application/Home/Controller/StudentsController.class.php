<?php
namespace Home\Controller;
class StudentsController extends HomeController {
    private $_month = null;
    function _initialize() {
        parent::_initialize();
        $this->_month = session('date');
        $this->assign('from', strtolower(ACTION_NAME));
        $this->assign('controller', CONTROLLER_NAME);
        $this->assign('pageCount', 20);

    }

    ///学员信息//////////////////////////////////////////////////////////////////
    public function index(){
        $this->assign('title', '学员报名-'.get_school_name(session('school_id')).
            '-'.session('user_name'));
        $this->display();
    }

    public function charge($id){
        $Students = D('Students');
        $student = $Students->find($id);

        $this->assign('id', $id);
        $this->assign('name', $student['name']);
        $this->assign('std_id', $student['std_id']);
        $this->assign('balance', round(D('Consumption')->getBalance($student['std_id']), 2));
        $this->belong_user=M('user')->where(['school'=>get_school_id($student['school']),'is_del'=>0,'position_id'=>['in',[11,12,13,18,19]]])->getField('id,name');
        $this->display();
    }

    public function toreturn($id){
        $Students = D('Students');
        $student = $Students->find($id);

        $this->assign('id', $id);
        $this->assign('name', $student['name']);
        $this->assign('std_id', $student['std_id']);
        $this->assign('balance', round(D('Consumption')->getBalance($student['std_id']), 2));
        $this->display();
    }

    public function addStudent(){
        $returnData = array(
            'state' => 'error',
            'info'  => '添加失败',
        );

        if(D('Students')->addOne($_POST)){
            $returnData = array(
                'state' => 'ok',
                'info'  => '添加成功',
            );
        }

        $this->ajaxReturn($returnData);
    }

    public function saveStudent(){
        if(!$_POST['id']){
            return $this->addStudent();
        }

        $returnData = array(
            'state' => 'error',
            'info'  => '保存失败',
        );

        if(D('Students')->saveOne($_POST)){
            $returnData = array(
                'state' => 'ok',
                'info'  => '保存成功',
            );
        }

        $this->ajaxReturn($returnData);
    }

    public function getStudent(){
        $returnData = array(
            'state' => 'error',
            'info'  => '获取内容失败',
        );

        $Students = D('Students');
        $one = $Students->getOne((int)$_GET['id']);
        if($one){
            $returnData = array(
                'state' => 'ok',
                'data'  => $one,
            );
        }
        $this->ajaxReturn($returnData);
    }

    public function getStudents(){
        $month = $this->_month;
        $start = (int)$_GET['start'];
        $count = (int)$_GET['count'];
        $from  = $_GET['from'];
        $search = $_GET['search'];

        $order = '`id` desc';
        $conditionCom = array(
            'is_del' => array('eq', 0),
            'month' => $month,
            );
        $listCondition = $this->_mergeCondition($conditionCom,
                                                    json_decode($search, true));
        if(!empty($listCondition['contact'])){
            $listCondition['contact1|contact2|contact3'] = $listCondition['contact'];
        }

        $Students = D('Students');
        $data = array(
            'state'    => 'ok',
            'data'     => $Students->getList($listCondition, $start, $count,
                            $order),
            'start'    => $start,
            'count'    => $count,
            'total'    => $Students->where($listCondition)->count(),
        );

        $this->ajaxReturn($data);
    }

    public function delStudents(){
        $returnData = array(
            'state' => 'error',
            'info'  => '删除失败',
        );
        $Students = D('Students');
        if($Students->del($_POST['id'])){
            $returnData = array(
                'state' => 'ok',
                'info'  => '删除成功',
            );
        }

        $this->ajaxReturn($returnData);
    }

    public function chargeStudent(){
        $returnData = ['state' => 'error', 'info'  => '缴费失败'];

        if(I('pay_no') && M('consumption')->where(['emp_school'=>session('school_id'),'pay_no'=>I('pay_no')])->find())
        $this->ajaxReturn(['state' => 'error', 'info'  => '该收据已经录入过了']);
		
		if(I('cardNo')){
			$card = M('vipcard')->where(['card_no'=>I('cardNo'),'card_state'=>'01'])->find();
			
			if($card){
				if(M('consumption')->where(['card_id'=>$card['id']])->find()){
					$this->ajaxReturn(['state' => 'error', 'info'  => '该贵宾卡片已经录入过了！']);	
				}else{
					if($card['card_value']<$_POST['tocharge']){
						$this->ajaxReturn(['state' => 'error', 'info'  => '缴费金额大于贵宾卡金额，请重新输入！']);	
					}
				}				
			}else{
				$card2 = M('vipcard')->where(['card_no'=>I('cardNo')])->find();
				if($card2){
					if($card2['card_state'] == '03'){
						$this->ajaxReturn(['state' => 'error', 'info'  => '该贵宾卡已经消费完！']);	
					}else if($card2['card_state'] == '02'){
						$this->ajaxReturn(['state' => 'error', 'info'  => '该贵宾卡处于禁用状态！']);
					}
				}else{
					$this->ajaxReturn(['state' => 'error', 'info'  => '该贵宾卡不存在！']);	
				}
				
			}
			
			M('vipcard')->where(['id'=>$card['id']])->save(['updator'=>session('auth_id'),'card_state'=>'03']);
		}
        
		
        $student = D('Students')->find($_POST['id']);
        if(D('Consumption')->charge([
            'belong_type'    => I('belong_type'),
            'belong_user'    => I('belong_user'),
            'belong_user_name'    => get_user_name(I('belong_user')),
            'student'    => $student['name'],
            'std_id'     => $student['std_id'],
            'value'      => $_POST['tocharge'],
            'pay_no'     => $_POST['pay_no'],
            'remark'     => $_POST['remark'],
            'type'     => $_POST['type'],
            'card_id' => $card['id']
        ])){
            $returnData = ['state' => 'ok', 'info'  => '缴费成功'];
        }

        $this->ajaxReturn($returnData);
    }

    public function returnStudent(){
        $returnData = ['state' => 'error', 'info'  => '退费失败'];

        $student = D('Students')->find($_POST['id']);
        if(D('Consumption')->yreturn([
            'student'    => $student['name'],
            'std_id'     => $student['std_id'],
            'value'      => -$_POST['tocharge'],
            'pay_no'     => $_POST['pay_no'],
            'remark'     => $_POST['remark'],
        ])){
            $returnData = ['state' => 'ok', 'info'  => '退费成功'];
        }

        $this->ajaxReturn($returnData);
    }

    public function exportStudents(){
        $month = $this->_month;
        $search = $_POST;

        $condition = array(
            'is_del' => array('eq', 0),
            'month' => $month,
            );

        $condition = $this->_mergeCondition($condition, $search);

        $Students = D('Students');
        $list = $Students->getList($condition, 0, 1000000, '`id` desc');

        $excel = new \Home\Hongwen\ExportExcel();
        $head = array(
            array('id'                 , '序号'),
            array('month'              , '期次'),
            array('std_id'             , '学号'),
            array('name'               , '姓名'),
            array('sex'                , '性别'),
            array('pre_school'         , '学校'),
            array('pre_grade_class'    , '年级班级'),
            array('contact1'           , '联系方式1'),
            array('contact2'           , '联系方式2'),
            array('contact3'           , '联系方式3'),
            array('apply_user'         , '创建人'),
            array('update_time_str'    , '资料更新时间'),
            array('create_time_str'    , '资料创建时间'),
        );
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition:attachment; filename='
            .ACTION_NAME.date(' Y-m-d H-i-s').'.xls');
        header('Cache-Control: max-age=0');
        die($excel->export($list, $head));
    }

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
            if($value == '_all_'){
                unset($condition[$key]);
            }
        }

        return $condition;
    }

    //特殊小组课管理
    public function te_grade(){

        $mod=M('CourseTeGrade');
        if(I('post.add')){                  //创建小组
            $mod->create();
            $mod->add();
        }else if(I('post.add_student')){    //添加成员
            $mod->create();
            if(empty(I('post.school')))$mod->school=session('school_id');
            $mod->add();
        }elseif(I('get.del')){              //删除成员
            $mod->where(['id'=>I('get.del')])->setField('is_del',1);
            $this->ajaxReturn('ok');
        }

        $w['school']=I('get.school')?:session('school_id');
        $this->grade=$mod->where($w)->where('type=1')->select();
        //获取校区所有学员
        $this->stu_list=D('Students')->where(['school'=>get_school_name($w['school'])])->order('p')->getField('id,name');
        $list=$mod->where($w)->where(['pid'=>(I('get.grade')?:-1),'is_del'=>0])->select();
        foreach ($list as &$v) {
            $x=M('Course')->where(['id'=>$v['course_id']])->Field('hour,used_hour')->select();
            $v['last_day']=$x[0]['hour']-$x[0]['used_hour'];
        }
        $this->list=$list;
        $this->display();
    }

    //获取上课记录
    public function ajax_get_record($id){
        $data=M('Course')->where(['id'=>$id])->getField('log');
        $dat=explode('|',$data);
        rsort($dat);
        $this->ajaxReturn($dat);

    }


}
