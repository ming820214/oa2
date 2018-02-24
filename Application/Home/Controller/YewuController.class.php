<?php
namespace Home\Controller;

class YewuController extends HomeController {
	
/**
信息录入部分
*/
    public function index($condition=null){
        $condition['track_user']=session('auth_id');
        $condition['school']=session('school_id');
        $condition['state']=['neq',30];

		$temp_arr_lst = D('Yewu')->get_list($condition,10);//信息数据
		$temp_stuid_lst = array_column($temp_arr_lst,'id');
		$w['stuid'] = array('in',$temp_stuid_lst);
		if(!empty($temp_stuid_lst)){
			$track_tme_lst = M('yewu_track')->where($w)->order('track_time desc')->select();	
		}else{
			$track_tme_lst = NULL;
		}
		
		
		if(!empty($track_tme_lst)){
			foreach($temp_arr_lst as &$item){
				foreach($track_tme_lst as $key=>$value){
					if($value['stuid'] == $item['id']){
						$item['track_time'] = date('Y-m-d',strtotime($value['track_time']));
						break;
					}
				}
			}
			unset($item);
		}
		
		
		/*在列表页面添加最后跟进时间显示字段
		 * $this->list=D('Yewu')->get_list($condition,10);//信息数据
		 * */		
		$this->list= $temp_arr_lst;
        $this->user='';
        $this->maxCount=D('Yewu')->listCount($condition);
		
		$area = M('area_school')->where("level = 1 and status = 1")->select();
		$school = M('area_school')->where("level = 2 and status = 1")->select();
		$this->assign("area",$area);
		$this->assign("school",$school);
		
		$this->assign('gradeList', C('SCHOOL_GRADE'));
		$grade_lst = C('SCHOOL_GRADE');
		$gradelst = array_column($grade_lst,'name','id');
		$this->gradelst = $gradelst;
        $this->display('index');
    }

	public function delRecord($id=0){
		
		$mod=M('yewu_students');	
		if($id){
			//物理删除记录
			if($mod->delete($id)){
				// 发送给页面的数据
				$this->ajaxReturn([
					'state'=>'ok'
					]);		
			}
		}
		
	}
    
