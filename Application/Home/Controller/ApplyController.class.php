<?php
namespace Home\Controller;

class ApplyController extends HomeController {

	//把校区列表、科目类别输出到前段模版
	public function _initialize(){
        parent::_initialize();
		foreach (C('SCHOOL') as $v) {
			$school[$v['id']]=$v['name'];
		}
		$this->assign('school',$school);//校区
		$subject=M("FinanceType")->where('pid=268')->select();
		foreach ($subject as $v) {
			$subjectId[$v['id']]=$v['name'];
		}
		$this->subject=$subject;
		$this->subjectId=$subjectId;
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
####################################增删改查
*/
/*
*申请添加、修改
*/
	public function write(){
		array_empty_delt($_POST);
		$mod=M('apply');
		$mod->create();
		if(($mod->type == 40 || $mod->type == '40') && (($mod->count * $mod->unit_price)>500)){
			$this->ajaxReturn('计划类型为简易预算的，申请金额不能大于500元！');
			exit;
		}
		
		if(I('post.subject')){
			$dept=$this->get_check_dept(I('post.subject'));
			$mod->dept1=$dept['dept1'];
			$mod->dept2=$dept['dept2'];
			$mod->dept_name=$dept['dept_name'];
		}

		//修改
		if(I('post.id')){
			$mod->save();
			//处理修改造成的审核部门丢失问题
			if(I('post.subject')){
				$info=$mod->find(I('post.id'));
				if(in_array($info['state'],[20,110])&&$info['dept1']==0)D('Apply')->check(1,[I('post.id')]);
				if(in_array($info['state'],[5,55,95]))D('Apply')->check(1,[I('post.id')]);
			}
			D('Apply')->save_check([['id'=>I('post.id')]],2);
			$this->ajaxReturn('更新成功');
		}

		$mod->school=session('school_id');
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
			if(D('Apply')->check(I('post.type'),I('post.data')['id'],I('post.why')))$this->ajaxReturn('ok');
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
    		if($w['date1'])$w['create_time']=['between',[$w['date1'].' 00:00:00',$w['date2'].' 00:00:00']];
    		$this->get_condition($w);//附加浏览的查询条件
    		$data=M('apply')->where($w)->order('state asc,money_time asc,school asc,subject asc,type asc,id desc')->field('record',true)->limit(I('get.offset'),I('get.count'))->select();
    		$total=M('apply')->where($w)->count();
    		$count=$this->get_count($w);
    		$this->get_edit($data,$w);//设置页面修改权限
    		$this->ajaxReturn(['state'=>'ok','data'=>$data,'total'=>$total,'count'=>$count]);
    	}else{
    		$this->ajaxReturn(['state'=>'error','info'=>'没有查询到数据']);
    	}
    }

