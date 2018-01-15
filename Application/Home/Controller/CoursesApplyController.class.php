<?php
namespace Home\Controller;
// 解决有些课程订单需要特殊申请审核的问题。
class CoursesApplyController extends HomeController {
    public function _initialize(){
        parent::_initialize();
        foreach (C('SCHOOL') as $v) {
            $school[$v['id']]=$v['name'];
        }
        $this->school=$school;//校区
        $this->course=M('UnitpriceRole')->where(['school'=>session('school_id'),'is_del'=>0])->getField('id,name');//课程列表
    }

/**
申请
*/
    public function index(){
        $this -> display('Courses/course_apply');
    }

/**
使用优惠
*/
    public function course_use(){
        $this -> display('Courses/course_apply');
    }

/**
退课优惠
*/
    public function course_return(){
        $this -> display('Courses/course_apply');
    }

/**
审核申请
*/
    public function examine(){
        $this -> display('Courses/course_apply');
    }

/**
数据管理
*/
    public function manage(){
        $this -> display('Courses/course_apply');
    }
/**
####################################增删改查
*/
/*
*申请添加、修改
*/
    public function write(){
        array_empty_delt($_POST);
        $mod=M('course');
        $mod->create();
        $plan=M('UnitpriceRole')->find(I('unit_plan'));
        $mod->grade=$plan['grade'];
        $mod->level=$plan['level'];
        $mod->course=$plan['course'];

        //修改
        if(I('post.id')){
            $mod->save();
            $this->save_check([['id'=>I('post.id')]],2);
            $this->ajaxReturn('更新成功');
        }

        //新增
        $mod->school=session('school_id');
        $mod->state=100;
        $mod->type_state=0;
        $mod->type=1;
        $mod->create_time=time();
        if(M('hw001.student',null)->where(['name'=>I('name'),'std_id'=>I('std_id')])->find())
        if($mod->add())$this->ajaxReturn('添加成功');

        $this->ajaxReturn('学员信息不匹配');
    }
/*
*审核操作
*/
    public function check(){
        if(IS_AJAX&&I('post.data')){
            if($this->checked(I('post.type'),I('post.data')['id'],I('post.reason')))$this->ajaxReturn('ok');
            $this->ajaxReturn('账户余额不足，请充值');
        }
    }

/*
*页面数据列表
*/
    public function ajax_list(){
        if(IS_AJAX){
            $w=I('get.search');
            array_empty_delt($w);
            $this->get_condition($w);//附加浏览的查询条件
            $ww=$w;unset($ww['act']);
            $data=D('CourseView')->where($ww)->order('state asc,school asc,id desc')->limit(I('get.offset'),I('get.count'))->select();
            $total=D('CourseView')->where($ww)->count();
            $this->get_edit($data,$w);//设置页面修改权限
            $this->ajaxReturn(['state'=>'ok','data'=>$data,'total'=>$total,'count'=>$count]);
        }else{
            $this->ajaxReturn(['state'=>'error','info'=>'没有查询到数据']);
        }
    }

