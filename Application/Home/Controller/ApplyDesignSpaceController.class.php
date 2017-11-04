<?php
namespace Home\Controller;

class ApplyDesignSpaceController extends HomeController {

	//把校区列表、科目类别输出到前段模版
	public function _initialize(){
        parent::_initialize();
        foreach (C('SCHOOL') as $v) {
            if($v['name'] != '集团'){
                if($v['id'] == session('school_id')){
                    $school['s'. $v['id']]=$v['name'];
                }
                $school_all['s'. $v['id']]=$v['name'];
            }
        } 
        
        /* $dept_lst = M('dept')->where('is_del = 0 and pid !=28 and id != 28 and id=64')->getField('id,name');
        
        foreach ($dept_lst as $key=>$val){
            $dept['b' . $key] = $val;
        } */
        
        
        
        $this->assign('school',$school);//校区
        
        $this->assign('school_all',$school_all);//校区
        
        $this->assign('dept',$dept);//校区
        
        $this->assign('month',date("Y-") . (date(m) +1) );
		
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
		
		$config = array(
		    'maxSize'    =>    10485760,
		    'rootPath'   =>    './Uploads/',
		    'savePath'   =>    'DesignFlat/' . session('school_id') . '/',
		    'subName'       =>  array('date', 'Y-m-d H_i'),
		    'exts'       =>     array('jpg', 'gif', 'png', 'jpeg'),
		    'saveName'   =>    ''
		);
		
		$upload = new \Think\Upload($config);// 实例化上传类
		
		//修改
		if(I('post.id')){
		    $data = $mod->where(['id'=>I('post.id')])->field('record',true)->select();
		    if($data && $data[0]['reference_pic']){
		        $files = explode(';',$data[0]['reference_pic']);
		        foreach ($files as $fl){
		            $tmp_fl = iconv('UTF-8','GBK',$fl);
		            if (file_exists($tmp_fl)) {
    		           unlink($fl);
    		         }
		        }
		    }
		    
		    if($data && $data[0]['space_pic']){
		     $files = explode(';',$data[0]['space_pic']);
		     foreach ($files as $fl){
    		      $tmp_fl = iconv('UTF-8','GBK',$fl);
    		      if (file_exists($tmp_fl)) {
    		       unlink($fl);
    		      }
		     }
		    }
		    
		    if($data && $data[0]['install_pos_pic']){
		        $files = explode(';',$data[0]['install_pos_pic']);
		        foreach ($files as $fl){
		            $tmp_fl = iconv('UTF-8','GBK',$fl);
		            if (file_exists($tmp_fl)) {
		                unlink($fl);
		            }
		        }
		    }
		    
		    
		    if($data && $data[0]['space_show_pic']){
		        $files = explode(';',$data[0]['space_show_pic']);
		        foreach ($files as $fl){
		            $tmp_fl = iconv('UTF-8','GBK',$fl);
		            if (file_exists($tmp_fl)) {
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
		     $space = '';
		     $ref = '';
		     $pos = '';
		     $show = '';
		     foreach($info as $k=>$v){
		      if($v['key'] == 'space_pic'){
		       $space .= './Uploads/' . $v['savepath'].$v['savename'] . ';';
		      }else if($v['key'] == 'references'){
		       $ref .= './Uploads/' . $v['savepath'].$v['savename'] . ';';
		      }else if($v['key'] == 'install_pos_pic'){
		          $pos .= './Uploads/' . $v['savepath'].$v['savename'] . ';';
		      }else if($v['key'] == 'space_show_pic'){
		          $show .= './Uploads/' . $v['savepath'].$v['savename'] . ';';
		      }
		     }
		     	
		     $mod->space_pic = $space;
		     $mod->reference_pic = $ref;
		     
		     $mod->install_pos_pic = $pos;
		     $mod->space_show_pic = $show;
		     	
		     if($mod->save()){
		      //echo $info['savepath'].$info['savename'];
		         $this->success('更新成功');exit;
		     }
		    }
		    
			
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
	    $space = '';
	    $ref = '';
	    $pos = '';
	    $show = '';
		 foreach($info as $k=>$v){
		     if($v['key'] == 'space_pic'){
		         $space .= './Uploads/' . $v['savepath'].$v['savename'] . ';';
		     }else if($v['key'] == 'references'){
		         $ref .= './Uploads/' . $v['savepath'].$v['savename'] . ';';
		     }else if($v['key'] == 'install_pos_pic'){
		         $pos .= './Uploads/' . $v['savepath'].$v['savename'] . ';';
		     }else if($v['key'] == 'space_show_pic'){
		         $show .= './Uploads/' . $v['savepath'].$v['savename'] . ';';
		     }
		 }
		 
		 $mod->space_pic = $space;
		 $mod->reference_pic = $ref;
		 
		 $mod->install_pos_pic = $pos;
		 $mod->space_show_pic = $show;
		 
		 if($mod->add()){
		   //echo $info['savepath'].$info['savename'];
		   $this->success('保存成功');exit;
		 }
		}
		$this->error('操作失败');
	}
	
	
	public function getExcelPic(){
	    require_cache(VENDOR_PATH.'PHPExcel/PHPExcel.class.php');
	    define('EXCEL_EXTENSION_2003', "xls");
	    define('EXCEL_EXTENSION_2007', "xlsx");
	    
	    
	    $fileName2003 = "./Uploads/2003.xls";
	    $fileName2007 = "./Uploads/2007.xlsx";
	    
	    $fileName = $fileName2003;
	    //$fileName = $fileName2007;
	    
	    if(self::getExtendFileName($fileName) == EXCEL_EXTENSION_2003)
	    {
	        $reader = \PHPExcel_IOFactory::createReader('Excel5');
	    }
	    else if(self::getExtendFileName($fileName) == EXCEL_EXTENSION_2007)
	    {
	        $reader = new \PHPExcel_Reader_Excel2007();
	    }
	    
	    $PHPExcel = $reader->load($fileName);
	    $worksheet = $PHPExcel->getActiveSheet();
	    $imageInfo = self::extractImageFromWorksheet($worksheet,"./Uploads/importImg/");
	    
	    print_r($imageInfo);  
	}
	
	function getExtendFileName($file_name) {
	    
	    $extend = pathinfo($file_name);
	    $extend = strtolower($extend["extension"]);
	    return $extend;
	}
	
	function extractImageFromWorksheet($worksheet,$basePath){
	    require_cache(VENDOR_PATH.'PHPExcel/PHPExcel.class.php');
	    $result = array();
	    
	    $imageFileName = "";
	    
	    foreach ($worksheet->getDrawingCollection() as $drawing) {
	        $xy=$drawing->getCoordinates();
	        $path = $basePath;
	        // for xlsx
	        if ($drawing instanceof \PHPExcel_Worksheet_Drawing) {
	            
	            $filename = $drawing->getPath();
	            
	            $imageFileName = $drawing->getIndexedFilename();
	            
	            $path = $path . $drawing->getIndexedFilename();
	            
	            copy($filename, $path);
	            
	            $result[$xy] = $path;
	            
	            // for xls
	        } else if ($drawing instanceof \PHPExcel_Worksheet_MemoryDrawing) {
	            
	            $image = $drawing->getImageResource();
	            
	            $renderingFunction = $drawing->getRenderingFunction();
	            
	            switch ($renderingFunction) {
	                
	                case \PHPExcel_Worksheet_MemoryDrawing::RENDERING_JPEG:
	                    
	                    $imageFileName = $drawing->getIndexedFilename();
	                    $path = $path . $drawing->getIndexedFilename();
	                    imagejpeg($image, $path);
	                    break;
	                    
	                case \PHPExcel_Worksheet_MemoryDrawing::RENDERING_GIF:
	                    $imageFileName = $drawing->getIndexedFilename();
	                    $path = $path . $drawing->getIndexedFilename();
	                    imagegif($image, $path);
	                    break;
	                    
	                case \PHPExcel_Worksheet_MemoryDrawing::RENDERING_PNG:
	                    $imageFileName = $drawing->getIndexedFilename();
	                    $path = $path . $drawing->getIndexedFilename();
	                    imagegif($image, $path);
	                    break;
	                    
	                case \PHPExcel_Worksheet_MemoryDrawing::RENDERING_DEFAULT:
	                    $imageFileName = $drawing->getIndexedFilename();
	                    $path = $path . $drawing->getIndexedFilename();
	                    imagegif($image, $path);
	                    break;
	            }
	            $result[$xy] = $imageFileName;
	        }
	    }
	    
	    return $result;
	}  
	
/*
*审核操作
*/
	public function check(){
		if(IS_AJAX&&I('post.data')){
			if(D('ApplyDesign')->check(I('post.type'),I('post.data')['id'],I('post.why')))$this->ajaxReturn('ok');
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
    	            $w['apply_school'] = 's' . session('school_id');
    	            $school = 's' . session('school_id');
    	            if(session('position_id') == '10'){
    	                //校长
    	                $w['state'] = 10;
    	            }else if(session('auth_id') == '439'){
    	                //何亮
    	                unset($w['apply_school']);
    	                $w['area'] = '黑龙江';
    	                $w['state'] = 20;
    	            }elseif(session('auth_id') == '651'){
    	                //王大鹏
    	                unset($w['apply_school']);
    	                $w['area'] = '吉林';
    	                $w['state'] = 20;
    	            }elseif(session('auth_id') == '2101'){
    	                unset($w['apply_school']);
    	                unset($w['area']);
    	                $w['state'] = 70;
    	            }else{
    	                unset($w['state']);
    	                $w['add_user'] = session('auth_id');
    	                $w['state'] = array('elt',80);
    	                $flag = 1;
    	            }
    	        }else{
    	            
    	            $w['apply_school'] = 'b' . session('dept_id');
    	            $school = 'b' . session('dept_id');
    	            
    	            if(session('auth_id') == '1293'){
    	                //姜博文
    	                unset($w['apply_school']);
    	                $w['area'] = '辽宁';
    	                $w['state'] = 20;
    	            }elseif(session('auth_id') == '1'){
    	                //何亮
    	                unset($w['apply_school']);
    	                $w['area'] = '黑龙江';
    	                $w['state'] = 20;
    	            }elseif(get_school_name()=='集团' && (session('auth_id') == '89')){
    	                //王胜鑫
    	                unset($w['apply_school']);
    	                unset($w['area']);
    	                $w['state'] = array('in','40,50');
    	            }elseif(get_school_name()=='集团' && (session('auth_id') == '90')){
    	                //赵锡睿
    	                unset($w['apply_school']);
    	                unset($w['area']);
    	                $w['state'] = 60;
    	            }elseif(session('auth_id') == '2101'){
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
    	    
    	    $w['product_type'] = 2;
    		
    	    if($flag){
    	        $map = $w;
    	    }else{
    	        if($stage == 1){
    	            $map['_logic']='or';
    	            $map['_complex'] = $w;
    	            $map['apply_school&add_user&product_type&state&is_del'] = array($school,session('auth_id'),'2',array('elt',80),array('neq',1),'_multi'=>true);
    	        }else{
    	            $map = $w;
    	        }
    	    }
    	    
    	    
    	    $data=M('applyDesign')->where($map)->order('id desc')->field('id, state, apply_month, apply_type, apply_school, apply_user, tel, expect_date, email, design_type, flat_count, flat_size, flat_format, flat_create_unit, space_pic, install_pos_pic, space_show_pic, content_descp, reference_pic, record, why, create_time, update_time, is_del, add_user, add_user_name, back, descp,area,design_type_other')->limit(I('get.offset'),I('get.count'))->select();
    		
    		if(get_school_name()!='集团'){
    		  foreach ($data as &$vo){
    		      if(strpos($vo['apply_school'],'s') !== false){
    		          $vo['school_name'] = M('foo_info')->where('id=' . substr($vo['apply_school'],1))->getField('name');
    		      }else if(strpos($vo['apply_school'],'b') !== false){
    		          $vo['school_name'] = M('dept')->where('id=' . substr($vo['apply_school'],1))->getField('name');
    		      }
    		      
    		      if($vo['reference_pic']){
    		          $hrefs = explode(";",$vo['reference_pic']);
    		          $ref = '';
    		          foreach($hrefs as $arr){
    		              $ref .= '<a href="' . $arr . '" target="_blank">' . $arr . '</a><br/>';
    		          }
    		          $vo['references'] = $ref;
    		      }
    		      
    		      
    		      if($vo['space_pic']){
    		          $hrefs = explode(";",$vo['space_pic']);
    		          $ref = '';
    		          foreach($hrefs as $arr){
    		              $ref .= '<a href="' . $arr . '" target="_blank">' . $arr . '</a><br/>';
    		          }
    		          $vo['space_pics'] = $ref;
    		      }
    		      
    		      
    		      if($vo['install_pos_pic']){
    		          $hrefs = explode(";",$vo['install_pos_pic']);
    		          $ref = '';
    		          foreach($hrefs as $arr){
    		              $ref .= '<a href="' . $arr . '" target="_blank">' . $arr . '</a><br/>';
    		          }
    		          $vo['install_pos_pics'] = $ref;
    		      }
    		      
    		      
    		      if($vo['space_show_pic']){
    		          $hrefs = explode(";",$vo['space_show_pic']);
    		          $ref = '';
    		          foreach($hrefs as $arr){
    		              $ref .= '<a href="' . $arr . '" target="_blank">' . $arr . '</a><br/>';
    		          }
    		          $vo['space_show_pics'] = $ref;
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
    		          if($vo['state'] >= 80){
    		              $vo['edit'] = 0;
    		          }
    		      }
    		      
    		     /* if($vo['state']>0){
    		         if($w['stage']==1){
    		             $vo['edit'] = 0;
    		         }else{
    		             $vo['edit'] = 1;
    		         }
    		     }else{
    		      $vo['edit'] = 1;
    		     } */
    		  }
    		}else{
         	  foreach ($data as &$vo){
         	      if(strpos($vo['apply_school'],'s') !== false){
         	          $vo['school_name'] = M('foo_info')->where('id=' . substr($vo['apply_school'],1))->getField('name');
         	      }else if(strpos($vo['apply_school'],'b') !== false){
         	          $vo['school_name'] = M('dept')->where('id=' . substr($vo['apply_school'],1))->getField('name');
         	      }
         	      
         	      if($vo['reference_pic']){
         	          $hrefs = explode(";",$vo['reference_pic']);
         	          $ref = '';
         	          foreach($hrefs as $arr){
         	              $ref .= '<a href="' . $arr . '" target="_blank">' . $arr . '</a><br/>';
         	          }
         	          $vo['references'] = $ref;
         	      }
         	      
         	      
         	      if($vo['space_pic']){
         	          $hrefs = explode(";",$vo['space_pic']);
         	          $ref = '';
         	          foreach($hrefs as $arr){
         	              $ref .= '<a href="' . $arr . '" target="_blank">' . $arr . '</a><br/>';
         	          }
         	          $vo['space_pics'] = $ref;
         	      }
         	      
         	      
         	      if($vo['install_pos_pic']){
         	          $hrefs = explode(";",$vo['install_pos_pic']);
         	          $ref = '';
         	          foreach($hrefs as $arr){
         	              $ref .= '<a href="' . $arr . '" target="_blank">' . $arr . '</a><br/>';
         	          }
         	          $vo['install_pos_pics'] = $ref;
         	      }
         	      
         	      
         	      if($vo['space_show_pic']){
         	          $hrefs = explode(";",$vo['space_show_pic']);
         	          $ref = '';
         	          foreach($hrefs as $arr){
         	              $ref .= '<a href="' . $arr . '" target="_blank">' . $arr . '</a><br/>';
         	          }
         	          $vo['space_show_pics'] = $ref;
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
         	          if($vo['state'] >= 80){
         	              $vo['edit'] = 0;
         	          }
         	      }
         		 
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
	    if(session('auth_id') !== '1'){
	        die;
	    }
	    $w=I('post.');
	    array_empty_delt($w);
	    unset($w['stage']);
	    $w['is_del']=0;
	    
	    if($w['date1'])$w['create_time']=['between',[$w['date1'].' 00:00:00',$w['date2'].' 23:59:59']];
	    
	    if($w['apply_user']){
	        $w['apply_user'] = array('like','%'. $w['apply_user'] . '%');
	    }
	    
	    $w['product_type'] = 2;
	    
	    $dat=M('applyDesign')->where($w)->order('id desc')->field("id, state, apply_month, apply_type, apply_school, apply_user, tel, expect_date, email, design_type, flat_count, flat_size, flat_format, flat_create_unit, space_pic, install_pos_pic, space_show_pic, content_descp, reference_pic, record, why, create_time, update_time, is_del, add_user, add_user_name, CASE back WHEN 1 THEN '退回' else '正常' END as back, descp,area,design_type_other")->select();;
	    
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
        				<td>设计类型其它</td>
                        <td>文字内容</td>
        				<td>空间实景图片</td>
        				<td>安放位置图片</td>
        				<td>空间显示信息图片</td>
                        <td>参考案例</td>
                        <td>区域</td>
                        <td>备注</td>
                        <td>退回原因</td>
        				<td>最后审核时间</td>
        				<td>创建人</td>
        				</tr>";
	    $apply_state=get_config('APPLYDESIGN_STATE');
	    
	    foreach ($dat as &$vo) {
	        
	        $vo['state']=$apply_state[$vo['state']];
	        $vo['apply_school']=$this->school_all[$vo['apply_school']];
	        
	        if($vo['reference_pic']){
	            $hrefs = explode(";",$vo['reference_pic']);
	            $ref = '';
	            foreach($hrefs as $arr){
	                $ref .= '<a href="' . C('WWW').'/oa2'.substr($arr,1) . '" target="_blank">' . substr($arr,1) . '</a><br/>';
	            }
	            $vo['reference_pic'] = $ref;
	        }
	        
	        if($vo['space_pic']){
	            $hrefs = explode(";",$vo['space_pic']);
	            $ref = '';
	            foreach($hrefs as $arr){
	                $ref .= '<a href="' . C('WWW').'/oa2'.substr($arr,1) . '" target="_blank">' . substr($arr,1) . '</a><br/>';
	            }
	            $vo['space_pic'] = $ref;
	        }
	        
	        
	        if($vo['install_pos_pic']){
	            $hrefs = explode(";",$vo['install_pos_pic']);
	            $ref = '';
	            foreach($hrefs as $arr){
	                $ref .= '<a href="' . C('WWW').'/oa2'.substr($arr,1) . '" target="_blank">' . substr($arr,1) . '</a><br/>';
	            }
	            $vo['install_pos_pic'] = $ref;
	        }
	        
	        
	        if($vo['space_show_pic']){
	            $hrefs = explode(";",$vo['space_show_pic']);
	            $ref = '';
	            foreach($hrefs as $arr){
	                $ref .= '<a href="' . C('WWW').'/oa2'.substr($arr,1) . '" target="_blank">' . substr($arr,1) . '</a><br/>';
	            }
	            $vo['space_show_pic'] = $ref;
	        }
	        
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
	        $output .= "<td>".$vo['design_type_other']."</td>";
	        $output .= "<td>".$vo['content_descp']."</td>";
	        $output .= "<td>".$vo['space_pic']."</td>";
	        $output .= "<td>".$vo['install_pos_pic']."</td>";
	        $output .= "<td>".$vo['space_show_pic']."</td>";
	        $output .= "<td>".$vo['reference_pic']."</td>";
	        $output .= "<td>".$vo['area']."</td>";
	        $output .= "<td>".$vo['descp']."</td>";
	        $output .= "<td>".$vo['why']."</td>";
	        $output .= "<td>".$vo['update_time']."</td>";
	        $output .= "<td>".$vo['add_user_name']."</td>";
	        $output .= "</tr>";
	    }
	    $output .= "</TABLE>";
	    $output .= "</BODY>";
	    $output .= "</HTML>";
	    $filename='空间类设计申请明细导出表'.date('Y-m-d');
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
