<?php
namespace Home\Controller;

class ApplyDesignController extends HomeController {

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
		$mod=M('applyDesign');
		$mod->create();
		$mod->type = implode(",",$mod->type);
		
		$config = array(
		  'maxSize'    =>    10485760,
		  'rootPath'   =>    './Uploads/',
		  'savePath'   =>    'Design/' . session('school_id') . '/',
		  'exts'       =>    array('jpg', 'gif', 'png', 'jpeg')
		);
		
		$upload = new \Think\Upload($config);// 实例化上传类
		
		//修改
		if(I('post.id')){
		    $data = $mod->where(['id'=>I('post.id')])->field('record',true)->select();
		    if($data && $data[0]['position']){
		        $files = explode(';',$data[0]['position']);
		        foreach ($files as $fl){
    		         if (file_exists($fl)) {
    		           unlink($fl);
    		         }
		        }
		    }
		    
		    if($data && $data[0]['references']){
		     $files = explode(';',$data[0]['references']);
		     foreach ($files as $fl){
		      if (file_exists($fl)) {
		       unlink($fl);
		      }
		     }
		    }
		    
		    
		    // 上传文件
		    $info   =   $upload->upload();
		    if(!$info) {// 上传错误提示错误信息
		     $this->error('保存失败：' . $upload->getError());
		    }else{// 上传成功 获取上传文件信息
		     //新增
		     $pos = '';
		     $ref = '';
		     foreach($info as $k=>$v){
		      if($v['key'] == 'position'){
		       $pos .= './Uploads/' . $v['savepath'].$v['savename'] . ';';
		      }else if($v['key'] == 'references'){
		       $ref .= './Uploads/' . $v['savepath'].$v['savename'] . ';';
		      }
		     }
		     	
		     $mod->position = $pos;
		     $mod->references = $ref;
		     	
		     if($mod->save()){
		      //echo $info['savepath'].$info['savename'];
		      $this->success('保存成功');
		     }
		    }
		    
			$this->success('更新成功');
		}

		//$mod->school=session('school_id');
		$mod->add_user=session('auth_id');
		$mod->add_user_name=session('user_name');
		
		// 上传文件
		$info   =   $upload->upload();
		if(!$info) {// 上传错误提示错误信息
		 $this->error('保存失败：' . $upload->getError());
		}else{// 上传成功 获取上传文件信息
		 //新增
		 $pos = '';
		 $ref = '';
		 foreach($info as $k=>$v){
		   if($v['key'] == 'position'){
		      $pos .= './Uploads/' . $v['savepath'].$v['savename'] . ';';
		   }else if($v['key'] == 'references'){
		      $ref .= './Uploads/' . $v['savepath'].$v['savename'] . ';';
		   }
		 }
		 
		 $mod->position = $pos;
		 $mod->references = $ref;
		 
		 if($mod->add()){
		   //echo $info['savepath'].$info['savename'];
		   $this->success('保存成功');
		 }
		}
		$this->error('操作失败');
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
    		if($w['date1'])$w['create_time']=['between',[$w['date1'].' 00:00:00',$w['date2'].' 23:59:59']];
    		$w['is_del']=0;
    		
    		if($w['course_info']){
    		 $w['course_info'] = array('like','%'. $w['course_info'] . '%');
    		}
    		
    		/* if(strpos(strstr($_SERVER['HTTP_REFERER'],'&a='),'manage') === FALSE){
    		 
         		 if(get_school_name()!='集团'){
         		   $w['school'] = session('school_id');
         		 }
         		 
         		 if(get_school_name()=='集团' && (session('auth_id') == '1293'  || session('auth_id') == '1')){
         		  //姜博文
         		  unset($w['school']);
         		  $w['area'] = array('in',['辽宁']);
         		  $w['state'] = 10;
         		 } elseif(get_school_name()=='集团' && (session('auth_id') == '89')){
         		  //王胜鑫
         		  unset($w['school']);
         		  unset($w['area']);
         		  //$w['area'] = array('in',['辽宁','吉林','黑龙江','其他']);
         		  $w['state'] = 20;
         		 }elseif(get_school_name()=='集团' && session('auth_id') == '509'){
         		  $w['state'] = 30;
         		 }
    		 
    		} */
    		
    		
    		