    //查询条件
    private function get_condition(&$w){
        $w['is_del']=0;
        $w['school']=$w['school']?:session('school_id');
        $w['type']=1;
        $w['state']=100;
        if($w['act']=='index'){
            $w['type_state']=['lt','40'];
        }else if($w['act']=='course_use') {
            unset($w['state']);
            $w['type_state']=['in','25,30'];
        }else if($w['act']=='examine') {
            
            if(session('position_id')==10 && !in_array(session('auth_id'),[2100,673,1283,439,651,2119,2186,2095,2175])){
                $w['type_state']=['in','10,50'];
            }else if(session('auth_id')==2100){//多种经营 李明帅
                $mod = M('foo_info');
                $sch_ids = $mod->where(['region'=>50,'pid'=>15,'is_del' => 0])->getField('id',true);
                $w['school'] = array('in',$sch_ids);
                
                $w['type_state']=['in','10,15,55'];
                $w['_string'] = " (operate_area is null or operate_area = '') "; 
                
            }else if(session('auth_id')==673){//辽东 张鹏
                $mod = M('foo_info');
                $sch_ids = $mod->where(['region'=>10,'pid'=>15,'is_del' => 0])->getField('id',true);
                $w['school'] = array('in',$sch_ids);
                
                $w['type_state']=['in','10,15,55'];
                $w['_string'] = " (operate_area is null or operate_area = '') "; 
            }else if(session('auth_id')==1283){//辽西 张玉珠
                $mod = M('foo_info');
                $sch_ids = $mod->where(['region'=>20,'pid'=>15,'is_del' => 0])->getField('id',true);
                $w['school'] = array('in',$sch_ids);
                
                $w['type_state']=['in','15,55'];
                $w['_string'] = " (operate_area is null or operate_area = '') "; 
            }else if(session('auth_id')==439){//黑龙江 何亮
                $mod = M('foo_info');
                $sch_ids = $mod->where(['region'=>40,'pid'=>15,'is_del' => 0])->getField('id',true);
                $w['school'] = array('in',$sch_ids);
                
                $w['type_state']=['in','10,15,55'];
                $w['_string'] = " (operate_area is null or operate_area = '') "; 
            }else if(session('auth_id')==651){//吉林 王大鹏
                $mod = M('foo_info');
                $sch_ids = $mod->where(['region'=>30,'pid'=>15,'is_del' => 0])->getField('id',true);
                $w['school'] = array('in',$sch_ids);
                
                $w['type_state']=['in','10,15,55'];
                
            }else if(session('auth_id')==2119){//运营1,4区是杜喃喃
                unset($w['school']);
                
                $w['type_state']=['in','15,55'];
                $w['area'] = 60;
                $w['operate_area'] = array('in','1,4');
                
            }else if(session('auth_id')==2186){//运营2,3区是孟轩
                unset($w['school']);
                
                $w['type_state']=['in','15,55'];
                $w['area'] = 60;
                $w['operate_area'] = array('in','2,3');
                
            }else if(session('auth_id')==2095){//优途 于忠盛 总裁审核
                unset($w['school']);
                
                $w['type_state']=['in','20,60'];
                $w['area'] = 60;
                unset($w['operate_area']);
                
            }else if(session('auth_id')==89){
                $mod = M('foo_info');
                $sch_ids = $mod->where(['region'=>array('not in', '50,60'),'pid'=>15,'is_del' => 0])->getField('id',true);
                $w['school'] = array('in',$sch_ids);
                
                $w['type_state']=['in','20,60'];
                $w['_string'] = " (operate_area is null or operate_area = '') "; 
            }else if(session('auth_id')==2175){ //吕雪茹
                $mod = M('foo_info');
                $sch_ids = $mod->where(['region'=>50,'pid'=>15,'is_del' => 0])->getField('id',true);
                $w['school'] = array('in',$sch_ids);
                $w['type_state']=['in','20,60'];
                $w['_string'] = " (operate_area is null or operate_area = '') "; 
            }else if(session('auth_id')==509){//优途 于忠盛 总裁审核
                $w['type_state']=['in','25,65'];
                unset($w['school']);
                unset($w['area']);
                unset($w['operate_area']);
                
            }else{
                $w['type_state']=888888;
            }
        }else if($w['act']=='course_return') {
            unset($w['state']);
            $w['type_state']=['BETWEEN','40,70'];
        }else if($w['act']=='manage') {
            unset($w['state']);
            // if(get_school_name()=='集团')unset($w['school']);
        }else{
                $w['type_state']=888888;
        }
    }

    //修改权限，$v['edit']，1允许，0不允许
    private function get_edit(&$data,$w){
        foreach ($data as &$v) {
            $v['edit']=0;
            
            if($v['area']){
                $v['area_name'] = get_config('SCHOOL_REGION')[$v['area']];
            }else{
                $v['area_name'] = '';
            }
            
            if(!$v['operate_area']){
                $v['operate_area_name'] = '';
            }else{
                $v['operate_area_name'] = '运营' . $v['operate_area'] . '区' ;
            }
            
            if($v['state']==100){
                if($w['act']=='index' && in_array($v['type_state'],[0,5]))$v['edit']=1;
                if($w['act']=='examine' && in_array($v['type_state'],[10,15,20,25,50,55,60,65]))$v['edit']=1;
                if($w['act']=='course_use' && $v['type_state']==30)$v['edit']=1;
            }elseif ($v['state']==200) {
                if($w['act']=='course_return' && $v['type_state']==40)$v['edit']=1;
            }

        }
    }
/**
////////////////////////////////////////////////////////////////////////////////////////////////
*/
    private function checked($type,$ids,$reason=''){

        $list=D('CourseView')->where(['id'=>['in',$ids]])->field('id,name,std_id,school,type_state,price,return_price')->select();
        /*if($type==-1){//删除数据
            $this->check_del($list);
            if($this->save_check($list,4))return TRUE;
        }elseif ($type==0){//退回数据
            $this->check_back($list,$reason); RETURN TRUE;
//          if($this->save_check($list,3))return TRUE;  //无法真正退回订单到学习管理师手上；edit by zhangxm at 2016-03-31 09:56
        }elseif ($type==1) {//审核数据
            $this->check_access($list);
            if($this->save_check($list,1,$reason))return TRUE;
        }*/
        
        if($type==-1){//删除数据
            $this->check_del($list);
            $this->save_check($list,4);
        }elseif ($type==0){//退回数据
            $this->check_back($list,$reason); 
//          if($this->save_check($list,3))return TRUE;  //无法真正退回订单到学习管理师手上；edit by zhangxm at 2016-03-31 09:56
        }elseif ($type==1) {//审核数据
            $this->check_access($list);
            if(!$this->save_check($list,1,$reason)){
                return false;
            }
        }
		
		$this->check_notice($list);//微信通知，edit by zhangxm at 2016-08-23 
		
        return TRUE;

    }

