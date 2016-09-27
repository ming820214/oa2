<?php
namespace Home\Model;
use Think\Model\ViewModel;

class  CourseViewModel extends ViewModel {
    public $viewFields = array(
        'Course'        => ['*','_type' => 'left'],
        'UnitpriceRole' => ['is_join','count','label','id' => 'plan_id', 'name' => 'plan_name', '_on' => 'UnitpriceRole.id=Course.unit_plan']
        );

    public function getList($condition, $start, $count, $order) {
        $list = $this->where($condition)->limit($start, $count)->order($order)->select();
        $CourseSbt = D('CourseSbtView');

        $sbt = [];
        foreach ($list as &$item) {
            $sbt = $CourseSbt->getSbt($item['id']);
            $sbt_str = '';
            if (is_array($sbt)) {
                foreach ($sbt as $val) {
                    $sbt_str .= $val['subject_name'].' > '.$val['teacher_name'].'<br />';
                }
            }
            $item['subject_teacher'] = trim($sbt_str, '<br />');
            $item['create_time_str'] = formatTime('Y:m:d H:i:s', $item['create_time']);
            $item['state_str']       = getCourseStateById($item['state'])['name'];
            $item['std_type_name']   = '';
            foreach(C('SCHOOL_STUDENT_TYPE') as $value) {
                if ($item['std_type'] == $value['id']) {
                    $item['std_type_name'] = $value['name'];
                    break;
                }
            }
        }

        return $list;
    }

//获取课时信息，无分页大小限制
	public function getAllDataList($condition,$order,&$list_stdid) {
		
//		$list_stdid = $this->distinct(TRUE)->where($condition)->order($order)->getField('std_id',TRUE);
		
        $list = $this->where($condition)->order($order)->select();
		
        $CourseSbt = D('CourseSbtView');
		
        $sbt = [];
        foreach ($list as &$item) {
            $sbt = $CourseSbt->getSbt($item['id']);
            $sbt_str = '';
            if (is_array($sbt)) {
                foreach ($sbt as $val) {
                    $sbt_str .= $val['subject_name'].' > '.$val['teacher_name'].'<br />';
                }
            }
            $item['subject_teacher'] = trim($sbt_str, '<br />');
            $item['create_time_str'] = formatTime('Y:m:d H:i:s', $item['create_time']);
            $item['state_str']       = getCourseStateById($item['state'])['name'];
            $item['std_type_name']   = '';
            foreach(C('SCHOOL_STUDENT_TYPE') as $value) {
                if ($item['std_type'] == $value['id']) {
                    $item['std_type_name'] = $value['name'];
                    break;
                }
            }
        }

        return $list;
    }

//获取相应学习管理师管辖的学员及课时信息
	public function getXueguanStudentInfo($condition,$param,$order,$pageNum,$pageCount,&$maxCount,$keyword){
		
		/*$session_id = session_id();
		$cache_data = S($session_id . 'teaching_supervisor');
		
		if(!empty($cache_data)){
		
			if(!empty($pageCount)){
				$result = array_slice($cache_data,$pageNum,$pageCount);	
			}else{
				$result = $cache_data;
			}
			
			$maxCount = count($cache_data);
			return $result;
		}*/
		
		//获取学习管理师所属的学员信息列表
		$stu = D('Students');
		$w['state'] = 1;
		
		//学生类型
		if(!empty($param['student_type'])){
			$w['type'] = $param['student_type'];			
		}
		
		//学生维护频率
		if(!empty($param['frequency'])){
			$w['frequency'] = $param['frequency'];			
		}
		
		//学生回访类型
		if(!empty($param['visit_type'])){
			$w['visit_type'] = $param['visit_type'];			
		}

		//学生的学习管理师
		if(!empty($param['teacher'])){
			$w['xueguan'] = $param['teacher'];			
		}
		
		if(!empty($param['name'])){
			$w['name'] = array('like','%'.$param['name'].'%');	
		}
		
		//学生的所在校区
		if(!empty($param['school']) || $param['school'] === '0'){
			$foo = M('foo_info');
			if($param['school'] === '0'){
				$school_name = '集团';	
			}else{
				$school_name = $foo->where('id=%d',$param['school'])->field(trim('name'))->find();	
			}
			
			if(!empty($school_name)){
				$w[trim('school')] = $school_name['name'];	
			}
			
		}
		
		//筛选年级信息
		if(!empty($param['grade'])){
			$w['grade'] = $param['grade']; 	
		}
		
		//查询该学管管辖的学员学号列表
		$std_lst = $stu->where($w)->order($order)->getField('std_id',TRUE);
		if(empty($std_lst)){
			return NULL;
		}
		//课程表实例化
		$cl = M('hw001.Class',NULL);
		
		//有费学员学号列表
		$money = D('Consumption');
		$co = $money->order($order)->group('std_id')->having('sum("value")>0')->getField('std_id',TRUE);
		
		
		
		if(!empty($param['huifang'])){
			//维护回访表实例化
			$weihu = M('weihu');
			//如果未回访条件不为空
			$param_wh['Date(max(create_time))']=array('egt',$param['huifang']);
			$w_h['std_id'] = array('in',$std_lst);
			$weihu_std_id_arr = $weihu->where($w_h)->group('std_id')->having('DATEDIFF(CURDATE(),Date(max(create_time)))>=' . $param['huifang'])->order('Date(max(create_time)) DESC')->getField('std_id',TRUE);
			$w['std_id'] = array('in',$weihu_std_id_arr);
			unset($std_lst);
			$std_lst = $weihu_std_id_arr;
			unset($w_h);
			unset($param_wh);
			unset($weihu); //销毁维护回访表实例化变量
		}
		
		
		//临时存储变量初始化
		$data = NULL;
		$list = null;
		$param_w = null;
		$arr = null;
		$arrd = null;
		
		//学员课程状态信息筛选
		switch($param['state']){
			case 1:
				//全部
				$data = $stu->where($w)->order($order)->select();
				foreach($data as &$item){
					$item['sex'] = $item['sex'] == 0? '女':'男';
				}
				unset($item); // 最后取消掉引用
				$condition['std_id'] = array('in',$std_lst);
				$list = $this->getAllDataList($condition,$order,$list_std);
			break;
			case 2:
				//有费学员
				$arr = array_intersect($co, $std_lst); //查询所有有费的学员信息	
				
			break;
			case 3:
//				有费(未上课时间不超过30天)
				$w3['timee']=array('gt',date('Y-m-d',time()-30*24*3600));
				$w3['state']=array('neq',2);
				$w3['std_id'] = array('in',$std_lst);
				//未上课时间不超过30天
				$c_std_lst = $cl->where($w3)->group('std_id')->having('count(*)>0')->getField('std_id',TRUE);
				
				//查询所有有费的学员信息	
				$arrd = array_intersect($co, $std_lst); 
				
				$arr = array_intersect($arrd, $c_std_lst);
							
				
			break;
			case 4:
//				有费(超过30天未上课)
				$w4['timee']=array('gt',date('Y-m-d',time()-30*24*3600));
				$w4['state']=array('neq',2);
				$w4['std_id'] = array('in',$std_lst);
				
				//30天内都上过课的学员学号列表
				$cl_std_lst = $cl->where($w4)->group('std_id')->having('count(*)>0')->getField('std_id',TRUE);
				
				//超过30天未上课的学员学号列表
				$temp_arr = array_diff($std_lst, $cl_std_lst);
				
				//查询所有有费的学员信息	
				$arrd = array_intersect($co, $std_lst); 
				
				$arr = array_intersect($arrd, $temp_arr);
				
				
			break;
			case 5:
				//没费，没课的学员std_id列表
				//没费学员
				$nol = $money->order($order)->group('std_id')->having('sum(value)=0')->getField('std_id',TRUE);
				
				$w5['timee']=array('gt',date('Y-m-d',time()-30*24*3600));	
				$w5['state']=array('neq',2);
				$w5['std_id'] = array('in',$std_lst);
				//有课学员
				$cd_std_lst = $cl->where($w5)->group('std_id')->having('count(*)>0')->getField('std_id',TRUE);
				
				$arrd = array_diff($std_lst, $cd_std_lst);
								
				$arr = array_intersect($nol,$arrd);
				
			break; 
		}

		
		
		//对相应的信息进行查询
		if($param['state'] != 1 && count($arr)>0){
			$param_w['std_id'] = array('in',$arr); 
			$data = $stu->where($param_w)->order($order)->select();
			foreach($data as &$item){
				$item['sex'] = $item['sex'] == 0? '女':'男';
			}
			unset($item); // 最后取消掉引用
			
			$condition['std_id'] = array('in',$arr);
			
			$list = $this->getAllDataList($condition,$order,$list_std);
		}
		
		//销毁临时变量
		unset($param_w);
		unset($arr);
		unset($arrd);		
		
		$cls = D('class');
		$lession_hour = 0;
		$wc = NULL;
		
		//计算总课时 使用的课时及剩余的课时外加学员课时状态信息
		$state = [];
		foreach($data as &$dt){
			$state = [];
			foreach($list as &$item){
				if($dt['std_id'] === $item['std_id']){
					$dt['hour'] += $item['hour'] + $item['ext_hour'];
					$dt['used_hour'] += $item['used_hour'];
					$dt['plan_name'] .= $item['plan_name'];
					
					if(($item['state']>=200) && ($item['state']<=500)){
						array_push($state,$item['state']);	
					}
					
					if($item['state'] == 200){
						$dt['subject_teacher'] .= $item['subject_teacher'];
					}
				}
			}
			unset($item); // 最后取消掉引用
			//针对拥有多个课程状态的学员，获取该学员的有效状态
			if(count($state)>0){
				$dt['state'] = min($state);	
			}else{
				$dt['state'] = '';
			}
			//计算剩余课时
			if(!empty($dt['hour'])){
				$dt['remain_hour'] = $dt['hour'] - $dt['used_hour'];	
			}
			
			
			
			$wc['std_id'] = $dt['std_id'];
			$wc['timee'] = array('egt',date('Y/m/d'));
			$lession_hour = $cls->where($wc)->sum('count');
			$dt['lession_hour'] = $lession_hour;
			
			$wt['std_id'] = $dt['std_id'];
			$wt['timee'] = array('eq',date('Y/m/d'));
			$day_hour = $cls->where($wt)->sum('count');
			$dt['day_hour'] = empty($day_hour)? 0 : $day_hour; //当天总课时;
			$wt['cwqr'] = array('exp','is not null');
			$used_hour = $cls->where($wt)->sum('count');
			$dt['used_hour'] = empty($used_hour)? 0 : $used_hour; //实到课时;
			$dt['unused_hour'] = $day_hour - $used_hour; //缺席课时;
	    }
		
		unset($wc); //销毁变量
		unset($lession_hour); //销毁变量
		
		unset($dt); // 最后取消掉引用
		//销毁临时变量
		unset($state);
		
		//筛选课程分类信息
		if(!empty($param['course_type'])){
			foreach($data as $key => $value){
				if(!empty($value['plan_name']) && strpos($value['plan_name'], $param['course_type']) === FALSE){
					unset($data[$key]);
				}elseif(empty($value['plan_name'])){
					unset($data[$key]);
				}
			}
		}
		
		//筛选课时分类信息
		if(($param['remainhours'] == '0') || (!empty($param['remainhours']))){
			
			foreach($data as $key => $value){
				if($param['remainhours'] == 0){
					if($value['remain_hour'] != 0){
						unset($data[$key]);
					}
				}elseif($param['remainhours'] == 380){
					if($value['remain_hour'] <= 380){
						unset($data[$key]);
					}
				}else{
					$art = split(",", $param['remainhours']);
					if($value['remain_hour']<$art[0] || $value['remain_hour']>$art[1]){
						unset($data[$key]);						
					}
				}
			}
		}
		
		
		//关键字筛选查询
		if(trim($keyword) != ''){
			foreach($data as $k=>$value){
				if(strpos($value['name'], $keyword) === FALSE){
					unset($data[$k]);
				}
			}	
		}
			
			
			
		$data_temp = array_values($data);
		
//		if(empty($cache_data)){
			//缓存数据,缓存时间15分钟
//			S($session_id.'teaching_supervisor',$data_temp,0);
			
			if(!empty($pageCount)){
				$result = array_slice($data_temp,$pageNum,$pageCount);	
			}else{
				$result = $data_temp;
			}
			$maxCount = count($data_temp);
			
			//重新排序数组，并返回符合条件的记录
			return $result;
		
//		}
		
	}

