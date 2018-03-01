<?php
namespace Home\Controller;

class SatisfactController extends HomeController {

	//把校区列表、科目类别输出到前段模版
	public function _initialize(){
        parent::_initialize();
        $ws['is_del'] = 0;
        $ws['region'] = array('in',array_keys(get_config('SCHOOL_REGION')));
        $ws['pid'] = 15;
        $sch_lst = M('foo_info')->where($ws)->getField('id,name,region');
        $this->sch_lst = $sch_lst;
		
        $w['is_del'] = 0;
        $w['position_id'] = array('in',[18,12]);
        $sg_lst = M('user')->where($w)->getField('id,name,wechat_userid');
        
        $this->assign('sg_lst',$sg_lst);
        
		$this->assign('month',date("Y-") . date(m) );
		
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
		$mod=M('applySatisfact');
		$mod->create();
		
		//修改
		if(I('post.id')){
		    if($mod->save()){
		        //echo $info['savepath'].$info['savename'];
		        $this->success('更新成功');
		        exit;
		    }
		}

		$mod->add_user=session('auth_id');
		$mod->add_user_name=session('user_name');
		
	    if($mod->add()){
	        $this->success('保存成功');
	        exit;
	    }
	}
/*
*审核操作
*/
	public function check(){
		if(IS_AJAX&&I('post.data')){
			if(D('ApplySatisfact')->check(I('post.type'),I('post.data')['id'],I('post.why')))$this->ajaxReturn('ok');
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
    		$w['is_del']= array('neq',1);
    		
    		$stage = $w['stage'];
    		
    		unset($w['stage']);
    		
    		$flag = 0;
    		
    		$school = '';
    		
    		if(strpos(strstr($_SERVER['HTTP_REFERER'],'&a='),'manage') === FALSE){
    		 
         		 if(get_school_name()!='集团'){
         		     $w['apply_school'] = session('school_id');
         		     if(session('position_id') == '10'){
         		         
         		         if(session('auth_id') == '673'){
         		             //张鹏
         		             unset($w['apply_school']);
         		             $w['_string']="((area='10' and state=30) or (state=20 and apply_school='173'))";
         		         }elseif(session('auth_id') == '439'){
         		             //何亮
         		             unset($w['apply_school']);
         		             //              		     $w['area'] = '40';
         		             //              		     $w['state'] = 20;
         		             $w['_string']="((area='40' and state=30) or (state=20 and apply_school='11'))";
         		         }elseif(session('auth_id') == '651'){
         		             //王大鹏
         		             unset($w['apply_school']);
         		             //              		     $w['area'] = '30';
         		             //              		     $w['state'] = 20;
         		             $w['_string']="((area='30' and state=30) or (state=20 and apply_school='8'))";
         		         }else{
         		             //校长
         		             $w['state'] = 20;
         		         }
         		     }else if(session('position_id') == '18' || session('position_id') == '12'){
         		         $w['state'] = 10;
         		         $w['teacher'] = session('auth_id');
         		     }
         		     
         		     
         		 }else{
         		     
         		     
         		     if(session('auth_id') == '1283'){
         		         //张玉珠
         		         unset($w['apply_school']);
         		         $w['area'] = '20';
         		         $w['state'] = 30;
         		     }elseif(session('auth_id') == '1'){
         		         //张晓明
         		         unset($w['apply_school']);
         		         $w['area'] = '20';
         		         $w['state'] = 30;
         		     }elseif(session('auth_id') == '2100'){
         		         //李明帅
         		         unset($w['apply_school']);
         		         $w['area'] = '50';
         		         $w['state'] = 30;
         		     }elseif(session('auth_id') == '1473' || session('auth_id') == '2335' || session('auth_id') == '566'){
         		         //李冰 赵金玲
         		         if($stage != 1){
         		             unset($w['apply_school']);
         		             unset($w['area']);
         		             $w['state'] = 40;
         		         }
         		         
         		     }
         		     
         		 }
    		}else if($stage == 5){
    		    $flag = 1;
    		    if(get_school_name()!='集团'){
    		        $w['apply_school'] = session('school_id');
    		        if(session('position_id') == '10'){
    		            
    		            if(session('auth_id') == '673'){
    		                //张鹏
    		                unset($w['apply_school']);
    		                $w['_string']="((area='10'and state>=30) or (apply_school='173'and state>=20))";
    		            }elseif(session('auth_id') == '439'){
    		                //何亮
    		                unset($w['apply_school']);
    		                $w['_string']="((area='40'and state>=30) or (apply_school='11'and state>=20))";
    		            }elseif(session('auth_id') == '651'){
    		                //王大鹏
    		                unset($w['apply_school']);
    		                
    		                $w['_string']="((area='30'and state>=30) or (apply_school='8'and state>=20))";
    		            }else{
    		                $w['state'] = array('egt',20);
    		            }
    		        
    		        
    		        }else if(session('position_id') == '18' || session('position_id') == '12'){
    		            $w['state'] = array('egt',10);
    		            $w['teacher'] = session('auth_id');
    		        }
    		    }else{
    		        
    		        
    		        if(session('auth_id') == '1283'){
    		            //张玉珠
    		            unset($w['apply_school']);
    		            $w['area'] = '20';
    		            $w['state'] = array('egt',30);
    		        }elseif(session('auth_id') == '1'){
    		            //张晓明
    		            unset($w['apply_school']);
    		            $w['area'] = '20';
    		            $w['state'] = array('egt',30);
    		        }elseif(session('auth_id') == '2100'){
    		            //李明帅
    		            unset($w['apply_school']);
    		            $w['area'] = '50';
    		            $w['state'] = array('egt',30);
    		        }
    		        
    		    }
    		}
    		
    		if($flag){
    		    $map = $w;
    		}else{
    		    if($stage == 1){
    		        
    		        if(!(I('get.search')['state']) && !(I('get.search')['date1']) && !(I('get.search')['back']) && !(I('get.search')['area']) && !(I('get.search')['apply_school']) && !(I('get.search')['id'])){
    		            $map['_logic']='or';
    		            $map['_complex'] = $w;
    		            $map['state&is_del'] = array(array('elt',50),array('neq',1),'_multi'=>true);
    		        }else{
    		            $map = $w;
    		        }
    		        
    		    }else{
    		        $map = $w;
    		    }
    		}
    		
    		$data=M('applySatisfact')->where($map)->order('id desc')->field('id, state, apply_month, apply_school, area, teacher, student, feedback, why, create_time, update_time, is_del, add_user, add_user_name, back, resolve, question1, question2, question3, question4, question5, question6, question7, question8, question9, question10, xg_score, school_score, spot_way, xg_qr, xz_qr, qy_qr, resolve_content, school_minus, xg_minus')->limit(I('get.offset'),I('get.count'))->select();
    		
    		  foreach ($data as &$vo){
    		      $vo['school_name'] = get_school_name($vo['apply_school']);
    		      $vo['area_name'] = get_config("SCHOOL_REGION")[$vo['area']];
    		      $vo['teacher_name'] =  M('user')->where(array("id"=>$vo['teacher'],"is_del"=>0))->getField("name"); 
    		   
    		    
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
    		    if($vo['state'] > 40){
    		        $vo['edit'] = 0;
    		    }
    		  }
    		
    		$total=M('applySatisfact')->where($w)->count();
    		
    		
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
    		
    		
    		
    		$w['_string'] = "LOCATE('" . $_SESSION['user_name'] . "',record) != 0"; 
    		
    		$w['is_del']= array('neq',1);
    		
    		$data=M('applyInspect')->where($w)->order('apply_month, expect_date, update_time')->field('record',true)->limit(I('get.offset'),I('get.count'))->select();
    		
    		foreach ($data as &$vo){
    		    $vo['school_name'] = M('foo_info')->where('id=' . $vo['apply_school'])->getField('name');
    		    $vo['area_name'] = get_config("SCHOOL_REGION")[$vo['area']];
    		    
    		    if($vo['questions']){
    		        $content = explode("@",$vo['questions']);
    		        $content = array_filter($content);
    		        $content = array_values($content);
    		        
    		        foreach($content as $k=>$v){
    		            $content_item = explode("^",$v);
    		            foreach($content_item as $m=>$n){
    		                
    		                switch($m){
    		                    case 0:$str .= " <b style=\"color:red; \">存在的问题：</b>" . $n;break;
    		                    case 1:$str .=" <b style=\"color:red; \">整改方案：</b>" . $n;break;
    		                    case 2:$str .=" <b style=\"color:red; \">整改描述：</b>" . $n;break;
    		                    case 3:
    		                        {
    		                            $href_item = explode(";",$n);
    		                            foreach($href_item as $b=>$p){
    		                                $ref .= '<a href="' . $p . '" target="_blank">' . $p . '</a><br/>';
    		                            }
    		                            if($ref){
    		                                $str .= " 整改图片如下：" . $ref ;
    		                            }
    		                            
    		                            unset($ref);
    		                        }
    		                        break;
    		                }
    		                
    		            }
    		        }
    		        
    		        $vo['content'] = $str;
    		    }
    		    
    		    unset($str);
    		    
    		    $vo['edit'] = 0;
    		        
    		}
    		
    		$total=M('applyInspect')->where($w)->count();
    		
    		$this->ajaxReturn(['state'=>'ok','data'=>$data,'total'=>$total]);
    	}else{
    		$this->ajaxReturn(['state'=>'error','info'=>'没有查询到数据']);
    	}
    }