    /**
    *执行审核数据
    */
    private function check_access(&$list){
        foreach ($list as &$v) {
            if($v['type_state']==70)continue;
            
            if($v['type_state']=== '0' || $v['type_state']==30 || $v['type_state']==40){
                $v['type_state']+=10;
            }else{
                $v['type_state']+=5;
            }
            
        }
    }

    /**
    *执行退回数据
    */
    private function check_back(&$list,$reason){
        /*foreach ($list as &$v) {
            $v['type_state']=($v['type_state']>40)?45:5;//退回修改,//无法真正退回订单到学习管理师手上；edit by zhangxm at 2016-03-31 09:56
            $v['reason']=$reason;
        }*/
         $mod=M('course');
		 
		 $info=session('user_name'). ',退回,' . date('Y-m-d H:i:s');
		 
        foreach ($list as &$v) {
            if($v['type_state']>40){
                $v['type_state']=40;//申请退回修改
                $v['state'] = 200;
            }else{
                $v['type_state']=5;//退回修改
                $v['state'] = 100;
            }
//             $v['type_state']=5;//退回修改
            $v['reason']=$reason;
// 			$v['state'] = 100;
			$v['return_price'] = 0;
			
			$inf=$info.','.$v['type_state'];
            $v['record']=['exp', "CONCAT_WS('|', `record`, '{$inf}')"];
            if($v['reason'])$v['reason']=get_user_name().'：'.$v['reason'];
            $mod->where(['id'=>$v['id']])->save($v);
        }
		
    }

    /**
    *执行删除数据
    */
    private function check_del(&$list){
        foreach ($list as &$v) {
            $v['is_del']=1;//删除数据
        }
    }

    /**
    *记录审核过程,1通过，2修改,3退回,4删除
    */
    private function save_check($data,$type=1,$reason=''){
        $mod=M('course');
        $info=session('user_name').($type==1?',通过,':($type==2?',修改,':($type==3?',退回,':',删除,'))).date('Y-m-d H:i:s');
        foreach ($data as $v) {

            //转存消费记录并将订单状态变为正常
            if($v['type_state']==40){
                if(round(D('Consumption')->getBalance($v['std_id']), 2) >= $v['price']){
                    $dat=[
                        'state'=>C('CONSUME_STATES')['CHECK1']['id'],
                        'type'=>C('CONSUME_TYPE')['BOOK']['id'],
                        'std_id'=>$v['std_id'],
                        'student'=>$v['name'],
                        'emp'=>session('user_name'),
                        'emp_no'=>session('auth_id'),
                        'emp_school'=>session('school_id'),
                        'value'=>0-$v['price'],
                        'month'=>date('Y-m'),
                        'create_time'=>time()
                        ];
                    $order_id=M('Consumption')->add($dat);
                    if($order_id){
                        $v['order_id']=$order_id;
                        $v['state']=200;
                    }else{
                        continue;
                    }
                }else{
                    return false;
                }
            }elseif ($v['type_state']==50) {
                $v['state']=100;
//              $v['return_price']=$v['reason'];//该位置导致每次特殊优惠退费的时候，退费金额不对； edit by zhangxm at 2016-03-23 16:26
				$v['return_price']=$reason;
            }elseif ($v['type_state']==70) {
                /*$dat=[
                    'state'=>C('CONSUME_STATES')['CHECK1']['id'],
                    'type'=>C('CONSUME_TYPE')['DROP']['id'],
                    'std_id'=>$v['std_id'],
                    'student'=>$v['name'],
                    'emp'=>session('user_name'),
                    'emp_no'=>session('auth_id'),
                    'emp_school'=>session('school_id'),
                    'value'=>$v['price']-$v['return_price'], //该位置导致每次特殊优惠退费的时候，退费金额不对； edit by zhangxm at 2016-03-23 16:26
                    'month'=>date('Y-m'),
                    'create_time'=>time()
                    ];*/
                    
                    $dat=[
                    'state'=>C('CONSUME_STATES')['CHECK1']['id'],
                    'type'=>C('CONSUME_TYPE')['DROP']['id'],
                    'std_id'=>$v['std_id'],
                    'student'=>$v['name'],
                    'emp'=>session('user_name'),
                    'emp_no'=>session('auth_id'),
                    'emp_school'=>session('school_id'),
                    'value'=>$v['return_price'],
                    'month'=>date('Y-m'),
                    'create_time'=>time()
                    ];
                $order_id=M('Consumption')->add($dat);
                if($order_id){
                    $v['order_id']=$order_id;
                    $v['state']=500;
                }else{
                    continue;
                }
            }

            $inf=$info.','.$v['type_state'];
            $v['record']=['exp', "CONCAT_WS('|', `record`, '{$inf}')"];
            if($v['reason'])$v['reason']=get_user_name().'：'.$v['reason'];
            $mod->where(['id'=>$v['id']])->save($v);
        }

        return true;
    }