    		$data=M('applyDesign')->where($w)->order('state desc,school asc,id desc')->field('id, state, school, apply_user, substring_index(apply_user,"#",1) as apply_user2, apply_uses, product_form, type, size, tel, count, is_urgent, is_plan, case is_urgent when 0 then "否" when 1 then "是" end as is_urgents, case is_plan when 0 then "否" when 1 then "是" end as is_plans, descp, position, `references`, expect_date, why, create_time, update_time, is_del, add_user, add_user_name, back')->limit(I('get.offset'),I('get.count'))->select();
    		
    		if(get_school_name()!='集团'){
    		  foreach ($data as &$vo){
    		    $vo['type'] = explode(",",$vo['type']);
    		    
    		     if($vo['state']>0){
    		      $vo['edit'] = 0;
    		     }else{
    		      $vo['edit'] = 1;
    		     }
    		  }
    		}else{
         	  foreach ($data as &$vo){
         	     $vo['type'] = explode(",",$vo['type']);
         		 $vo['edit'] = 1;
         	   }
    		}
    		
    		$total=M('applyDesign')->where($w)->count();
    		
    		
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
    
    public function export_word(){
     
     require_cache(VENDOR_PATH.'PHPWord/PHPWord.php');
     
     $mod = M('applyDesign');
     $data = $mod->where(['id' => I('get.id'),'is_del'=>0])->field('id, state, school, apply_user, substring_index(apply_user,"#",1) as apply_user2, apply_uses, product_form, type, size, tel, count, is_urgent, is_plan, case is_urgent when 0 then "否" when 1 then "是" end as is_urgents, case is_plan when 0 then "否" when 1 then "是" end as is_plans, descp, position, `references`, expect_date, why, create_time, update_time, is_del, add_user, add_user_name, back')->select();
     
     if($data && count($data)>0){
      
      // New Word Document
      $PHPWord = new \PHPWord();
       
      // New portrait section
      $section = $PHPWord->createSection();
      $PHPWord->addFontStyle('rStyle', array('bold'=>true,'color'=>'000000','size'=>16));
      $PHPWord->addParagraphStyle('pStyle', array('align'=>'center'));
      $section->addText('鸿文教育设计需求申请单', 'rStyle', 'pStyle');
      $section->addTextBreak(2);
       
      // Define table style arrays
      $styleTable = array('borderSize'=>6, 'borderColor'=>'000000', 'cellMargin'=>80);
       
       
      // Add table style
      $PHPWord->addTableStyle('myOwnTableStyle', $styleTable);
       
      // Add table
      $table = $section->addTable('myOwnTableStyle');
      $fontStyle = array('bold'=>true, 'align'=>'center');
      $fontStylen = array('align'=>'center');
      $contentfontStyle = array('align'=>'left');
       
      // Add more rows / cells
      $table->addRow();
      $table->addCell(2000)->addText("提交部门（校区）",$fontStyle);
      $table->addCell(3000)->addText(get_school_name($data[0][school]),$fontStylen);
      $table->addCell(2000)->addText("提交日期",$fontStyle);
      $table->addCell(3000)->addText(substr($data[0]['create_time'],0,10),$fontStylen);
       
      $table->addRow();
      $table->addCell(2000)->addText("提交人",$fontStyle);
      $table->addCell(3000)->addText(strstr($data[0]['apply_user'],'#',true),$fontStylen);
      $table->addCell(2000)->addText("要求完成日期",$fontStyle);
      $table->addCell(3000)->addText($data[0]['expect_date'],$fontStylen);
       
       
      $table->addRow();
      $table->addCell(2000)->addText("联系电话",$fontStyle);
      $table->addCell(3000)->addText($data[0]['tel'],$fontStylen);
      $table->addCell(2000)->addText("数量",$fontStyle);
      $table->addCell(3000)->addText($data[0]['count'],$fontStylen);
      
      $table->addRow();
      $table->addCell(2000)->addText("是否紧急",$fontStyle);
      $table->addCell(3000)->addText($data[0]['is_urgents'],$fontStylen);
       
      $section->addTextBreak(2);
       
      $styleTable2 = array('borderColor'=>'000000', 'borderSize'=>6,'cellMargin'=>80);
      $fontStyle2 = array('align'=>'center');
      $fontStyle3 = array('bold'=>true,'align'=>'center');
       
      // Add table style
      $PHPWord->addTableStyle('myOwnTableStyle2', $styleTable2);
       
      $table2 = $section->addTable('myOwnTableStyle2');
       
      $table2->addRow();
      $table2->addCell(1500)->addText("申请用途",$fontStyle3);
      $table2->addCell(8500)->addText($data[0]['apply_uses'],$fontStyle2);
       
      $table2->addRow();
      $table2->addCell(1500)->addText("制作形式",$fontStyle3);
      $table2->addCell(8500)->addText($data[0]['product_form'],$fontStyle2);
       
       
      $table2->addRow();
      $table2->addCell(1500)->addText("类型",$fontStyle3);
      $table2->addCell(8500)->addText($data[0]['type'],$fontStyle2);
       
       
      $table2->addRow();
      $table2->addCell(1500)->addText("大小尺寸",$fontStyle3);
      $table2->addCell(8500)->addText($data[0]['size'],$fontStyle2);
       
       
      $table2->addRow();
      $table2->addCell(1500)->addText("是否需要企划部提供创意文案",$fontStyle3);
      $table2->addCell(8500)->addText($data[0]['is_plans'],$fontStyle2);
       
       
      $table2->addRow();
      $table2->addCell(1500)->addText("内容概述",$fontStyle3);
      $table2->addCell(8500)->addText($data[0]['descp'],$fontStyle2);
       
      $table2->addRow();
      $table2->addCell(1500)->addText("安放位置",$fontStyle3);
      $pos = $table2->addCell(8500);
      if($data[0]['position']){
       $pos_arr = explode(';',$data[0]['position']);
       array_empty_delt($pos_arr);
       foreach ($pos_arr as $arr){
        $pos->addImage($arr, array('width'=>500, 'height'=>350,'align'=>'center'));
        $pos->addTextBreak(1);
       }
       //$pos->addImage('./Uploads/Design/0/2017-07-30/597d6ecfd21f9.jpg', array('width'=>350, 'height'=>200,'align'=>'right'));
      }else{
       $pos->addText('',$fontStyle2);
      }

      $table2->addRow();
      $table2->addCell(1500)->addText("参考案例",$fontStyle3);
      $ref = $table2->addCell(8500);
      
      
      if($data[0]['references']){
       $ref_arr = explode(';',$data[0]['references']);
       array_empty_delt($ref_arr);
       foreach ($ref_arr as $vo){
        $ref->addImage($vo, array('width'=>500, 'height'=>350,'align'=>'center'));
        $ref->addTextBreak(1);
       }
       //$pos->addImage('./Uploads/Design/0/2017-07-30/597d6ecfd21f9.jpg', array('width'=>350, 'height'=>200,'align'=>'right'));
      }else{
       $ref->addText('',$fontStyle2);
      }
      //$ref->addImage('./Uploads/Design/0/2017-07-30/597d6ecfd21f9.jpg', array('width'=>350, 'height'=>200,'align'=>'right'));
      
      //Two enter
      $section->addTextBreak(2);
      //Add image
      //$section->addImage('./Uploads/Design/0/2017-07-30/597d6ecfd21f9.jpg', array('width'=>350, 'height'=>200,'align'=>'right'));
       
      $objWrite = \PHPWord_IOFactory::createWriter($PHPWord, 'Word2007');
      $objWrite->save('index2.docx');
      
     }
     
    }

}
