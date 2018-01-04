<?php
namespace Home\Controller;

class ApplyCourseController extends HomeController {

	//把校区列表、科目类别输出到前段模版
	public function _initialize(){
        parent::_initialize();
		foreach (C('SCHOOL') as $v) {
			$school[$v['id']]=$v['name'];
		}
		
		$lst = M('user')->join('oa_foo_info on oa_user.school = oa_foo_info.id')->where(array('oa_user.position_id'=>10,'oa_user.is_del'=>0))->order('school')->getField('oa_user.id,oa_user.name,concat_ws("->",oa_foo_info.name,oa_user.name) AS school');
		
		/* foreach ($lst as &$val){
		  $val['name'] = $val['school'] . ' ' .$val['name'];
		} */
		
		$grade_lst = C('SCHOOL_GRADE');
		//array_unshift($grade_lst,array('id'=>'','name'=>"选择课程年级",'remark'=>'','sort'=>'0','pid'=>'16','group'=>'16','ext'=>'','is_del'=>'0')); //增加前端年级选项变动，调动相应的js事件
		$this->assign('gradeList'        , $grade_lst);
		
		$this->assign('rector',$lst);
		
		$this->assign('school',$school);//校区
		
	}
/**
计划申请
*/
    public function index(){
     
    /*  $wx= getWechatObj();
     $wx->sendNewsMsg(
       [$wx->buildNewsItem("您有退费记录待审核,信息是否收到？给张晓明回个QQ信息",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=Return/check3')),'')],
       ['touser'=>['CWdqsy002','WW']],
       C('WECHAT_APP')['TZTX']
       ); */
     
        $this -> display();
    }

/**
审核申请
*/
	public function examine(){
        $this -> display('index');
	}

/**
数据管理
*/
	public function manage(){
        $this -> display('index');
	}
	