    public function ajax_repeatCheck(){
        if(IS_AJAX){
            $w=I();
            array_empty_delt($w);
            
            $w['is_del']= array('neq',1);
            
            $data=M('applySatisfact')->where($w)->field('record',true)->select();
            if(count($data)>0){
                $this->ajaxReturn(['state'=>'error','info'=>'该学员已经抽查过了！']);
            }else{
                $this->ajaxReturn(['state'=>'sucess','info'=>'ok']);
            }
        }

    }
	//数据导出功能
	public function export(){

		if(session('school_id')!=0)die;
		/* if(session('auth_id') !== '1'){
		    die;
		} */
		$w=I('post.');
		array_empty_delt($w);
		unset($w['stage']);
		$w['is_del']=0;
		
		if($w['date1'])$w['create_time']=['between',[$w['date1'].' 00:00:00',$w['date2'].' 23:59:59']];
		
		
		$dat=M('applySatisfact')->where($w)->order('id desc')->field('record',true)->select();

        $output = "<HTML>";
        $output .= "<HEAD>";
        $output .= "<META http-equiv=Content-Type content=\"text/html; charset=utf-8\">";
        $output .= "</HEAD>";
        $output .= "<BODY>";
        $output .= "<TABLE BORDER=1>";
        $output .= "<tr>
        				<td>序号</td>
                        <td>是否退回</td>
        				<td>流程状态</td>
        				<td>审核状态</td>
        				<td>所属区域</td>
        				<td>校区</td>
        				<td>学习管理师</td>
        				<td>学员</td>
        				<td>沟通内容反馈</td>
        				<td>是否需要反馈</td>
                        <td>解决问题反馈内容</td>    
        				<td>抽查方式</td>
        				<td>抽查时间</td>
        				<td>校长确认时间</td>
        				<td>区域总监确认时间</td>
        				<td>客服专员</td>
        				<td>退回原因</td>
        				<td>最后审核时间</td>
                        <td>问题1</td>
                        <td>问题2</td>
                        <td>问题3</td>
                        <td>问题4</td>
                        <td>问题5</td>
                        <td>问题6</td>
                        <td>问题7</td>
                        <td>问题8</td>
                        <td>问题9</td>
                        <td>问题10</td>
                        <td>学管师扣分</td>
                        <td>校区扣分</td>
                        <td>学管师维护得分</td>
        				<td>校区得分</td>
        				</tr>";
        $apply_state=get_config('SATISFACT_STATE');
        $region=get_config('SCHOOL_REGION');
        foreach ($dat as &$vo) {
            
            $vo['state']=$apply_state[$vo['state']];
            $vo['apply_school']=get_school_name($vo['apply_school']);
            $vo['teacher'] = M('user')->where(['is_del'=>0,id=>$vo['teacher'],position_id=>array('in',[18,12])])->getField('name');
            $vo['area'] = $region[$vo['area']];

            $output .= "<tr>";
            $output .= "<td>".$vo['id']."</td>";
            $output .= "<td>". ($vo['back']==1?"退回":"正常") ."</td>";
            $output .= "<td>".$vo['state']."</td>";
            $output .= "<td>".$vo['apply_month']."</td>";
            $output .= "<td>".$vo['area']."</td>";
            $output .= "<td>".$vo['apply_school']."</td>";
            $output .= "<td>".$vo['teacher']."</td>";
            $output .= "<td>".$vo['student']."</td>";
            $output .= "<td>".$vo['feedback']."</td>";
            $output .= "<td>".$vo['resolve']."</td>";
            $output .= "<td>".$vo['resolve_content']."</td>";
            $output .= "<td>".$vo['spot_way']."</td>";
            $output .= "<td>".$vo['create_time']."</td>";
            $output .= "<td>".$vo['xz_qr']."</td>";
            $output .= "<td>".$vo['qy_qr']."</td>";
            $output .= "<td>".$vo['add_user_name']."</td>";
            $output .= "<td>".$vo['why']."</td>";
            $output .= "<td>".$vo['update_time']."</td>";
            $output .= "<td>".$vo['question1']."</td>";
            $output .= "<td>".$vo['question2']."</td>";
            $output .= "<td>".$vo['question3']."</td>";
            $output .= "<td>".$vo['question4']."</td>";
            $output .= "<td>".$vo['question5']."</td>";
            $output .= "<td>".$vo['question6']."</td>";
            
            $output .= "<td>".$vo['question7']."</td>";
            $output .= "<td>".$vo['question8']."</td>";
            $output .= "<td>".$vo['question9']."</td>";
            $output .= "<td>".$vo['question10']."</td>";
            
            $output .= "<td>".$vo['xg_minus']."</td>";
            $output .= "<td>".$vo['school_minus']."</td>";
            $output .= "<td>".$vo['xg_score']."</td>";
            $output .= "<td>".$vo['school_minus']."</td>";
            $output .= "</tr>";
        }
        $output .= "</TABLE>";
        $output .= "</BODY>";
        $output .= "</HTML>";
        $filename='满意度抽查明细导出表'.date('Y-m-d');
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