    //计算统计各阶段的金额统计
    private function get_count($w){
    	$data=M('apply')->where($w)->getField('id,state,unit_price,count,money');
    	foreach ($data as $v) {
    		if($v['state']>=0&&$v['state']<=50)$count1+=$v['unit_price']*$v['count'];//计划申请
    		if($v['state']>50&&$v['state']<=70)$count2+=$v['unit_price']*$v['count'];//资金申请
    		if($v['state']>=80&&$v['state']<=160){
    			$count3+=$v['unit_price']*$v['count'];//报销申请
    			$count4+=$v['money'];//实际支出
    		}
    		if($v['state']>=0&&$v['state']<=160)$count+=$v['unit_price']*$v['count'];//合计
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
		//校区账户不区分谁申请的
		if(get_school_name()!='集团'){
			$w['school']=session('school_id');
		}elseif(in_array($w['stage'],[1,2,3])){
			$w['add_user']=session('auth_id');
		}

		//==计划申请阶段
		if($w['stage']==1){
			$w['state']=['between','0,50'];
		}
		//==资金申请阶段
		if($w['stage']==2){
			$w['state']=['between','50,80'];
		}
		//==报销申请阶段
		if($w['stage']==3){
			$w['state']=['between','80,160'];
//			if(session('user_name')=='刘丹丹'){
			if(session('user_name')=='王丽丽'){
				$w['school|receive_school']=session('school_id');
				unset($w['add_user']);
			}
		}

		//==========申请审核
		if($w['stage']==4){
			unset($w['add_user']);
			if(get_school_name()!='集团')$w['state']=['in','10,100'];
			if(get_school_name()=='集团'){
				//部门主管
				if(session('position_id')==8){
					$w['state']=['in','20,110'];
					$w['dept1']=session('dept_id');
				//中心总裁
				}elseif(session('position_id')==7){
					$w['state']=['in','30,120'];
					$w['dept2']=session('dept_id');
				}else{
					$w['state']=888;
				}
			}
			// //集团财务
			// if(session('user_name')=='齐静'){
			// 	unset($w['state'],$w['dept1'],$w['dept2']);
			// 	$w['_string']='(state=60) OR (state=140) OR (state=30 AND dept2=32) OR (state=120 AND dept2=32)';
			// }
			//集团大财务
			if(session('user_name')=='齐静' || session('user_name')=='张晓明'){
				unset($w['state'],$w['dept1'],$w['dept2']);
				$w['_string']='(state=30 AND dept2=32) OR (state=40 AND type=10) OR (state=60) OR (state=70) OR (state=120 AND dept2=32) OR (state=140) OR (state=150)';
			}
			
//			-10=>审核失败,0=>待提交,5=>计划退回,10=>校长审核,20=>部门审核,30=>中心审核,40=>总裁审核,
//			50=>计划通过,55=>申请退回,60=>资金申请,70=>资金审批,80=>审批通过,90=>报销申请,95=>退回报销,
//			100=>校长确认,110=>部门确认,120=>中心确认,130=>总裁确认,140=>费用确认,150=>入账确认,160=>审核完成
			//给  张毅(总裁办 部门ID29+人事总裁 部门ID54) 添加浏览权限
			$uname='张毅';
			if(session('user_name') == $uname || session('user_name')=='张晓明'){
				unset($w['state'],$w['dept1'],$w['dept2']);
				$w['_string']='(state=30 AND dept2=54) OR (state=30 AND dept2=29) OR (state=120 AND dept2=54) OR (state=120 AND dept2=29) ';
			}
			
			// 集团大总裁
			if(session('user_name')=='李文龙' || session('user_name')=='张晓明'){
				unset($w['state'],$w['dept1'],$w['dept2']);
				$w['_string']='(state=40 AND type=20) OR (state=130)';
			}
		}
		if($w['stage']==5){
			$w['state']=$w['state']?:['lt',200];
			if((get_school_name()!='集团'))$w['state']=888;
		}
		if(!in_array($w['stage'],[0,1,2,3,4,5]))$w['state']=888;//没有找到符合条件的让其查询不到信息
	}

	//设置数据到页面的修改权限，$v['edit']，1允许，0不允许
	private function get_edit(&$data,$w){
		foreach ($data as &$v) {
			$v['edit']=0;
			if(in_array($w['stage'],[1,2,3])){
				if(in_array($v['state'],[0,5,55,90,95]))$v['edit']=1;
				if($w['stage']==2&&$v['state']==50)$v['edit']=1;
				if($w['stage']==3&&$v['state']==80)$v['edit']=1;

				// 一些环节的特殊处理，stage 1计划申请阶段，2资金计划申请阶段，3资金报销申请阶段，4申请审核，5数据管理
//				if(get_school_name()=='集团'&&$w['stage']==3&&session('user_name')!='刘丹丹')$v['edit']=0;//集团只能丹丹报销
				if(get_school_name()=='集团'&&$w['stage']==3&&session('user_name')!='王丽丽')$v['edit']=0;//集团只能王丽丽报销。
				if(get_position_name()=='校长'&&($v['state']==50||$v['state']==80))$v['edit']=0;//校长不许用资金申请和报销申请
				if(get_position_name()=='校长'&&$v['state']==50&&$v['add_user']==session('auth_id'))$v['edit']=1;//校长可以资金申请自己的


			}elseif($w['stage']==4){
				$v['edit']=1;
			}elseif($w['stage']==5&&get_school_name()=='集团'){
				if(session('user_name')=='张晓明'||session('user_name')=='齐静'||session('user_name')=='张毅')
				$v['edit']=1;
			}
		}
	}

	//数据导出功能
	public function export(){

		if(session('school_id')!=0)die;
		$w=I('post.');
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
