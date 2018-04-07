<?php
namespace Home\Controller;

class ApplyMarketController extends HomeController {

	//把校区列表、科目类别输出到前段模版
	public function _initialize(){
        parent::_initialize();
		
		$this->assign('month',date("Y-m",strtotime("+1 month",time())));
		
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
		$mod=M('ApplyMarket');
		$mod->create();
		
		//修改
		if(I('post.id')){
		    $data = $mod->where(['id'=>I('post.id')])->field('record',true)->select();
		    
		    if($mod->save()){
		        $this->success('更新成功');
		        exit;
		    }
		}

		//$mod->school=session('school_id');
		$mod->add_user=session('auth_id');
		$mod->add_user_name=session('user_name');
		
		if($mod->add()){
		    $this->success('保存成功');
		    exit;
		}
		
		//$this->error('操作失败');
	}
/*
*审核操作
*/
	public function check(){
		if(IS_AJAX&&I('post.data')){
			if(D('ApplyMarket')->check(I('post.type'),I('post.data')['id'],I('post.why')))$this->ajaxReturn('ok');
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
    		$w['is_del']= array('neq',1);
    		
    		if($w['apply_user']){
    		 $w['apply_user'] = array('like','%'. $w['apply_user'] . '%');
    		}
    		
    		$stage = $w['stage'];
    		
    		unset($w['stage']);
    		
    		$flag = 0;
    		
    		$school = '';
    		
    		if(strpos(strstr($_SERVER['HTTP_REFERER'],'&a='),'manage') === FALSE){
    		 
         		 if(get_school_name()!='集团'){
         		     $w['apply_school'] = session('school_id');
         		     $school = session('school_id');
         		     if(session('position_id') == '10'){
         		         
         		         if(session('auth_id') == '2116'){
         		             unset($w['apply_school']);
         		             unset($w['area']);
         		             $w['state'] = 70;
         		         }else{
         		             //校长
         		             $w['state'] = 10;
         		         }
         		     }else{
             		     unset($w['state']);
             		     $w['add_user'] = session('auth_id');
             		     $w['state'] = array('elt',80);
             		     $flag = 1;
             		 }
         		 }else{
         		     
         		     $w['apply_school'] = session('dept_id');
         		     $school = session('dept_id');
         		     
         		    if(session('auth_id') == '2116'){
         		         unset($w['apply_school']);
         		         unset($w['area']);
         		         $w['state'] = 70;
         		     }else{
         		         unset($w['state']);
         		         $w['add_user'] = session('auth_id');
         		         $w['state'] = array('elt',80);
         		         $flag = 1;
         		     }
         		     
         		 }
    		}else if($stage == 5 && $w['school']){
    		    $w['apply_school'] = $w['school'];
    		}
    		
    		if($flag){
    		    $map = $w;
    		}else{
    		    if($stage == 1){
    		        $map['_logic']='or';
    		        $map['_complex'] = $w;
    		        $map['apply_school&add_user&state&is_del'] = array($school,session('auth_id'),array('elt',80),array('neq',1),'_multi'=>true);
    		    }else{
    		        $map = $w;
    		    }
    		}
    		
    		$data=M('applyMarket')->where($map)->order('id desc')->field('id, state, apply_month, apply_type, apply_school, apply_user, tel, expect_date, email, design_type,why, create_time, update_time, is_del, add_user, add_user_name, back, descp')->limit(I('get.offset'),I('get.count'))->select();
    		
    		if(get_school_name()!='集团'){
    		  foreach ($data as &$vo){
    		      $vo['school_name'] = M('foo_info')->where('id=' . $vo['apply_school'])->getField('name');
    		    if($flag){//不拥有审核权限
    		        $vo['edit'] = 0;
    		        if($stage==1){
    		            if($vo['state']<=0){
    		                $vo['edit'] = 1;
    		            }
    		        }
    		    }else{
    		        $vo['edit'] = 1;
    		        if($stage==1){
    		            if($vo['state']<=0){
    		                $vo['edit'] = 1;
    		            }else{
    		                $vo['edit'] = 0;
    		            }
    		        }else{
    		            if($vo['state']<=0){
    		                $vo['edit'] = 0;
    		            }
    		        }
    		    }
    		    if($vo['state'] >= 80){
    		        $vo['edit'] = 0;
    		    }
    		    if($stage == 5){
    		        $vo['edit'] = 1;
    		    }
    		  }
    		}else{
         	  foreach ($data as &$vo){
         	      $vo['school_name'] = M('foo_info')->where('id=' . $vo['apply_school'])->getField('name');
         	      
         	      if($vo['reference_pic']){
         	          $hrefs = explode(";",$vo['reference_pic']);
         	          $ref = '';
         	          foreach($hrefs as $arr){
         	              $ref .= '<a href="' . $arr . '" target="_blank">' . $arr . '</a><br/>';
         	          }
         	          $vo['references'] = $ref;
         	      }
         		 
         	      if($flag){//不拥有审核权限
         	          $vo['edit'] = 0;
         	          if($stage==1){
         	              if($vo['state']<=0){
         	                  $vo['edit'] = 1;
         	              }
         	          }
         	      }else{
         	          $vo['edit'] = 1;
         	          if($stage==1){
         	              if($vo['state']<=0){
         	                  $vo['edit'] = 1;
         	              }else{
         	                  $vo['edit'] = 0;
         	              }
         	          }else{
         	              if($vo['state']<=0){
         	                  $vo['edit'] = 0;
         	              }
         	          }
         	      }
         	      if($vo['state'] >= 80){
         	          $vo['edit'] = 0;
         	      }
         	      if($stage == 5){
         	          $vo['edit'] = 1;
         	      }
         	   }
    		}
    		
    		$total=M('applyMarket')->where($w)->count();
    		
    		
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
    		$data=M('applyMarket')->where($w)->order('state asc,money_time asc,school asc,subject asc,type asc,id desc')->field('record',true)->limit(I('get.offset'),I('get.count'))->select();
    		$total=M('applyMarket')->where($w)->count();
    		$count=$this->get_count($w);
    		
    		$this->ajaxReturn(['state'=>'ok','data'=>$data,'total'=>$total,'count'=>$count]);
    	}else{
    		$this->ajaxReturn(['state'=>'error','info'=>'没有查询到数据']);
    	}
    }

	