	/**
    *根据状态确定微信通知
    *@param array  数据
    *@return bool  状态
    *    'POSITION_ID' => [ // 用到的职位ID
    *    'SCHOOL_DIRECTOR' => 19, // 教学主任
    *    'SCHOOL_DIRECTOR_XZ' => 13, // 业务副校长
    *    'SCHOOL_MANAGER'  => 18, // 学管师
    *    'SCHOOL_MASTER'   => 10, // 校长
    *    'CONTROLLER'      => 8,  // 二级主管
    *    'PRESIDENT'       => 7,  // 一级总裁
    *]
	*0=>待提交,5=>退回修改,10=>校区审核,20=>集团审核,30=>审核通过,40=>订单使用中,45=>申请退回,50=>校区审核,60=>集团审核,70=>退课完成 
    */
    public function check_notice($list){
        foreach ($list as $v) {
            //审核过程通知相应人员
                //通知校长
                if($v['type_state']==10||$v['type_state']==50){
                    $w['position_id']=C('POSITION_ID')['SCHOOL_MASTER'];
                    $w['school']=session('school_id');
                }
                if($v['type_state']==15||$v['type_state']==55){
                    if($v['area'] == 50 ||  get_school_region($v['school']) == 50){
                        $w['name']='李明帅';
                    }else if($v['area'] == 40 || get_school_region($v['school']) == 40){
                        $w['name']='何亮';
                    }else if($v['area'] == 30 ||  get_school_region($v['school']) == 30){
                        $w['name']='王大鹏';
                    }else if($v['area'] == 20 ||  get_school_region($v['school']) == 20){
                        $w['name']='张玉珠';
                    }else if($v['area'] == 10 ||  get_school_region($v['school']) == 10){
                        $w['name']='张鹏';
                    }else if($v['area'] == 60){
                        if(in_array($v['operate_area'],[1,4])){
                            $w['name']='杜喃喃';
                        }else if(in_array($v['operate_area'],[2,3])){
                            $w['name']='孟轩';
                        }
                    }
                }
                
                if($v['type_state']==20||$v['type_state']==60){
                    //通知集团运营总裁
                    if((get_school_region($v['school']) != 50 && get_school_region($v['school']) != 60) && ($v['area'] != 50 && $v['area'] != 60 )){
                        $w['name']='王胜鑫';//'张晓明';
                    }else if(get_school_region($v['school']) == 50 || $v['area'] == 50){
                        $w['name']='吕雪茹';
                    }else if(get_school_region($v['school']) == 60 || $v['area'] == 60){
                        $w['name']='于忠盛';
                    }
                }
                
                
                //通知学习管理师
                if(in_array($v['type_state'],[30,40,5,45,70])){
                    $w['position_id']=C('POSITION_ID')['SCHOOL_MANAGER'];
                    $w['school']=session('school_id');
                    $xueguan = M('hw001.student',NULL)->where(['std_id'=>$v['std_id'],'is_del'=>0])->getField('xueguan');
					if($xueguan){
						$w['name'] = $xueguan;	
					}else{
						unset($xueguan);
						continue;
					}
					unset($xueguan);
                }
				
            if($w){
            	$user[]=M('user')->where(['is_del'=>0])->where($w)->getField('wechat_userid');//wechat_userid	
            }
            
            unset($w);
        }
        $user=array_unique($user);

//      $info='点击可直接进入审核……';

        //微信通知
        if(empty($user)){
        	return;
        }
        	
        //存储一下被通知过的人,方便后期查看
        $ff=(array)F('weixin_tsyh');
        $f2=array_merge($ff,$user);
        F('weixin_tsyh',$f2);

        $wx= getWechatObj();
       /* $wx->sendNewsMsg(
            [$wx->buildNewsItem("有特殊优惠待审核",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=CoursesApply/examine')),'')],
            ['touser'=>$user],
            C('WECHAT_APP')['XZMS']
        );*/
        $wx->sendTextMsg("特殊优惠有新的动态，请查看",
            ['touser'=>$user],
            C('WECHAT_APP')['TZTX']);
			
         /*$wx->sendNewsMsg(
            [$wx->buildNewsItem("有特殊优惠待审核",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=CoursesApply/examine')),'')],
            ['touser'=>$user],
            C('WECHAT_APP')['XZMS']
        );*/
    }

}