    /*
     * 客服人员页面初始化
     */
    public function service($condition=null){
//         $condition['_string']="region is not null && region != ''";
        $condition['source'] = array('eq',2);
        $condition['addx']=session('auth_id');
        
        //$temp_arr_lst = D('Yewu')->get_list($condition,10);//信息数据
        $temp_arr_lst = M('Yewu_students')->where($condition)->field('id, school, name, parents, parent_type, sex, wl, get_way, tel1, tel2, grade, schoolx, address, other, yixiang, yixiang_qiang, addx, track_user, update_time, create_time, save_time, is_del, state, city_id, class_old, assign_time, type_gj, region, source, addx_name')->order('id desc')->limit(10)->select();
        $temp_stuid_lst = array_column($temp_arr_lst,'id');
        $w['stuid'] = array('in',$temp_stuid_lst);
        if(!empty($temp_stuid_lst)){
            $track_tme_lst = M('yewu_track')->where($w)->order('track_time desc')->select();
        }else{
            $track_tme_lst = NULL;
        }
        
        
        if(!empty($track_tme_lst)){
            foreach($temp_arr_lst as &$item){
                foreach($track_tme_lst as $key=>$value){
                    if($value['stuid'] == $item['id']){
                        $item['track_time'] = date('Y-m-d',strtotime($value['track_time']));
                        break;
                    }
                }
            }
            unset($item);
        }
        
        
        /*在列表页面添加最后跟进时间显示字段
         * $this->list=D('Yewu')->get_list($condition,10);//信息数据
         * */
        $this->list= $temp_arr_lst;
        $this->user='';
        $this->maxCount=D('Yewu')->listCount($condition);
        
        $area = M('area_school')->where("level = 1 and status = 1")->select();
        $school = M('area_school')->where("level = 2 and status = 1")->select();
        $this->assign("area",$area);
        $this->assign("school",$school);
        
        $ws['pid'] = 15;
        $ws['is_del'] = 0;
        
        $sch_lst = M('foo_info')->where($ws)->getField('id,name,region');
        $this->sch_lst = $sch_lst;
        
        $this->assign('gradeList', C('SCHOOL_GRADE'));
        $grade_lst = C('SCHOOL_GRADE');
        $gradelst = array_column($grade_lst,'name','id');
        $this->gradelst = $gradelst;
        $this->display('service');
    }
    
    
    //增加修改
    public function add_service_students($type=null){
        $mod=M('yewu_students');
        $mod->create();
        if(I('post.id')){
            if($mod->save())$type?:$this->ajaxReturn('ok');
        }else{
            
            $mod->addx=session('auth_id');
            $mod->addx_name=session('user_name');
            if($mod->region == '60'){
                //针对鸿文优途的客户资源直接指定到具体校区与具体人员，这里把鸿文优途直接作为校区使用
                $mod->track_user=2186; //指定跟踪人为孟轩
                $mod->school=354; //指定校区为鸿文优途
            }else{
                $mod->school=88888;
                $mod->track_user=session('auth_id');
            }
            
            $mod->state=0;
            $area = $mod->region;
            $condition['tel1|tel2']=['in',I('post.tel2')?[I('post.tel1'),I('post.tel2')]:[I('post.tel1')]];
            $repeat=$mod->where($condition)->find();
            if($repeat)$this->ajaxReturn('录入有重复……'.$repeat['name'].','.get_school_name($repeat['school']));
            if($mod->add()){
                //$user[] = [];
                if($area == '10'){
                    //张鹏
                    $user[] = 'XZsmqh29';
                }elseif($area == '20'){
                    //张玉珠
                    $user[] = 'XZdl01';
                    $user[] = "WW";
                }elseif($area == '30'){
                    //王大鹏
                    $user[] = 'XZfx01';
                }elseif($area == '40'){
                    //何亮
                    $user[] = 'XZsy01';
                }elseif($area == '50'){
                    //李明帅
                    $user[] = 'JZsyjn03';
                }elseif($area == '60'){
                    //孟轩 鸿文优途
                    $user[] = 'mengxuan';
                }
                
                $info='点击可直接进入分配……';
                
                //微信通知
                if(count($user)>0){
                    $wx= getWechatObj();
                    $wx->sendNewsMsg(
                        [$wx->buildNewsItem("有新的市场资源分配到你这里！",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=Yewu/service_set')),'')],
                        ['touser'=>$user],
                        C('WECHAT_APP')['XZMS']
                        );
                }
                
                $type?:$this->ajaxReturn('ok');
            }
        }
    }
	
    //获取单条或多条信息
    public function pager($id=0){
            $mod=M('yewu_students');
        if($id){
            $data[0]=$mod->find($id);//获取到数据
            $count=1;
        }
    
        if(IS_AJAX && I('get.pageCount')){
            $page=I('get.pageNumber');//请求第几条开始
            $page_count=I('get.pageCount');//一页多少条记录

            $condition=I('post.');//获取json查询条件转换成php数组
            $condition['school']=$condition['school']?:session('school_id');
            if($condition['keyword'])$condition['name|tel1|tel2'] = array('like', "%" . $condition['keyword'] . "%");
			
			if($condition['schoolx']){
				$condition['schoolx'] = array('like','%'.$condition['schoolx'].'%');
			}else{
				$condition['schoolx'] = '';
			}
			
			if($condition['class_old']){
				$condition['class_old'] = array('like','%'.$condition['class_old'].'%');
			}
			
			if((I('get.act')=='index') || (I('get.act')=='huifang') ){
				$condition['track_user']=session('auth_id');	
			}
			
//           $condition['state']=((I('get.act')=='set')?['in','0,10,20']:['in','10,20']); //不知道为什么添加这条语句调整，由于这条语句调整导致无法进行状态筛选，从而屏蔽这条语句
 
			if($condition['tel1|tel2']){
				$condition['tel1|tel2'] =  array('like', "%" . $condition['tel1|tel2'] . "%");	
			} 
            
			if($condition['city_id'] === '0'){
				$condition['city_id'] = '';
			}
			
			if($condition['school'] === '0'){
				$condition['school'] = '';
			}
			
			if($condition['time1'] && $condition['time2']){
			  $condition['create_time'] = array('between',array($condition['time1'] . " 00:00:00",$condition['time2'] . " 23:59:59"));
			  unset($condition['time1']);
			  unset($condition['time2']);
			}elseif($condition['time1']){
			  $condition['create_time'] = array('EGT',$condition['time1'] . " 00:00:00");
			  unset($condition['time1']);
			}elseif($condition['time2']){
			  $condition['create_time'] = array('ELT',$condition['time2'] . " 23:59:59");
			  unset($condition['time2']);
			}
			
            array_empty_delt($condition);
    		$count=$mod->where($condition)->count();//满足条件的记录总数
    		$data=$mod->where($condition)->limit($page,$page_count)->order('id desc')->select();//获取到数据
        }

        foreach ($data as &$v) {//跟踪人
            $v['track_user']=M('user')->where(['id'=>$v['track_user']])->getField('name');
            $record=M('YewuTrack')->where(['stuid'=>$v['id']])->order('track_next desc,timestamp desc')->find();
            if($record){
                $v['track_time']=substr($record['track_time'],0,10);
                $v['track_next']=substr($record['track_next'],0,10);
            }
        }

		// 发送给页面的数据
		$this->ajaxReturn([

			'state'=>'ok',//查询结果
			'maxCount'=>$count,//查询到数据库有多少条满足条件记录
			'data'=>$data

		  ]);
    
    }
    
    
    //获取单条或多条信息
    public function pagerService($id=0){
        $mod=M('yewu_students');
        if($id){
            $data[0]=$mod->find($id);//获取到数据
            $count=1;
        }
        
        if(IS_AJAX && I('get.pageCount')){
            $page=I('get.pageNumber');//请求第几条开始
            $page_count=I('get.pageCount');//一页多少条记录
            
            $condition=I('post.');//获取json查询条件转换成php数组
            //$condition['school']=$condition['school']?:session('school_id');
            if($condition['keyword'])$condition['name|tel1|tel2'] = array('like', "%" . $condition['keyword'] . "%");
            
            if($condition['schoolx']){
                $condition['schoolx'] = array('like','%'.$condition['schoolx'].'%');
            }else{
                $condition['schoolx'] = '';
            }
            
            if($condition['class_old']){
                $condition['class_old'] = array('like','%'.$condition['class_old'].'%');
            }
            
            //           $condition['state']=((I('get.act')=='set')?['in','0,10,20']:['in','10,20']); //不知道为什么添加这条语句调整，由于这条语句调整导致无法进行状态筛选，从而屏蔽这条语句
            
            if($condition['tel1|tel2']){
                $condition['tel1|tel2'] =  array('like', "%" . $condition['tel1|tel2'] . "%");
            }
            
            if($condition['city_id'] === '0'){
                $condition['city_id'] = '';
            }
            
            if($condition['school'] === '0'){
                $condition['school'] = '';
            }
            
            if($condition['time1'] && $condition['time2']){
                $condition['create_time'] = array('between',array($condition['time1'] . " 00:00:00",$condition['time2'] . " 23:59:59"));
                unset($condition['time1']);
                unset($condition['time2']);
            }elseif($condition['time1']){
                $condition['create_time'] = array('EGT',$condition['time1'] . " 00:00:00");
                unset($condition['time1']);
            }elseif($condition['time2']){
                $condition['create_time'] = array('ELT',$condition['time2'] . " 23:59:59");
                unset($condition['time2']);
            }
            
            /* if(session('user_name') == '张鹏'){
                //张鹏
                $condition['region']=10;//'辽东';
            }elseif(session('user_name') == '张玉珠'){
                //张玉珠
                $condition['region']=20;//'辽西';
            }elseif(session('user_name') == '王大鹏'){
                //王大鹏
                $condition['region']=30;//'吉林';
            }elseif(session('user_name') == '何亮'){
                //何亮
                $condition['region']=40;//'黑龙江';
            }elseif(session('user_name') == '李明帅'){
                //李明帅
                $condition['region']=50;//'黑龙江';
            }else */
            if(session('user_name') == '孟轩'){
                //孟轩
                $condition['region']=60;//'鸿文优途';
            }else{
                $condition['addx']=session('auth_id');
            }
            
            $condition['source'] = 2;
            
            array_empty_delt($condition);
            $count=$mod->where($condition)->count();//满足条件的记录总数
            $data=$mod->where($condition)->limit($page,$page_count)->order('assign_time ASC,school desc')->select();//获取到数据
        }
        
        foreach ($data as &$v) {//跟踪人
            $v['track_user']=M('user')->where(['id'=>$v['track_user']])->getField('name');
            $record=M('YewuTrack')->where(['stuid'=>$v['id']])->order('track_next desc,timestamp desc')->find();
            
            $v['schooln'] = M('foo_info')->where(['id'=>$v['school']])->getField('name');
            if($record){
                $v['track_time']=substr($record['track_time'],0,10);
                $v['track_next']=substr($record['track_next'],0,10);
            }
        }
        
        // 发送给页面的数据
        $this->ajaxReturn([
            
            'state'=>'ok',//查询结果
            'maxCount'=>$count,//查询到数据库有多少条满足条件记录
            'data'=>$data
            
        ]);
        
    }

    //增加修改
    public function add_students($type=null){
	    	$mod=M('yewu_students');
            $mod->create();
            if(I('post.id')){
                if($mod->save())$type?:$this->ajaxReturn('ok');
            }else{
                $mod->school=session('school_id');
                $mod->addx=session('auth_id');
                $mod->addx_name=session('user_name');
    	    	$mod->track_user=session('auth_id');
                $mod->state=10;
                $area = $mod->region;
                $condition['tel1|tel2']=['in',I('post.tel2')?[I('post.tel1'),I('post.tel2')]:[I('post.tel1')]];
                $repeat=$mod->where($condition)->find();
                if($repeat)$this->ajaxReturn('录入有重复……'.$repeat['name'].','.get_school_name($repeat['school']));
    	    	if($mod->add()){
    	    	    $type?:$this->ajaxReturn('ok');
    	    	}
            }
    }

    //指派任务添加
    public function save_track(){
        if(I('post.user') && I('post.stuid')){
            $w['id']=['in',I('post.stuid')];
			$currentTime = date('Y-m-d H:i:s');
            if(M('yewu_students')->where($w)->setField(['track_user'=>I('post.user'),'state'=>10,'assign_time'=>$currentTime]))
                $this->ajaxReturn('ok');
        }
    }
    
    //区域指派市场资源到校区校长
    public function assign_school(){
        if(I('post.user') && I('post.stuid') && I('post.schoolId')){
            $w['id']=['in',I('post.stuid')];
            $currentTime = date('Y-m-d H:i:s');
            if(M('yewu_students')->where($w)->setField(['track_user'=>I('post.user'),'state'=>10,'school'=>I('post.schoolId'),'assign_time'=>$currentTime])){
                $info='点击可直接进入分配……';
                $user[] = M('user')->where(['id'=>I('post.user')])->getField('wechat_userid');
                
                //微信通知
                if(count($user)>0){
                    $wx= getWechatObj();
                    $wx->sendNewsMsg(
                        [$wx->buildNewsItem("有新的市场资源分配到你这里！",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=Yewu/set')),'')],
                        ['touser'=>$user],
                        C('WECHAT_APP')['XZMS']
                        );
                }
                $this->ajaxReturn('ok');
            }
                
        }
    }

/**
回访跟进部分
*/

	public function huifang(){
        if(I('post.edit'))$cc=$this->add_students(1);
		$condition['school']=session('school_id');
        $condition['state']=['in','10,20'];//跟进的状态学员
        $condition['track_user']=session('auth_id');
        $condition['yixiang_qiang']=['lt',40];

        $list=D('Yewu')->need_track($condition);
        foreach ($list as &$v) {//跟踪人
            $v['track_user']=M('user')->where(['id'=>$v['track_user']])->getField('name');
        }
        $this->list=$list;
        $this->maxCount=1;//显示全部数据
        
        $area = M('area_school')->where("level = 1 and status = 1")->select();
		$school = M('area_school')->where("level = 2 and status = 1")->select();
		$this->assign("area",$area);
		$this->assign("school",$school);
		
		$this->assign('gradeList', C('SCHOOL_GRADE'));
		$grade_lst = C('SCHOOL_GRADE');
		$gradelst = array_column($grade_lst,'name','id');
		$this->gradelst = $gradelst;
		
        $this->display('index');
	}

    public function huifang_history($stuid,$date1,$date2){
    	
        if(IS_AJAX){
        	
            $data=M('yewuTrack')->where([
                'stuid'=>$stuid,
                'track_time'=>['BETWEEN',[$date1,$date2]]
                ])->order('track_time desc')->select();
            $this->ajaxReturn($data);
        }
    }

    public function huifang_add(){
        if(IS_AJAX){
            $mod=M('yewuTrack');
            $mod->create();
            $mod->track_user=session('auth_id');
            //如果需要根据意向强度来设置跟进周期,由于页面已经设置了默认时间可以先不考虑了
            // $yx=M('yewu_students')->where(['id'=>I('post.stuid')])->getField('yixiang_qiang');
            // $mod->flag=1;
            // if($yx<40)$mod->track_next=date('Y-m-d',time()+7*24*360*$yx);

            if($mod->add())$this->ajaxReturn('ok');
        }
    }

    //学员转正操作
    public function jiaojie_zz($id){
        if(IS_AJAX && $id){
            $stuid=D('Yewu')->save_to($id);
            if($stuid)$this->ajaxReturn($stuid);
        }
    }

    //学员交接的相关信息
    public function jiaojie_add($id){
        //获取学员学号
        $std_id=M('hw001.student',null)->where(['oa_stuid'=>$id])->getField('std_id');
        if($std_id){
            //设置信息为已交接
            M('yewu_students')->where(['id'=>$id])->setField('state',30);
            $mod=M('hw001.student_info',null);
            $mod->create();
            $mod->std_id=$std_id;
            if($mod->add())$this->ajaxReturn('ok');
        }
    }

/**
信息分配
*/

    public function set(){
        $condition['school']=session('school_id');
        $condition['state']=['neq',30];
        $this->list=D('Yewu')->get_list($condition,10);//信息数据
        $maxcount = M('yewu_students')->where($condition)->count();
        $condition['position_id']=['in',[19,13,11]];//教学主任，业务副校长，咨询副校长
        $condition['is_del']=0;
		unset($condition['state']);
        $this->user=M('user')->where($condition)->field('id,name')->select();//任务指派人员列表
        $this->maxCount=$maxcount;
		
		$area = M('area_school')->where("level = 1 and status = 1")->select();
		$school = M('area_school')->where("level = 2 and status = 1")->select();
		$this->assign("area",$area);
		$this->assign("school",$school);
		
		$this->assign('gradeList', C('SCHOOL_GRADE'));
		$grade_lst = C('SCHOOL_GRADE');
		$gradelst = array_column($grade_lst,'name','id');
		$this->gradelst = $gradelst;
        $this->display('index');
    }
    
    public function service_set(){
        
        /* if(session('user_name') == '张鹏'){
            //张鹏
            $condition['region']=10;//'辽东';
        }elseif(session('user_name') == '张玉珠'){
            //张玉珠
            $condition['region']=20;//'辽西';
        }elseif(session('user_name') == '王大鹏'){
            //王大鹏
            $condition['region']=30;//'吉林';
        }elseif(session('user_name') == '何亮'){
            //何亮
            $condition['region']=40;//'黑龙江';
        }elseif(session('user_name') == '李明帅'){
            //李明帅
            $condition['region']=50;//'黑龙江';
        }else */
        if(session('user_name') == '孟轩' || session('user_name') == '张晓明'){
            //孟轩
            $condition['region']=60;//'鸿文优途';
        }elseif(session('user_name') == '李冰' || session('user_name') == '赵金玲'){
            $condition['addx']=session('auth_id');
        }else{
            $this->error('您没有该权限，请联系系统管理员');
        }
        
        $condition['source']=2;
        $this->list=D('Yewu')->get_list($condition,10);//信息数据
        $maxcount = M('yewu_students')->where($condition)->count();
        
        
        //$ws['region'] = $condition['region'];
        $ws['pid'] = 15;
        $ws['is_del'] = 0;
        
        $sch_lst = M('foo_info')->where($ws)->getField('id,name,region');
        
        $sch_id = array_keys($sch_lst);
        
        $this->sch_lst = $sch_lst;
        $w['position_id']=10;//校长
        $w['is_del']=0;
        if($sch_id){
            $w['school']= array('in',$sch_id);
        }
        
        $this->user=M('user')->where($w)->field('id,name,school')->select();//任务指派人员列表
        $this->maxCount=$maxcount;
        
        $area = M('area_school')->where("level = 1 and status = 1")->select();
        $school = M('area_school')->where("level = 2 and status = 1")->select();
        $this->assign("area",$area);
        $this->assign("school",$school);
        
        $this->assign('gradeList', C('SCHOOL_GRADE'));
        $grade_lst = C('SCHOOL_GRADE');
        $gradelst = array_column($grade_lst,'name','id');
        $this->gradelst = $gradelst;
        $this->display('service');
    }
    
    public function recycleCustomers(){
      
      $trackUserId = I('post.recycle_worker');
      
      $w['school'] = session('school_id');
      $w['track_user'] = $trackUserId;
      $w['state'] = array('neq',30);
      
      $data['track_user'] = null;
      
      if(IS_AJAX && $trackUserId){
         $mod = M('YewuStudents');
         $mod->where($w)->save($data);
         $this->ajaxReturn(['stated'=>'success']);
      }else{
        $this->ajaxReturn(['stated'=>'failure']);
      }
      
    }
/**
跟进统计
*/
    public function tongji(){
    	
    	$positionID = session('position_id');
		
		$w['school']=I('param.school')?:session('school_id');
        if(I('school')=='all')unset($w);
		
		$w2['track_time']=I('post.date1')?['BETWEEN',[I('post.date1').' 00:00:00',I('post.date2').' 23:59:59']]:['like',date('Y-m-d').'%'];
		
//		==============================================================================================================================================
		//针对教学主任只能查看本人的统计情况 edit by zhangxm at 2016-01-05
		if($positionID == 19){
			
			$list=M('user')->where(['is_del'=>0,'id'=>session('auth_id')])->field('id,name,school')->select();
			
			$w2['track_user'] = session('auth_id');
			$list[0]['track_count'] = M('yewuTrack')->where($w2)->count();
		}else{
			$list=M('user')->where($w)->where(['is_del'=>0,'position_id'=>['in','19,13,11']])->field('id,name,school')->select();
	        foreach ($list as $k=>$v){
	            $w2['track_user']=$v['id'];
	            $list[$k]['track_count']=M('yewuTrack')->where($w2)->count();
	        }
		}
//      =====================================================================================================================================================
        /*$list=M('user')->where($w)->where(['is_del'=>0,'position_id'=>['in','19,13,11']])->field('id,name,school')->select();
        foreach ($list as $k=>$v){
            $w2['track_user']=$v['id'];
            $list[$k]['track_count']=M('yewuTrack')->where($w2)->count();
        }*/
       
        $this->list=$list;
        $this->display();
    }

    //页面查询任务跟进记录
    public function tongji_ajax(){
        $w['YewuTrack.track_time']=I('post.date1')?['BETWEEN',[I('post.date1').' 00:00:00',I('post.date2').' 23:59:59']]:['like',date('Y-m-d').'%'];
        $w['YewuTrack.track_user']=I('post.track_user');
        
        if(I('post.tel1')){
        	$w['YewuStudents.tel1']=I('post.tel1');
        }
        
        $data=D('Yewu')->where($w)->select();
        foreach ($data as &$obj){
        	switch($obj['grade']){
        		case "22":$obj['grade'] = "高三";break;
        		case "21":$obj['grade'] = "高二";break;
        		case "20":$obj['grade'] = "高一";break;
        		case "50":$obj['grade'] = "九年级";break;
        		case "40":$obj['grade'] = "八年级";break;
        		case "39":$obj['grade'] = "七年级";break;
        		case "38":$obj['grade'] = "六年级";break;
        		case "93":$obj['grade'] = "五年级";break;
        		case "100":$obj['grade'] = "四年级";break;
        		case "101":$obj['grade'] = "三年级";break;
        		case "102":$obj['grade'] = "二年级";break;
        		case "103":$obj['grade'] = "一年级";break;
        	}
        	
        }
        $this->ajaxReturn($data);
    }
/**
数据导入
*/
    public function import(){
        // M('yewu_students')->where('addx=620')->delete();
        // die;
        if(IS_POST){
            if($_FILES['file']['type']!='application/vnd.ms-excel'||!strstr($_FILES['file']['name'],'.csv')){
                $this->success('文件格式不正确，<br/>请使用提供的模版导入！'); die;
            }
            // var_dump($_FILES['file']);die;
            $file = fopen($_FILES['file']['tmp_name'], "r");
            while ($data = fgetcsv($file)) {
                $list[] = $data;
             }
            if($list[0][0]!='父母姓名(5个字以内)')$zz=true;
            if(count($list[0])!=9)die('数据格式出错');
            fclose($file);
             foreach ($list as $k => $v) {
                if($k<1)continue;
                if($zz)
                foreach ($v as &$val) {
                    $val=$this->characett($val);
                }

				switch($v[5]){
					case "高三": $v[5] = 22;break;
					case "高二": $v[5] = 21;break;
					case "高一": $v[5] = 20;break;
					case "初三":
					case "九年级":$v[5] = 50;break;
					case "初二":
					case "八年级":$v[5] = 40;break;
					case "初一":
					case "七年级":$v[5] = 39;break;	
					case "六年级":$v[5] = 38;break;
					case "五年级":$v[5] = 93;break;	
					case "四年级":$v[5] = 100;break;	
					case "三年级":$v[5] = 101;break;
					case "二年级":$v[5] = 102;break;	
					case "一年级":$v[5] = 103;break;
				}
                $dat[]=[
                    'parents'=>$v[0],
                    'get_way'=>60,
                    'tel1'=> trim($v[1]),
                    'tel2'=> trim($v[2]),
                    'name'=>$v[3],
                    'sex'=>($v[4]=='男'?1:0),
                    'grade'=>$v[5],
                    'wl'=>$v[6],
                    'schoolx'=>$v[7],
                    'school'=>session('school_id'),
                    'addx'=>session('auth_id'),
                    'other'=>$v[8]
                   ];
             }
             // 数据去内部重复
             foreach ($dat as $k=>$v) {
                if($v['tel1'])
                if(in_array($v['tel1'],$tmp)){
                    unset($dat[$k]);
                }else{
                    $tmp[]=$v['tel1'];
                }
                if($v['tel2'])
                if(in_array($v['tel2'],$tmp)){
                    unset($dat[$k]);
                }else{
                    $tmp[]=$v['tel2'];
                }
             }
            // 数据库里查找是否有重复
            $m=M('yewu_students')->where('school!=0')->field('tel1')->distinct(true)->select();
            $m=array_column($m,'tel1');
            $m2=M('yewu_students')->where('school!=0')->field('tel2')->distinct(true)->select();
            $m2=array_column($m2,'tel2');
            $mm=array_filter(array_unique(array_merge($m,$m2)));
            foreach ($dat as $k => $v) {
                if(in_array($v['tel1'],$mm)||in_array($v['tel2'],$mm))unset($dat[$k]);
            }
            // var_dump($dat);die;
            M('yewu_students')->addAll($dat);
            $this->success('导入成功了'.count($dat).'条记录');
        }
    }

    //输出导入使用的模版文件
    public function import_template(){

        $output='父母姓名(5个字以内),联系电话1(11位数字),联系电话2(11位数字),学员姓名,性别(男/女),年级,文理,就读的学校,备注(把多余的信息放在备注里)';
        header("Content-type:application/vnd.ms-excel;charset=utf8");
        header("Content-disposition: attachment; filename=业务数据导入模版文件.csv");
        header("Cache-control: private");
        header("Pragma: private");
        print(iconv('UTF-8','GBK',$output));

    }

    //自动识别文件编码并转换为utf-8,（有问题判断）
    function characett($data){
      if( !empty($data) ){    
        $fileType = mb_detect_encoding($data , ['GBK','UTF-8','ISO-8859-1','UTF-32']) ;   
        if( $fileType != 'UTF-8'){   
          $data = mb_convert_encoding($data ,'utf-8' , $fileType);   
        }
      } 
      return $data;    
    }

/**
业务数据
*/
    public function tongji2(){
    	$positionID = session('position_id');
		$trackUserId = session('auth_id');
		
//		==============================================================================================================================
		//针对教学主任只能查看本人的统计情况 edit by zhangxm at 2016-01-05
		if($positionID == 19){
			$w['track_user'] = $trackUserId;
		}else{
			$w['school']=I('school')?:session('school_id');
        	if(I('school')=='all')unset($w);	
		}
//		==============================================================================================================================
        
		/*$w['school']=I('school')?:session('school_id');
        if(I('school')=='all')unset($w);	*/
			
        $w['state']=['in','20,30'];
        $w['save_time']=I('post.date1')?['BETWEEN',[I('post.date1').' 00:00:00',I('post.date2').' 23:59:59']]:['like',date('Y-m-d').'%'];
        $student=M('yewu_students')->where($w)->getField('id,state,school,track_user,get_way');
        foreach ($student as &$v) {

            $v['school_name']=get_school_name($v['school']);
            $v['track_name']=get_user_name($v['track_user']);
            $list[$v['track_name']]['uid']=$v['track_user'];
            $list[$v['track_name']]['school']=$v['school_name'];
            switch ($v['get_way']) {
                // 直访
                case '10':
                    $list[$v['track_name']]['zf']['count'][]=$v['id'];
                    if($v['state']>10)
                    $list[$v['track_name']]['zf']['ok'][]=$v['id'];
                    break;
                
                // 热线
                case '20':
                    $list[$v['track_name']]['rx']['count'][]=$v['id'];
                    if($v['state']>10)
                    $list[$v['track_name']]['rx']['ok'][]=$v['id'];
                    break;

                // 转介绍
                case '30':
                    $list[$v['track_name']]['zjs']['count'][]=$v['id'];
                    if($v['state']>10)
                    $list[$v['track_name']]['zjs']['ok'][]=$v['id'];
                    break;
                    
                // 其余的归外呼
                default:
                    $list[$v['track_name']]['wh']['count'][]=$v['id'];
                    if($v['state']>10)
                    $list[$v['track_name']]['wh']['ok'][]=$v['id'];
                    break;
            }
            if($v['state']>10)
            $list[$v['track_name']]['count'][]=$v['id'];
        }
        foreach ($list as &$v) {
            $stuid=M('yewuTrack')
                ->where([
                    'track_user'=>$v['uid'],
                    'track_time'=>$w['save_time']
                ])->distinct('stuid')->getField('stuid',true);
            $v['wh']['count']=array_unique(array_merge((array)$v['wh']['count'],(array)$stuid));
        }
        $this->list=$list;
        $this->display();
    }

    public function tongji2_ajax(){
        if(IS_AJAX){
            $ids=I('ids');
            if(!empty($ids))
            $data=M('yewu_students')->where(['id'=>['in',$ids]])->select();
            foreach ($data as &$obj){
            	switch($obj['grade']){
            		case "22":$obj['grade'] = "高三";break;
            		case "21":$obj['grade'] = "高二";break;
            		case "20":$obj['grade'] = "高一";break;
            		case "50":$obj['grade'] = "九年级";break;
            		case "40":$obj['grade'] = "八年级";break;
            		case "39":$obj['grade'] = "七年级";break;
            		case "38":$obj['grade'] = "六年级";break;
            		case "93":$obj['grade'] = "五年级";break;
            		case "100":$obj['grade'] = "四年级";break;
            		case "101":$obj['grade'] = "三年级";break;
            		case "102":$obj['grade'] = "二年级";break;
            		case "103":$obj['grade'] = "一年级";break;
            	}
            	 
            }
            $this->ajaxReturn($data);
        }
    }

} 