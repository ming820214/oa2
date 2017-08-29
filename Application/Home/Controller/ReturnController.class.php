<?php
namespace Home\Controller;

class ReturnController extends HomeController {
    public function add(){
    	
		$this->assign('gradeList', C('SCHOOL_GRADE'));
		$grade_lst = C('SCHOOL_GRADE');
		$gradelst = array_column($grade_lst,'name','id');
		$this->gradelst = $gradelst;
		
        if($_POST['id']){
            $m=M('hw003.money_return',null);
            
            $m->create();
			
            if($_POST['x'])$m->state=1;
            if($m->save()){
			if((session('auth_id') != 90) && (session('auth_id') != 89) && (session('auth_id') != 69) && (session('auth_id') != 1283) )
				  {
			       
				   
				   	{
					   	 
						$record_gt = M('hw003.return','money_')->where(['id'=>$_POST['id']])->find();
						if($record_gt && $record_gt['state'] == 3 && $record_gt['why3']){
							
							
							/* 退费项目默认都是交由王胜鑫处理的，之前是把这个项目交由赵锡睿处理，现在是又归还给张玉珠处理；*/
							 //如果是王思雷，则只能审核高报项目
							if((strpos($record_gt['class'], '高考志愿填报') === FALSE) && (strpos($record_gt['class'], '自主招生') === FALSE) && (strpos($record_gt['class'], '港澳台') === FALSE) && ($record_gt['class1'] != 8) && ($record_gt['class1'] != 9) && ($record_gt['class1'] != 10)){ //将高报退费转到王胜鑫那里
								$user[]= 'YY001'; //王胜鑫
				        		//M('user')->where(['is_del'=>0,'school'=>get_school_id(),'position_id'=>10])->getField('wechat_userid');//wechat_userid							
							}else{
								$user[]= ['XZdl01']; //张玉珠
							} 
						}
					}
			        
			        $user=array_unique($user);
			
			 		//微信通知
			        if(!empty($user)){
			        	
						//存储一下被通知过的人,方便后期查看
				        $ff=(array)F('weixin_tfgl_gt');
				        $f2=array_merge($ff,$user);
				        F('weixin_tfgl_gt',$f2);
						
						$info='点击可直接进入审核……';
						
				        $wx= getWechatObj();
				        $wx->sendNewsMsg(
				            [$wx->buildNewsItem("您有退费记录待审核",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=Return/check3')),'')],
				            ['touser'=>$user],
				            C('WECHAT_APP')['TZTX']
				        );
				        /*$wx->sendTextMsg("特殊优惠有新的动态，请查看",
				            ['touser'=>$user],
				            C('WECHAT_APP')['TZTX']);
							
				         $wx->sendNewsMsg(
				            [$wx->buildNewsItem("有特殊优惠待审核",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=CoursesApply/examine')),'')],
				            ['touser'=>$user],
				            C('WECHAT_APP')['XZMS']
				        );*/
						
			        }
					
					R('Return/record',array($value,'修改'));//记录修改
				}else{
					R('Return/record',array($value,'修改'));//记录修改
				}
			}   
                $this->success('数据修改成功……');
        }elseif($_POST){
            $m=M('hw003.money_return',null);
            $m->create();
            if($_POST['why'])$m->why1=implode('；',$_POST['why']);
            $m->school=get_school_name();
            $m->date=session('date');
            $m->time1=date('Y-m-d H:i:s');
            $m->ka=session('user_name');
            if($m->add()){
            	
				{
			        $user[]=M('user')->where(['is_del'=>0,'school'=>get_school_id(),'position_id'=>22])->getField('wechat_userid');//wechat_userid	
			        
			        $user=array_unique($user);
			
			 		//微信通知
			        if(empty($user)){
			        	return;
			        }
			        	
			        //存储一下被通知过的人,方便后期查看
			        $ff=(array)F('weixin_tfgl_cw');
			        $f2=array_merge($ff,$user);
			        F('weixin_tfgl_cw',$f2);
					
					$info='点击可直接进入审核……';
					
			        $wx= getWechatObj();
			        $wx->sendNewsMsg(
			            [$wx->buildNewsItem("您有退费记录待审核",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=Return/check1')),'')],
			            ['touser'=>$user],
			            C('WECHAT_APP')['TZTX']
			        );
			        /*$wx->sendTextMsg("特殊优惠有新的动态，请查看",
			            ['touser'=>$user],
			            C('WECHAT_APP')['TZTX']);*/
						
			         /*$wx->sendNewsMsg(
			            [$wx->buildNewsItem("有特殊优惠待审核",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=CoursesApply/examine')),'')],
			            ['touser'=>$user],
			            C('WECHAT_APP')['XZMS']
			        );*/
					
				}

                $this->redirect('Return/add');
            }else{
                $this->error('失败');
            }
            // var_dump($why1);
        }else{
        	
			$foo = M("FooInfo");
			$class2 = $foo->where(['pid'=>17,'is_del'=>array('neq',1)])->field('id,name,`group`')->order('`group`,sort')->select();
			$this->class2=$class2;
			
            $w['date']=session('date');
            $w['state']=array('elt','1');
            $w['school']=get_school_name();
            $list=M('hw003.money_return',null)->where($w)->order('id desc,state asc')->select();
			foreach($list as &$vor){
				foreach($class2 as $cl2){
					if($vor['class2'] == $cl2['id']){
						$vor['class2'] = $cl2['name'];
						break;
					}
				}
				
				$class1 = get_config('COURSE_GROUP');
				foreach($class1 as $key=>$value){
					if((string)$vor['class1'] === (string)$key){
						$vor['class1'] = $value;
						break;
					}
				}
			}
			unset($vor);
			unset($cl2);
			unset($key);
			unset($value);
			
            $this->list=$list;

            $w['state']=array('gt','1');
            $list2=M('hw003.money_return',null)->where($w)->order('id desc')->select();
			
			foreach($list2 as &$vor){
				foreach($class2 as $cl2){
					if($vor['class2'] == $cl2['id']){
						$vor['class2'] = $cl2['name'];
						break;
					}
				}
				
				$class1 = get_config('COURSE_GROUP');
				foreach($class1 as $key=>$value){
					if((string)$vor['class1'] === (string)$key){
						$vor['class1'] = $value;
						break;
					}
				}
			}
			
			unset($vor);
			unset($cl2);
			unset($key);
			unset($value);
            $this->list2=$list2;
			
			
			
            $this->display();
        }
    }

    public function delt(){
    	
    	$this->assign('gradeList', C('SCHOOL_GRADE'));
		$grade_lst = C('SCHOOL_GRADE');
		$gradelst = array_column($grade_lst,'name','id');
		$this->gradelst = $gradelst;
		
        if($_POST['id'])
            foreach ($_POST['id'] as $v) {
                M('hw003.money_return',null)->where(['id'=>$v])->setField('state',-2);
                R('Return/record',array($v,'删除'));
            }
        $this->success('删除成功！');
    }

//修改数据回调使用
    public function api_c($id){
      if(isset($_POST['id'])&&$_POST['id']!=''){
        $where['id']=$_POST['id'];
        $shuchu=M('hw003.money_return',null)->where($where)->find();
        print(json_encode($shuchu));//将信息发送给浏览器
      }
    }

//财务确认
    public function check1(){
    	
		$this->assign('gradeList', C('SCHOOL_GRADE'));
		$grade_lst = C('SCHOOL_GRADE');
		$gradelst = array_column($grade_lst,'name','id');
		$this->gradelst = $gradelst;
		
        if($_POST['aax']){
            foreach ($_POST['id'] as $key => $value) {
                $w['id']=$value;
                $d['state']=2;
				$d['cwqr_time'] =  date("Y-m-d H:i:s"); 
                $rr=M('hw003.return','money_')->where($w)->save($d);
                //审核记录
                R('Return/record',array($value,'财务确认'));
				
				
				{
			        $user[]=M('user')->where(['is_del'=>0,'school'=>get_school_id(),'position_id'=>10])->getField('wechat_userid');//wechat_userid	
					
				}

            }
            if($rr){
            	
				{
					
					$user=array_unique($user);
			
			 		//微信通知
			        if(empty($user)){
			        	return;
			        }
			        	
			        //存储一下被通知过的人,方便后期查看
			        $ff=(array)F('weixin_tfgl_xq');
			        $f2=array_merge($ff,$user);
			        F('weixin_tfgl_xq',$f2);
					
					$info='点击可直接进入审核……';
					
			        $wx= getWechatObj();
			        $wx->sendNewsMsg(
			            [$wx->buildNewsItem("您有退费记录待审核",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=Return/check2')),'')],
			            ['touser'=>$user],
			            C('WECHAT_APP')['TZTX']
			        );
			        /*$wx->sendTextMsg("特殊优惠有新的动态，请查看",
			            ['touser'=>$user],
			            C('WECHAT_APP')['TZTX']);*/
						
			         /*$wx->sendNewsMsg(
			            [$wx->buildNewsItem("有特殊优惠待审核",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=CoursesApply/examine')),'')],
			            ['touser'=>$user],
			            C('WECHAT_APP')['XZMS']
			        );*/
					
				}
                $this->success('审核完成！');
            }else{
                $this->success('选择要审核的条目！');
            }
        }elseif($_POST['bt']){
            foreach ($_POST['id'] as $key => $value) {
                $w['id']=$value;
                $d['state']=0;
				$d['cwqr_time'] =  date("Y-m-d H:i:s"); 
                $rr=M('hw003.return','money_')->where($w)->save($d);
                R('Return/record',array($value,'数据退回'));
				
				{
					$xueguan = M('hw003.return','money_')->where($w)->getField('bb');
					
					$xg = M('user')->where(['is_del'=>0,'school'=>get_school_id(),'position_id'=>18,'name'=>$xueguan])->getField('wechat_userid');//wechat_userid
					
					if($xg){
						$user[]= $xg;	
					}
			        	
					
				}
            }
            if($rr){
            	
				{
					
					$user=array_unique($user);
			
			 		//微信通知
			        if(empty($user)){
			        	return;
			        }
			        	
			        //存储一下被通知过的人,方便后期查看
			        $ff=(array)F('weixin_tfgl_cw_th');
			        $f2=array_merge($ff,$user);
			        F('weixin_tfgl_cw_th',$f2);
					
					$info='点击可直接进入审核……';
					
			        $wx= getWechatObj();
			        $wx->sendNewsMsg(
			            [$wx->buildNewsItem("您有退费申请被退回，请及时查看！",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=Return/add')),'')],
			            ['touser'=>$user],
			            C('WECHAT_APP')['TZTX']
			        );
			        /*$wx->sendTextMsg("特殊优惠有新的动态，请查看",
			            ['touser'=>$user],
			            C('WECHAT_APP')['TZTX']);*/
						
			         /*$wx->sendNewsMsg(
			            [$wx->buildNewsItem("有特殊优惠待审核",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=CoursesApply/examine')),'')],
			            ['touser'=>$user],
			            C('WECHAT_APP')['XZMS']
			        );*/
					
				}
                $this->success('数据已退回！');
            }else{
                $this->error('选择要退回的数据！');
            }
        }elseif ($_POST['dl']) {
            foreach ($_POST['id'] as $key => $value) {
//                 $w['id']=$value;
//                 $w['state']=array('in','0,-1');
//                 $rr=M('hw003.return','money_')->where($w)->delete();
                $w['id']=$value;
                $data['state'] = -2;
                $rr=M('hw003.return','money_')->where($w)->save($data);
                R('Return/record',array($value,'删除'));
				
				{
					$xueguan = M('hw003.return','money_')->where(['id'=>$value])->getField('bb');
					
					$xg = M('user')->where(['is_del'=>0,'school'=>get_school_id(),'position_id'=>18,'name'=>$xueguan])->getField('wechat_userid');//wechat_userid
					
					if($xg){
						$user[]= $xg;	
					}
			        	
					$del_id[] = $value;
				}
            }
            if($rr)
			
				{
					
					$user=array_unique($user);
			
			 		//微信通知
			        if(empty($user)){
			        	return;
			        }
			        	
			        //存储一下被通知过的人,方便后期查看
			        $ff=(array)F('weixin_tfgl_cw_sc');
			        $f2=array_merge($ff,$user);
			        F('weixin_tfgl_cw_sc',$f2);
					
					$info='点击可直接进入审核……';
					
					$del_id_str = implode("__", $del_id);
			        $wx= getWechatObj();
			        $wx->sendNewsMsg(
			            [$wx->buildNewsItem("您有退费申请被删除". $del_id_str ,$info,wx_oauth(C('WWW').U('Public/log_wx?urll=Return/add')),'')],
			            ['touser'=>$user],
			            C('WECHAT_APP')['TZTX']
			        );
			        /*$wx->sendTextMsg("特殊优惠有新的动态，请查看",
			            ['touser'=>$user],
			            C('WECHAT_APP')['TZTX']);*/
						
			         /*$wx->sendNewsMsg(
			            [$wx->buildNewsItem("有特殊优惠待审核",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=CoursesApply/examine')),'')],
			            ['touser'=>$user],
			            C('WECHAT_APP')['XZMS']
			        );*/
					$this->success('删除成功！');
				}
			
                
        }else{
        	
			$foo = M("FooInfo");
			$class2 = $foo->where(['pid'=>17,'is_del'=>array('neq',1)])->field('id,name,`group`')->order('`group`,sort')->select();
			$this->class2=$class2;
			
            $w['state']=1;
            $w['school']=get_school_name();
            $w['date']=session('date');
            $list=M('hw003.money_return',null)->where($w)->order('id desc')->select();
			
			foreach($list as &$vor){
				foreach($class2 as $cl2){
					if($vor['class2'] == $cl2['id']){
						$vor['class2'] = $cl2['name'];
						break;
					}
				}
				
				$class1 = get_config('COURSE_GROUP');
				foreach($class1 as $key=>$value){
					if((string)$vor['class1'] === (string)$key){
						$vor['class1'] = $value;
						break;
					}
				}
			}
			unset($vor);
			unset($cl2);
			unset($key);
			unset($value);
			
            $this->list=$list;

            $w['state']=array('egt','2');
            $list=M('hw003.money_return',null)->where($w)->order('id desc')->select();
			
			foreach($list as &$vor){
				foreach($class2 as $cl2){
					if($vor['class2'] == $cl2['id']){
						$vor['class2'] = $cl2['name'];
						break;
					}
				}
				
				$class1 = get_config('COURSE_GROUP');
				foreach($class1 as $key=>$value){
					if((string)$vor['class1'] === (string)$key){
						$vor['class1'] = $value;
						break;
					}
				}
			}
			unset($vor);
			unset($cl2);
			unset($key);
			unset($value);
			
            $this->list2=$list;
			$this->module = 'cwqr'; //财务确认审核步骤；
            $this -> display('check');

        }
    }

//校区审核
    public function check2(){
    	
		$this->assign('gradeList', C('SCHOOL_GRADE'));
		$grade_lst = C('SCHOOL_GRADE');
		$gradelst = array_column($grade_lst,'name','id');
		$this->gradelst = $gradelst;
		
        if($_POST['aax']){
            foreach ($_POST['id'] as $key => $value) {
                $w['id']=$value;
                $d['state']=3;
				$d['xqsh_time'] =  date("Y-m-d H:i:s"); 
                $rr=M('hw003.return','money_')->where($w)->save($d);
                //审核记录
                R('Return/record',array($value,'校区审核'));
				
				{
					$record_xq = M('hw003.return','money_')->where($w)->find();
					if($record_xq){
						//如果是王思雷，则只能审核高报项目
					    if((strpos($record_xq['class'], '高考志愿填报') === FALSE) && (strpos($record_xq['class'], '自主招生') === FALSE) && (strpos($record_xq['class'], '港澳台') === FALSE) && ($record_xq['class1'] != 8) && ($record_xq['class1'] != 9) && ($record_xq['class1'] != 10) && ($record_xq['class1'] != 12) && ($record_xq['class1'] != 13) && ($record_xq['class1'] != 14)){
//							$user[0]= 'ZXhld001'; //邹德涛
							$user[0]= 'XGryxc22'; //邹婧
			        		//M('user')->where(['is_del'=>0,'school'=>get_school_id(),'position_id'=>10])->getField('wechat_userid');//wechat_userid							
						}elseif(($record_xq['class1'] == 12) || ($record_xq['class1'] == 13)){
							$user[0]= 'AAA'; //李文龙
						}else{
// 							$user[0]= 'Azl'; //王思雷
							$user[0]= 'QThwzb002'; //刘媛媛
							
						}
					}
				}
            }
            if($rr){
            	
				{
					
					$user=array_unique($user);
			
			 		//微信通知
			        if(empty($user)){
			        	return;
			        }
			        	
			        //存储一下被通知过的人,方便后期查看
			        $ff=(array)F('weixin_tfgl_zb');
			        $f2=array_merge($ff,$user);
			        F('weixin_tfgl_zb',$f2);
					
					$info='点击可直接进入审核……';
					
			        $wx= getWechatObj();
			        
			        if(($record_xq['class1'] == 12)){
			        	$wx->sendNewsMsg(
			        			[$wx->buildNewsItem("您有退费记录待审核",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=Return/check3')),'')],
			        			['touser'=>$user],
			        			C('WECHAT_APP')['TZTX']
			        			);
			        	
			        }else{
			        	$wx->sendNewsMsg(
			        			[$wx->buildNewsItem("您有退费记录待审核",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=Return/gt')),'')],
			        			['touser'=>$user],
			        			C('WECHAT_APP')['TZTX']
			        			);
			        }
			        
			        /*$wx->sendTextMsg("特殊优惠有新的动态，请查看",
			            ['touser'=>$user],
			            C('WECHAT_APP')['TZTX']);*/
						
			         /*$wx->sendNewsMsg(
			            [$wx->buildNewsItem("有特殊优惠待审核",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=CoursesApply/examine')),'')],
			            ['touser'=>$user],
			            C('WECHAT_APP')['XZMS']
			        );*/
				}	
				
				
                $this->success('审核完成！');
            }else{
                $this->success('选择要审核的条目！');
            }
        }elseif($_POST['bt']){
            foreach ($_POST['id'] as $key => $value) {
                $w['id']=$value;
                $d['state']=0;
				$d['xqsh_time'] =  date("Y-m-d H:i:s"); 
                $rr=M('hw003.return','money_')->where($w)->save($d);
                R('Return/record',array($value,'数据退回'));
				
				{
					$xueguan = M('hw003.return','money_')->where($w)->getField('bb');
					
					$xg = M('user')->where(['is_del'=>0,'school'=>get_school_id(),'position_id'=>18,'name'=>$xueguan])->getField('wechat_userid');//wechat_userid
					
					if($xg){
						$user[]= $xg;	
					}
			        	
					
				}
            }
            if($rr){
            	
				{
					
					$user=array_unique($user);
			
			 		//微信通知
			        if(empty($user)){
			        	return;
			        }
			        	
			        //存储一下被通知过的人,方便后期查看
			        $ff=(array)F('weixin_tfgl_zb_th');
			        $f2=array_merge($ff,$user);
			        F('weixin_tfgl_zb_th',$f2);
					
					$info='点击可直接进入审核……';
					
			        $wx= getWechatObj();
			        $wx->sendNewsMsg(
			            [$wx->buildNewsItem("您有退费申请被退回，请及时查看！",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=Return/add')),'')],
			            ['touser'=>$user],
			            C('WECHAT_APP')['TZTX']
			        );
			        /*$wx->sendTextMsg("特殊优惠有新的动态，请查看",
			            ['touser'=>$user],
			            C('WECHAT_APP')['TZTX']);*/
						
			         /*$wx->sendNewsMsg(
			            [$wx->buildNewsItem("有特殊优惠待审核",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=CoursesApply/examine')),'')],
			            ['touser'=>$user],
			            C('WECHAT_APP')['XZMS']
			        );*/
					
				}
				
                $this->success('数据已退回！');
            }else{
                $this->error('选择要退回的数据！');
            }
        }elseif ($_POST['dl']) {
            foreach ($_POST['id'] as $key => $value) {
//                 $w['id']=$value;
//                 $w['state']=array('in','0,-1');
//                 $rr=M('hw003.return','money_')->where($w)->delete();
                $w['id']=$value;
                $data['state'] = -2;
                $rr=M('hw003.return','money_')->where($w)->save($data);
                R('Return/record',array($value,'删除'));
				
				{
					$xueguan = M('hw003.return','money_')->where(['id'=>$value])->getField('bb');
					
					$xg = M('user')->where(['is_del'=>0,'school'=>get_school_id(),'position_id'=>18,'name'=>$xueguan])->getField('wechat_userid');//wechat_userid
					
					if($xg){
						$user[]= $xg;	
					}
			        	
					$del_id[] = $value;
				}
            }
            if($rr)
			
				{
					
					$user=array_unique($user);
			
			 		//微信通知
			        if(empty($user)){
			        	return;
			        }
			        	
			        //存储一下被通知过的人,方便后期查看
			        $ff=(array)F('weixin_tfgl_zb_sc');
			        $f2=array_merge($ff,$user);
			        F('weixin_tfgl_zb_sc',$f2);
					
					$info='点击可直接进入审核……';
					
					$del_id_str = implode("__", $del_id);
			        $wx= getWechatObj();
			        $wx->sendNewsMsg(
			            [$wx->buildNewsItem("您有退费申请被删除". $del_id_str ,$info,wx_oauth(C('WWW').U('Public/log_wx?urll=Return/add')),'')],
			            ['touser'=>$user],
			            C('WECHAT_APP')['TZTX']
			        );
			        /*$wx->sendTextMsg("特殊优惠有新的动态，请查看",
			            ['touser'=>$user],
			            C('WECHAT_APP')['TZTX']);*/
						
			         /*$wx->sendNewsMsg(
			            [$wx->buildNewsItem("有特殊优惠待审核",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=CoursesApply/examine')),'')],
			            ['touser'=>$user],
			            C('WECHAT_APP')['XZMS']
			        );*/
					$this->success('删除成功！');
				}
                
        }else{
        	
			$foo = M("FooInfo");
			$class2 = $foo->where(['pid'=>17,'is_del'=>array('neq',1)])->field('id,name,`group`')->order('`group`,sort')->select();
			$this->class2=$class2;
			
            $w['state']=2;
            $w['school']=get_school_name();

//给龙哥特别处理
            if(session('user_name')=='总裁'){
                $w['school']=array('like',array('盘锦水木清华校区','恒泰校区','盘锦日月兴城校区'),'OR');
            }
//给龙哥特别处理

            $w['date']=session('date');
            $list=M('hw003.money_return',null)->where($w)->order('id desc')->select();
			
			foreach($list as &$vor){
				foreach($class2 as $cl2){
					if($vor['class2'] == $cl2['id']){
						$vor['class2'] = $cl2['name'];
						break;
					}
				}
				
				$class1 = get_config('COURSE_GROUP');
				foreach($class1 as $key=>$value){
					if((string)$vor['class1'] === (string)$key){
						$vor['class1'] = $value;
						break;
					}
				}
			}
			unset($vor);
			unset($cl2);
			unset($key);
			unset($value);
			
            $this->list=$list;

            $w['state']=array('egt','3');
            $list=M('hw003.money_return',null)->where($w)->order('id desc')->select();
			
			foreach($list as &$vor){
				foreach($class2 as $cl2){
					if($vor['class2'] == $cl2['id']){
						$vor['class2'] = $cl2['name'];
						break;
					}
				}
				
				$class1 = get_config('COURSE_GROUP');
				foreach($class1 as $key=>$value){
					if((string)$vor['class1'] === (string)$key){
						$vor['class1'] = $value;
						break;
					}
				}
			}
			unset($vor);
			unset($cl2);
			unset($key);
			unset($value);
			
            $this->list2=$list;
			$this->module = 'xqsh'; //校区审核步骤；
            $this -> display('check');

        }
    }

//部门审核
    public function check3(){
    	
		$this->assign('gradeList', C('SCHOOL_GRADE'));
		$grade_lst = C('SCHOOL_GRADE');
		$gradelst = array_column($grade_lst,'name','id');
		$this->gradelst = $gradelst;
		
        if($_POST['aax']){
            foreach ($_POST['id'] as $key => $value) {
                $w['id']=$value;
                $d['state']=4;
                $d['time2']=date('Y-m-d H:i:s');
                $rr=M('hw003.return','money_')->where($w)->save($d);
                //审核记录
                R('Return/record',array($value,'部门审核'));
            }
            if($rr){
            	{
			       	$user[0]= 'YX005';//王丽丽 ；'A02';//wechat_userid 齐静	
			        	
			        //存储一下被通知过的人,方便后期查看
			        $ff=(array)F('weixin_tfgl_bm');
			        $f2=array_merge($ff,$user);
			        F('weixin_tfgl_bm',$f2);
					
					$info='点击可直接进入审核……';
					
			        $wx= getWechatObj();
			        $wx->sendNewsMsg(
			            [$wx->buildNewsItem("您有退费记录待审核",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=Return/check4')),'')],
			            ['touser'=>$user],
			            C('WECHAT_APP')['TZTX']
			        );
			        /*$wx->sendTextMsg("特殊优惠有新的动态，请查看",
			            ['touser'=>$user],
			            C('WECHAT_APP')['TZTX']);*/
						
			         /*$wx->sendNewsMsg(
			            [$wx->buildNewsItem("有特殊优惠待审核",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=CoursesApply/examine')),'')],
			            ['touser'=>$user],
			            C('WECHAT_APP')['XZMS']
			        );*/
					
				}
                $this->success('审核完成！');
            }else{
                $this->success('选择要审核的条目！');
            }
        }elseif($_POST['bt']){
            foreach ($_POST['id'] as $key => $value) {
                $w['id']=$value;
                $d['state']=0;
                $rr=M('hw003.return','money_')->where($w)->save($d);
                R('Return/record',array($value,'数据退回'));
				
				{
// 					$xueguan = M('hw003.return','money_')->where($w)->getField('bb');
					$data_t = M('hw003.return','money_')->where($w)->getField('bb,school');
					$xueguan = array_keys($data_t)[0];
					$school = array_values($data_t)[0];
					
					$xg = M('user')->where(['is_del'=>0,'school'=>get_school_id($school),'position_id'=>18,'name'=>$xueguan])->getField('wechat_userid');//wechat_userid
					
					if($xg){
						$user[]= $xg;	
					}
			        	
					
				}
            }
            if($rr){
            	{
					
					$user=array_unique($user);
			
			 		//微信通知
			        if(empty($user)){
			        	return;
			        }
			        	
			        //存储一下被通知过的人,方便后期查看
			        $ff=(array)F('weixin_tfgl_bm_th');
			        $f2=array_merge($ff,$user);
			        F('weixin_tfgl_bm_th',$f2);
					
					$info='点击可直接进入审核……';
					
			        $wx= getWechatObj();
			        $wx->sendNewsMsg(
			            [$wx->buildNewsItem("您有退费申请被退回，请及时查看！",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=Return/add')),'')],
			            ['touser'=>$user],
			            C('WECHAT_APP')['TZTX']
			        );
			        /*$wx->sendTextMsg("特殊优惠有新的动态，请查看",
			            ['touser'=>$user],
			            C('WECHAT_APP')['TZTX']);*/
						
			         /*$wx->sendNewsMsg(
			            [$wx->buildNewsItem("有特殊优惠待审核",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=CoursesApply/examine')),'')],
			            ['touser'=>$user],
			            C('WECHAT_APP')['XZMS']
			        );*/
					
				}
                $this->success('数据已退回！');
            }else{
                $this->error('选择要退回的数据！');
            }
        }elseif ($_POST['dl']) {
            foreach ($_POST['id'] as $key => $value) {
                $w['id']=$value;
                $w['state']=array('in','0,-1');
                $rr=M('hw003.return','money_')->where($w)->delete();
                R('Return/record',array($value,'删除'));
				
				{
// 					$xueguan = M('hw003.return','money_')->where(['id'=>$value])->getField('bb');

					$data_t = M('hw003.return','money_')->where(['id'=>$value])->getField('bb,school');
					$xueguan = array_keys($data_t)[0];
					$school = array_values($data_t)[0];
					
					$xg = M('user')->where(['is_del'=>0,'school'=>get_school_id($school),'position_id'=>18,'name'=>$xueguan])->getField('wechat_userid');//wechat_userid
					
					if($xg){
						$user[]= $xg;	
					}
			        	
					$del_id[] = $value;
				}
            }
            if($rr)
				{
					
					$user=array_unique($user);
			
			 		//微信通知
			        if(empty($user)){
			        	return;
			        }
			        	
			        //存储一下被通知过的人,方便后期查看
			        $ff=(array)F('weixin_tfgl_bm_sc');
			        $f2=array_merge($ff,$user);
			        F('weixin_tfgl_bm_sc',$f2);
					
					$info='点击可直接进入审核……';
					
					$del_id_str = implode("__", $del_id);
			        $wx= getWechatObj();
			        $wx->sendNewsMsg(
			            [$wx->buildNewsItem("您有退费申请被删除". $del_id_str ,$info,wx_oauth(C('WWW').U('Public/log_wx?urll=Return/add')),'')],
			            ['touser'=>$user],
			            C('WECHAT_APP')['TZTX']
			        );
			        /*$wx->sendTextMsg("特殊优惠有新的动态，请查看",
			            ['touser'=>$user],
			            C('WECHAT_APP')['TZTX']);*/
						
			         /*$wx->sendNewsMsg(
			            [$wx->buildNewsItem("有特殊优惠待审核",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=CoursesApply/examine')),'')],
			            ['touser'=>$user],
			            C('WECHAT_APP')['XZMS']
			        );*/
					$this->success('删除成功！');
				}
        }else{
        	
			$foo = M("FooInfo");
			$class2 = $foo->where(['pid'=>17,'is_del'=>array('neq',1)])->field('id,name,`group`')->order('`group`,sort')->select();
			$this->class2=$class2;
			
			/* 此处审核交由张玉珠处理，默认情况下，都是由王胜鑫处理的。（之前是交由赵锡睿处理的）*/
			  if(session('auth_id') == 1283){
				//如果是王思雷，则只能审核高报项目
//				$w['class'] = array('like',array('%高考志愿填报%','%自主招生%','港澳台'),'OR');
				$w['_string'] = "(`class` LIKE '%高考志愿填报%' OR `class` LIKE '%自主招生%' OR `class` LIKE '%港澳台%') or `class1` in(8,9,10,14)";
			}else{
//				$w['class'] = array('notlike',array('%高考志愿填报%','%自主招生%','港澳台'),'AND');
				$w['_string'] = "(`class` NOT LIKE '%高考志愿填报%' AND `class` NOT LIKE '%自主招生%' AND `class` NOT LIKE '%港澳台%') AND (`class1` not in (8,9,10) OR (`class1` is null))";
			} 
			
            $w['state']=3;
            
            //李文龙查看长颈鹿项目、童话项目退费
            if(session('auth_id') == 69){
            	$w['class1'] = array('in',[12,13]); //长颈鹿项目、童话
            }else{
                $w['class1'] = array('not in',[12,13,14]); //非长颈鹿项目、童话
            	$w['why3']=array('neq','');
            }
            
            
            $w['date']=session('date');
            $list=M('hw003.money_return',null)->where($w)->order('id desc')->select();
			
			foreach($list as &$vor){
				foreach($class2 as $cl2){
					if($vor['class2'] == $cl2['id']){
						$vor['class2'] = $cl2['name'];
						break;
					}
				}
				
				$class1 = get_config('COURSE_GROUP');
				foreach($class1 as $key=>$value){
					if((string)$vor['class1'] === (string)$key){
						$vor['class1'] = $value;
						break;
					}
				}
			}
			unset($vor);
			unset($cl2);
			unset($key);
			unset($value);
			
            $this->list=$list;

            $w['state']=array('in','4,5');
            $list=M('hw003.money_return',null)->where($w)->order('id desc')->select();
			
			foreach($list as &$vor){
				foreach($class2 as $cl2){
					if($vor['class2'] == $cl2['id']){
						$vor['class2'] = $cl2['name'];
						break;
					}
				}
				
				$class1 = get_config('COURSE_GROUP');
				foreach($class1 as $key=>$value){
					if((string)$vor['class1'] === (string)$key){
						$vor['class1'] = $value;
						break;
					}
				}
			}
			unset($vor);
			unset($cl2);
			unset($key);
			unset($value);
			
            $this->list2=$list;

            $this -> display('check');

        }
    }

//总部沟通
    public function gt(){
    	
		$this->assign('gradeList', C('SCHOOL_GRADE'));
		$grade_lst = C('SCHOOL_GRADE');
		$gradelst = array_column($grade_lst,'name','id');
		$this->gradelst = $gradelst;
		
    	$this->module = ACTION_NAME; //设置前端变量，在进行总部沟通保存的时候进行判断，是否添加总部沟通的时间保存；
        if($_POST['aax']){
        	//此处代码不走，已经作废！
            foreach ($_POST['id'] as $key => $value) {
                $w['id']=$value;
                $d['state']=5;
				$d['zbgt_time'] =  date("Y-m-d H:i:s"); 
                $rr=M('hw003.return','money_')->where($w)->save($d);
                //审核记录
                R('Return/record',array($value,'集团审核'));
            }
            if($rr){
                $this->success('审核完成！');
            }else{
                $this->success('选择要审核的条目！');
            }
        }elseif($_POST['bt']){
            foreach ($_POST['id'] as $key => $value) {
                $w['id']=$value;
                $d['state']=0;
				$d['zbgt_time'] =  date("Y-m-d H:i:s"); 
                $rr=M('hw003.return','money_')->where($w)->save($d);
                R('Return/record',array($value,'数据退回'));
				
				{
// 					$xueguan = M('hw003.return','money_')->where($w)->getField('bb');

					$data_t = M('hw003.return','money_')->where($w)->getField('bb,school');
					$xueguan = array_keys($data_t)[0];
					$school = array_values($data_t)[0];
					
					
					$xg = M('user')->where(['is_del'=>0,'school'=>get_school_id($school),'position_id'=>18,'name'=>$xueguan])->getField('wechat_userid');//wechat_userid
					
					if($xg){
						$user[]= $xg;	
					}
			        	
					
				}
            }
            if($rr){
            	
				{
					
					$user=array_unique($user);
			
			 		//微信通知
			        if(empty($user)){
			        	return;
			        }
			        	
			        //存储一下被通知过的人,方便后期查看
			        $ff=(array)F('weixin_tfgl_jt_th');
			        $f2=array_merge($ff,$user);
			        F('weixin_tfgl_jt_th',$f2);
					
					$info='点击可直接进入审核……';
					
			        $wx= getWechatObj();
			        $wx->sendNewsMsg(
			            [$wx->buildNewsItem("您有退费申请被退回，请及时查看！",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=Return/add')),'')],
			            ['touser'=>$user],
			            C('WECHAT_APP')['TZTX']
			        );
			        /*$wx->sendTextMsg("特殊优惠有新的动态，请查看",
			            ['touser'=>$user],
			            C('WECHAT_APP')['TZTX']);*/
						
			         /*$wx->sendNewsMsg(
			            [$wx->buildNewsItem("有特殊优惠待审核",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=CoursesApply/examine')),'')],
			            ['touser'=>$user],
			            C('WECHAT_APP')['XZMS']
			        );*/
					
				}
				
                $this->success('数据已退回！');
            }else{
                $this->error('选择要退回的数据！');
            }
        }else{
        	
			$foo = M("FooInfo");
			$class2 = $foo->where(['pid'=>17,'is_del'=>array('neq',1)])->field('id,name,`group`')->order('`group`,sort')->select();
			$this->class2=$class2;
			
// 			if(session('auth_id') == 93){
			if(session('auth_id') == 1292){
				//此处交由刘媛媛处理，之前是王思雷处理的,
				//如果是王思雷，则只能审核高报项目
//				$w['class'] = array('like',array('%高考志愿填报%','%自主招生%','%港澳台%'),'OR');
				
				$w['_string'] = "(`class` LIKE '%高考志愿填报%' OR `class` LIKE '%自主招生%' OR `class` LIKE '%港澳台%') or `class1` in(8,9,10,14)";
			}else{
//				$w['class'] = array('notlike',array('%高考志愿填报%','%自主招生%','%港澳台%'),'AND');
				
				$w['_string'] = "(`class` NOT LIKE '%高考志愿填报%' AND `class` NOT LIKE '%自主招生%' AND `class` NOT LIKE '%港澳台%') AND (`class1` not in (8,9,10,12,13,14) OR (`class1` is null))";
			}
			
            $w['state']=3;
            $w['date']=session('date');
            $list=M('hw003.money_return',null)->where($w)->order('id desc')->select();
			
			foreach($list as &$vor){
				foreach($class2 as $cl2){
					if($vor['class2'] == $cl2['id']){
						$vor['class2'] = $cl2['name'];
						break;
					}
				}
				
				$class1 = get_config('COURSE_GROUP');
				foreach($class1 as $key=>$value){
					if((string)$vor['class1'] === (string)$key){
						$vor['class1'] = $value;
						break;
					}
				}
			}
			unset($vor);
			unset($cl2);
			unset($key);
			unset($value);
			
            $this->list=$list;

            $w['state']=array('in','0,1,2');
            $list=M('hw003.money_return',null)->where($w)->order('id desc')->select();
			
			foreach($list as &$vor){
				foreach($class2 as $cl2){
					if($vor['class2'] == $cl2['id']){
						$vor['class2'] = $cl2['name'];
						break;
					}
				}
				
				$class1 = get_config('COURSE_GROUP');
				foreach($class1 as $key=>$value){
					if((string)$vor['class1'] === (string)$key){
						$vor['class1'] = $value;
						break;
					}
				}
			}
			unset($vor);
			unset($cl2);
			unset($key);
			unset($value);
			
            $this->list2=$list;

            $this->why3='1';
            $this->bt='1';
            $this -> display('check');

        }
    }

//集团审批
    public function check4(){
    	
    	$this->assign('gradeList', C('SCHOOL_GRADE'));
		$grade_lst = C('SCHOOL_GRADE');
		$gradelst = array_column($grade_lst,'name','id');
		$this->gradelst = $gradelst;
		
        if($_POST['aax']){
            foreach ($_POST['id'] as $key => $value) {
                $w['id']=$value;
                $d['state']=5;
				$d['jtsp_time'] =  date("Y-m-d H:i:s"); 
                $rr=M('hw003.return','money_')->where($w)->save($d);
                //审核记录
                R('Return/record',array($value,'集团审核'));
				
				{
// 					$blr = M('hw003.return','money_')->where($w)->getField('ka');
					
						
					$data_t = M('hw003.return','money_')->where($w)->getField('ka,school');
					$blr = array_keys($data_t)[0];
					$school = array_values($data_t)[0];
					
					
					$cond['is_del'] = 0;
					$cond['school'] = get_school_id($school);
					if($blr){
						$cond['_string'] = " position_id=22 OR name='" . $blr .  "'";	
					}else{
						$cond['position_id'] = 22;
					}
					
			        $user[] = M('user')->where($cond)->getField('wechat_userid');//wechat_userid
			        	
				}
            }
            if($rr){
            	{
            		$user=array_unique($user);
			
			 		//微信通知
			        if(empty($user)){
			        	return;
			        }
			        	
			        //存储一下被通知过的人,方便后期查看
			        $ff=(array)F('weixin_tfgl_jt');
			        $f2=array_merge($ff,$user);
			        F('weixin_tfgl_jt',$f2);
					
					$info='点击可直接进入审核……';
					
			        $wx= getWechatObj();
			        /*$wx->sendNewsMsg(
			            [$wx->buildNewsItem("有退费记录通过审核",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=Return/check1')),'')],
			            ['touser'=>$user],
			            C('WECHAT_APP')['TZTX']
			        );*/
			        $wx->sendTextMsg("退费申请审核通过，请及时查看",
			            ['touser'=>$user],
			            C('WECHAT_APP')['TZTX']);
						
			         /*$wx->sendNewsMsg(
			            [$wx->buildNewsItem("有特殊优惠待审核",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=CoursesApply/examine')),'')],
			            ['touser'=>$user],
			            C('WECHAT_APP')['XZMS']
			        );*/
            	}
                $this->success('审核完成！');
            }else{
                $this->success('选择要审核的条目！');
            }
        }elseif($_POST['bt']){
            foreach ($_POST['id'] as $key => $value) {
                $w['id']=$value;
                $d['state']=0;
				$d['jtsp_time'] =  date("Y-m-d H:i:s"); 
                $rr=M('hw003.return','money_')->where($w)->save($d);
                R('Return/record',array($value,'数据退回'));
				
				{
// 					$xueguan = M('hw003.return','money_')->where($w)->getField('bb');
					
					$data_t = M('hw003.return','money_')->where($w)->getField('bb,school');
					$xueguan = array_keys($data_t)[0];
					$school = array_values($data_t)[0];
					
					$xg = M('user')->where(['is_del'=>0,'school'=>get_school_id($school),'position_id'=>18,'name'=>$xueguan])->getField('wechat_userid');//wechat_userid
					
					if($xg){
						$user[]= $xg;	
					}
			        	
					
				}
            }
            if($rr){
            	{
					
					$user=array_unique($user);
			
			 		//微信通知
			        if(empty($user)){
			        	return;
			        }
			        	
			        //存储一下被通知过的人,方便后期查看
			        $ff=(array)F('weixin_tfgl_jt_th');
			        $f2=array_merge($ff,$user);
			        F('weixin_tfgl_jt_th',$f2);
					
					$info='点击可直接进入审核……';
					
			        $wx= getWechatObj();
			        $wx->sendNewsMsg(
			            [$wx->buildNewsItem("您有退费申请被退回，请及时查看！",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=Return/add')),'')],
			            ['touser'=>$user],
			            C('WECHAT_APP')['TZTX']
			        );
			        /*$wx->sendTextMsg("特殊优惠有新的动态，请查看",
			            ['touser'=>$user],
			            C('WECHAT_APP')['TZTX']);*/
						
			         /*$wx->sendNewsMsg(
			            [$wx->buildNewsItem("有特殊优惠待审核",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=CoursesApply/examine')),'')],
			            ['touser'=>$user],
			            C('WECHAT_APP')['XZMS']
			        );*/
					
				}
                $this->success('数据已退回！');
            }else{
                $this->error('选择要退回的数据！');
            }
        }elseif ($_POST['dl']) {
            foreach ($_POST['id'] as $key => $value) {
                $w['id']=$value;
//                 $w['state']=array('in','0,-1');
                $data['state'] = -2;
//                 $rr=M('hw003.return','money_')->where($w)->delete();
                $rr=M('hw003.return','money_')->where($w)->save($data);
                R('Return/record',array($value,'删除'));
				{
// 					$xueguan = M('hw003.return','money_')->where(['id'=>$value])->getField('bb');

					
					$data_t = M('hw003.return','money_')->where(['id'=>$value])->getField('bb,school');
					$xueguan = array_keys($data_t)[0];
					$school = array_values($data_t)[0];
					
					$xg = M('user')->where(['is_del'=>0,'school'=>get_school_id($school),'position_id'=>18,'name'=>$xueguan])->getField('wechat_userid');//wechat_userid
					
					if($xg){
						$user[]= $xg;	
					}
			        	
					$del_id[] = $value;
				}
            }
            if($rr)
				{
					
					$user=array_unique($user);
			
			 		//微信通知
			        if(empty($user)){
			        	return;
			        }
			        	
			        //存储一下被通知过的人,方便后期查看
			        $ff=(array)F('weixin_tfgl_jt_sc');
			        $f2=array_merge($ff,$user);
			        F('weixin_tfgl_jt_sc',$f2);
					
					$info='点击可直接进入审核……';
					
					$del_id_str = implode("__", $del_id);
			        $wx= getWechatObj();
			        $wx->sendNewsMsg(
			            [$wx->buildNewsItem("您有退费申请被删除". $del_id_str ,$info,wx_oauth(C('WWW').U('Public/log_wx?urll=Return/add')),'')],
			            ['touser'=>$user],
			            C('WECHAT_APP')['TZTX']
			        );
			        /*$wx->sendTextMsg("特殊优惠有新的动态，请查看",
			            ['touser'=>$user],
			            C('WECHAT_APP')['TZTX']);*/
						
			         /*$wx->sendNewsMsg(
			            [$wx->buildNewsItem("有特殊优惠待审核",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=CoursesApply/examine')),'')],
			            ['touser'=>$user],
			            C('WECHAT_APP')['XZMS']
			        );*/
					$this->success('删除成功！');
				}
//              $this->success('删除成功！');
        }else{
        	
			$foo = M("FooInfo");
			$class2 = $foo->where(['pid'=>17,'is_del'=>array('neq',1)])->field('id,name,`group`')->order('`group`,sort')->select();
			$this->class2=$class2;
			
            $w['state']=4;
            $w['date']=session('date');
            $list=M('hw003.money_return',null)->where($w)->order('id desc')->select();
			
			foreach($list as &$vor){
				foreach($class2 as $cl2){
					if($vor['class2'] == $cl2['id']){
						$vor['class2'] = $cl2['name'];
						break;
					}
				}
				
				$class1 = get_config('COURSE_GROUP');
				foreach($class1 as $key=>$value){
					if((string)$vor['class1'] === (string)$key){
						$vor['class1'] = $value;
						break;
					}
				}
			}
			unset($vor);
			unset($cl2);
			unset($key);
			unset($value);
			
            $this->list=$list;

            //$w['state']=array('egt','5');
            //$list=M('hw003.money_return',null)->where($w)->order('id desc')->select();
            //$this->list2=$list;

            $this -> display('check');

        }
        
    }

//退款确认
    public function check5(){
    	
		$this->assign('gradeList', C('SCHOOL_GRADE'));
		$grade_lst = C('SCHOOL_GRADE');
		$gradelst = array_column($grade_lst,'name','id');
		$this->gradelst = $gradelst;
		
        if($_POST['aax']){
            foreach ($_POST['id'] as $key => $value) {
                $w['id']=$value;
                $d['state']=6;
                $d['time3']=date('Y-m-d');
                $d['kb']=session('user_name');
                $rr=M('hw003.return','money_')->where($w)->save($d);
                //审核记录
                R('Return/record',array($value,'退款确认'));
            }
            if($rr){
                $this->success('退款完成！');
            }else{
                $this->success('选择要确认的条目！');
            }
        }else{
        	
			$foo = M("FooInfo");
			$class2 = $foo->where(['pid'=>17,'is_del'=>array('neq',1)])->field('id,name,`group`')->order('`group`,sort')->select();
			$this->class2=$class2;
			
            $w['state']=5;
            $w['school']=get_school_name();
            $w['date']=session('date');
            $list=M('hw003.money_return',null)->where($w)->order('id desc')->select();
			
			foreach($list as &$vor){
				foreach($class2 as $cl2){
					if($vor['class2'] == $cl2['id']){
						$vor['class2'] = $cl2['name'];
						break;
					}
				}
				
				$class1 = get_config('COURSE_GROUP');
				foreach($class1 as $key=>$value){
					if((string)$vor['class1'] === (string)$key){
						$vor['class1'] = $value;
						break;
					}
				}
			}
			unset($vor);
			unset($cl2);
			unset($key);
			unset($value);
			
            $this->list=$list;

            $w['state']=array('egt','6');
            $list=M('hw003.money_return',null)->where($w)->order('id desc')->select();
			
			foreach($list as &$vor){
				foreach($class2 as $cl2){
					if($vor['class2'] == $cl2['id']){
						$vor['class2'] = $cl2['name'];
						break;
					}
				}
				
				$class1 = get_config('COURSE_GROUP');
				foreach($class1 as $key=>$value){
					if((string)$vor['class1'] === (string)$key){
						$vor['class1'] = $value;
						break;
					}
				}
			}
			unset($vor);
			unset($cl2);
			unset($key);
			unset($value);
			
            $this->list2=$list;

            $this->bt='1';
            $this -> display('check');
        }
    }

    public function all(){
        $w['state']=['neq',-2];
        if(I('param.school'))$w['school']=I('param.school');
        if(I('param.date'))$w['date']=I('param.date');
        if(I('param.state'))$w['state']=I('post.state');
        if(I('param.state')=='all')$w['state']=['neq',-2];
        if(I('param.school'))$w['school']=I('post.school');
        if(I('param.grade'))$w['grade']=I('post.grade');
		
		if(I('param.name')) $w['student'] = I('post.name');
		
		
        if(I('post.import'))$this->import();
        $ww['class']='school';
        $selt=M('hw003.money_sort',null)->where($ww)->select();
        $this->selt=$selt;


		$foo = M("FooInfo");
		$class2 = $foo->where(['pid'=>17,'is_del'=>array('neq',1)])->field('id,name,`group`')->order('`group`,sort')->select();
		$this->class2=$class2;
			
        $w['date']=session('date');
        $list=M('hw003.money_return',null)->where($w)->order('id desc')->select();
		
		foreach($list as &$vor){
				foreach($class2 as $cl2){
					if($vor['class2'] == $cl2['id']){
						$vor['class2'] = $cl2['name'];
						break;
					}
				}
				
				$class1 = get_config('COURSE_GROUP');
				foreach($class1 as $key=>$value){
					if((string)$vor['class1'] === (string)$key){
						$vor['class1'] = $value;
						break;
					}
				}
			}
			unset($vor);
			unset($cl2);
			unset($key);
			unset($value);
			
        $this->list=$list;
		
		$this->assign('gradeList', C('SCHOOL_GRADE'));
		$grade_lst = C('SCHOOL_GRADE');
		$gradelst = array_column($grade_lst,'name','id');
		$this->gradelst = $gradelst;
		
        $this->display();
    }

    public function import(){

        if($_POST['school'])$w['school']=$_POST['school'];
        if($_POST['date'])$w['date']=$_POST['date'];
        if($_POST['state']){
            $w['state']=I('post.state');
            if(I('post.state')=='all')$w['state']=['BETWEEN','-1,6'];
        }else{
            $w['state']=6;
        }
		
		$foo = M("FooInfo");
		$class2 = $foo->where(['pid'=>17,'is_del'=>array('neq',1)])->field('id,name,`group`')->order('`group`,sort')->select();
		$this->class2=$class2;
			
        $mm=M('hw003.return','money_')->where($w)->order('id desc')->select();

		foreach($mm as &$vor){
				foreach($class2 as $cl2){
					if($vor['class2'] == $cl2['id']){
						$vor['class2'] = $cl2['name'];
						break;
					}
				}
				
				$class1 = get_config('COURSE_GROUP');
				foreach($class1 as $key=>$value){
					if((string)$vor['class1'] === (string)$key){
						$vor['class1'] = $value;
						break;
					}
				}
			}
			unset($vor);
			unset($cl2);
			unset($key);
			unset($value);
			
        $output = "<HTML>";
        $output .= "<HEAD>";
        $output .= "<META http-equiv=Content-Type content=\"text/html; charset=utf-8\">";
        $output .= "</HEAD>";
        $output .= "<BODY>";
        $output .= "<TABLE BORDER=1>";
        /* $output .= "<tr><td>序号</td><td>状态</td><td>期次</td><td>校区</td><td>学员姓名</td><td>年级</td><td>联系电话</td><td>教学主任</td><td>学习管理师</td><td>课程类型</td><td>课程类型2</td><td>缴费时间</td><td>交费总额</td><td>缴费课时数</td><td>已上课时</td><td>科目/教师</td><td>剩余计算</td><td>应退金额</td><td>退费原因</td><td>校区反馈</td><td>总部沟通</td><td>部门审核时间</td><td>办理人</td><td>申请时间</td><td>退费人</td><td>退费时间</td><td>备注</td></tr>"; */
        $output .= "<tr><td>序号</td><td>状态</td><td>期次</td><td>校区</td><td>学员姓名</td><td>年级</td><td>联系电话</td><td>教学主任</td><td>学习管理师</td><td>课程类型</td><td>首次缴费日期</td><td>退出费用缴费时间</td><td>交费总额</td><td>缴费课时数</td><td>已上课时</td><td>科目/教师</td><td>剩余计算</td><td>应退金额</td><td>退费原因</td><td>校区反馈</td><td>总部沟通</td><td>部门审核时间</td><td>办理人</td><td>申请时间</td><td>退费人</td><td>财务审批时间</td><td>退费时间</td><td>备注</td></tr>";
            foreach ($mm as $m) {
                switch ($m['state']) {
                    case '0':
                        $state='退回修改';
                        break;
                    case '1':
                        $state='财务确认';
                        break;
                    case '2':
                        $state='校区审核';
                        break;
                    case '3':
                        $state='部门审核';
                        break;
                    case '4':
                        $state='财务审批';
                        break;
                    case '5':
                        $state='退款确认';
                        break;
                    case '6':
                        $state='退款完成';
                        break;
                }
				
				switch($m['grade']){
					case 22: $grade = "高三";break;
					case 21: $grade = "高二";break;
					case 20: $grade = "高一";break;
					case 50: $grade = "九年级";break;
					case 40: $grade = "八年级";break;
					case 39: $grade = "七年级";break;	
					case 38: $grade = "六年级";break;
					case 93: $grade = "五年级";break;	
					case 100: $grade = "四年级";break;	
					case 101: $grade = "三年级";break;
					case 102: $grade = "二年级";break;	
					case 103: $grade = "一年级";break;
					case 217: $grade = "其他";break;
				}
//          $output .= "<tr><td>".$m['id']."</td><td>".$state."</td><td>".$m['date']."</td><td>".$m['school']."</td><td>".$m['student']."</td><td>".$m['grade']."</td><td>".$m['tel']."</td><td>".$m['aa']."</td><td>".$m['bb']."</td><td>".$m['class']."</td><td>".$m['timed']."</td><td>".$m['ze']."</td><td>".$m['count']."</td><td>".$m['countd']."</td><td>".$m['km']."</td><td>".$m['sy']."</td><td>".$m['je']."</td><td>".$m['why1']."</td><td>".$m['why2']."</td><td>".$m['why3']."</td><td>".$m['time3']."</td><td>".$m['ka']."</td><td>".$m['time1']."</td><td>".$m['kb']."</td><td>".$m['time2']."</td><td>".$m['other']."</td></tr>";

//			$output .= "<tr><td>".$m['id']."</td><td>".$state."</td><td>".$m['date']."</td><td>".$m['school']."</td><td>".$m['student']."</td><td>".$grade."</td><td>".$m['tel']."</td><td>".$m['aa']."</td><td>".$m['bb']."</td><td>".$m['class']."</td><td>".$m['timed']."</td><td>".$m['ze']."</td><td>".$m['count']."</td><td>".$m['countd']."</td><td>".$m['km']."</td><td>".$m['sy']."</td><td>".$m['je']."</td><td>".$m['why1']."</td><td>".$m['why2']."</td><td>".$m['why3']."</td><td>".$m['time3']."</td><td>".$m['ka']."</td><td>".$m['time1']."</td><td>".$m['kb']."</td><td>".$m['time2']."</td><td>".$m['other']."</td></tr>"; edit by zhangxm at 2016-08-05 升级课程类型代码

			$output .= "<tr><td>".$m['id']."</td><td>".$state."</td><td>".$m['date']."</td><td>".$m['school']."</td><td>".$m['student']."</td><td>".$grade."</td><td>".$m['tel']."</td><td>".$m['aa']."</td><td>".$m['bb']."</td><td>".$m['class1']."=>".$m['class2']."</td><td>".$m['first_time']."</td><td>".$m['timed']."</td><td>".$m['ze']."</td><td>".$m['count']."</td><td>".$m['countd']."</td><td>".$m['km']."</td><td>".$m['sy']."</td><td>".$m['je']."</td><td>".$m['why1']."</td><td>".$m['why2']."</td><td>".$m['why3']."</td><td>".$m['time3']."</td><td>".$m['ka']."</td><td>".$m['time1']."</td><td>".$m['kb']."</td><td>" . $m['jtsp_time'] . "</td><td>".$m['time2']."</td><td>".$m['other']."</td></tr>";
            }
        $output .= "</TABLE>";
        $output .= "</BODY>";
        $output .= "</HTML>";

        $filename='财务系统退费明细导出表'.date('Y-m-d');
        header("Content-type:application/msexcel");
        header("Content-disposition: attachment; filename=$filename.xls");
        header("Cache-control: private");
        header("Pragma: private");
        print($output);
    }

    /**
	 * 根据月份、课程类型统计出各个校区的退费金额
	 * $month= '2016-04'
	 * $classtype =1 代表常规退费，$classtype =2 非常规退费
	 * $yjtype 新签、续签、转介绍、激活
	 * 
	 */
//  public function getReturnMonthRecords($month,$classtype,$yjtype){
    public function getReturnMonthRecords($month,$yjtype){
    		
    	$return = M('hw003.return','money_');
		//设置期次
		if($month){
			$w['re.date'] = $month;	
		}
		//设置业绩类型
		if($yjtype){
			$w['re.yj_type'] = $yjtype;
		}
		
		$w['state'] = 6; //退款确认的数据；
		$w['info.pid'] = 15; //查询校区字典数据
		$w['info.is_del'] = array('neq',1); //去除无效的校区数据；
		$w['info.id'] = array('neq',174);//排除长颈鹿项目校区；
		
		//常规与非常规项目退费筛选；
		/*if($classtype == 1){
			$w['re.class1'] = array('not in', '5,7,8,9,10');
		}else{
			$w['re.class1'] = array('in', '5,7,8,9,10');
		}*/
		
		$w['re.class1'] = array('not in', '5,7,8,9,10'); //常规
		
    	$result1 = $return->alias('re')->join('hongwen_oa.oa_foo_info as info on re.school = info.name ')->where($w)->group('info.id')->order('info.id')->getField("info.id as school,round(sum(re.je),2) as money");
		
		$w['re.class1'] = array('in', '5,7,8,9,10'); //非常规
    	
    	$result2 = $return->alias('re')->join('hongwen_oa.oa_foo_info as info on re.school = info.name ')->where($w)->group('info.id')->order('info.id')->getField("info.id as school,round(sum(re.je),2) as money");
		
		$result[0] = $result1;
		$result[1] = $result2;
		
		return $result;
		
    }
	
    //审核记录
    public function record($id,$info){
        $w['id']=$id;
        $inf=M('hw003.return','money_')->where($w)->find();
        $d['record']=$inf['record'].'<'.$info.date('Y-m-d H:i:s').session('user_name').'>';
        M('hw003.return','money_')->where($w)->save($d);
    }


    //校区课时确认
    public function classd($school=null){
        try{
        	$md=M('hw001.class',null);

        //$info=$md->find($_GET['qr']);
        if($_GET['qr']){
        	$info=$md->where("id=" . $_GET['qr'])->find();
        }else{
        	$info=$md->find();
        }
        	
        if($_GET['qr']){
            if($info['course_id']){
                if($info['state']==1){
                    if(!$this->confirmCourse($info['course_id'],$info['id'],$info['count']))
                    $this->ajaxReturn('课程剩余课时不足');
                }
                $md->where(array('id'=>(int)$_GET['qr'],'school'=>get_school_name()))->setField('cwqr',session('user_name'));                
            }else{
                $md->where(array('id'=>(int)$_GET['qr'],'school'=>get_school_name()))->setField('cwqr',session('user_name'));                
            }
            $this->ajaxReturn('ok');
        }
        if($_GET['delt']){
            $info=$md->delete($_GET['delt']);
            if($info){
                $this->ajaxReturn('ok');
            }else{
                $this->ajaxReturn('删除失败');
            }
        }
        if($_POST['save']){
            if(!(int)$_POST['course_id'])unset($_POST['course_id']);
            if(!$_POST['why'])unset($_POST['why']);
			
			//edit by zhangxm 监控财务数据修改，进而排查调课排课冲突问题；
			$classR= $md->where("id=". I('id'))->find();
			if($classR){
				$temp_str = "财务修改之前的数据如下：" . $classR['course_id'] . "_" . $classR['std_id'] . "_"  . $classR['stuid'] . "_" . $classR['time1'] . "_" . $classR['time2'] . "_" . session('user_name');
			}
			
            $md->create();
			
			if($temp_str){
				$md->reason = $temp_str;
			}
			
            if((int)$_POST['course_id']){
                die("暂时不支持修改");
                $course=M('course')->find(I('course_id'));
                if($course){
                    $md->course_id=I('course_id');
                    $md->std_id=$course['std_id'];
                    $md->stuid=M('hw001.student',null)->where(['std_id'=>$course['std_id']])->getField('id');
                }
            }
            
            if($_POST['count']!=(strtotime(date('Y-m-d ').$_POST['time2'].':00')-strtotime(date('Y-m-d ').$_POST['time1'].':00'))/3600){
            	$this->error('课时有错误，请重新修改……');
            }
            
				
			/*if($_POST['count']!=(strtotime(date('Y-m-d ').$_POST['time2'].':00')-strtotime(date('Y-m-d ').$_POST['time1'].':00'))/3600)
				$this->error('课时有错误，请重新修改……');*/
			
	            $md->save();
        }
//      $w['school']=(get_school_name($school)=='阜新校区')?['in','阜新校区,阜新二部']:get_school_name($school);
		$w['school']=(get_school_name($school)=='阜新实验校区')?['in','阜新实验校区,阜新二部']:get_school_name($school);
        $w['timee']=date('Y-m-d');
        if(I('param.date'))$w['timee']=I('param.date');
        $m=$md->where($w)->order('time1,teacher')->select();
        foreach ($m as $k=>$v) {
            $m[$k]['student']=M('hw001.student',null)->where(array('id'=>$v['stuid']))->getField('name');
            if($v['stuid']==88888)$m[$k]['student']='@试听';
        }

        $this->date=$w['timee'];
        $this->list=$m;
        $this->display();
        }catch(Exception $e){
        	echo $e;
        }
			
        
    }

    /**
    * 确认一堂课！财务确认后调用
    * @param  int    $courseId 订单中课程的Id
    * @param  int    $classId  排课表的Id
    * @param  float  $hour     课时数
    * @return boolen 是否操作成功
    */
    public function confirmCourse($courseId, $classId, $hour) {
        $Course = M('Course');

        $course = $Course->find($courseId);
        if($course['used_hour'] + $hour > $course['hour'] + $course['ext_hour'])return false;
        // 如果这课上完了，，状态置为已结课
        if(($course['used_hour'] + $hour) == ($course['hour'] + $course['ext_hour'])){
            $Course->save([
                'id' => $courseId,
                'state' => 300, // 结课状态值
            ]);
        } elseif ($course['state'] == 300 && $course['used_hour'] + $hour < $course['hour'] + $course['ext_hour']) {
        // 如果 没上完，但是状态已结课，就修正为正常状态
            $Course->save([
                'id' => $courseId,
                'state' => 200, // 正常状态值
            ]);
        }

        //如果这节课在记录中已近被确认过就直接返回，防止重复确认问题
        if(strpos($course['log'],'>'.$classId)!==false)return true;

        if ($Course->save([
            'id'        => $courseId,
            'used_hour' => ['exp', '`used_hour` + '.$hour],
            'log'       => ['exp', "CONCAT_WS('>', `log`, '{$classId}')"], // 记录确认课
        ]))return true;

        return false;
    }

}
