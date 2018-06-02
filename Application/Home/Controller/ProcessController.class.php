<?php
namespace Home\Controller;

class ProcessController extends HomeController {

	//把校区列表、科目类别输出到前段模版
	public function _initialize(){
        parent::_initialize();
		
		$node = M("Department");
		$menu = array();
		$menu = $node->where(["is_del"=>0]) -> field('id,pid,name,is_del,dept_grade') -> order('sort asc') -> select();
		$this->department = $menu;
		$tree = list_to_tree($menu);
		$this -> assign('menu', popup_tree_menu($tree));
		
	}
/**
计划申请
*/
    public function index(){
        $this -> display();
    }
/**
资金申请
*/
	public function apply(){
        $this -> display('index');
	}
/**
报销申请
*/
	public function cost(){
        $this -> display('index');
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
	 * 各个校区浏览自己校区的财务数据
	 */
	public function export_list(){
	    $this -> display('export_list');
	}
	
	
	public function getSubjectLst($id){
	    $model = M('DeptSubject');
	    $vo = $model->where(['dept_id'=>$id,'is_del'=>0])->order('sort')->select();
	    
	    foreach($vo as &$v){
	        $v['dept_name'] = M('Department')->where(['id'=>$v['dept_id']])->getField('name');
	        $v['check_dept_name'] = M('Department')->where(['id'=>$v['check_dept_id']])->getField('name');
	        $v['subject_name'] = get_config('FINANCE_SUBJECT')[$v['subject_id']];
	        $v['remark'] = $v['remark'];
	        $v['is_del_name'] = $v['is_del']==1?'禁用':'启用';
	    }
	    if (IS_AJAX) {
	        if ($vo !== false) {// 读取成功
	            $return['data'] = $vo;
	            $return['state'] = 'ok';
	            $return['info'] = "读取成功";
	            $return['total'] = count($vo);
	            $this -> ajaxReturn($return);
	        } else {
	            $return['status'] = 0;
	            $return['info'] = "读取错误";
	            $this -> ajaxReturn($return);
	        }
	    }
	    $this -> assign('vo', $vo);
	    $this -> display();
	    return $vo;
	}
	
/**
####################################增删改查
*/
/*
*申请添加、修改
*/
	public function write(){
		array_empty_delt($_POST);
		$mod=M('Process');
		$mod->create();
		
		
		if(I('post.subject')){
			
			$dept_rule = M('DeptRule')->where(['dept_id'=>I('post.dept'),'is_del'=>0])->select();
			$level = 0;
			$all_phase_name = '';
			foreach($dept_rule as $key=>$vo){
			    
			    if($vo['bottom'] <= (I('post.unit_price') * I('post.count')) && (I('post.unit_price') * I('post.count'))<$vo['top']){
			        $level = $vo['value'];
			    }
			}
			
			if($level){
			    $mod->all_phase = $level;
			}
			$lwl = false;
			$rank_rule = M('ProcessRule')->where(['dept_id'=>I('post.dept'),'phase'=>array('elt',$level),'is_del'=>0])->order('phase')->select();
			foreach($rank_rule as $mo=>$obj){
			    $all_phase_name .= $obj['rank_name'] . '->';
			    if($obj['rank_id'] == 90){
			        $lwl = true;
			    }
			}
			if((I('post.unit_price') * I('post.count'))>=5000 && !$lwl){
			    $all_phase_name .= "集团总裁级->资金部主管->财务中心总裁级";
			}else{
			    $all_phase_name .= "资金部主管->财务中心总裁级";
			}
			
			$mod->all_phase_name = $all_phase_name;
			//$all_phase_name = mb_substr($all_phase_name,0,(count($all_phase_name)-3));
			
		}

		//修改
		if(I('post.id')){
			$mod->save();
			//处理修改造成的审核部门丢失问题
			/* if(I('post.subject')){
				$info=$mod->find(I('post.id'));
				if(in_array($info['state'],[20,110])&&$info['dept1']==0)D('Apply')->check(1,[I('post.id')]);
				if(in_array($info['state'],[5,55,95]))D('Apply')->check(1,[I('post.id')]);
			} */
			D('Process')->save_check([['id'=>I('post.id')]],2);
			$this->ajaxReturn('更新成功');
		}

		$mod->apply_dept=session('new_dept_id');
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
		    if(D('Process')->check(I('post.type'),I('post.data')['id'],I('post.why'))){
		        $this->ajaxReturn('ok');
		    }else{
		        $this->ajaxReturn('审核出错');
		    }
		}
	}

/*
*页面数据列表
*/
    public function ajax_list(){
    	if(IS_AJAX){
    		$w=I('get.search');
    		array_empty_delt($w);
    		if($w['date1'])$w['create_time']=['between',[$w['date1'].' 00:00:00',$w['date2'].' 00:00:00']];
    		$this->get_condition($w);//附加浏览的查询条件
    		$data=M('process')->where($w)->order('state asc,money_time asc,dept asc,subject asc,id desc')->field('record',true)->limit(I('get.offset'),I('get.count'))->select();
    		$total=M('process')->where($w)->count();
    		$count=$this->get_count($w);
    		$this->get_edit($data,$w);//设置页面修改权限
    		$this->ajaxReturn(['state'=>'ok','data'=>$data,'total'=>$total,'count'=>$count]);
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
    		$data=M('process')->where($w)->order('state asc,money_time asc,dept asc,subject asc,id desc')->field('record',true)->limit(I('get.offset'),I('get.count'))->select();
    		$total=M('process')->where($w)->count();
    		$count=$this->get_count($w);
    		
    		$this->ajaxReturn(['state'=>'ok','data'=>$data,'total'=>$total,'count'=>$count]);
    	}else{
    		$this->ajaxReturn(['state'=>'error','info'=>'没有查询到数据']);
    	}
    }
    
    
    /*
     *各个校区自己申请的财务页面数据列表
     */
    public function ajax_export_list(){
        if(IS_AJAX){
            $w=I('get.search');
            array_empty_delt($w);
            if($w['info']) $w['info'] = array('like','%' . $w['info'] . '%');
            if($w['date1'])$w['create_time']=['between',[$w['date1'].' 00:00:00',$w['date2'].' 00:00:00']];
            
            if(session('school_id')!=0){
                $w['school'] = session('school_id');
            }
            
            $data=M('process')->where($w)->order('state asc,money_time asc,dept asc,subject asc,id desc')->field('record',true)->limit(I('get.offset'),I('get.count'))->select();
            $total=M('process')->where($w)->count();
            $count=$this->get_count($w);
            
            $this->ajaxReturn(['state'=>'ok','data'=>$data,'total'=>$total,'count'=>$count]);
        }else{
            $this->ajaxReturn(['state'=>'error','info'=>'没有查询到数据']);
        }
    }
    
