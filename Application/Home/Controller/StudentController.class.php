<?php
namespace Home\Controller;

class StudentController extends HomeController {
    // protected $config = array('app_type' => 'master');

    public function indexx(){
        $this->show("<extend name='Layout/ins_page' />");
    }

    public function index($stuid=0){
		
        if (!empty($_POST['keyword'])) {
            $w['name|tel|xueguan|jiaoxue|std_id'] = array('like', "%" . $_POST['keyword'] . "%");
        }else{
            // $w['state']=1;
        }

        if($_POST['add']){
            $this->addx();
        }elseif ($_POST['delt']) {
            $this->deltx();
        }else{
            if($stuid)$w['id']=$stuid;
            $w['school']=get_school_name();
            if(get_school_name()=='集团')unset($w['school']);//给集团帐号做的特别处理
            /*if(I('param.school')){
            	if(is_array(I('param.school'))){
            		$w['school']= array('neq','大连校区');
            	}else{
            		$w['school']=I('param.school');
            	}
            	
			}else{
				$w['school'] = array('neq','大连校区');
			}*/
            if(I('param.xueguan'))$w['xueguan']=I('param.xueguan');
            if(I('param.jiaoxue'))$w['jiaoxue']=I('param.jiaoxue');
            if(I('param.state')){
            	if(is_array(I('param.state'))){
            		$w['state']= array('neq',4);
            	}else{
            		$w['state']=I('param.state');	
            	}
            	
			}else{
				$w['state'] = array('neq',4);
			}
            if(I('param.grade'))$w['grade']=I('param.grade');
            if(I('param.type'))$w['type']=I('param.type');
            // if(get_position_name()=='教学主任')$w['jiaoxue']=session('user_name');
            // if(get_position_name()=='学习管理师')$w['xueguan']=session('user_name');
            $Data = M('hw001.Student',null); // 实例化Data数据对象
            $count      = $Data->where($w)->count();// 查询满足要求的总记录数 $map表示查询条件
            $Page       = new \Home\Hongwen\Page($count,20);// 实例化分页类 传入总记录数
             //分页跳转的时候保证查询条件x
            foreach($w as $key=>$val) {
                $Page->parameter[$key]   =   $val;
            }
            $show       = $Page->show();// 分页显示输出
            // 进行分页数据查询
            $list = $Data->where($w)->order('id')->limit($Page->firstRow.','.$Page->listRows)->select();

            $this->assign('list',$list);// 赋值数据集
            $this->assign('page',$show);// 赋值分页输出

            //学管输出
//          $w2['school']=session('school_id'); edit by zhangxm 因为集团账户登录进来后，选择校区后无法选择相应校区的学习管理师，在此处添加针对集团账户的处理
			if(session('school_id') !== '0'){
				$w2['school']=session('school_id');	
			}
			
            $w2['position_id']=get_position_id('学习管理师');
            $w2['is_del'] = 0;
            $this->xueguan=M('user')->where($w2)->getField('name',true);
            //教学主任输出
            $w3['school']=session('school_id');
            $w3['position_id']=['in', [C('POSITION_ID')['SCHOOL_DIRECTOR'],C('POSITION_ID')['SCHOOL_CONSULTS_XZ'],C('POSITION_ID')['SCHOOL_DIRECTOR_XZ']]];
            $this->jiaoxue=M('user')->where($w3)->getField('name',true);
			
			//设置年级列表
			$this->assign('gradeList'        , C('SCHOOL_GRADE'));
			
            $this->display(); // 输出模板
        }

    }

	public function getXg($school_name){
			$w['school'] = get_school_id($school_name);
		 	$w['position_id']=get_position_id('学习管理师');
            $xg_lst=M('user')->where($w)->getField('name',true);
			$this->ajaxReturn([
				'state'=>'ok',//查询结果
				'data'=>$xg_lst//查询到数据库有多少条满足条件记录
			  ]);
	}
    //外部api调用
    public function info($id){
        $info= M('hw001.Student',null)->find($id);
        $info['tk']=explode('|', $info['tk']);
        $this->ajaxReturn($info);
    }

    function addx(){
        $m = M('hw001.Student',null);
        $m->create();
        if($_POST['tk']){
            $tk=implode('|',$_POST['tk']);
            $m->tk=$tk;
        }
        $m->school=get_school_name();
        $cc  = new \Home\Hongwen\Firstname();
        $p=$cc->getInitials(substr(I('post.name'),0,3));
        $m->p=$p?$p:'※';
        $m->id='';
        $m->std_id = date('Ymd').sprintf("%03d", session('school_id')).sprintf("%03d",
            $m->where("FROM_UNIXTIME(".time().",'%Y-%m-%d')=CURRENT_DATE() and school='".get_school_name(session('school_id'))."'")
            ->count() + 1);
        if($m->add()){
            $this->success('数据添加成功……');
        }else{
            $this->error('操作失败……');
        }
    }

    function savex(){
        $m = M('hw001.Student',null);
        $m->create();
        if($_POST['tk']){
            $tk=implode('|',$_POST['tk']);
            $m->tk=$tk;
        }else{
            $m->tk='';
        }
        S(session('school_id').'student_status',null);
        if($m->save())print_r(json_encode(1));
    }