	//数据导出功能
	public function export(){

		//if(session('school_id')!=0)die;
		/* if(session('auth_id') !== '1'){
		    die;
		} */
		$w=I('post.');
		array_empty_delt($w);
		unset($w['stage']);
		$w['is_del']=0;
		
		if($w['date1'])$w['create_time']=['between',[$w['date1'].' 00:00:00',$w['date2'].' 23:59:59']];
		
		if($w['apply_user']){
		    $w['apply_user'] = array('like','%'. $w['apply_user'] . '%');
		}
		
		$w['product_type'] = 1;
		
		$dat=M('applyMarket')->where($w)->order('id desc')->field("id, state, apply_month, apply_type, apply_school, apply_user, tel, expect_date, email, design_type, record, why, create_time, update_time, is_del, add_user, add_user_name, CASE back WHEN 1 THEN '退回' else '正常' END as back, descp")->select();;

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
        				<td>提报月度</td>
        				<td>计划类型</td>
        				<td>提报人</td>
        				<td>联系电话</td>
        				<td>提报日期</td>
        				<td>截稿日期</td>
        				<td>部门接收邮箱</td>
        				<td>设计类型</td>
                        <td>备注</td>
                        <td>退回原因</td>
        				<td>最后审核时间</td>
        				<td>创建人</td>
        				</tr>";
        $apply_state=get_config('APPLYDESIGN_STATE');
        
        foreach ($dat as &$vo) {
            
            $vo['state']=$apply_state[$vo['state']];
            $vo['apply_school']=M('foo_info')->where('id=' . $vo['apply_school'])->getField('name');
            
            $output .= "<tr>";
            $output .= "<td>".$vo['id']."</td>";
            $output .= "<td>".$vo['back']."</td>";
            $output .= "<td>".$vo['state']."</td>";
            $output .= "<td>".$vo['apply_month']."</td>";
            $output .= "<td>".$vo['apply_type']."</td>";
            $output .= "<td>".$vo['apply_user']."</td>";
            $output .= "<td>".$vo['tel']."</td>";
            $output .= "<td>".$vo['create_time']."</td>";
            $output .= "<td>".$vo['expect_date']."</td>";
            $output .= "<td>".$vo['email']."</td>";
            $output .= "<td>".$vo['design_type']."</td>";
            $output .= "<td>".$vo['descp']."</td>";
            $output .= "<td>".$vo['why']."</td>";
            $output .= "<td>".$vo['update_time']."</td>";
            $output .= "<td>".$vo['add_user_name']."</td>";
            $output .= "</tr>";
        }
        $output .= "</TABLE>";
        $output .= "</BODY>";
        $output .= "</HTML>";
        $filename='营销企划类设计申请明细导出表'.date('Y-m-d');
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
