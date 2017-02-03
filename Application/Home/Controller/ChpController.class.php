<?php
namespace Home\Controller;

class ChpController extends HomeController {

	private $pageNumber=0;
	private $pageCount=10;
	
	public function index(){
		
		$mod = M('chpInfo');
		$condition['is_del'] = 1; //正常记录
		
		$dic = M("chpDictionary")->where(['dept_id'=>session('dept_id'),'leaf'=>1])->getField('id',true);
		
		$condition['item1|item2'] = array('in',$dic);
		
        $list=$mod->where($condition)->limit($pageNumber,$pageCount)->select();
		
       
        foreach ($list as &$v) {//跟踪人
        	if($v['record_type'] == 1){
        		$v['record_type'] = "积分";
        	}else if($v['record_type'] == 2){
        		$v['record_type'] = "兑换";
        	}
        	 
        	if($v['flag'] == 1){
        		$v['flag'] = "申请积分兑换";
        	}else if($v['flag'] == 2){
        		$v['flag'] = "兑换完成";
        	}else{
        		$v['flag'] = null;
        	}
        	 
        	$v['scheme'] = M('chpDictionary')->where(['id'=>$v['scheme']])->getField('name');
        	$v['item1'] = M('chpDictionary')->where(['id'=>$v['item1']])->getField('name');
        	if($v['item2']){
        		$v['item2'] = M('chpDictionary')->where(['id'=>$v['item2']])->getField('name');
        	}else{
        		$v['item2'] = null;
        	}
        	 
        	$v['user_id'] = M('hw003.person_all',null)->where(['id'=>$v['user_id']])->getField('name');
        	 
        	$v['creator']=M('user')->where(['id'=>$v['creator']])->getField('name');
        
        	$v['updator'] = M('user')->where(['id'=>$v['updator']])->getField('name');
        	 
        }
        
        $this->list=$list;
        $this->maxCount=$list?count($list):1;//显示全部数据
		
        //集团所有员工信息列表
        $user_list = M('hw003.person_all',null)->where("state=1")->group("school,position,name")->getField('id,school,position,name');
        
        $this->user_list = $user_list;
        
        //CHP积分方案列表
        $scheme_list = M('chpDictionary')->where("pid=0 and is_del=1 and `group` = 1 and dept_id = " . session('dept_id'))->order('sort')->select();
		
        $this->scheme_list = $scheme_list;
        
		$this->display();
	}
	
	//获取相关方案下所属积分项
	public  function getItems(){
		$pid = isset($_POST['pid'])?$_POST['pid']:null;
		$item_list = M('chpDictionary')->where(['pid'=>$pid,'is_del'=>1])->order('sort')->select();
		
		if(IS_AJAX && $item_list){
			// 发送给页面的数据
			$this->ajaxReturn([
			
					'state'=>'ok',//查询结果
					'data'=>$item_list
			
			]);
		}else{
			return null;
		}
		
	}
	
	
	//获取单条或多条信息
    public function pager($id=0){
            
    	$mod=M('chpInfo');
        
    
        if(IS_AJAX && I('get.pageCount')){
            $page=I('get.pageNumber');//请求第几条开始
            $page_count=I('get.pageCount');//一页多少条记录

            $condition=I('post.');//获取json查询条件转换成php数组
            
            $condition['is_del'] = 1; //有效数据; 
            
            if($condition['keyword']){
            	$condition['descp'] = array('like', "%" . $condition['keyword'] . "%");
            }
            	
			
			if($condition['begin'] && $condition['end']){
				$condition['worth'] = array('between',array($condition['begin'],$condition['end']));
			}else if($condition['begin']){
				$condition['worth'] = array('egt',$condition['begin']);
			}else if($condition['end']){
				$condition['worth'] = array('elt',$condition['end']);
			}
			
			
			if($condition['beginDate'] && $condition['endDate']){
				$condition['exchange_time'] = array('between',array($condition['beginDate'],$condition['endDate']));
			}else if($condition['beginDate']){
				$condition['exchange_time'] = array('egt',$condition['beginDate']);
			}else if($condition['endDate']){
				$condition['exchange_time'] = array('elt',$condition['endDate']);
			}
			
			
            array_empty_delt($condition);
    		$count=$mod->where($condition)->count();//满足条件的记录总数
    		$data=$mod->where($condition)->limit($page,$page_count)->order('create_time desc,update_time desc')->select();//获取到数据
        }

        foreach ($data as &$v) {//跟踪人
        	if($v['record_type'] == 1){
        		$v['record_type'] = "积分";
        	}else if($v['record_type'] == 2){
        		$v['record_type'] = "兑换";
        		$v['worth'] = -$v['worth']; 
        	}
        	
        	if($v['flag'] == 1){
        		$v['flag'] = "申请积分兑换";
        	}else if($v['flag'] == 2){
        		$v['flag'] = "兑换完成";
        	}else{
        		$v['flag'] = null;
        	}
        	
        	$v['scheme'] = M('chpDictionary')->where(['id'=>$v['scheme']])->getField('name');
        	$v['item1'] = M('chpDictionary')->where(['id'=>$v['item1']])->getField('name');
        	if($v['item2']){
        		$v['item2'] = M('chpDictionary')->where(['id'=>$v['item2']])->getField('name');
        	}else{
        		$v['item2'] = null;
        	}
        	
        	$v['user_id'] = M('hw003.person_all',null)->where(['id'=>$v['user_id']])->getField('name');
        	
            $v['creator']=M('user')->where(['id'=>$v['creator']])->getField('name');
            
			$v['updator'] = M('user')->where(['id'=>$v['updator']])->getField('name');
           
        }
        
        
        if($id){
        	$data[0]=$mod->find($id);//获取到数据
        	$count=1;
        }

		// 发送给页面的数据
		$this->ajaxReturn([

			'state'=>'ok',//查询结果
			'maxCount'=>$count,//查询到数据库有多少条满足条件记录
			'data'=>$data

		  ]);
    
    }