	/**
	 * 各个岗位浏览自己审核过的数据
	 */
	public function checked_list(){
		$this -> display('list_checked_info');
	}

/**
####################################增删改查
*/
/*
*申请添加、修改
*/
	public function write(){
		array_empty_delt($_POST);
		$mod=M('applyCourse');
		$mod->create();
		$mod->subject = implode(",",$mod->subject);
		$mod->marketing = implode(",",$mod->marketing);
		$mod->grade = implode(",",$mod->grade);
		//修改
		if(I('post.id')){
		    
			$mod->save();
		
			$this->ajaxReturn('更新成功');
		}

	//	$mod->school=session('school_id');
		$mod->add_user=session('auth_id');
		$mod->add_user_name=session('user_name');
		//新增
		if($mod->add())$this->ajaxReturn('添加成功');

		$this->ajaxReturn('操作失败');
	}
/*
*审核操作
*/
	public function check(){
		if(IS_AJAX&&I('post.data')){
			if(D('ApplyCourse')->check(I('post.type'),I('post.data')['id'],I('post.why')))$this->ajaxReturn('ok');
			$this->ajaxReturn('审核出错');
		}
	}

/*
*页面数据列表
*/
    public function ajax_list(){
          
          
    	if(IS_AJAX){
    		$w=I('get.search');
    		array_empty_delt($w);
    		if($w['date1'])$w['activity_begin']=['between',[$w['date1'].' 00:00:00',$w['date2'].' 00:00:00']];
    		$w['is_del']=0;
    		
    		if($w['course_info']){
    		 $w['course_info'] = array('like','%'. $w['course_info'] . '%');
    		}
    		
    		if(strpos(strstr($_SERVER['HTTP_REFERER'],'&a='),'manage') === FALSE){
    		 
         		 if(get_school_name()!='集团'){
         		   $w['school'] = session('school_id');
         		 }

         		 if(get_school_name()=='集团' && session('auth_id') == '1293'  ){
         		  //姜博文
         		  unset($w['school']);
         		  $w['area'] = array('in',['辽宁']);
         		  $w['state'] = array('in','10,60');
         		 }elseif(get_school_name()=='集团' && session('auth_id') == '673'  ){
         		     //张鹏
         		     unset($w['school']);
         		     $w['area'] = array('in',['辽东']);
         		     $w['state'] = array('in','10,60');
         		 }elseif(get_school_name()=='集团' && session('auth_id') == '1283'  ){
         		     //张玉珠
         		     unset($w['school']);
         		     $w['area'] = array('in',['辽西']);
         		     $w['state'] = array('in','10,60');
         		 }elseif(get_school_name()=='集团' && session('auth_id') == '2100'  ){
         		     //李明帅
         		     unset($w['school']);
         		     $w['area'] = array('in',['多种经营事业部']);
         		     $w['state'] = array('in','10,60');
         		 }else if(session('auth_id') == '651'  ){
         		     //王大鹏
         		     unset($w['school']);
         		     $w['area'] = array('in',['吉林']);
         		     $w['state'] = array('in','10,60');
         		 } else if(session('auth_id') == '439'  ){
         		     //何亮
         		     unset($w['school']);
         		     $w['area'] = array('in',['黑龙江']);
         		     $w['state'] = array('in','10,60');
         		 }elseif(get_school_name()=='集团' && session('auth_id') == '2175'){
         		     unset($w['school']);
         		     $w['area'] = array('in',['多种经营事业部']);
         		     $w['state'] = array('in','20,70');
         		 }elseif(get_school_name()=='集团' && (session('auth_id') == '89')){
         		  //王胜鑫
         		  unset($w['school']);
         		  $w['area'] = array('neq','多种经营事业部');
         		  $w['state'] = array('in','20,70');
         		 }elseif(get_school_name()=='集团' && session('auth_id') == '1091'){
         		     $w['state'] = array('in','30,80');
         		 }elseif(get_school_name()=='集团' && session('auth_id') == '509'){
         		     $w['state'] = array('in','40,90');
         		 }
         		 
         		 /* elseif(get_school_name()=='集团' && session('auth_id') == '1'){
         		 //张晓明
         		 unset($w['school']);
         		 unset($w['area']);
         		 } */
    		 
    		}
    		
    		
    		
    		$data=M('applyCourse')->where($w)->order('state desc,school asc,id desc')->field('id, state, school, area,apply_user, substring_index(apply_user,"#",1) as apply_user2, course_info,grade,grade as graded, class_type, activity_begin, activity_end, subject, charge_descp, marketing, course_point, expect_date, other, why, create_time, update_time, is_del, add_user, add_user_name, back')->limit(I('get.offset'),I('get.count'))->select();
    		
    		foreach ($data as &$obj){
    		    $mo = explode(",",$obj['graded']);
    		    $obj['grade'] = explode(",",$obj['graded']);

    		    foreach($mo as $k=>$v){
    		        switch($v){
    		            case "22":$mo[$k]= "高三";break;
    		            case "21":$mo[$k]= "高二";break;
    		            case "20":$mo[$k]= "高一";break;
    		            case "50":$mo[$k]= "九年级";break;
    		            case "40":$mo[$k]= "八年级";break;
    		            case "39":$mo[$k]= "七年级";break;
    		            case "38":$mo[$k]= "六年级";break;
    		            case "93":$mo[$k]= "五年级";break;
    		            case "100":$mo[$k]= "四年级";break;
    		            case "101":$mo[$k]= "三年级";break;
    		            case "102":$mo[$k]= "二年级";break;
    		            case "103":$mo[$k]= "一年级";break;
    		            case "217":$mo[$k]= "其他";break;
    		        }
    		    }
    		    $obj['graded'] = implode(",",$mo); 
    		    
    		}
    		
    		if(get_school_name()!='集团' && (session('auth_id') != '651') && (session('auth_id') != '439')){
    		  foreach ($data as &$vo){
    		    $vo['subject'] = explode(",",$vo['subject']);
    		    $vo['marketing'] = explode(",",$vo['marketing']);
    		     if($vo['state']>0){
    		      $vo['edit'] = 0;
    		     }else{
    		      $vo['edit'] = 1;
    		     }
    		  }
    		}else{
         	  foreach ($data as &$vo){
         	     $vo['subject'] = explode(",",$vo['subject']);
         	     $vo['marketing'] = explode(",",$vo['marketing']);
         		 $vo['edit'] = 1;
         	   }
    		}
    		
    		$total=M('applyCourse')->where($w)->count();
    		
    		
    		$this->ajaxReturn(['state'=>'ok','data'=>$data,'total'=>$total]);
    	}else{
    		$this->ajaxReturn(['state'=>'error','info'=>'没有查询到数据']);
    	}
    }