    //计算统计各阶段的金额统计
    private function get_count($w){
    	$data=M('process')->where($w)->getField('id,state,unit_price,count,money');
    	foreach ($data as $v) {
    		if($v['state']>=0&&$v['state']<=120)$count1+=$v['unit_price']*$v['count'];//计划申请
    		if($v['state']>120&&$v['state']<=150)$count2+=$v['unit_price']*$v['count'];//资金申请
    		if($v['state']>=210&&$v['state']<=280){
    			$count3+=$v['unit_price']*$v['count'];//报销申请
    			$count4+=$v['money'];//实际支出
    		}
    		if($v['state']>=0&&$v['state']<=290)$count+=$v['unit_price']*$v['count'];//合计
    	}
    	return $count?['【合计：'.round($count,2).'元】','【计划：'.round($count1,2).'元，资金：'.round($count2,2).'元，报销：'.round($count3,2).'元，实际：'.round($count4,2).'元，差额：'.round($count3-$count4,2).'元】']:[0,0];
    }

	//获取科目类别涉及的审核部门
	private function get_check_dept($subjectid){
		foreach (($this->subject) as $v) {
			$name=$v['dept_name'].'+'.$v['dept_name2'];
			if($v['id']==$subjectid)return ['dept1'=>$v['dept_id'],'dept2'=>$v['dept_id2'],'dept_name'=>$name];
		}
		return ['dept1'=>0,'dept2'=>0,'dept_name'=>''];
	}