	private function filterStudentInfo(&$data_temp,$condition,$param,$order,$pageNum,$pageCount,&$maxCount,$keyword){
		
		try{
		
			//==================针对查询============================================================================================================
		
			foreach($data_temp as $key=>$value){
				
				
				//将毕业的学生隐藏掉；2016-06-30 ，将本届高三毕业的隐藏掉；
				if($param['stu_state'] != 4){
					if($value['state'] == 4){
						unset($data_temp[$key]);
						continue;	
					}		
				}
			
				//学生所在的校区
				if(!empty($param['school'])){
					if($value['school'] != $param['school']){
						unset($data_temp[$key]);
						continue;	
					}			
				}
				
				//学生的学习管理师
				if(!empty($param['teacher'])){
					if($value['xueguan'] != $param['teacher']){
						unset($data_temp[$key]);
						continue;	
					}			
				}
			
				//学生类型
				if(!empty($param['student_type'])){
					if($value['type'] != $param['student_type']){
						unset($data_temp[$key]);
						continue;	
					}			
				}
				
				/*//学生状态  状态合并，都变到一个switch里了  edit by zhangxm at 2016-02-14 
				if(!empty($param['stu_state'])){
					if($value['state'] != $param['stu_state']){
						unset($data_temp[$key]);
						continue;	
					}			
				}*/
				
				//学生维护频率
				if(!empty($param['frequency'])){
					if($value['frequency'] != $param['frequency']){
						unset($data_temp[$key]);
						continue;	
					}		
				}
				
				//学生回访类型
				if(!empty($param['visit_type'])){
					if($value['visit_type'] != $param['visit_type']){
						unset($data_temp[$key]);
						continue;	
					}		
				}
		
				
				//关键字筛选查询
				if(!empty($keyword) && trim($keyword) != ''){
					if(strpos($value['name'], $keyword) === FALSE){
						unset($data_temp[$key]);
						continue;
					}
				}
				
				if(!empty($param['name'])){
					if(strpos($value['name'], $param['name']) === FALSE){
						unset($data_temp[$key]);
						continue;	
					}	
				}
				
				
				//筛选年级信息
				if(!empty($param['grade'])){
					if($value['grade'] != $param['grade']){
						unset($data_temp[$key]);
						continue;	
					}
				}	
				
				
				//筛选课程分类信息
				if(!empty($param['course_type'])){
					if(!empty($value['plan_name']) && strpos($value['plan_name'], $param['course_type']) === FALSE){
						unset($data_temp[$key]);
						continue;	
					}elseif(empty($value['plan_name'])){
						unset($data_temp[$key]);
						continue;	
					}
				}
		
				
				//筛选课时分类信息
				if(($param['remainhours'] === '0') || (!empty($param['remainhours']))){
					
					if($param['remainhours'] === '0'){
						if($value['remain_hour'] != 0){
							unset($data_temp[$key]);
							continue;
						}
					}elseif($param['remainhours'] == 380){
						if($value['remain_hour'] <= 380){
							unset($data_temp[$key]);
							continue;
						}
					}else{
						$art = split(",", $param['remainhours']);
						if($value['remain_hour']<$art[0] || $value['remain_hour']>$art[1]){
							unset($data_temp[$key]);	
							continue;					
						}
					}
				}
			}
			
			
			$data_temp = array_values($data_temp);
			
			$std_llst = array_column($data_temp,'std_id');
			
			if(!empty($param['huifang'])){
				//维护回访表实例化
				$weihu = M('weihu');
				//如果未回访条件不为空
				$param_wh['Date(max(create_time))']=array('egt',$param['huifang']);
				$w_h['std_id'] = array('in',$std_llst);
				$weihu_std_id_arr = $weihu->where($w_h)->group('std_id')->having('DATEDIFF(CURDATE(),Date(max(create_time)))>=' . $param['huifang'])->order('Date(max(create_time)) DESC')->getField('std_id',TRUE);
				
				foreach($data_temp as $key=>$value){
					if(!in_array($value['std_id'], $weihu_std_id_arr)){
						unset($data_temp[$key]);
						continue;
					}	
				}
				
				$data_temp = array_values($data_temp);
				unset($weihu_std_id_arr);
				unset($w_h);
				unset($param_wh);
				unset($weihu); //销毁维护回访表实例化变量
			}
			
			$std_lst = array_column($data_temp,'std_id');
			
			$arr = [];
			//学员课程状态信息筛选
			/*switch($param['state']){
				
				case 2:
					//有费学员学号列表
					$money = D('Consumption');
					$wm['std_id'] = array('in',$std_lst);
					$co = $money->order($order)->where($wm)->group('std_id')->having('sum(value)>0')->getField('std_id',TRUE);
					
					$wm['state'] = array('in',[200,250]);
					$cs = $this->where($wm)->order($order)->group('std_id')->having('sum(hour+ext_hour-used_hour)>0')->getField('std_id',TRUE);
					if(!empty($co) && !empty($cs)){
						$yf = array_merge($co,$cs);	
					}else{
						$yf = empty($co)?$cs:$co;
					}
					
					//有费学员
					$arr = array_unique($yf);
				break;
				case 3:
	//				有费(未上课时间不超过30天)
					$w3['timee']=array('gt',date('Y-m-d',time()-30*24*3600));
					$w3['state']=array('neq',2);
					$w3['std_id'] = array('in',$std_lst);
					
					//课程表实例化
					$cl = M('hw001.Class',NULL);
					//未上课时间不超过30天
					$c_std_lst = $cl->where($w3)->group('std_id')->having('count(*)>0')->getField('std_id',TRUE);
					
					//有费学员学号列表
					$money = D('Consumption');
					
					$wm['std_id'] = array('in',$std_lst);
					$co = $money->order($order)->where($wm)->group('std_id')->having('sum(value)>0')->getField('std_id',TRUE);
					
					$wm['state'] = array('in',['200','250']);
					$cs = $this->where($wm)->order($order)->group('std_id')->having('sum(hour+ext_hour-used_hour)>0')->getField('std_id',TRUE);
					
					if(!empty($co) && !empty($cs)){
						$yf = array_merge($co,$cs);	
					}else{
						$yf = empty($co)?$cs:$co;
					}
					
					if(!empty($c_std_lst) && !empty($yf)){
						$yf = array_unique($yf);
						$arr = array_intersect($yf, $c_std_lst);
					}else{
						$arr = NULL;
					}
					
				break;
				case 4:
	//				有费(超过30天未上课)
					$w4['timee']=array('gt',date('Y-m-d',time()-30*24*3600));
					$w4['state']=array('neq',2);
					$w4['std_id'] = array('in',$std_lst);
					
					//课程表实例化
					$cl = M('hw001.Class',NULL);
					//30天内都上过课的学员学号列表
					$cl_std_lst = $cl->where($w4)->group('std_id')->having('count(*)>0')->getField('std_id',TRUE);
					
					//超过30天未上课的学员学号列表
					if(!empty($cl_std_lst)){
						$temp_arr = array_diff($std_lst, $cl_std_lst);	
					}else{
						$temp_arr = $std_lst;
					}
					
					//有费学员学号列表
					$money = D('Consumption');
					
					$wm['std_id'] = array('in',$std_lst);
					$co = $money->order($order)->where($wm)->group('std_id')->having('sum(value)>0')->getField('std_id',TRUE);
					
					$wm['state'] = array('in',['200','250']);
					$cs = $this->where($wm)->order($order)->group('std_id')->having('sum(hour+ext_hour-used_hour)>0')->getField('std_id',TRUE);
					
					if(!empty($co) && !empty($cs)){
						$yf = array_merge($co,$cs);	
					}else{
						$yf = empty($co)?$cs:$co;
					}
					 
					if(!empty($yf) && !empty($temp_arr)){
						$yf = array_unique($yf);
						$arr = array_intersect($yf, $temp_arr);	
					}else{
						$arr = NULL;
					}
					
				break;
				case 5:
					//没费，没课的学员std_id列表
					//没费学员
					$money = D('Consumption');
					
					$wm['std_id'] = array('in',$std_lst);
					$co = $money->order($order)->where($wm)->group('std_id')->having('sum(value)<=0')->getField('std_id',TRUE);
					
					$co2 = $money->where($wm)->group('std_id')->getField('std_id',TRUE);
					
					$wm['state'] = array('in',['200','250']);
					$cs = $this->where($wm)->order($order)->group('std_id')->having('sum(hour+ext_hour-used_hour)=0')->getField('std_id',TRUE);
					
					
					if(!empty($co2)){
						$wf2 = array_diff($std_lst, $co2);	
					}else{
						$wf2 = $std_lst;
					}	
						
					if(!empty($co) && !empty($cs)){
						$wf = array_intersect($co,$cs);
					}else{
						$wf = NULL;	
					}
					
					$w5['timee']=array('egt',date('Y-m-d',time()));	
					$w5['state']=array('neq',2);
					$w5['std_id'] = array('in',$std_lst);
					
					//课程表实例化
					$cl = M('hw001.Class',NULL);
					//无课学员
					$cd_std_lst = $cl->where($w5)->group('std_id')->having('count(*)=0')->getField('std_id',TRUE);
					
					if(!empty($cd_std_lst) && !empty($wf)){
						$arr = array_intersect($wf, $cd_std_lst);	
						
						if(!empty(wf2)){
							if(!empty($arr)){
								$arr = array_merge($arr,$wf2);
								$arr = array_unique($arr);	
							}else{
								$arr = $wf2;
							}
						}
					}else{
						$arr = $wf2;
					}
					
				break; 
				
				case 6:
					//有费没课的信息筛选
					//有费学员学号列表
					$money = D('Consumption');
					
					$wm['std_id'] = array('in',$std_lst);
					$co = $money->order($order)->where($wm)->group('std_id')->having('sum(value)>0')->getField('std_id',TRUE);
					
					$cs = $this->where($wm)->order($order)->group('std_id')->having('sum(hour+ext_hour-used_hour)>0')->getField('std_id',TRUE);
					
					if(!empty($co) && !empty($cs)){
						$yf = array_merge($co,$cs);	
					}else{
						$yf = empty($co)?$cs:$co;
					}
					
					
					$w5['timee']=array('egt',date('Y-m-d',time()));	
					$w5['state']=array('neq',2);
					$w5['std_id'] = array('in',$std_lst);
					
					//课程表实例化
					$cl = M('hw001.Class',NULL);
					//无课学员
					$cd_std_lst = $cl->where($w5)->group('std_id')->having('count(*)=0')->getField('std_id',TRUE);
					
					if(!empty($cd_std_lst) && !empty($yf)){
						$yf = array_unique($yf);
						$arr = array_intersect($yf, $cd_std_lst);	
					}else{
						$arr = NULL;
					}
					
					break;
				}*/
				
				
				switch($param['stu_state']){
				
				case 21:
					//有费学员学号列表
					$money = D('Consumption');
					$wm['std_id'] = array('in',$std_lst);
					$co = $money->order($order)->where($wm)->group('std_id')->having('sum(value)>0')->getField('std_id',TRUE);
					
					$wm['state'] = array('in',[200,250]);
					$cs = $this->where($wm)->order($order)->group('std_id')->having('sum(hour+ext_hour-used_hour)>0')->getField('std_id',TRUE);
					if(!empty($co) && !empty($cs)){
						$yf = array_merge($co,$cs);	
					}else{
						$yf = empty($co)?$cs:$co;
					}
					
					//有费学员（也即有效学员）
					$arr = array_unique($yf);
				break;
				case 1:
	//				有费(未上课时间不超过30天)
					$w3['timee']=array('gt',date('Y-m-d',time()-30*24*3600));
					$w3['state']=array('neq',2);
					$w3['std_id'] = array('in',$std_lst);
					
					//课程表实例化
					$cl = M('hw001.Class',NULL);
					//未上课时间不超过30天
					$c_std_lst = $cl->where($w3)->group('std_id')->having('count(*)>0')->getField('std_id',TRUE);
					
					//有费学员学号列表
					$money = D('Consumption');
					
					$wm['std_id'] = array('in',$std_lst);
					$co = $money->order($order)->where($wm)->group('std_id')->having('sum(value)>0')->getField('std_id',TRUE);
					
					$wm['state'] = array('in',['200','250']);
					$cs = $this->where($wm)->order($order)->group('std_id')->having('sum(hour+ext_hour-used_hour)>0')->getField('std_id',TRUE);
					
					if(!empty($co) && !empty($cs)){
						$yf = array_merge($co,$cs);	
					}else{
						$yf = empty($co)?$cs:$co;
					}
					
					//正常在读(有费,未上课时间不超过30天)
					if(!empty($c_std_lst) && !empty($yf)){
						$yf = array_unique($yf);
						$arr = array_intersect($yf, $c_std_lst);
					}else{
						$arr = NULL;
					}
					
				break;
				case 2:
	//				有费(超过30天未上课)
					$w4['timee']=array('gt',date('Y-m-d',time()-30*24*3600));
					$w4['state']=array('neq',2);
					$w4['std_id'] = array('in',$std_lst);
					
					//课程表实例化
					$cl = M('hw001.Class',NULL);
					//30天内都上过课的学员学号列表
					$cl_std_lst = $cl->where($w4)->group('std_id')->having('count(*)>0')->getField('std_id',TRUE);
					
					//超过30天未上课的学员学号列表
					if(!empty($cl_std_lst)){
						$temp_arr = array_diff($std_lst, $cl_std_lst);	
					}else{
						$temp_arr = $std_lst;
					}
					
					//有费学员学号列表
					$money = D('Consumption');
					
					$wm['std_id'] = array('in',$std_lst);
					$co = $money->order($order)->where($wm)->group('std_id')->having('sum(value)>0')->getField('std_id',TRUE);
					
					$wm['state'] = array('in',['200','250']);
					$cs = $this->where($wm)->order($order)->group('std_id')->having('sum(hour+ext_hour-used_hour)>0')->getField('std_id',TRUE);
					
					if(!empty($co) && !empty($cs)){
						$yf = array_merge($co,$cs);	
					}else{
						$yf = empty($co)?$cs:$co;
					}
					
					//停课(超过30天未上课) 
					if(!empty($yf) && !empty($temp_arr)){
						$yf = array_unique($yf);
						$arr = array_intersect($yf, $temp_arr);	
					}else{
						$arr = NULL;
					}
					
				break;
				case 3:
					//结课的学生
					$xg_stat = M('xg_statistic');
					
					$w_tk['type'] = '结课';
					//学生的学习管理师
					if(!empty($param['teacher'])){
						$w_tk['xueguan'] = $param['teacher'];
					}
					
					$w_tk['std_id'] = array('in',$std_lst);
					
					$arr_data = $xg_stat->where($w_tk)->select();
					
					//结课(课时为0)
					if(!empty($arr_data)){
						$arr = array_column($arr_data,'std_id');	
					}else{
						$arr = NULL;
					}
					 
					
					break;
				case 4: 
					//学生状态为毕业
					foreach($data_temp as $key=>$value){
						if($value['state'] != $param['stu_state']){
							unset($data_temp[$key]);
							continue;	
						}			
					}
					
					$data_temp = array_values($data_temp);
					$arr = array_column($data_temp,'std_id');; 
				break; 
				case 5:
					//学生状态为退费
					$consump = M('consumption');
					$arr  = $consump->where(['type'=>200,'std_id'=>array('in',$std_lst)])->getField('std_id',TRUE);
					
					break; 
				case 6:
					
					//自习室学员
					$sql_str = "select std_id from oa_course where oa_course.std_id in ( ";
			
					$sql_str .= " select std_id from oa_course where oa_course.unit_plan in ( ";
					
					$sql_str .= " select our.id ";
					
					$sql_str .= " from oa_unitprice_role as our,oa_foo_info as F1,oa_foo_info as F2,oa_foo_info as F3,oa_foo_info as F4 ";
					
					$sql_str .= " where our.school = F1.id and our.grade = F2.id and our.level = F3.id and our.course = F4.id and F4.id in (57,72,81,82,83))) ";
					
					$sql_str .= " group by oa_course.std_id having sum(oa_course.hour+oa_course.ext_hour-oa_course.used_hour)>0 ";
					
					$model = new \Think\Model();
					
					$arr = $model->query($sql_str);
					
					
					break;
				case 17:
					//没费，没课的学员std_id列表
					//没费学员
					$money = D('Consumption');
					
					$wm['std_id'] = array('in',$std_lst);
					$co = $money->order($order)->where($wm)->group('std_id')->having('sum(value)<=0')->getField('std_id',TRUE);
					
					$co2 = $money->where($wm)->group('std_id')->getField('std_id',TRUE);
					
					$wm['state'] = array('in',['200','250']);
					$cs = $this->where($wm)->order($order)->group('std_id')->having('sum(hour+ext_hour-used_hour)=0')->getField('std_id',TRUE);
					
					
					if(!empty($co2)){
						$wf2 = array_diff($std_lst, $co2);	
					}else{
						$wf2 = $std_lst;
					}	
						
					if(!empty($co) && !empty($cs)){
						$wf = array_intersect($co,$cs);
					}else{
						$wf = NULL;	
					}
					
					$w5['timee']=array('egt',date('Y-m-d',time()));	
					$w5['state']=array('neq',2);
					$w5['std_id'] = array('in',$std_lst);
					
					//课程表实例化
					$cl = M('hw001.Class',NULL);
					//无课学员
					$cd_std_lst = $cl->where($w5)->group('std_id')->having('count(*)=0')->getField('std_id',TRUE);
					
					
					//无费，无排课
					if(!empty($cd_std_lst) && !empty($wf)){
						$arr = array_intersect($wf, $cd_std_lst);	
						
						if(!empty(wf2)){
							if(!empty($arr)){
								$arr = array_merge($arr,$wf2);
								$arr = array_unique($arr);	
							}else{
								$arr = $wf2;
							}
						}
					}else{
						$arr = $wf2;
					}
					
				break; 
				
				case 18:
					//有费没课的信息筛选
					//有费学员学号列表
					$money = D('Consumption');
					
					$wm['std_id'] = array('in',$std_lst);
					$co = $money->order($order)->where($wm)->group('std_id')->having('sum(value)>0')->getField('std_id',TRUE);
					
					$cs = $this->where($wm)->order($order)->group('std_id')->having('sum(hour+ext_hour-used_hour)>0')->getField('std_id',TRUE);
					
					if(!empty($co) && !empty($cs)){
						$yf = array_merge($co,$cs);	
					}else{
						$yf = empty($co)?$cs:$co;
					}
					
					
					$w5['timee']=array('egt',date('Y-m-d',time()));	
					$w5['state']=array('neq',2);
					$w5['std_id'] = array('in',$std_lst);
					
					//课程表实例化
					$cl = M('hw001.Class',NULL);
					//无课学员
					$cd_std_lst = $cl->where($w5)->group('std_id')->having('count(*)=0')->getField('std_id',TRUE);
					
					//有费，无排课
					if(!empty($cd_std_lst) && !empty($yf)){
						$yf = array_unique($yf);
						$arr = array_intersect($yf, $cd_std_lst);	
					}else{
						$arr = NULL;
					}
					
					break;
				}
	
				if(!empty($arr)){
					foreach($data_temp as $key=>$value){
						if(!in_array($value['std_id'], $arr)){
							unset($data_temp[$key]);
							continue;
						}	
					}
					
					$data_temp = array_values($data_temp);
				}elseif(!empty($param['stu_state']) && $param['stu_state']!= 'all'){
					$data_temp = NULL;
				}
				
//======================================================================================================================================	
			
		}catch(\Exception $e){
			var_dump($e->getMessage());
			throw new \Exception($e->getMessage());   //抛出异常，注意这里异常类的命名空间引用；
			die;
		}
		
			
	}

