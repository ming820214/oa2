<?php
namespace Home\Controller;

class BusinessDataController extends HomeController {

	//把校区列表、科目类别输出到前段模版
	public function _initialize(){
        parent::_initialize();
		foreach (C('SCHOOL') as $v) {
			$school[$v['id']]=$v['name'];
		}
		$this->assign('school',$school);//校区
		$subject=M("FinanceType")->where('pid=268')->order("sort ASC")->select();
		foreach ($subject as $v) {
			$subjectId[$v['id']]=$v['name'];
		}
		$this->subject=$subject;
		$this->subjectId=$subjectId;
	}

    public function index(){
        $this -> display();
    }

	public function statistic(){
        $this -> display();
	}

	public function bar(){
        $this -> display('bar');
	}
	
	
	public function newBar(){
	    $this -> display('new_bar');
	}


	public function newPersonBar(){
        $this -> display('new_person_bar');
	}


	public function newAverageBar(){
        $this -> display('new_average_bar');
	}
	
	public function renewBar(){
	    $this -> display('renew_bar');
	}
	
	
	public function renewPersonBar(){
	    $this -> display('renew_person_bar');
	}
	
	
	public function renewAverageBar(){
	    $this -> display('renew_average_bar');
	}
	
	public function consumpArchBar(){
	    $this -> display('consump_achieve_bar');
	}
	
	
	public function consumpClsBar(){
	    $this -> display('consump_class_bar');
	}
	
	public function subjectBar(){
	    $this -> display('subject_bar');
	}
	
	public function validClass(){
	    $this -> display('valid_class_list');
	}
	
	public function classStudent(){
	    $this -> display('class_student_list');
	}
	