	//浏览数据的权限管理
	private function get_condition(&$w){
		$w['is_del']=0;
		if($w['stage']!=5)unset($w['state']);
		
		$w['add_user'] = session('auth_id');
		//==计划申请阶段
		if($w['stage']==1){
			$w['state']=['between','0,110'];
		}
		//==资金申请阶段
		if($w['stage']==2){
			$w['state']=['between','120,140'];
		}
		//==报销申请阶段
		if($w['stage']==3){
			$w['state']=['between','150,300'];
		}

		//==========申请审核
		if($w['stage']==4){
			unset($w['add_user']);
			
			
			$w['dept']=session('new_dept_id');
			$w['state'] = array('in',[session('new_rank_id'),(200+session('new_rank_id'))]);
			
			//集团总裁李文龙
			if(session('new_position_id') == 1){
			    unset($w['state']);
			    unset($w['dept']);
			    $w['state'] = array('in',[90,290]);
			}
			
			//集团财务中心总裁
			if(session('new_position_id') == 48){
			    unset($w['state']);
			    unset($w['dept']);
			    $subjects = M('PostSubject')->getField('post_id');
			    if(count($subjects)>0){
			        $w['state'] = array('in',[110,130,300]);
			        $w['state'] = 60;
			        $w['dept'] = 1;//财务中心部门ID暂待定
			    }else{
			        $w['state'] = array('in',[100,110,130,300]);
			        $w['state'] = 60;
			        $w['dept'] = 1;//财务中心部门ID暂待定
			    }
			    
			}
			
			
			//如果是财务主管岗位，则要对相应的科目进行筛选
			if(in_array(session('new_position_id'),[49,51,53])){
			    $sub_lst = M('PostSubject')->where(['post_id'=>session('new_position_id')])->getField('subject_id',true);
			    if(count($sub_lst)>0){
			        $w['subject'] = array('in',$sub_lst);
			        //集团财务资金主管 王丽丽
			        if(session('new_position_id') == 51){
			            unset($w['state']);
			            unset($w['dept']);
			            $w['state']= array('in',[100,140]);
			        }else{
			            $w['state'] = 100;
			            $w['phase'] = 8;
			        }
			    }
			    
			}
		}
		if($w['stage']==5){
			$w['state']=$w['state']?:['lt',400];
			//if((get_school_name()!='集团'))$w['state']=888;
		}
		if(!in_array($w['stage'],[0,1,2,3,4,5]))$w['state']=888;//没有找到符合条件的让其查询不到信息
	}

	//设置数据到页面的修改权限，$v['edit']，1允许，0不允许
	private function get_edit(&$data,$w){
		foreach ($data as &$v) {
			$v['edit']=0;
			$v['dept_name'] = get_department_name($this->department,$v['dept']);
			$v['phase_name'] = $v['phase']==0?'待提交':M('ProcessRule')->where(['dept'=>$v[dept],'phase'=>$v['phase']])->getField('rank_name');
			$v['state_name'] = get_config('FINANCE_PROCESS_NODE')[$v['state']]; 
			$v['apply_dept_name'] = get_department_name($this->department,$v['apply_dept']);
			$v['belong_dept_name'] = get_department_name($this->department,$v['belong_apply_dept']);
			$v['receive_dept_name'] = get_department_name($this->department,$v['receive_dept']);
			$v['subject_name'] = get_config('FINANCE_SUBJECT')[$v['subject']];
			
			if(in_array($w['stage'],[1,2,3])){
			    
			    
			    if($w['stage'] == 1 && $v['add_user'] == session('auth_id')){
			        if(($v['state'] === 0)){
			            $v['edit'] = 1;
			        }else{
			            $v['edit'] = 0;
			        }
			    }
			    
			    if(strpos($v['state'],'5') !== false){
			        $v['edit']=1;
			    }
			    
			    if($w['stage']==2&&$v['state']==120){
			        $v['edit']=1;
			    }
				// 一些环节的特殊处理，stage 1计划申请阶段，2资金计划申请阶段，3资金报销申请阶段，4申请审核，5数据管理
				if($w['stage']==3&&$v['state']==150){
				    switch (session('new_position_id')){
					    case '93':$v['edit']=1;break;//财务专员
					    case '52':$v['edit']=1;break;//资金专员
						default:$v['edit']=0;break;
					}
				}

			}elseif($w['stage']==4 && $v['dept'] == session('new_dept_id')){
			    if(($v['state'] == session('new_rank_id')) || ($v['state'] == (200+session('new_rank_id')))){
			        $v['edit']=1;
			    }
			}elseif($w['stage']==5){
				if(session('user_name')=='张晓明' || session('user_name')=='齐静' || session('user_name')=='张毅')
				$v['edit']=1;
			}
		}
	}