    function deltx(){
        $id=$_POST['id'];
        if(M('hw001.Student',null)->delete($id)){
            $this->success('数据删除成功……');
        }else{
            $this->error('操作失败……');
        }
    }
/**
小组管理
*/
    public function grade(){

        $mod=M('hw001.school_grade',null);
        if(IS_POST){
            $mod->create();
            $mod->school=I('post.school')?I('post.school'):get_school_name();
            $mod->add();
        }
        $w['school']=get_school_name();
        if(I('get.school'))$w['school']=I('get.school');
        $w['is_del']=0;
        $list=$mod->where($w)->select();
        foreach ($list as $k=>$v) {
            $stus=M('hw001.stu_grade',null)->where(['gid'=>$v['id']])->select();//小组成员列表
            foreach ($stus as $v2) {
                if(!M('course')->where(['id'=>$v2['course_id'],'state'=>200])->find())
                $list[$k]['bd']='异常';
                $list[$k]['students'].=','.$v2['name'];
            }
        }
        $this->list = $list;
        $w['state']=1;
        $this->list2=M('hw001.student',null)->where($w)->order('p')->getField('id,p,name');//校区所有学员列表
        $this->display();
    }

    //删除班级
    public function delt_grade($id){
        if(IS_AJAX && $id){
            if(M('hw001.school_grade',null)->where(['id'=>I('get.id')])->setField('is_del',1)){
                M('hw001.class',null)->where(['grade'=>$id,'timee'=>['gt',date('Y-m-d')],'state'=>0])->delete();//删除排课系统里的未上数据
                $this->ajaxReturn('ok');
            }
        }
    }

    //获取班级成员信息
    public function ajax_grade_info($gid){
        if(IS_AJAX){
            $grade=M('hw001.school_grade',null)->find($gid);
            $students=M('hw001.stu_grade',null)->where(['gid'=>$gid])->select();
            $this->ajaxReturn([$grade,$students]);
        }
    }

    //获取学员课程信息
    public function get_course($sid,$gid=0){
        if(IS_AJAX){
            $data[0]=M('hw001.student',null)->where(['id'=>$sid])->getField('std_id');//学号
            $w['std_id']=$data[0];
            $w['state']=['in',[C('COURSE_STATES')['CHECK']['id'],C('COURSE_STATES')['NORMAL']['id']]];
            $w['is_del']=0;
            $data[1]=D('CourseView')->where($w)->select();//课程列表
            
              //防止使用一对一的绑定
              foreach ($data[1] as $k=>$v) {
                if(strpos($v['plan_name'],'一对一'))unset($data[1][$k]);
              }

            if($gid)$data[2]=M('hw001.stu_grade',null)->where(['stuid'=>$sid,'gid'=>$gid])->getField('course_id');//获取选中的课程id
            $this->ajaxReturn($data);
        }
    }

    //班级成员的增删改
    public function grade_student_add(){
        //删除
        if(I('get.delt')&&IS_AJAX){
            $id=M('hw001.stu_grade',null)->where(['gid'=>I('get.gid'),'stuid'=>I('get.delt')])->delete();
            $this->reset_grade_class(I('get.gid'));
            if($id)$this->ajaxReturn('ok');
        }
        //修改、新增
        // if(I('get.course_id'))
        if(IS_AJAX){
            $info=M('hw001.student',null)->find(I('get.stuid'));
            $d['stuid']=I('get.stuid');
            $d['gid']=I('get.gid');
            $d['course_id']=I('get.course_id');
            $d['name']=$info['name'];
            $d['school']=$info['school'];
            $id=M('hw001.stu_grade',null)->where(['stuid'=>I('get.stuid'),'gid'=>I('get.gid')])->find();
            if($id){
                $cc=M('hw001.stu_grade',null)->where(['id'=>$id['id']])->save($d);
                $cc2=M('hw001.school_grade',null)->where(['id'=>$id['gid']])->save(['name'=>I('get.name'),'other'=>I('get.other')]);
                $cc=$cc?$cc:$cc2;
            }else{
                $cc=M('hw001.stu_grade',null)->add($d);
            }
            if($cc){
                $this->reset_grade_class(I('get.gid'));
                $this->ajaxReturn('ok');
            }else{
                $this->ajaxReturn('error');
            }
        }
    }

    //当班级成员变更时课表调整重置,当stuid为真，仅删除stuid的排课
    // private function reset_grade_class($gid,$stuid=0){
    private function reset_grade_class($gid){
        $gid=(int)$gid;
        if($gid==0)return;
        $stuids=M('hw001.stu_grade',null)->where(['gid'=>$gid])->select();//获取班级的成员
        foreach ($stuids as $k=>$v) {
            $stuids[$k]['std_id']=M('hw001.student',null)->where(['id'=>$v['stuid']])->getField('std_id');
            unset($stuids[$k]['id'],$stuids[$k]['name'],$stuids[$k]['gid'],$stuids[$k]['school'],$stuids[$k]['timestamp']);
        }
        if(empty($stuids))return;
        $class=M('hw001.class',null)->where([
            'grade'=>$gid,
            'state'=>0,
            'timee'=>['egt',date('Y-m-d')]
            ])->select();
        if(empty($class))return;
        //取出不重复的一组排课记录
        foreach ($class as $k=>&$v) {
            $ids[]=$v['id'];
            unset($v['id'],$v['stuid'],$v['std_id'],$v['course_id'],$v['timestamp']);
            if(in_array($v['timee'].$v['time1'],$cc))unset($class[$k]);
            if($v)$cc[]=$v['timee'].$v['time1'];
        }
        //全体删除一次
        // if($stuid)$w['stuid']=$stuid;
        // M('hw001.class',null)->where(['id'=>['in',$ids]])->where($w)->delete();
        M('hw001.class',null)->where(['id'=>['in',$ids]])->delete();
        // if($stuid)return;
        // 给全体成员重新添加一次
        foreach ($class as $c) {
            foreach ($stuids as $s) {
                if($s['std_id'])
                $data[]=array_merge($c,$s);
            }
        }
        M('hw001.class',null)->addAll($data);
        return true;
    }
}