	//获取相应学习管理师管辖的学员及课时信息 + 缓存功能
	public function getXueguanStudentInfoByCache($condition,$param,$order,$pageNum,$pageCount,&$maxCount,$keyword){
		
		try{
			
			$session_id = session('school_id');
			$cache_id = $session_id . 'teaching_supervisor';
			$cache_data = S($cache_id);
			
			//学生的所在校区
			if(!empty($param['school']) || $param['school'] === '0'){
				if($param['school'] !== '0'){
					$w[trim('school')] = get_school_name($param['school']);
				}else{
					$sch = M('foo_info');
					
					$school_lst = $sch->where('pid=15')->getField('name',TRUE);
					
					$w[trim('school')] = array('in',$school_lst);
				}
					
				$param['school'] = get_school_name($param['school']);
			}
			
			if(!empty($cache_data)){
				//条件过滤
				$this->filterStudentInfo($cache_data,$condition,$param,$order,$pageNum,$pageCount,$maxCount,$keyword);
				if(!empty($pageCount)){
					$result = array_slice($cache_data,$pageNum,$pageCount);	
				}else{
					$result = $cache_data;
				}
				
				$maxCount = count($cache_data);
				return $result;
			}
			
			//获取学习管理师所属的学员信息列表
			$stu = D('Students');

			//临时存储变量初始化
			$data = NULL;
			
			/*$model = new \Think\Model();
			unset($sql_str);
			
			$sql_str = "SELECT std_id FROM hw001.student  WHERE school in (select name from hongwen_oa.oa_foo_info where pid=15 and is_del=0)  ";
			
			$sw_lst = $model->query($sql_str);*/
			
			/*if(!empty($sw_lst)){
				$w['std_id'] = array('not in',array_column($sw_lst,'std_id'));
			}*/
			
			//全部数据
			$data = $stu->where($w)->order($order)->select();
			
			foreach($data as &$item){
				$item['sex'] = $item['sex'] == 0? '女':'男';
			}
			unset($item); // 最后取消掉引用
			
			//查询该校区管辖的学员学号列表
//			$std_lst = $stu->where($w)->order($order)->getField('std_id',TRUE); //为了提高效率，删除此处查询，直接在一次查询后获取std_id这一列
			$std_lst = array_column($data,'std_id');
			if(empty($std_lst)){
				return NULL;
			}
			
			//临时存储变量初始化
			$list = NULL;
			
			$condition['std_id'] = array('in',$std_lst);
			$condition['Course.state'] = array('in',[200,250]);
			$condition['Course.is_del'] = 0;
			 //edit by zhangxm at 2016-01-22 修改剩余课时计算不准确问题，订单的状态编码去finance.php中寻找
			$list = $this->getAllDataList($condition,$order,$list_std);
			
			
			
			$cls = D('class');
			$lession_hour = 0;
			$wc = NULL;
			
			$wc['std_id'] = array('in',$std_lst);
			$wc['timee'] = array('egt',date('Y/m/d'));
			$lession_hour = $cls->where($wc)->group('std_id')->getField('std_id,sum(count)');
			
			$wt['std_id'] = array('in',$std_lst);
			$wt['timee'] = array('eq',date('Y/m/d'));
			$day_hour = $cls->where($wt)->group('std_id')->getField('std_id,sum(count)');
			
			$wt['cwqr'] = array('exp','is not null');
			$used_hour = $cls->where($wt)->group('std_id')->getField('std_id,sum(count)');
			
			
			//计算总课时 使用的课时及剩余的课时外加学员课时状态信息
			$state = [];
			foreach($data as &$dt){
				$state = [];
				
				foreach($list as &$item){
					if($dt['std_id'] === $item['std_id']){
						$dt['hour'] += $item['hour'] + $item['ext_hour'];
						$dt['used_hour'] += $item['used_hour'];
						$dt['plan_name'] .= $item['plan_name'];
						
						if(($item['state']>=200) && ($item['state']<=500)){
							array_push($state,$item['state']);	
						}
						
						if($item['state'] == 200){
							$dt['subject_teacher'] .= $item['subject_teacher'];
						}
					}
				}
				unset($item); // 最后取消掉引用
				//针对拥有多个课程状态的学员，获取该学员的有效状态
				if(count($state)>0){
					$dt['course_state'] = min($state);	
				}else{
					$dt['course_state'] = '';
				}
				//计算剩余课时
				if(!empty($dt['hour'])){
					$dt['remain_hour'] = $dt['hour'] - $dt['used_hour'];	
				}
				
				
				foreach($lession_hour as $key=>$value){
					if($key == $dt['std_id']){
						$dt['lession_hour'] = empty($value)? 0 : $value; //预排课时总数;
						break;
					}
				}
				
				foreach($day_hour as $key=>$value){
					if($key == $dt['std_id']){
						$dt['day_hour'] = empty($value)? 0 : $value; //当天总课时;
						break;
					}
				}


				foreach($used_hour as $key=>$value){
					if($key == $dt['std_id']){
						$dt['used_hour'] = empty($value)? 0 : $value; //实到课时;
						break;
					}
				}
				
				
				$dt['unused_hour'] = $dt['day_hour'] - $dt['used_hour']; //缺席课时;
				
				/*$wc['std_id'] = $dt['std_id'];
				$wc['timee'] = array('egt',date('Y/m/d'));
				$lession_hour = $cls->where($wc)->sum('count');
				$dt['lession_hour'] = $lession_hour;
				
				$wt['std_id'] = $dt['std_id'];
				$wt['timee'] = array('eq',date('Y/m/d'));
				$day_hour = $cls->where($wt)->sum('count');
				$dt['day_hour'] = empty($day_hour)? 0 : $day_hour; //当天总课时;
				
				$wt['cwqr'] = array('exp','is not null');
				$used_hour = $cls->where($wt)->sum('count');
				$dt['used_hour'] = empty($used_hour)? 0 : $used_hour; //实到课时;
				$dt['unused_hour'] = $day_hour - $used_hour; //缺席课时;*/
		    }
			
			unset($wc); //销毁变量
			unset($lession_hour); //销毁变量
			
			unset($dt); // 最后取消掉引用
			//销毁临时变量
			unset($state);
			
			
			$data_temp = array_values($data);
			
			
			//根据学生状态进行排序
			$order_stu_state = [];
			$order_stu_std = [];
			foreach($data_temp as $key=>$value){
				$order_stu_state[$key] = $value['state'];
				$order_stu_std[$key] = $value['std_id'];
			}
			
			array_multisort($order_stu_state,SORT_ASC,$order_stu_std,SORT_DESC,$data_temp);
			
			//缓存数据,缓存时间为5分钟
			S($cache_id,$data_temp,300);
			
			//条件过滤
			$this->filterStudentInfo($data_temp,$condition,$param,$order,$pageNum,$pageCount,$maxCount,$keyword);
				
			if(!empty($pageCount)){
				$result = array_slice($data_temp,$pageNum,$pageCount);	
			}else{
				$result = $data_temp;
			}
			$maxCount = count($data_temp);
			
			//重新排序数组，并返回符合条件的记录
			return $result;
			
		}catch(\Exception $e){
			var_dump($e->getMessage());
			throw new \Exception($e->getMessage());   //抛出异常，注意这里异常类的命名空间引用；
			die;
		}
		
	}

}