	public function backStudent(){
	    $this -> display('back_student_list');
	}
/**
####################################增删改查
*/
/*
*申请添加、修改
*/
	public function write(){
		array_empty_delt($_POST);
		$mod=M('businessData');
		$mod->create();

		//修改
		if(I('post.id')){
			$mod->save();
			$this->ajaxReturn('更新成功');
		}

		$mod->creater=session('auth_id');
		
		//新增
		if($mod->add())$this->ajaxReturn('添加成功');

		$this->ajaxReturn('操作失败');
	}
/*
*审核操作
*/
	public function check(){
		if(IS_AJAX&&I('post.data')){
		    
		    if(I('post.type') == -1){
		        $status = 2;
		    }else{
		        $status = 1;
		    }
		    if(M('businessData')->where(I('post.data')['id'])->save(array('state'=>$status))){
		        $this->ajaxReturn('ok');
		    }else{
		        $this->ajaxReturn('提交出错，请与系统管理员联系！');
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
    		if($w['month1']){
    		    $w['_string']= "CONCAT_WS('-',`month`,'01') between '" . $w['month1'] . "-01'" . " and '" . $w['month2'] . "-01'"; 
    		}
    		$w['creater'] = session('auth_id');
    		$w['state'] = array('neq',2);
//     		$w['school'] = session('school_id');
//     		$w['region'] = session('region_id');
    		$data=M('businessData')->where($w)->order('state desc,create_time desc')->limit(I('get.offset'),I('get.count'))->select();
    		$total=M('businessData')->where($w)->count();
    		$this->get_edit($data,$w);//设置页面修改权限
    		$this->ajaxReturn(['state'=>'ok','data'=>$data,'total'=>$total]);
    	}else{
    		$this->ajaxReturn(['state'=>'error','info'=>'没有查询到数据']);
    	}
    }

    /*
     *页面数据列表
     */
    public function ajax_statistic(){
        if(IS_AJAX){
            $w=I('get.search');
            array_empty_delt($w);
            
            if($w['month1']){
                $w['_string']= "CONCAT_WS('-',`month`,'01') between '" . $w['month1'] . "-01'" . " and '" . $w['month2'] . "-01'";
            }
            
            $month = $w['month1'] . "至" . $w['month2'];
            
            $w['state'] = array('eq',1);
            
            if(session('auth_id') == 1283){
                $w['area'] = 20;
            }else if(session('auth_id') == 673){
                $w['area'] = 10;
            }else if(session('auth_id') == 492){
                $w['area'] = 40;
            }else if(session('auth_id') == 651){
                $w['area'] = 30;
            }else if(session('auth_id') == 2100){
                $w['area'] = 50;
            }else if(session('auth_id') == 2095){
                $w['area'] = 60;
            }else if(session('position_id') == 10){
                $w['school'] = session('school_id');
               
                $data=M('businessData')->where($w)
                ->group('school')
                ->field("school as unit,'" . $month . "' as `month`,sum(all_achieve) as all_achieve, sum(all_part_achieve) as all_part_achieve, sum(new_month_achieve) as new_month_achieve, sum(new_month_person) as new_month_person, sum(new_month_achieve)/sum(new_month_person) as new_month_average, sum(renew_month_achieve) as renew_month_achieve, sum(renew_month_person) as renew_month_person, sum(renew_month_achieve)/sum(renew_month_person) as renew_month_average, sum(consump_achieve) as consump_achieve, sum(consump_class) as consump_class, sum(valid_person) as valid_person, sum(class_person) as class_person, sum(back_person) as back_person,round(sum(consump_class) / sum(valid_person),2) AS valid_average_class,round(sum(consump_class) / sum(class_person),2) AS class_student_average,round(sum(back_person) / sum(valid_person),2) AS back_rate,round(sum(consump_achieve) / sum(teacher_num),2) AS school_person_rate,round(sum(consump_achieve) / sum(school_area),2) AS school_area_rate ")
                ->select();
                
                if($data){
                    foreach($data as &$obj2){
                        $obj2['unit'] = get_school_name($obj2['unit']);
                    }
                }
                
                $this->ajaxReturn(['state'=>'ok','data'=>$data]);
            }else{
                
                $data2=M('businessData')->where($w)
                ->group('area')
                ->field("area as unit,'" . $month . "' as `month`,sum(all_achieve) as all_achieve, sum(all_part_achieve) as all_part_achieve, sum(new_month_achieve) as new_month_achieve, sum(new_month_person) as new_month_person, sum(new_month_achieve)/sum(new_month_person) as new_month_average, sum(renew_month_achieve) as renew_month_achieve, sum(renew_month_person) as renew_month_person, sum(renew_month_achieve)/sum(renew_month_person) as renew_month_average, sum(consump_achieve) as consump_achieve, sum(consump_class) as consump_class, sum(valid_person) as valid_person, sum(class_person) as class_person, sum(back_person) as back_person,round(sum(consump_class) / sum(valid_person), 2) AS valid_average_class,round(sum(consump_class) / sum(class_person), 2) AS class_student_average, round(sum(back_person) / sum(valid_person), 2) AS back_rate,round(sum(consump_achieve) / sum(teacher_num), 2) AS school_person_rate,round(sum(consump_achieve) / sum(school_area), 2) AS school_area_rate")
                ->select();
                
                if($data2){
                    foreach($data2 as &$vo){
                        $vo['unit'] = get_config('SCHOOL_REGION')[$vo['unit']];
                    }
                }
                
                $data3=M('businessData')->where($w)
                ->group('school')
                ->field("school as unit,'" . $month . "' as `month`,sum(all_achieve) as all_achieve, sum(all_part_achieve) as all_part_achieve, sum(new_month_achieve) as new_month_achieve, sum(new_month_person) as new_month_person, sum(new_month_achieve)/sum(new_month_person) as new_month_average, sum(renew_month_achieve) as renew_month_achieve, sum(renew_month_person) as renew_month_person, sum(renew_month_achieve)/sum(renew_month_person) as renew_month_average, sum(consump_achieve) as consump_achieve, sum(consump_class) as consump_class, sum(valid_person) as valid_person, sum(class_person) as class_person, sum(back_person) as back_person,round(sum(consump_class) / sum(valid_person), 2) AS valid_average_class,round(sum(consump_class) / sum(class_person), 2) AS class_student_average, round(sum(back_person) / sum(valid_person), 2) AS back_rate,round(sum(consump_achieve) / sum(teacher_num), 2) AS school_person_rate,round(sum(consump_achieve) / sum(school_area), 2) AS school_area_rate ")
                ->select();
                
                if($data3){
                    foreach($data3 as &$obj){
                        $obj['unit'] = get_school_name($obj['unit']);
                    }
                }
                
                unset($w['area']);
                $data1 = M('businessData')->where($w)
                ->field("'集团' as unit,'" . $month . "' as `month`,sum(all_achieve) as all_achieve, sum(all_part_achieve) as all_part_achieve, sum(new_month_achieve) as new_month_achieve, sum(new_month_person) as new_month_person, sum(new_month_achieve)/sum(new_month_person) as new_month_average, sum(renew_month_achieve) as renew_month_achieve, sum(renew_month_person) as renew_month_person, sum(renew_month_achieve)/sum(renew_month_person) as renew_month_average, sum(consump_achieve) as consump_achieve, sum(consump_class) as consump_class, sum(valid_person) as valid_person, sum(class_person) as class_person, sum(back_person) as back_person,round(sum(consump_class) / sum(valid_person), 2) AS valid_average_class,round(sum(consump_class) / sum(class_person), 2) AS class_student_average, round(sum(back_person) / sum(valid_person), 2) AS back_rate,round(sum(consump_achieve) / sum(teacher_num), 2) AS school_person_rate,round(sum(consump_achieve) / sum(school_area), 2) AS school_area_rate ")
                ->select();
                
                $data = array_merge($data1,$data2,$data3);
                $this->ajaxReturn(['state'=>'ok','data'=>$data]);
            }
            
                                    
            if(in_array(session('auth_id'),array(1283,673,492,651,2100,2095))){
                
                $data2=M('businessData')->where($w)
                ->group('area')
                ->field("area as unit,'" . $month . "' as `month`,sum(all_achieve) as all_achieve, sum(all_part_achieve) as all_part_achieve, sum(new_month_achieve) as new_month_achieve, sum(new_month_person) as new_month_person, sum(new_month_achieve)/sum(new_month_person) as new_month_average, sum(renew_month_achieve) as renew_month_achieve, sum(renew_month_person) as renew_month_person, sum(renew_month_achieve)/sum(renew_month_person) as renew_month_average, sum(consump_achieve) as consump_achieve, sum(consump_class) as consump_class, sum(valid_person) as valid_person, sum(class_person) as class_person, sum(back_person) as back_person,round(sum(consump_class) / sum(valid_person), 2) AS valid_average_class,round(sum(consump_class) / sum(class_person), 2) AS class_student_average, round(sum(back_person) / sum(valid_person), 2) AS back_rate,round(sum(consump_achieve) / sum(teacher_num), 2) AS school_person_rate,round(sum(consump_achieve) / sum(school_area), 2) AS school_area_rate ")
                ->select();
                
                if($data2){
                    foreach($data2 as &$vo){
                        $vo['unit'] = get_config('SCHOOL_REGION')[$vo['unit']];
                    }
                }
                
                $data3=M('businessData')->where($w)
                ->group('school')
                ->field("school as unit,'" . $month . "' as `month`,sum(all_achieve) as all_achieve, sum(all_part_achieve) as all_part_achieve, sum(new_month_achieve) as new_month_achieve, sum(new_month_person) as new_month_person, sum(new_month_achieve)/sum(new_month_person) as new_month_average, sum(renew_month_achieve) as renew_month_achieve, sum(renew_month_person) as renew_month_person, sum(renew_month_achieve)/sum(renew_month_person) as renew_month_average, sum(consump_achieve) as consump_achieve, sum(consump_class) as consump_class, sum(valid_person) as valid_person, sum(class_person) as class_person, sum(back_person) as back_person,round(sum(consump_class) / sum(valid_person), 2) AS valid_average_class,round(sum(consump_class) / sum(class_person), 2) AS class_student_average, round(sum(back_person) / sum(valid_person), 2) AS back_rate,round(sum(consump_achieve) / sum(teacher_num), 2) AS school_person_rate,round(sum(consump_achieve) / sum(school_area), 2) AS school_area_rate ")
                ->select();
                
                if($data3){
                    foreach($data3 as &$obj){
                        $obj['unit'] = get_school_name($obj['unit']);
                    }
                }
                
                unset($w['area']);
                $data1 = M('businessData')->where($w)
                ->field("'集团' as unit,'" . $month . "' as `month`,sum(all_achieve) as all_achieve, sum(all_part_achieve) as all_part_achieve, sum(new_month_achieve) as new_month_achieve, sum(new_month_person) as new_month_person, sum(new_month_achieve)/sum(new_month_person) as new_month_average, sum(renew_month_achieve) as renew_month_achieve, sum(renew_month_person) as renew_month_person, sum(renew_month_achieve)/sum(renew_month_person) as renew_month_average, sum(consump_achieve) as consump_achieve, sum(consump_class) as consump_class, sum(valid_person) as valid_person, sum(class_person) as class_person, sum(back_person) as back_person,round(sum(consump_class) / sum(valid_person), 2) AS valid_average_class,round(sum(consump_class) / sum(class_person), 2) AS class_student_average, round(sum(back_person) / sum(valid_person), 2) AS back_rate,round(sum(consump_achieve) / sum(teacher_num), 2) AS school_person_rate,round(sum(consump_achieve) / sum(school_area), 2) AS school_area_rate ")
                ->select();
                
                $data = array_merge($data1,$data2,$data3);
                $this->ajaxReturn(['state'=>'ok','data'=>$data]);
            }
                                    
            
            
            //$this->get_edit($data,$w);//设置页面修改权限
            
        }else{
            $this->ajaxReturn(['state'=>'error','info'=>'没有查询到数据']);
        }
    }
    
    /*
     *各个岗位自己已审核的页面数据列表
     */
    public function allAchieveBar(){
    	if(IS_AJAX){
    		$w=I('get.search');
    		array_empty_delt($w);
    		
    		if($w['month1']){
    		    $w['_string']= "CONCAT_WS('-',`month`,'01') between '" . $w['month1'] . "-01'" . " and '" . $w['month2'] . "-01'";
    		}
    		
    		$w['state'] = array('eq',1);
    		
    		$data=M('businessData')->where($w)
    		->group('area,`month`')
    		->field("area as name,`month`,sum(all_achieve) as y")
    		->select();
    		
    		if($data){
    		    foreach($data as &$vo){
    		        switch($vo['name']){
    		            case '10': $vo['color'] = "#FF0000";break;
    		            case '20': $vo['color'] = "#00FF00";break;
    		            case '30': $vo['color'] = "#0000FF";break;
    		            case '40': $vo['color'] = "#FFFF00";break;
    		        }
    		        $vo['name'] = get_config('SCHOOL_REGION')[$vo['name']];
    		        $vo['y'] = (float)$vo['y'];
    		        
    		    }   
    		}
    		
    		$this->ajaxReturn(['state'=>'ok','data'=>$data]);
    	}else{
    		$this->ajaxReturn(['state'=>'error','info'=>'没有查询到数据']);
    	}
    }
    
    
    public function newMonthAchieveBar(){
        if(IS_AJAX){
            $w=I('get.search');
            array_empty_delt($w);
            
            if($w['month1']){
                $w['_string']= "CONCAT_WS('-',`month`,'01') between '" . $w['month1'] . "-01'" . " and '" . $w['month2'] . "-01'";
            }
            
            $w['state'] = array('eq',1);
            
            $data=M('businessData')->where($w)
            ->group('area,`month`')
            ->field("area as name,`month`,sum(new_month_achieve) as y")
            ->select();
            
            if($data){
                foreach($data as &$vo){
                    switch($vo['name']){
                        case '10': $vo['color'] = "#FF0000";break;
                        case '20': $vo['color'] = "#00FF00";break;
                        case '30': $vo['color'] = "#0000FF";break;
                        case '40': $vo['color'] = "#FFFF00";break;
                    }
                    $vo['name'] = get_config('SCHOOL_REGION')[$vo['name']];
                    $vo['y'] = (float)$vo['y'];
                    
                }
            }
            
            $this->ajaxReturn(['state'=>'ok','data'=>$data]);
        }else{
            $this->ajaxReturn(['state'=>'error','info'=>'没有查询到数据']);
        }
    }
    
    public function newMonthPersonBar(){
        if(IS_AJAX){
            $w=I('get.search');
            array_empty_delt($w);
            
            if($w['month1']){
                $w['_string']= "CONCAT_WS('-',`month`,'01') between '" . $w['month1'] . "-01'" . " and '" . $w['month2'] . "-01'";
            }
            
            $w['state'] = array('eq',1);
            
            $data=M('businessData')->where($w)
            ->group('area,`month`')
            ->field("area as name,`month`,sum(new_month_person) as y")
            ->select();
            
            if($data){
                foreach($data as &$vo){
                    switch($vo['name']){
                        case '10': $vo['color'] = "#FF0000";break;
                        case '20': $vo['color'] = "#00FF00";break;
                        case '30': $vo['color'] = "#0000FF";break;
                        case '40': $vo['color'] = "#FFFF00";break;
                    }
                    $vo['name'] = get_config('SCHOOL_REGION')[$vo['name']];
                    $vo['y'] = (float)$vo['y'];
                    
                }
            }
            
            $this->ajaxReturn(['state'=>'ok','data'=>$data]);
        }else{
            $this->ajaxReturn(['state'=>'error','info'=>'没有查询到数据']);
        }
    }
    
    public function newAverageBarData(){
        if(IS_AJAX){
            $w=I('get.search');
            array_empty_delt($w);
            
            if($w['month1']){
                $w['_string']= "CONCAT_WS('-',`month`,'01') between '" . $w['month1'] . "-01'" . " and '" . $w['month2'] . "-01'";
            }
            
            $w['state'] = array('eq',1);
            
            $data1=M('businessData')->where($w)
            ->group('area,`month`')
            ->field("area as name,`month`,sum(new_month_achieve)/sum(new_month_person) as y")
            ->select();
            
            $data2=M('businessData')->where($w)
            ->field("'集团' as name,`month`,sum(new_month_achieve)/sum(new_month_person) as y")
            ->select();
            
            $data = array_merge($data2,$data1);
            if($data){
                foreach($data as &$vo){
                    switch($vo['name']){
                        case '10': $vo['color'] = "#FF0000";break;
                        case '20': $vo['color'] = "#00FF00";break;
                        case '30': $vo['color'] = "#0000FF";break;
                        case '40': $vo['color'] = "#FFFF00";break;
                        default: $vo['color'] = "#000000";break;
                    }
                    if($vo['name'] != '集团'){
                        $vo['name'] = get_config('SCHOOL_REGION')[$vo['name']];
                    }
                    
                    $vo['y'] = (float)$vo['y'];
                    
                }
            }
            
            $this->ajaxReturn(['state'=>'ok','data'=>$data]);
        }else{
            $this->ajaxReturn(['state'=>'error','info'=>'没有查询到数据']);
        }
    }
    
    
    
    
    public function renewMonthAchieveBar(){
        if(IS_AJAX){
            $w=I('get.search');
            array_empty_delt($w);
            
            if($w['month1']){
                $w['_string']= "CONCAT_WS('-',`month`,'01') between '" . $w['month1'] . "-01'" . " and '" . $w['month2'] . "-01'";
            }
            
            $w['state'] = array('eq',1);
            
            $data=M('businessData')->where($w)
            ->group('area,`month`')
            ->field("area as name,`month`,sum(renew_month_achieve) as y")
            ->select();
            
            if($data){
                foreach($data as &$vo){
                    switch($vo['name']){
                        case '10': $vo['color'] = "#FF0000";break;
                        case '20': $vo['color'] = "#00FF00";break;
                        case '30': $vo['color'] = "#0000FF";break;
                        case '40': $vo['color'] = "#FFFF00";break;
                    }
                    $vo['name'] = get_config('SCHOOL_REGION')[$vo['name']];
                    $vo['y'] = (float)$vo['y'];
                    
                }
            }
            
            $this->ajaxReturn(['state'=>'ok','data'=>$data]);
        }else{
            $this->ajaxReturn(['state'=>'error','info'=>'没有查询到数据']);
        }
    }
    
    public function renewMonthPersonBar(){
        if(IS_AJAX){
            $w=I('get.search');
            array_empty_delt($w);
            
            if($w['month1']){
                $w['_string']= "CONCAT_WS('-',`month`,'01') between '" . $w['month1'] . "-01'" . " and '" . $w['month2'] . "-01'";
            }
            
            $w['state'] = array('eq',1);
            
            $data=M('businessData')->where($w)
            ->group('area,`month`')
            ->field("area as name,`month`,sum(renew_month_person) as y")
            ->select();
            
            if($data){
                foreach($data as &$vo){
                    switch($vo['name']){
                        case '10': $vo['color'] = "#FF0000";break;
                        case '20': $vo['color'] = "#00FF00";break;
                        case '30': $vo['color'] = "#0000FF";break;
                        case '40': $vo['color'] = "#FFFF00";break;
                    }
                    $vo['name'] = get_config('SCHOOL_REGION')[$vo['name']];
                    $vo['y'] = (float)$vo['y'];
                    
                }
            }
            
            $this->ajaxReturn(['state'=>'ok','data'=>$data]);
        }else{
            $this->ajaxReturn(['state'=>'error','info'=>'没有查询到数据']);
        }
    }
    
    public function renewAverageBarData(){
        if(IS_AJAX){
            $w=I('get.search');
            array_empty_delt($w);
            
            if($w['month1']){
                $w['_string']= "CONCAT_WS('-',`month`,'01') between '" . $w['month1'] . "-01'" . " and '" . $w['month2'] . "-01'";
            }
            
            $w['state'] = array('eq',1);
            
            $data1=M('businessData')->where($w)
            ->group('area,`month`')
            ->field("area as name,`month`,sum(renew_month_achieve)/sum(renew_month_person) as y")
            ->select();
            
            $data2=M('businessData')->where($w)
            ->field("'集团' as name,`month`,sum(renew_month_achieve)/sum(renew_month_person) as y")
            ->select();
            
            $data = array_merge($data2,$data1);
            if($data){
                foreach($data as &$vo){
                    switch($vo['name']){
                        case '10': $vo['color'] = "#FF0000";break;
                        case '20': $vo['color'] = "#00FF00";break;
                        case '30': $vo['color'] = "#0000FF";break;
                        case '40': $vo['color'] = "#FFFF00";break;
                        default: $vo['color'] = "#000000";break;
                    }
                    if($vo['name'] != '集团'){
                        $vo['name'] = get_config('SCHOOL_REGION')[$vo['name']];
                    }
                    
                    $vo['y'] = (float)$vo['y'];
                    
                }
            }
            
            $this->ajaxReturn(['state'=>'ok','data'=>$data]);
        }else{
            $this->ajaxReturn(['state'=>'error','info'=>'没有查询到数据']);
        }
    }
    
    
    public function consumpAchieveBar(){
        if(IS_AJAX){
            $w=I('get.search');
            array_empty_delt($w);
            
            if($w['month1']){
                $w['_string']= "CONCAT_WS('-',`month`,'01') between '" . $w['month1'] . "-01'" . " and '" . $w['month2'] . "-01'";
            }
            
            $w['state'] = array('eq',1);
            
            $data=M('businessData')->where($w)
            ->group('area,`month`')
            ->field("area as name,`month`,sum(consump_achieve) as y")
            ->select();
            
            if($data){
                foreach($data as &$vo){
                    switch($vo['name']){
                        case '10': $vo['color'] = "#FF0000";break;
                        case '20': $vo['color'] = "#00FF00";break;
                        case '30': $vo['color'] = "#0000FF";break;
                        case '40': $vo['color'] = "#FFFF00";break;
                    }
                    $vo['name'] = get_config('SCHOOL_REGION')[$vo['name']];
                    $vo['y'] = (float)$vo['y'];
                    
                }
            }
            
            $this->ajaxReturn(['state'=>'ok','data'=>$data]);
        }else{
            $this->ajaxReturn(['state'=>'error','info'=>'没有查询到数据']);
        }
    }
    
    
    public function consumpClassBar(){
        if(IS_AJAX){
            $w=I('get.search');
            array_empty_delt($w);
            
            if($w['month1']){
                $w['_string']= "CONCAT_WS('-',`month`,'01') between '" . $w['month1'] . "-01'" . " and '" . $w['month2'] . "-01'";
            }
            
            $w['state'] = array('eq',1);
            
            $data=M('businessData')->where($w)
            ->group('area,`month`')
            ->field("area as name,`month`,sum(consump_class) as y")
            ->select();
            
            if($data){
                foreach($data as &$vo){
                    switch($vo['name']){
                        case '10': $vo['color'] = "#FF0000";break;
                        case '20': $vo['color'] = "#00FF00";break;
                        case '30': $vo['color'] = "#0000FF";break;
                        case '40': $vo['color'] = "#FFFF00";break;
                    }
                    $vo['name'] = get_config('SCHOOL_REGION')[$vo['name']];
                    $vo['y'] = (float)$vo['y'];
                    
                }
            }
            
            $this->ajaxReturn(['state'=>'ok','data'=>$data]);
        }else{
            $this->ajaxReturn(['state'=>'error','info'=>'没有查询到数据']);
        }
    }
    
    
    public function subjectBarData(){
        if(IS_AJAX){
            $w=I('get.search');
            array_empty_delt($w);
            
            if($w['month1']){
                $w['_string']= "CONCAT_WS('-',`month`,'01') between '" . $w['month1'] . "-01'" . " and '" . $w['month2'] . "-01'";
            }
            
            $w['state'] = array('eq',1);
            
            $data1=M('businessData')->where($w)
            ->group('name,`month`')
            ->field("'数学' as name,`month`,sum(sx) as y")
            ->select();
            
            $data2=M('businessData')->where($w)
            ->group('name,`month`')
            ->field("'语文' as name,`month`,sum(yw) as y")
            ->select();
            
            $data3=M('businessData')->where($w)
            ->group('name,`month`')
            ->field("'英语' as name,`month`,sum(yy) as y")
            ->select();
            
            $data4=M('businessData')->where($w)
            ->group('name,`month`')
            ->field("'物理' as name,`month`,sum(wl) as y")
            ->select();
            
            $data5=M('businessData')->where($w)
            ->group('name,`month`')
            ->field("'化学' as name,`month`,sum(hx) as y")
            ->select();
            
            
            $data6=M('businessData')->where($w)
            ->group('name,`month`')
            ->field("'生物' as name,`month`,sum(sw) as y")
            ->select();
            
            
            $data7=M('businessData')->where($w)
            ->group('name,`month`')
            ->field("'文综' as name,`month`,sum(wz) as y")
            ->select();
            
            
            $data = array_merge($data1,$data2,$data3,$data4,$data5,$data6,$data7);
            
            if($data){
                foreach($data as &$vo){
                    switch($vo['name']){
                        case '数学': $vo['color'] = "#FF0000";break;
                        case '语文': $vo['color'] = "#00FF00";break;
                        case '英语': $vo['color'] = "#0000FF";break;
                        case '物理': $vo['color'] = "#FFFF00";break;
                        case '化学': $vo['color'] = "#00FFFF";break;
                        case '生物': $vo['color'] = "#CDFF00";break;
                        default: $vo['color'] = "#000000";break;
                    }
                    
                    $vo['y'] = (float)$vo['y'];
                    
                }
            }
            
            $this->ajaxReturn(['state'=>'ok','data'=>$data]);
        }else{
            $this->ajaxReturn(['state'=>'error','info'=>'没有查询到数据']);
        }
    }
    
    public function schoolPersonBar(){
        if(IS_AJAX){
            $w=I('get.search');
            array_empty_delt($w);
            
            if($w['month1']){
                $w['_string']= "CONCAT_WS('-',`month`,'01') between '" . $w['month1'] . "-01'" . " and '" . $w['month2'] . "-01'";
            }
            
            $w['state'] = array('eq',1);
            
            $data=M('businessData')->where($w)
            ->group('area,`month`')
            ->field("area as name,`month`,round(sum(consump_achieve) / sum(teacher_num), 2) as y")
            ->select();
            
            if($data){
                foreach($data as &$vo){
                    switch($vo['name']){
                        case '10': $vo['color'] = "#FF0000";break;
                        case '20': $vo['color'] = "#00FF00";break;
                        case '30': $vo['color'] = "#0000FF";break;
                        case '40': $vo['color'] = "#FFFF00";break;
                    }
                    $vo['name'] = get_config('SCHOOL_REGION')[$vo['name']];
                    $vo['y'] = (float)$vo['y'];
                    
                }
            }
            
            $this->ajaxReturn(['state'=>'ok','data'=>$data]);
        }else{
            $this->ajaxReturn(['state'=>'error','info'=>'没有查询到数据']);
        }
    }
    
    
    public function schoolAreaBar(){
        if(IS_AJAX){
            $w=I('get.search');
            array_empty_delt($w);
            
            if($w['month1']){
                $w['_string']= "CONCAT_WS('-',`month`,'01') between '" . $w['month1'] . "-01'" . " and '" . $w['month2'] . "-01'";
            }
            
            $w['state'] = array('eq',1);
            
            $data=M('businessData')->where($w)
            ->group('area,`month`')
            ->field("area as name,`month`,round(sum(consump_achieve) / sum(school_area), 2) as y")
            ->select();
            
            if($data){
                foreach($data as &$vo){
                    switch($vo['name']){
                        case '10': $vo['color'] = "#FF0000";break;
                        case '20': $vo['color'] = "#00FF00";break;
                        case '30': $vo['color'] = "#0000FF";break;
                        case '40': $vo['color'] = "#FFFF00";break;
                    }
                    $vo['name'] = get_config('SCHOOL_REGION')[$vo['name']];
                    $vo['y'] = (float)$vo['y'];
                    
                }
            }
            
            $this->ajaxReturn(['state'=>'ok','data'=>$data]);
        }else{
            $this->ajaxReturn(['state'=>'error','info'=>'没有查询到数据']);
        }
    }
    
    public function validClassList(){
        if(IS_AJAX){
            $w=I('get.search');
            array_empty_delt($w);
            
            
            $w['state'] = array('eq',1);
            
            $data=M('businessData')->where($w)
            ->group('school,`month`')
            ->order('y DESC')
            ->field("school as name,`month`,round(sum(consump_class) / sum(valid_person), 2) as y")
            ->select();
            
            if($data){
                foreach($data as &$vo){
                    
                    $vo['name'] = get_school_name($vo['name']);
                    $vo['y'] = (float)$vo['y'];
                    
                }
            }
            
            $this->ajaxReturn(['state'=>'ok','data'=>$data]);
        }else{
            $this->ajaxReturn(['state'=>'error','info'=>'没有查询到数据']);
        }
    }
    
    
    public function classStudentList(){
        if(IS_AJAX){
            $w=I('get.search');
            array_empty_delt($w);
            
            
            $w['state'] = array('eq',1);
            
            $data=M('businessData')->where($w)
            ->group('school,`month`')
            ->order('y DESC')
            ->field("school as name,`month`,round(sum(consump_class) / sum(class_person), 2) as y")
            ->select();
            
            if($data){
                foreach($data as &$vo){
                    
                    $vo['name'] = get_school_name($vo['name']);
                    $vo['y'] = (float)$vo['y'];
                    
                }
            }
            
            $this->ajaxReturn(['state'=>'ok','data'=>$data]);
        }else{
            $this->ajaxReturn(['state'=>'error','info'=>'没有查询到数据']);
        }
    }
    
    
    public function backStudentList(){
        if(IS_AJAX){
            $w=I('get.search');
            array_empty_delt($w);
            
            
            $w['state'] = array('eq',1);
            
            $data=M('businessData')->where($w)
            ->group('school,`month`')
            ->order('y DESC')
            ->field("school as name,`month`,round(sum(back_person) / sum(valid_person), 2) as y")
            ->select();
            
            if($data){
                foreach($data as &$vo){
                    
                    $vo['name'] = get_school_name($vo['name']);
                    $vo['y'] = (float)$vo['y'];
                    
                }
            }
            
            $this->ajaxReturn(['state'=>'ok','data'=>$data]);
        }else{
            $this->ajaxReturn(['state'=>'error','info'=>'没有查询到数据']);
        }
    }
    
    //设置数据到页面的修改权限，$v['edit']，1允许，0不允许
	private function get_edit(&$data,$w){
		foreach ($data as &$v) {
		    $v['school_name'] = get_school_name($v['school']);
		    $v['area_name'] = get_config('SCHOOL_REGION')[$v['area']];
			$v['edit']=0;
			if($v['creater'] == session('auth_id') && !$v['state']){
			    $v['edit'] = 1;
			}else{
			    $v['edit'] = 0;
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