	public function addChpInfo(){
		$mod=M('chpInfo');
        $mod->create();
        if(I('post.id')){
        	$mod->updator=session('auth_id');
        	$mod->update_time = date('Y-m-d H:i:s');
            if($mod->save()){
            	
            	{
            		$user[]=M('user')->where(['is_del'=>0,'id'=>session('auth_id')])->getField('wechat_userid');//wechat_userid
            			
            		$user=array_unique($user);
            			
            		//微信通知
            		if(empty($user)){
            			return;
            		}
            			
            		$info='点击可直接进入查看……';
            			
            		$wx= getWechatObj();
            		$wx->sendNewsMsg(
            				[$wx->buildNewsItem("请注意：您有一项积分活动被更新！",$info,wx_oauth(C('WWW').'/oa_old/weixin.php/chp/chp'),'')],
            				['touser'=>$user],
            				C('WECHAT_APP')['TZTX']
            				);
            			
            	}
            	
            	$this->ajaxReturn('ok');
			}
        }else{
            $mod->record_type=1;
            $mod->creator=session('auth_id');
            $mod->create_time = date('Y-m-d H:i:s');
            $mod->is_del = 1;
            $condition['descp']=I('post.descp');
            $repeat=$mod->where($condition)->find();
            if($repeat){
            	
            	$this->ajaxReturn('录入有重复……   ：    积分描述： ' . $repeat['descp']);
			}
	    	if($mod->add()) {
	    		
	    		
	    		{
	    			$user[]=M('user')->where(['is_del'=>0,'id'=>session('auth_id')])->getField('wechat_userid');//wechat_userid
	    			 
	    			$user=array_unique($user);
	    				
	    			//微信通知
	    			if(empty($user)){
	    				return;
	    			}
	    				
	    			$info='点击可直接进入查看……';
	    				
	    			$wx= getWechatObj();
	    			$wx->sendNewsMsg(
	    					[$wx->buildNewsItem("恭喜您，您又获得了积分",$info,wx_oauth(C('WWW').'/oa_old/weixin.php/chp/chp'),'')],
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
	    		
	    		$this->ajaxReturn('ok');
			}
        }
	}
	
	
	public function delChpInfo(){
		$mod=M('chpInfo');
		$mod->create();
		if(I('post.id')){
			
			$condition['id']=I('post.id');
			$repeat=$mod->where($condition)->find();
			if($repeat){
				$mod->updator=session('auth_id');
				$mod->update_time = date('Y-m-d H:i:s');
				$mod->is_del = 2;
				if($mod->save()){
					
					{
						$user[]=M('user')->where(['is_del'=>0,'id'=>session('auth_id')])->getField('wechat_userid');//wechat_userid
						 
						$user=array_unique($user);
						 
						//微信通知
						if(empty($user)){
							return;
						}
						 
						$info='点击可直接进入查看……';
						 
						$wx= getWechatObj();
						$wx->sendNewsMsg(
								[$wx->buildNewsItem("请注意：您有一项积分被删除！",$info,wx_oauth(C('WWW').'/oa_old/weixin.php/chp/chp'),'')],
								['touser'=>$user],
								C('WECHAT_APP')['TZTX']
								);
					
					}
					
					$this->ajaxReturn('ok');
				}
			}
			
		}else{
			$this->ajaxReturn('该积分记录不存在，请于系统管理员联系！');
		}
	}
	
	
	public function doReceiveInfo(){
		$mod=M('chpInfo');
		$mod->create();
		if(I('post.id')){
				
			$condition['id']=I('post.id');
			$repeat=$mod->where($condition)->find();
			if($repeat && $repeat['flag'] == 1){
				$mod->updator=session('auth_id');
				$mod->update_time = date('Y-m-d H:i:s');
				$mod->flag = 2;
				if($mod->save()){
					
					{
						$user[]=M('user')->where(['is_del'=>0,'id'=>session('auth_id')])->getField('wechat_userid');//wechat_userid
							
						$user=array_unique($user);
							
						//微信通知
						if(empty($user)){
							return;
						}
							
						$info='点击可直接进入查看……';
							
						$wx= getWechatObj();
						$wx->sendNewsMsg(
								[$wx->buildNewsItem("恭喜您：您有一份积分兑换已通过！",$info,wx_oauth(C('WWW').'/oa_old/weixin.php/chp/chp'),'')],
								['touser'=>$user],
								C('WECHAT_APP')['TZTX']
								);
							
					}
					$this->ajaxReturn('ok');
				}
			}else{
				$this->ajaxReturn('非积分兑换申请记录，请勿进行积分兑换确认操作！');
			}
				
		}else{
			$this->ajaxReturn('该积分记录不存在，请于系统管理员联系！');
		}
	}
	
/**
数据导入
*/
    public function import(){
        if(IS_POST){
            if($_FILES['file']['type']!='application/vnd.ms-excel'||!strstr($_FILES['file']['name'],'.csv')){
                $this->success('文件格式不正确，<br/>请使用提供的模版导入！'); die;
            }
            // var_dump($_FILES['file']);die;
            $file = fopen($_FILES['file']['tmp_name'], "r");
            while ($data = fgetcsv($file)) {
                $list[] = $data;
             }
            if($list[0][0]!='卡片编号')$zz=true;
            if(count($list[0])!=5)die('数据格式出错');
            fclose($file);
             foreach ($list as $k => $v) {
                if($k<1)continue;
                if($zz)
                foreach ($v as &$val) {
                    $val=$this->characett($val);
                }

				switch(trim($v[1])){
					case "黑卡": $v[1] = '01';break;
					case "金卡": $v[1] = '02';break;
					case "银卡": $v[1] = '03';break;
				}
				
                $dat[]=[
                    'card_no'=>$v[0],
                    'card_state'=>'02',
                    'card_type'=> $v[1],
                    'card_value'=> $v[2],
                    'card_owner'=> $v[3],
                    'owner_tel'=>$v[4],
                    'card_school' =>session('school_id'),
                    'del'=>0,
                    'creator'=>session('auth_id')
                   ];
             }
             // 数据去内部重复
             foreach ($dat as $k=>$v) {
                if($v['card_no'])
                if(in_array($v['card_no'],$tmp)){
                    unset($dat[$k]);
                }else{
                    $tmp[]=$v['card_no'];
                }
             }
            // 数据库里查找是否有重复
            $m=M('vipcard')->field('card_no')->distinct(true)->select();
            $m=array_column($m,'card_no');
           
            foreach ($dat as $k => $v) {
                if(in_array($v['card_no'],$m))unset($dat[$k]);
            }
            // var_dump($dat);die;
            M('vipcard')->addAll($dat);
            $this->success('导入成功了'.count($dat).'条记录');
        }
    }

    //输出导入使用的模版文件
    public function import_template(){

        $output='卡片编号,卡片类型,卡片价值,持卡人,持卡人电话';
        header("Content-type:application/vnd.ms-excel");
        header("Content-disposition: attachment;filename=VIP卡数据导入模版文件.csv");
        header("Cache-control: private");
        header("Pragma: private");
        print($output);

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
	
}