    /*
     *各个岗位自己已审核的页面数据列表
     */
    public function ajax_checked_list(){
    	if(IS_AJAX){
    		$w=I('get.search');
    		array_empty_delt($w);
    		if($w['info']) $w['info'] = array('like','%' . $w['info'] . '%');
    		if($w['date1'])$w['create_time']=['between',[$w['date1'].' 00:00:00',$w['date2'].' 00:00:00']];
    		$w['_string'] = "LOCATE('" . $_SESSION['user_name'] . "',record) != 0"; 
    		$data=M('apply')->where($w)->order('state asc,money_time asc,school asc,subject asc,type asc,id desc')->field('record',true)->limit(I('get.offset'),I('get.count'))->select();
    		$total=M('apply')->where($w)->count();
    		$count=$this->get_count($w);
    		
    		$this->ajaxReturn(['state'=>'ok','data'=>$data,'total'=>$total,'count'=>$count]);
    	}else{
    		$this->ajaxReturn(['state'=>'error','info'=>'没有查询到数据']);
    	}
    }

	


	//数据导出功能
	public function export(){

		if(session('school_id')!=0)die;
		$w=I('post.');
		array_empty_delt($w);
		unset($w['stage']);
		$w['is_del']=0;
		$dat=M('applyCourse')->where($w)->field("id, state, school, area,apply_user, substring_index(apply_user,'#',1) as apply_user2, course_info, class_type, activity_begin, activity_end, subject, charge_descp, marketing, course_point, expect_date, other, why, create_time, update_time, is_del, add_user, add_user_name,  CASE back WHEN 1 THEN '退回' else '正常' END as back")->select();

        $output = "<HTML>";
        $output .= "<HEAD>";
        $output .= "<META http-equiv=Content-Type content=\"text/html; charset=utf-8\">";
        $output .= "</HEAD>";
        $output .= "<BODY>";
        $output .= "<TABLE BORDER=1>";
        $output .= "<tr>
        				<td>序号</td>
        				<td>流程状态</td>
        				<td>审核状态</td>
        				<td>申请校区</td>
        				<td>区域</td>
        				<td>申请人</td>
        				<td>新课程名称</td>
        				<td>班型</td>
        				<td>活动开始时间</td>
        				<td>活动结束时间</td>
        				<td>开设科目</td>
        				<td>收费及优惠说明</td>
        				<td>营销手段</td>
        				<td>课程亮点</td>
        				<td>期望审批日期</td>
        				<td>其他说明</td>
                        <td>退回原因</td>
        				<td>最后审核时间</td>
        				<td>数据创建时间</td>
        				<td>创建人</td>
        				</tr>";
        $apply_state=get_config('APPLYCOURSE_STATE');
        
        foreach ($dat as &$vo) {
            
            $vo['state']=$apply_state[$vo['state']];
            $vo['school']=$this->school[$vo['school']];

            $output .= "<tr>";
            $output .= "<td>".$vo['id']."</td>";
            $output .= "<td>".$vo['back']."</td>";
            $output .= "<td>".$vo['state']."</td>";
            $output .= "<td>".$vo['school']."</td>";
            $output .= "<td>".$vo['area']."</td>";
            $output .= "<td>".$vo['apply_user2']."</td>";
            $output .= "<td>".$vo['course_info']."</td>";
            $output .= "<td>".$vo['class_type']."</td>";
            $output .= "<td>".$vo['activity_begin']."</td>";
            $output .= "<td>".$vo['activity_end']."</td>";
            $output .= "<td>".$vo['subject']."</td>";
            $output .= "<td>".$vo['charge_descp']."</td>";
            $output .= "<td>".$vo['marketing']."</td>";
            $output .= "<td>".$vo['course_point']."</td>";
            $output .= "<td>".$vo['expect_date']."</td>";
            $output .= "<td>".$vo['why']."</td>";
            $output .= "<td>".$vo['other']."</td>";
            $output .= "<td>".$vo['update_time']."</td>";
            $output .= "<td>".$vo['create_time']."</td>";
            $output .= "<td>".$vo['add_user_name']."</td>";
            $output .= "</tr>";
        }
        $output .= "</TABLE>";
        $output .= "</BODY>";
        $output .= "</HTML>";
        $filename='新课程申请明细导出表'.date('Y-m-d');
        header("Content-type:application/msexcel");
        header("Content-disposition: attachment; filename=$filename.xls");
        header("Cache-control: private");
        header("Pragma: private");
        print($output);
    }

    //附件上传
    public function add_picture(){
		$upload = new \Think\Upload();// 实例化上传类
		$upload->maxSize   =     10485760 ;// 设置附件上传大小
		$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
		$upload->savePath  =      'Apply/'; // 设置附件上传目录
		// 上传文件
		$info   =   $upload->upload();
		return $info;
    }

}