	//数据导出功能
	public function export(){

// 		if(session('school_id')!=0)die;
	    
		$w=I('post.');
		
		if(session('school_id')!=0){
		    $w['school'] = session('school_id');
		}
		array_empty_delt($w);
		unset($w['stage']);
		$w['is_del']=0;
		$dat=M('apply')->where($w)->order('state asc,money_time asc,school asc,subject asc,type asc,id desc')->field('record',true)->select();

        $output = "<HTML>";
        $output .= "<HEAD>";
        $output .= "<META http-equiv=Content-Type content=\"text/html; charset=utf-8\">";
        $output .= "</HEAD>";
        $output .= "<BODY>";
        $output .= "<TABLE BORDER=1>";
        $output .= "<tr>
        				<td>期次</td>
        				<td>序号</td>
        				<td>申请阶段</td>
        				<td>状态</td>
        				<td>审核部门</td>
        				<td>类型</td>
        				<td>申请校区</td>
        				<td>归属校区</td>
        				<td>科目类别</td>
        				<td>明细内容</td>
        				<td>单位</td>
        				<td>单价（元）</td>
        				<td>数量</td>
        				<td>金额</td>
        				<td>实际支出</td>
        				<td>支出说明</td>
        				<td>接收校区</td>
        				<td>接收人</td>
        				<td>接收卡号</td>
        				<td>期望审批日期</td>
        				<td>备注</td>
        				<td>采购类型</td>
        				<td>品牌</td>
        				<td>型号</td>
        				<td>物流</td>
        				<td>预算审批时间</td>
        				<td>最后审核时间</td>
        				<td>数据创建时间</td>
        				<td>创建人</td>
        				</tr>";
        $apply_state=get_config('APPLY_STATE');
        $apply_type=get_config('APPLY_TYPE');
        $apply_buy_type=get_config('APPLY_CG_TYPE');
        foreach ($dat as $m) {

        	$m['stage']=($m['state']<60)?'计划申请':(($m['state']<90)?'资金申请':'报销申请');
        	$m['state']=$apply_state[$m['state']];
        	$m['type']=$apply_type[$m['type']];
        	$m['school']=$this->school[$m['school']];
        	$m['belong']=$this->school[$m['belong']];
        	$m['subject']=$this->subjectId[$m['subject']];
        	$m['receive_school']=$this->school[$m['receive_school']];
        	$m['buy_type']=$apply_buy_type[$m['buy_type']];



            $output .= "<tr>";
            $output .= "<td>".$m['month']."</td>";
            $output .= "<td>".$m['id']."</td>";
            $output .= "<td>".$m['stage']."</td>";
            $output .= "<td>".$m['state']."</td>";
            $output .= "<td>".$m['dept_name']."</td>";
            $output .= "<td>".$m['type']."</td>";
            $output .= "<td>".$m['school']."</td>";
            $output .= "<td>".$m['belong']."</td>";
            $output .= "<td>".$m['subject']."</td>";
            $output .= "<td>".$m['info']."</td>";
            $output .= "<td>".$m['unit']."</td>";
            $output .= "<td>".$m['unit_price']."</td>";
            $output .= "<td>".$m['count']."</td>";
            $output .= "<td>".round($m['unit_price']*$m['count'],2)."</td>";
            $output .= "<td>".$m['money']."</td>";
            $output .= "<td>".$m['notes']."</td>";
            $output .= "<td>".$m['receive_school']."</td>";
            $output .= "<td>".$m['receive_user']."</td>";
            $output .= "<td>".$m['receive_card']."</td>";
            $output .= "<td>".$m['expect_date']."</td>";
            $output .= "<td>".$m['other']."</td>";
            $output .= "<td>".$m['buy_type']."</td>";
            $output .= "<td>".$m['buy_brand']."</td>";
            $output .= "<td>".$m['buy_info']."</td>";
            $output .= "<td>".$m['buy_transport']."</td>";
            $output .= "<td>".$m['money_time']."</td>";
            $output .= "<td>".$m['update_time']."</td>";
            $output .= "<td>".$m['create_time']."</td>";
            $output .= "<td>".$m['add_user_name']."</td>";
            $output .= "</tr>";
        }
        $output .= "</TABLE>";
        $output .= "</BODY>";
        $output .= "</HTML>";
        $filename='财务系统明细导出表'.date('Y-m-d');
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
