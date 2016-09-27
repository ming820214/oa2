<?php
namespace Home\Controller;

class ReportController extends HomeController {
	
	private static $rule_discount = [];
	
	private static $discount_all = [];
	
	function _initialize(){
		parent::_initialize();
		$data=M('discount_role')->where(['is_del'=>0])->order('school,bottom asc')->field('school,bottom,top,value')->select();
		foreach($data as $v){
			self::$rule_discount[$v['school']][]=$v;			
		}
		unset($data);
		
		 
        self::$discount_all = M('DiscountRole')->where([
            'is_del' => ['eq', 0],
            'pid'    => ['eq', C('DISCOUNT_ID')['HOUR']]
            ])->select();
	}
	
    public function index(){
        $this->show("<extend name='Layout/ins_page'/>");
    }

//================ 财务数据=================
    public function ls(){

        if(get_school_name()!='集团'){
            $w['b']=get_school_name();
            $w['school']=get_school_name();
            // $ww['school']=get_school_name();
            $www['school']=get_school_name();
        }

        if($_POST['date']){
            $date=I('post.date');
            $w['date']=$_POST['date'];
            $ww['date']=$date;
        }else{
            $w['date']=session('date');
            $ww['date']=session('date');
        }
        if($_POST['time1']&&$_POST['time2']){
            $t1=I('post.time1');
            $t2=I('post.time2');
            $w['d']=array('between',"$t1,$t2");
            $ww['time']=array('between',"$t1,$t2");
        }

        $w['state']=array('BETWEEN','0,3');
        $ww['state']=5;

        //预算统计
        $ss=M('hw003.money_budget',null)->where($ww)->select();
            foreach ($ss as  $vv) {
                    $school[$vv['jsxq']]['预算']+=round($vv['d']*$vv['e'],2);
            }

        //花销及其它
        $s=M('hw003.money_add',null)->where($w)->select();
            foreach ($s as $key => $v) {
                switch ($v['g']) {
                    case '员工借款':
                        $school[$v['b']]['借款']+=round($v['kk']*$v['l'],2);
                        break;
                    case '押金':
                        $school[$v['b']]['押金']+=round($v['kk']*$v['l'],2);
                        break;
                    case '福利金':
                        $school[$v['b']]['福利金']+=round($v['kk']*$v['l'],2);
                        break;
                    default:
                        $school[$v['b']]['花销']+=round($v['kk']*$v['l'],2);
                        break;
                }
            }

        //@相关记录
        $ss=M('hw003.money_add_note',null)->where($www)->select();
            foreach ($ss as  $vvv) {
                $school[$vvv['school']]['预支']=round($vvv['b'],2);
                $school[$vvv['school']]['结余']=round($vvv['c'],2);
                $school[$vvv['school']]['差额']=round($school[$vvv['school']]['预算']-$school[$vvv['school']]['花销']-$school[$vvv['school']]['押金']-$school[$vvv['school']]['借款']-$school[$vvv['school']]['福利金']-$school[$vvv['school']]['预支']-$school[$vvv['school']]['结余'],2);
                $school[$vvv['school']]['备注']=$vvv['e'];
            }
        //过滤校区
            if(get_school_name()!='集团'){
                $sch[get_school_name()]=$school[get_school_name()];
            }else{
                $sch=$school;
            }

        ksort($sch,SORT_STRING);//按键名排序
        $this->school=$sch;
        $this->w=$w;
        $this->display();
    }

    public function note(){
        if($_POST['s']){
            $data['b']=I('post.b');
            $data['c']=I('post.c');
            $data['e']=I('post.e');
            $w['school']=I('post.s');
            if(M('hw003.money_add_note',null)->where($w)->save($data))print(json_encode('1'));
        }
    }

    public function tx(){
        if($_POST['date']){
            $date=$_POST['date'];
        }else{
            $date=session('date');
        }
        //数据列表
        $w['state']=3;
        $w['date']=$date;
        $list=M('hw003.money_add',null)->where($w)->order('id desc')->select();
        $this->list=$list;

            foreach ($list as $v) {

                $school[$v['b']]['花销']+=round($v['kk']*$v['l'],2);
                $school[$v['c']]['归属']+=round($v['kk']*$v['l'],2);

                if($v['mm']>1)
                    for ($i=0; $i < $v['mm']; $i++) {
                        $t=strtotime(date('Y-m',strtotime($v['f'])))+$i*date('t',strtotime($v['f']))*24*3600;
                        if(strtotime($date)<$t){
                            $school[$v['c']]['未来']+=round($v['kk']*$v['l'],2)/$v['mm'];
                        }
                    }
            }

        //之前摊销到当期的
        $w2['date']=array('lt',$date);
        $w2['state']=3;
        $li2=M('hw003.money_add',null)->where($w2)->select();
            foreach ($li2 as $vv) {
                if($vv['mm']>1)
                    for ($i=0; $i < $vv['mm']; $i++) {
                        $t=strtotime(date('Y-m',strtotime($vv['f'])))+$i*date('t',strtotime($vv['f']))*24*3600;
                        if(strtotime(date('Y-m',strtotime($vv['f'])))==$t){
                            $school[$vv['c']]['曾经']+=round($vv['kk']*$vv['l'],2)/$vv['mm'];
                        }
                    }
            }

        $this->school=$school;

        $this->display();

    }
	
	private function _getExtHour($classHour, $school = -1) {
			
        $discountHour = 0;
		
        foreach (self::$discount_all as $role) {
        	if($role['school'] == $school || $role['school'] == -1){
        		if ($role['bottom'] <= $classHour && $classHour < $role['top']) {
	                $discountHour = $role['value'];
					break;
	            }	
        	}
            
        }

        return $discountHour;
    }
	
	/**  类似于该规则：30赠2 60赠5 100赠10，赠送不叠加。，返回该订单目前已经消耗的金额；
	根据已上课时计算课程订单已经消耗的金额
	*/
    private function course_xiaohao($data,$cur_hour=0){

    // 带赠送情况,分按正常校区赠送规则走和赠送要大于校区赠送规则的特殊优惠
	if($data['ext_hour']){
            if(self::$rule_discount[$data['school']]){
            	$ext_hour = $this->_getExtHour($data['hour'],$data['school']);
            	$is_sy=(($ext_hour+$data['hour']-$data['ext_hour'])>0)?1:0;
            	if($data['type']==1 && $is_sy){
            		if($data['used_hour'] - ($ext_hour+$data['hour'])>=0){
            			return $data['used_hour']-$cur_hour * $data['unitprice']*$data['factor'];
            		}else{
	            			foreach (self::$rule_discount[$data['school']] as $rl) {
			                    $hour=$data['used_hour'];
								
			                    if(($hour-$cur_hour)<=$rl['bottom']+$prev_ext){//30
			                    	if(($hour-$cur_hour)>=$rl['bottom'] && ($hour-$cur_hour)<= $rl['bottom']+$prev_ext){
			                    		return ($rl['bottom'])*$data['unitprice']*$data['factor'];
			                    	}else{
			                    		return (($data['used_hour']-$cur_hour-$prev_ext))*$data['unitprice']*$data['factor'];	
			                    	}
								
								}elseif(($hour-$cur_hour)>($rl['bottom']+$prev_ext) && ($hour-$cur_hour)<=($rl['bottom']+$rl['value'])){ //30-32
								
									return ($rl['bottom'])*$data['unitprice']*$data['factor'];
									
								}elseif(($hour-$cur_hour)>($rl['bottom']+$rl['value']) && ($hour-$cur_hour)<=($rl['top']+$rl['value'])){ //33-62
									
									return (($hour-$rl['value'])-$cur_hour) * $data['unitprice']*$data['factor'];
									
								}
								
								$prev_ext = $rl['value'];
		                }
            		}
            		
            	}else{
	                foreach (self::$rule_discount[$data['school']] as $rl) {
	                    $hour=$data['used_hour'];
						
	                    if(($hour-$cur_hour)<=$rl['bottom']+$prev_ext){//30
	                    	if(($hour-$cur_hour)>=$rl['bottom'] && ($hour-$cur_hour)<= $rl['bottom']+$prev_ext){
	                    		return ($rl['bottom'])*$data['unitprice']*$data['factor']/$data['count'];
	                    	}else{
	                    		return (($data['used_hour']-$cur_hour-$prev_ext))*$data['unitprice']*$data['factor']/$data['count'];	
	                    	}
						
						}elseif(($hour-$cur_hour)>($rl['bottom']+$prev_ext) && ($hour-$cur_hour)<=($rl['bottom']+$rl['value'])){ //30-32
						
							return ($rl['bottom'])*$data['unitprice']*$data['factor']/$data['count'];
							
						}elseif(($hour-$cur_hour)>($rl['bottom']+$rl['value']) && ($hour-$cur_hour)<=($rl['top']+$rl['value'])){ //33-62
							
							return ($hour-$cur_hour-$rl['value']) * $data['unitprice']*$data['factor']/$data['count'];
							
						}
						
						$prev_ext = $rl['value'];
	                }
            	}
            }
        }else{
            return ($data['used_hour']-$cur_hour)*$data['unitprice']*$data['factor']/$data['count'];
        }

 }



	public function course_all_student_remain_money(){
		
		$school = I('get.school')?:session('school_id');	
		$p_date = I('get.p_date')?:date('Y-m-d');
		
		if(empty($school) && $school == 0){
			$this->list = NULL;
			$this->data = NULL;
			$this->maxCount = 1;
			$this->school = $school;
			$this->cur_date = $p_date;
	        $this->display('xiaohao_student_detail');
		}else{
			 $w=['cu.is_del'=>0,'cu.state'=>200];
			
			
			if(!empty($school) && $school !== 0){
				$w['cu.school']=$school;
				$w2['school'] = get_school_name($school);	
				$wm['school'] = get_school_name($school);	
			}
	
	
			$course = M('course');
			
			$w['cu.std_id'] = array(array('exp','is not null'),array('NEQ',''));
			
			$data = $course->alias('cu')
				   ->join('hongwen_oa.oa_Unitprice_Role as upr on cu.unit_plan = upr.id')
				   ->field('cu.id, cu.state, cu.type, cu.type_state, cu.std_id, cu.name, 
				   cu.school, cu.unit_plan, cu.course, cu.grade, cu.level, cu.unitprice, 
				   cu.hour, cu.ext_hour, cu.factor, cu.price, cu.return_price, cu.std_type, 
				   cu.remark, cu.used_hour, cu.order_id, cu.reason, cu.log, cu.other, 
				   cu.create_time, cu.is_del, cu.record,upr.count,upr.label,upr.id as plan_id,upr.name as plan_name')
				   ->where($w)
				   ->order('cu.std_id')
				   ->select();
			
			$cur_date = I('get.p_date')?:date('Y-m-d');
			
			//针对解决试听课数据的排序及显示问题
			$list[get_school_name($school)][0]['remain'] = 0;
				
			foreach ($data as $v) {
				if($v['std_id']=='20160729002002'){
					echo 'here2';
				}
				$real_course_consump = $this->course_xiaohao($v);
	            $list[get_school_name($v['school'])][$v['std_id']]['remain']+=$v['price']-$real_course_consump; //订单剩余金额，price学生帐号总金额
	            $list[get_school_name($v['school'])][$v['std_id']]['used_hour']+=$v['used_hour'];//累计实际消耗课时数量；
				$list[get_school_name($v['school'])][$v['std_id']]['real_consump_value'] += $real_course_consump; //累计实际消耗金额； 
	        }
			
			
			$w2['timee'] = I('get.p_date')?:date('Y-m-d');
			$w2['state'] = array('NEQ',2);
			$w2['std_id'] = array(array('exp','is not null'),array('NEQ',''));
			
			
			$dd = I('get.p_date')?date('Y-m-01',strtotime(I('get.p_date'))):date('Y-m-01');
			$c=strtotime($dd);//获取月初时间戳
	        $cc=$c+date('t',$c)*24*3600;//获取月末时间戳
			$cc = date('Y-m-d',$cc);
			
			$wm['timee'] = array(array('egt',$dd),array('lt',$cc));
			$wm['state'] = array('NEQ',2);
			$wm['std_id'] = array(array('exp','is not null'),array('NEQ',''));
			
			$day_count = M('hw001.class',null)->where($w2)->group('stuid')->field("school,std_id,sum(count) as count ")->order('school,std_id')->select();
			
			$month_count = M('hw001.class',null)->where($wm)->group('stuid')->field("school,std_id,sum(count) as count ")->order('school,std_id')->select();
			
			if(!empty($school) && $school !== 0){
				
				//获取该校区所有学生当天的课时量
				foreach($day_count as $t=>$va){
					
					$list[$va['school']][$va['std_id']]['day']= $va['count'];	
				}
				
				$cwqr = 'go'; //设置条件，获取财务确认的课时费用即实时消费金额
				$sum_day_money = 'day'; //设置条件，获取当天的课时费用，即当天消耗金额
					
				//获取该校区所有学生当月的课时量
				foreach($month_count as $t=>$va){
					//获取该校区所有学生当月的课时量
					$list[$va['school']][$va['std_id']]['month_count']= $va['count'];	
				}
				
				
				
				unset($w);
				
				$w['cl.school'] = get_school_name($school);
				
//				$w['cl.timee'] = array(array('egt',$dd),array('lt',$cc));
				
				$w['cl.state'] = array('NEQ',2);
				
				$w['cl.std_id'] = array(array('exp','is not null'),array('NEQ',''));
				
				$w['cl.course_id'] = array(array('exp','is not null'),array('NEQ',0));
				
				$w['cou.state'] = 200;
				
				$w['cou.is_del'] = 0;
				
				$cls = M('hw001.class',NULL);
				
				unset($data);
				
				$data = $cls->alias('cl')->join('hongwen_oa.oa_course as cou on cou.id = cl.course_id ')
					->join('hongwen_oa.oa_unitprice_role as upr on cou.unit_plan = upr.id')
					->field('cou.id,cl.course_id,cl.school as school_name,cl.std_id as std_id,cl.timee as timee,cl.cwqr as cwqr,cl.count as count , cou.type as type,cou.hour as hour,cou.used_hour as used_hour,cou.ext_hour as ext_hour,cou.factor as factor ,cou.school as school,cou.unitprice as unitprice ,cou.price as price,upr.count as subject_count ')
					->where($w)
					->order('cl.school,cl.std_id,cl.course_id,cl.timee desc,cl.time1,cl.time2')
					->select();
					
				$cur_date = $p_date?:date('Y-m-d');
				

				$pre_item = 0; //上一条记录的std_id;
				$pre_count = 0; //上一条记录的课时数；
				$pre_course = 0; //上一条记录的订单编号；通过已上三条记录进行课时消耗金额的回推过程；
				foreach($data as &$item){
					
					if($pre_item != $item['std_id'] && $pre_course != $item['course_id']){
						$pre_item = $item['std_id'];
						$pre_count = $item['count'];
						$pre_course = $item['course_id'];	
						$p1 = $this->course_xiaohao($item);
						$p2 = $this->course_xiaohao($item,$item['count']);
						
					}else{
						$item['used_hour'] = $item['used_hour']-$pre_count;
						$p1 = $this->course_xiaohao($item);
						$p2 = $this->course_xiaohao($item,$item['count']);
						$pre_count += $item['count'];
					}
					
					$item['price'] = round($p1 - $p2,2);
					//排除旷课后，所有预排课的消耗金额
					if(date('Y-m-d',strtotime($item['timee'])) >= $dd && date('Y-m-d',strtotime($item['timee'])) < $cc){
						$list[$item['school_name']][$item['std_id']]['month_xh_money'] += $item['price']/$item['subject_count'];	
					}
					
					if(!empty($item['cwqr']) && $cwqr == 'go'){
						//排除旷课后，财务确认的数据，即实时消耗金额
						if(date('Y-m-d',strtotime($item['timee'])) >= $dd && date('Y-m-d',strtotime($item['timee'])) < $cc){
							$list[$item['school_name']][$item['std_id']]['sum_cwqr_money'] += $item['price']/$item['subject_count'];
							
							$list[$item['school_name']][$item['std_id']]['sum_cwqr_keshi'] += $item['count'];//月实际消耗课时数；
						}	
						 
					}
					
					
					if(!empty($item['timee']) && (date('Y-m-d',strtotime($item['timee'])) == $cur_date)){
						//排除旷课后，当天财务确认的数据，即当天实时消耗的金额
						
						$list[$item['school_name']][$item['std_id']]['sum_day_money'] += $item['price']/$item['subject_count'];
					}
					
				}
				unset($item);
				
				//校区所有学生账户剩余金额
				$csp_money =  M('hw001.student',NULL)->alias('st')
													->join('hongwen_oa.oa_consumption as csp on st.std_id = csp.std_id')
													->field('st.school,csp.std_id,sum(csp.value) as money')
													->where(['csp.is_del'=>0,'st.school'=>get_school_name($school)])
													->group('csp.std_id')
													->order('st.school,csp.std_id')
													->having('sum(value)>0')
													->select();
				foreach($csp_money as $vo){
					if($vo['std_id'] == '20160729002002'){
						echo 'here';
					}
					$list[get_school_name($school)][$vo['std_id']]['remain'] += $vo['money'];
					$list[get_school_name($school)][$vo['std_id']]['remain_csp'] += $vo['money'];	
				}									
			}
			 
			
			$std = M('hw001.student',NULL);
			$std_lst = [];
			foreach($list as $key=>$value){
        		foreach($value as $kk=>$vv){
					array_push($std_lst,$kk);        				
				}
			}
	
			$name_lst = $std->where(['std_id'=>array('in',$std_lst)])->getField('std_id,name');
			
			$grade_lst = $std->where(['std_id'=>array('in',$std_lst)])->getField('std_id,grade');
			
			
			$lst_cnt = 0;
			$i = 0;
			foreach($list as $key=>$value){
					$lst_cnt = count($value); 
	        		foreach($value as $kk=>$vv){
						
	        			if((string)$kk == '0'){
	        					$list[$key][$kk]['name'] = '试听课';
								$list[$key][$kk]['grade'] = '';
	        					continue;
	        			}else{
	        				foreach($name_lst as $std_id=>$name){
	        					if((string)$kk == $std_id){
	        						$list[$key][$kk]['name'] = $name;break;
	        					}	
	        				}
							
							foreach($grade_lst as $std_id=>$grade){
	        					if((string)$kk == $std_id){
	        						$list[$key][$kk]['grade'] = $grade;break;
	        					}	
	        				}
							 				
	        			}
					}
			}
			
			
			$this->assign('gradeList', C('SCHOOL_GRADE'));
			
			$this->list = $list;
			$this->data = json_encode($list);
			$this->maxCount = $lst_cnt;
			
			$this->cur_date = $p_date;
			$this->school = $school;
	        $this->display('xiaohao_student_detail');
		}
	}

//学生具体课时消耗数据导出功能
private function export($data, $head = array()){
        $table = '<table border="1">';

        if(!empty($head)){
            $table .= '<thead><tr>';
            foreach($head as $h){
                $table .= "<th>{$h[1]}</th>";
            }
            $table .= '</tr></thead>';
        }

        $table .= '<tbody>';
		
		$school = '';
		
        foreach($data as $key=>$record){
        	$school = $key;
			foreach($record as $k=>$v){
				$table .= "<tr>";
			
            	$table .= "<td>{$key}</td>";
			
				$table .= "<td>{$k}</td>";
				if(!empty($v)){
					$table .= "<td>{$v->name}</td>";
					
					if(!empty($v->day)){
						$table .= "<td>{$v->day}</td>";	
					}else{
						$table .= "<td>0</td>";
					}
					
					if(!empty($v->sum_day_money)){
						$table .= "<td>{$v->sum_day_money}</td>";	
					}else{
						$table .= "<td>0</td>";
					}
					
					
					if(!empty($v->sum_cwqr_keshi)){
						$table .= "<td>{$v->sum_cwqr_keshi}</td>";	
					}else{
						$table .= "<td>0</td>";
					}
					
					if(!empty($v->sum_cwqr_money)){
						$table .= "<td>{$v->sum_cwqr_money}</td>";	
					}else{
						$table .= "<td>0</td>";
					}
					
					if(!empty($v->month_count)){
						$table .= "<td>{$v->month_count}</td>";	
					}else{
						$table .= "<td>0</td>";
					}
					
					if(!empty($v->month_xh_money)){
						$table .= "<td>{$v->month_xh_money}</td>";	
					}else{
						$table .= "<td>0</td>";
					}
					
					if(!empty($v->used_hour)){
						$table .= "<td>{$v->used_hour}</td>";	
					}else{
						$table .= "<td>0</td>";
					}
					
					if(!empty($v->real_consump_value)){
						$table .= "<td>{$v->real_consump_value}</td>";	
					}else{
						$table .= "<td>0</td>";
					}
					
					if(!empty($v->remain_csp)){
						$table .= "<td>{$v->remain_csp}</td>";	
					}else{
						$table .= "<td>0</td>";
					}
					
					if(!empty($v->remain)){
						$table .= "<td>{$v->remain}</td>";	
					}else{
						$table .= "<td>0</td>";
					}
				}
				$table .= "</tr>";
			}
        }
		
        $table .= "</tbody>";
        $table .= "</table>";

        $html = "<html>
                    <head>
                        <meta charset=\"UTF-8\">
                        <title>{$this->config['title']}</title>
                    </head>
                    <body>
                        {$table}
                    </body>
                    </html>
        ";

        return $html;
    }

//学生具体课时消耗数据导出
public function export_studentDetails(){
	
        $data = I('post.export_data');	
		$data = json_decode($data);
		
		$school = ''; //获取校区名字;
		if($data){
			foreach($data as $key=>$record){
	        	$school = $key;break;
			}	
		}
		
		$current_date = I('post.export_date');	
        $head = array(
            array('school'           , '校区'),
            array('std_id'       , '学员编号'),
            array('name'          , '姓名'),
            array('day'             , $current_date.'消耗课时量(小时)'),
            array('sum_day_money'        , $current_date.'消耗金额(元)'),
            array('month_count'    , '月课时总数(小时)'),
            array('month_xh_money'         , '月预排课消耗金额(元)'),
            array('sum_cwqr_keshi'    , '月实际消耗课时总数(小时)'),
            array('sum_cwqr_money'          , '月实时消耗金额(元)'),
            array('used_hour'    , '学员累计实际消耗课时总是(小时)'),
            array('real_consump_value'    , '学员累计实际消耗金额(元)'),
            array('remain_csp' , '账户剩余金额(元)'),
            array('remain'          , '剩余总金额(元)')
            
        );
		
		$template = $this->export($data, $head);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition:attachment; filename=' . $school . '的学员课时消耗数据详情'.date(' Y-m-d H-i-s').'.xls');
        header('Cache-Control: max-age=0');
        die($template);
    }

//new 获取某一天课程详情及课程单价
function course_detail_ajax($std_id,$less_date=0,$p_school,$type="day"){
    	
    	if(!empty($std_id)){
			$w['cl.std_id']=$std_id;
			
			/*if($type == "day"){
				$w['cl.timee']=$less_date;	
			}else if($type != "all_sjxh"){
				$c=strtotime(date('Y-m-01',strtotime(substr($less_date, 0,7).'-01')));//获取月初时间戳
		        $cc=$c+date('t',$c)*24*3600;//获取月末时间戳
				$cc = date('Y-m-d',$cc);
				
				$w['cl.timee'] = array(array('egt',(substr($less_date, 0,7).'-01')),array('lt',$cc));
			}*/
	        
			$w['cl.state'] = array('NEQ',2);
			$data=M('hw001.class',null)->alias('cl')->join('hw001.student as st on cl.std_id = st.std_id')->where($w)->order('cl.course_id,cl.timee desc,cl.time1,cl.time2')->select();
			
			$pre_item = 0; //上一条记录的std_id;
			$pre_count = 0; //上一条记录的课时数；
			$pre_course = 0; //上一条记录的订单编号；通过已上三条记录进行课时消耗金额的回推过程；
			foreach($data as &$item){
				
				$course=D('CourseView')->where(['Course.state' => 200])->find($item['course_id']);
				
				if($pre_item != $item['std_id'] && $pre_course != $item['course_id']){
					$pre_item = $item['std_id'];
					$pre_count = $item['count'];
					$pre_course = $item['course_id'];	
					$p1 = $this->course_xiaohao($course);
					$p2 = $this->course_xiaohao($course,$item['count']);
					
				}else{
					$course['used_hour'] = $course['used_hour']-$pre_count;
					$p1 = $this->course_xiaohao($course);
					$p2 = $this->course_xiaohao($course,$item['count']);
					$pre_count += $item['count'];
				}
				
				$item['price'] = round($p1 - $p2,2);
			}
			
			unset($item);
		}elseif($std_id == 0){
			$w['cl.std_id']=$std_id;
			$w['cl.school'] = $p_school;
			if($type == "day"){
				$w['cl.timee']=$less_date;	
			}else if($type != "all_sjxh"){
				$c=strtotime(date('Y-m-01',strtotime(substr($less_date, 0,7).'-01')));//获取月初时间戳
		        $cc=$c+date('t',$c)*24*3600;//获取月末时间戳
				$cc = date('Y-m-d',$cc);
				
				$w['cl.timee'] = array(array('egt',(substr($less_date, 0,7).'-01')),array('lt',$cc));
			}
	        
			$w['cl.state'] = array('NEQ',2);
			$data=M('hw001.class',null)->alias('cl')->where($w)->select();
			
			foreach($data as $key=>$value){
				$data[$key]['name'] = '试听课学员';
				$data[$key]['price'] = 0;
			}
		}		
    	
    		
	      $this->ajaxReturn($data);
	        
    	
        
    }



//获取某一天课程详情及课程单价
    function course_detail_ajax_old($std_id,$less_date=0,$p_school,$type="day"){
    	
    	if(!empty($std_id)){
			$w['cl.std_id']=$std_id;
			
			if($type == "day"){
				$w['cl.timee']=$less_date;	
			}else{
				$c=strtotime(date('Y-m-01',strtotime(substr($less_date, 0,7).'-01')));//获取月初时间戳
		        $cc=$c+date('t',$c)*24*3600;//获取月末时间戳
				$cc = date('Y-m-d',$cc);
				
				$w['cl.timee'] = array(array('egt',(substr($less_date, 0,7).'-01')),array('lt',$cc));
			}
	        
			$w['cl.state'] = array('NEQ',2);
			$data=M('hw001.class',null)->alias('cl')->join('hw001.student as st on cl.std_id = st.std_id')->where($w)->select();
			
			foreach($data as &$item){
				
				$course=D('CourseView')->find($item['course_id']);
				$p1 = D('Course')->course_xiaohao($course);
				$p2 = D('Course')->course_xiaohao($course,$item['count']);
				$item['price'] = round($p1 - $p2,2);
				
			}
			unset($item);
		}elseif($std_id == 0){
			$w['cl.std_id']=$std_id;
			$w['cl.school'] = $p_school;
			if($type == "day"){
				$w['cl.timee']=$less_date;	
			}else{
				$c=strtotime(date('Y-m-01',strtotime(substr($less_date, 0,7).'-01')));//获取月初时间戳
		        $cc=$c+date('t',$c)*24*3600;//获取月末时间戳
				$cc = date('Y-m-d',$cc);
				
				$w['cl.timee'] = array(array('egt',(substr($less_date, 0,7).'-01')),array('lt',$cc));
			}
	        
			$w['cl.state'] = array('NEQ',2);
			$data=M('hw001.class',null)->alias('cl')->where($w)->select();
			
			foreach($data as $key=>$value){
				$data[$key]['name'] = '试听课学员';
				$data[$key]['price'] = 0;
			}
		}		
    	
    		
	      $this->ajaxReturn($data);
	        
    	
        
    }
	

    public function course(){
       
	   	$school = I('get.school')?:session('school_id');	
		$p_date = I('get.p_date');
			
        $w=['is_del'=>0,'state'=>200];
		
		if(!empty($school) && $school !== 0){
			$w['school']=$school;
		}
		
        $data=D('CourseView')->where($w)->select();
		
		$cur_date = date("Y-m");
		$month = I('get.p_date')?substr(I('get.p_date'), 0,7):$cur_date;
		
		$sw['month'] = $month;
		if(!empty($school) && $school !== 0){
			$sw['school'] = $school;	
		}

		$school_target_lst = M('school_target')->where($sw)->select();
	
        foreach ($data as $v) {
            $list[get_school_name($v['school'])]['remain']+=$v['price']-$this->course_xiaohao($v); //订单剩余金额，price学生帐号总金额
        }
        
        $w2=['timee'=>(I('get.p_date')?:date('Y-m-d'))];
		
		if(!empty($school) && $school !== 0){
			$w2['school'] = get_school_name($school);	
		}
		
		
		$dd = I('get.p_date')?date('Y-m-01',strtotime(I('get.p_date'))):date('Y-m-01');
		$c=strtotime($dd);//获取月初时间戳
        $cc=$c+date('t',$c)*24*3600;//获取月末时间戳
		$cc = date('Y-m-d',$cc);
		
		$wm['timee'] = array(array('egt',$dd),array('lt',$cc));
		if(!empty($school) && $school !== 0){
			$wm['school'] = get_school_name($school);	
		}
		
		$w2['state'] = array('NEQ',2);
		$wm['state'] = array('NEQ',2);
		$day_count = M('hw001.class',null)->where($w2)->group('school')->field("school,sum(count) as count ")->select();
		$month_count = M('hw001.class',null)->where($wm)->group('school')->field("school,sum(count) as count ")->select();
		
		
		
        foreach (C('SCHOOL') as $v) {
        	if($v['name'] == '集团'){
        		continue;
        	}
			
			if(!empty($school) && $v['id'] != $school){
				continue;
			}
			
			foreach($school_target_lst as $vo){
            	if($vo['school'] == $v['id']){
            		$list[$v['name']]['target'] = $vo['xiaohao'];
					break;
            	}
            }
			

			$arr_idx = array_search($v['name'], array_column($day_count, 'school'));
			$arr_idx2 = array_search($v['name'], array_column($month_count, 'school'));
			
			if($arr_idx !== FALSE){
				$list[$v['name']]['day']= $day_count[$arr_idx]['count'];
					
			}else{
				$list[$v['name']]['day']= 0;
				
			}
			
			if($arr_idx2 !== FALSE){
				$list[$v['name']]['month_count']= $month_count[$arr_idx2]['count'];	
			}else{
				$list[$v['name']]['month_count']= 0;
			}
			
			
			
			
			$cwqr = 'go'; //设置条件，获取财务确认的课时费用即实时消费金额
			$sum_day_money = 'day'; //设置条件，获取当天的课时费用，即当天消耗金额
			$list[$v['name']]['month_xh_money'] = $this->cal_month_xhm($v['name'],$sum_day_money,$p_date,$cwqr); //获取该校区本月消耗金额
			$list[$v['name']]['order_money'] =  $sum_day_money;//获取该校区当天消耗金额
			$list[$v['name']]['month_ssxh_money'] = $cwqr;//$this->cal_month_xhm($v['name'],1,'cwqr'); //获取该校区本月实时消耗金额
			
			unset($std_lst);
			
			$list[$v['name']]['remain'] +=  M('hw001.student',NULL)->alias('st')->join('hongwen_oa.oa_consumption as csp on st.std_id = csp.std_id')->where(['csp.is_del'=>0,'st.school'=>$v['name']])->sum('value');//校区所有账户剩余金额

        }
		unset($list['集团']);
        $this->list=$list;
		$this->cur_date = $p_date;
		$this->school = $school;
        $this->display('xiaohao_tongji');
    }

	function cal_month_xhm($less_school,&$day_money,$p_date,&$cwqr){
		
		$dd = $p_date?date('Y-m-01',strtotime($p_date)):date('Y-m-01');	
		$c=strtotime($dd);//获取月初时间戳
        $cc=$c+date('t',$c)*24*3600;//获取月末时间戳
		$cc = date('Y-m-d',$cc);
				
		if($less_school){
			$less_school = "cl.school='" . $less_school . "' ";
			
//			$less_date = "cl.timee >= '" . $dd . "' and cl.timee <= '" . $cc . "' ";	
			
			$order_str = " order by cl.school,cl.std_id,cl.course_id,cl.timee desc,cl.time1,cl.time2 ";
			
			$model = new \Think\Model();
			
			$sql_str = "select cl.std_id,cl.course_id,cl.timee as timee,cl.cwqr as cwqr,cl.count as count , cou.type as type,cou.hour as hour,cou.used_hour as used_hour,cou.ext_hour as ext_hour,cou.factor as factor ,cou.school as school,cou.unitprice as unitprice ,cou.price as price from hw001.class as cl,hongwen_oa.oa_course as cou where cou.id = cl.course_id and cl.std_id is not null and cl.std_id != '' and cl.course_id is not null and cl.course_id != 0 and cl.state != 2 and cou.is_del=0 and cou.state=200 and " . $less_school . $order_str;
			
			$data = $model->query($sql_str);
			
			$cur_date = $p_date?:date('Y-m-d');
			
			$pre_item = 0; //上一条记录的std_id;
			$pre_count = 0; //上一条记录的课时数；
			$pre_course = 0; //上一条记录的订单编号；通过已上三条记录进行课时消耗金额的回推过程；
			foreach($data as &$item){
				
				if($pre_item != $item['std_id'] && $pre_course != $item['course_id']){
					$pre_item = $item['std_id'];
					$pre_count = $item['count'];	
					$pre_course = $item['course_id'];
					$p1 = $this->course_xiaohao($item);
					$p2 = $this->course_xiaohao($item,$item['count']);
					
				}else{
					$item['used_hour'] = $item['used_hour']-$pre_count;
					$p1 = $this->course_xiaohao($item);
					$p2 = $this->course_xiaohao($item,$item['count']);
					$pre_count += $item['count'];
				}
				
				$item['price'] = round($p1 - $p2,2);
				//本月消耗
//				if(date('Y-m-d',strtotime($item['timee'])) >= $dd && date('Y-m-d',strtotime($item['timee'])) < $cc){
				if(!empty($item['timee']) && (substr($item['timee'], 0,10) >= $dd && substr($item['timee'], 0,10) < $cc)){
					$sum_cur_money += $item['price'];		
				}	
				 
				if(!empty($item['cwqr']) && $cwqr == 'go'){
					//排除旷课后，财务确认的数据
					if(!empty($item['timee']) && (date('Y-m-d',strtotime($item['timee'])) >= $dd && date('Y-m-d',strtotime($item['timee'])) < $cc)){
						$sum_cwqr_money += $item['price'];	
					}
					 
				}
				
				if(!empty($item['timee']) && (substr($item['timee'], 0,10) == $cur_date)){
					//排除旷课后，财务确认的数据
					$sum_day_money += $item['price'];	
					
				}
			}
			unset($item);
		}
		
		$day_money = $sum_day_money;
		$cwqr = $sum_cwqr_money;
		
		return $sum_cur_money;	
	}

	//获取某一天课程详情及课程单价
    function course_ajax($less_school,$less_date=0){
    	
    	if($less_school){
			$w['cl.school']=$less_school;
			
			$w['cl.course_id'] = array('NEQ',0);
			$dd = $less_date?date('Y-m-01',strtotime($less_date)):date('Y-m-01');	
			$c=strtotime($dd);//获取月初时间戳
	        $cc=$c+date('t',$c)*24*3600;//获取月末时间戳
			$cc = date('Y-m-d',$cc);
			
	        $data=M('hw001.class',null)->alias('cl')->where($w)->order('cl.std_id,cl.course_id,cl.timee desc,cl.time1,cl.time2')->select();
			
			$pre_item = 0; //上一条记录的std_id;
			$pre_count = 0; //上一条记录的课时数；
			$pre_course = 0; //上一条记录的订单编号；通过已上三条记录进行课时消耗金额的回推过程；
			foreach($data as $key=>$item){
				
				if(($item['stuid'] == '88888') || ($item['course_id'] == 0)){
					$item['name'] = "试听学员";
					$item['price'] = 0;
				}else{
						
					$course=D('CourseView')->where(['Course.state' => 200])->find($item['course_id']);
					
					$data[$key]['name'] = $course['name'];
					
					if($pre_item != $item['std_id'] && $pre_course != $item['course_id']){
						$pre_item = $item['std_id'];
						$pre_count = $item['count'];	
						$pre_course = $item['course_id'];
						$p1 = $this->course_xiaohao($course);
						$p2 = $this->course_xiaohao($course,$item['count']);
						
					}else{
						$course['used_hour'] = $course['used_hour']-$pre_count;
						$p1 = $this->course_xiaohao($course);
						$p2 = $this->course_xiaohao($course,$item['count']);
						$pre_count += $item['count'];
					}
					$data[$key]['price'] = round($p1 - $p2,2);
				}
				
				if(!empty($less_date)){
					if(substr($item['timee'], 0,10) != $less_date){
							unset($data[$key]);
					}
				}elseif(date('Y-m-d',strtotime($item['timee'])) < $dd || date('Y-m-d',strtotime($item['timee'])) >= $cc){
							unset($data[$key]);
				}
				
			}
		}

		$data = array_values($data);
		
	    $this->ajaxReturn($data);
        
    }

/**
学管数据维护
*/
    //记录前一天学管数据
    public function record_xueguan(){
        $where['date']=date('Y-m-d');
        if(!M('hw001.report_xueguan',null)->where($where)->find()){
            $xu['position']='学习管理师';
            $xueguan=M('user')->where($xu)->select();
            // var_dump($xueguan);
            // die;
            foreach ($xueguan as $val) {
                $data['xueguan']=$val['name'];
                $data['school']=$val['school'];
                $data['date']=date('Y-m-d');
                $w['xueguan']=$val['name'];
                $w['state']=2;//停课
                if(M('hw001.student',null)->where($w)->getField('id',true))$data['bb']=implode('|',M('hw001.student',null)->where($w)->getField('id',true));
                $w['state']=3;//结课
                if(M('hw001.student',null)->where($w)->getField('id',true))$data['cc']=implode('|',M('hw001.student',null)->where($w)->getField('id',true));
                $w['state']=5;//退费
                if(M('hw001.student',null)->where($w)->getField('id',true))$data['dd']=implode('|',M('hw001.student',null)->where($w)->getField('id',true));
                $w['state']=1;//正常
                if(M('hw001.student',null)->where($w)->getField('id',true))$data['aa']=implode('|',M('hw001.student',null)->where($w)->getField('id',true));
                M('hw001.report_xueguan',null)->add($data);
                unset($w);
                unset($data);
            }
        }
    }

// ======================学管数据=========================
    public function weihu_xueguan(){
        var_dump('88888');die;
        $w['xueguan']=session('user_name');
        $w['state']=array('in','1,2,3,5');
        $m=M('hw001.student',null)->where($w)->Field('id,state,name,grade,wl,xueguan,jiaoxue,tk')->select();
        $km=array('数学','语文','英语','物理','化学','生物','政治','历史','地理');
        $data['aa1']=count($m);
        foreach ($m as $k => $v) {
            $w2['stuid']=$v['id'];
            $w2['timee']=array('egt',date('Y-m-d'));
            if(M('hw001.class',null)->where($w2)->find() && $v['state']==1){
                $data['aa2']++;//本月正常在读
                $w2['timee']=array('like',date('Y-m')."%");
                $m2=M('hw001.class',null)->where($w2)->field('class,count')->select();//本月
                foreach ($m2 as $v2) {
                    $data['aaa'][$v2['class']]+=$v2['count'];//本月正常各科课时统计
                    $data['aa4']+=$v2['count'];//本月总课时
                    // $kmx[$v2['class']]+=$v2['count'];
                }
                //单科非正常在读
                foreach ($km as $v3) {
                    if(!substr_count($v['tk'],$v3)){
                        $w3['stuid']=$v['id'];
                        $w3['class']=$v3;
                        $max=M('hw001.class',null)->where($w3)->max('timee');
                        if($max>'2014-05-05'&&$max<date('Y-m-d'))$data['aba'][$v3]++;
                    }
                }
            }else{
                //本月非正常在读
                if($v['state']==1)$data['ab1']++;
                //停课学员数据
                if($v['state']==2)$data['ac1']++;
                //退费学员数据
                if($v['state']==5)$data['ac5']++;
                //结课学员数据
                if($v['state']==3)$data['ac9']++;
            }
        }
        // var_dump($data['xxx']);
        // die;
        //维护任务监控
        unset($w['state']);
        $w['date']=array('like',date('Y-m')."%");
        $wei=M('hw001.weihu',null)->where($w)->field('stuid,type,state')->select();
        foreach ($wei as $v4) {
            $weihu[$v4['type']][]=$v4['stuid'];//所有的任务
            if($v4['state']==0){
                $weihux[$v4['type']][]=$v4['stuid'];//未完成的任务
            }
            array_unique($weihu[$v4['type']]);
            array_unique($weihux[$v4['type']]);
        }
        $data['ada']=$weihu;//所有维护任务人次
        $data['adb']=$weihux;//所有未完成人次

        //讲师维护意见
        $data['adc']=M('hw001.weihu_advice',null)->where($w)->count();//所有维护意见
        $w['state']=1;
        $data['add']=M('hw001.weihu_advice',null)->where($w)->count();//已完成的意见

        $this->data=$data;
        $this->display();
    }

    public function weihu_school(){
        $w['school']=get_school_name();
        if($_POST['school'])$w['school']=$_POST['school'];

        //统计课时
        $class=self::school($w['school']);
        $data['bax']=$class[$w['school']];

        $w['state']=array('in','1,2,3,5');
        $m=M('hw001.student',null)->where($w)->Field('id,state,xueguan,tk')->select();
        $data['ba1']=count($m);
        foreach ($m as $v) {
            $w2['stuid']=$v['id'];
            $km=array('数学','语文','英语','物理','化学','生物','政治','历史','地理');
            $w2['timee']=array('egt',date('Y-m-d'));
            if(M('hw001.class',null)->where($w2)->find() && $v['state']==1){
                $data['baa'][$v['xueguan']]['正常'][]=$v['id'];//学管学员列表
                $data['bax']['人数']++;//本月正常在读人数
                //单科非正常在读学员(曾经上过，现在不上了)
                foreach ($km as $value) {
                    if(!substr_count($v['tk'],$value)){//相应科目没有停课
                        $w4['stuid']=$v['id'];
                        $w4['class']=$value;
                        $max=M('hw001.class',null)->where($w4)->max('timee');
                        if($max > '2014-05-05' && $max < date('Y-m-d')){
                            $data['baa'][$v['xueguan']]['单非'][$value][]=$v['id'];
                                $data['baa'][$v['xueguan']]['单非'][$value]=array_unique($data['baa'][$v['xueguan']]['单非'][$value]);
                            $data['bba']['单非'][$value][]=$v['id'];
                                $data['bba']['单非'][$value]=array_unique($data['bba']['单非'][$value]);
                            $data['baa'][$v['xueguan']]['单非']['总'][]=$v['id'];
                                $data['baa'][$v['xueguan']]['单非']['总']=array_unique($data['baa'][$v['xueguan']]['单非']['总']);
                            $data['bba']['单非']['总'][]=$v['id'];
                                $data['bba']['单非']['总']=array_unique($data['bba']['单非']['总']);
                        }
                        unset($w4['class']);
                    }
                }
            }else{
                //本月非正常在读
                if($v['state']==1){
                    $data['baa'][$v['xueguan']]['非正常'][]=$v['id'];
                    $data['bbx']['人数']++;//非正常在读
                }
                //停课学员数据
                if($v['state']==2){
                    $data['baa'][$v['xueguan']]['停课'][]=$v['id'];
                    $data['bcx1']['人数']++;
                }
                //退费学员数据
                if($v['state']==5){
                    $data['baa'][$v['xueguan']]['退费'][]=$v['id'];
                    $data['bcx2']['人数']++;//非正常在读
                }
                //结课学员数据
                if($v['state']==3){
                    $data['baa'][$v['xueguan']]['结课'][]=$v['id'];
                    $data['bcx3']['人数']++;//非正常在读
                }
            }
        }
        $km=array('数学','语文','英语','物理','化学','生物','政治','历史','地理');
        // var_dump($data['baa']);die;
        foreach ($data['baa'] as $k => $val) {//学管内部循环
            if($val['正常']){
                $w3['stuid']=array('in',$val['正常']);
                $w3['timee']=array('egt',date('Y-m-d'));
                foreach ($km as $v2) {
                    $w3['class']=$v2;
                    $data['baa'][$k][$v2]=M('hw001.class',null)->where($w3)->sum('count');
                    $data['baa'][$k]['总数']+=$data['baa'][$k][$v2];
                }
            }
            //变化量统计
            $where['xueguan']=$k;
            $where['date']=date('Y-m-d',(time()-24*3600));
            $xg=M('hw001.report_xueguan',null)->where($where)->find();
            if($xg)$data['baa'][$k]['新增']['停课']=array_diff(explode('|',$xg['bb']),$data['baa'][$k]['停课']);
            if($xg)$data['baa'][$k]['新增']['退费']=array_diff(explode('|',$xg['dd']),$data['baa'][$k]['退费']);
            if($xg)$data['baa'][$k]['新增']['结课']=array_diff(explode('|',$xg['cc']),$data['baa'][$k]['结课']);
            if($xg)$data['baa'][$k]['激活']['停课']=array_intersect($data['baa'][$k]['正常'],explode('|',$xg['bb']));
            if($xg)$data['baa'][$k]['激活']['退费']=array_intersect($data['baa'][$k]['正常'],explode('|',$xg['dd']));
            if($xg)$data['baa'][$k]['激活']['结课']=array_intersect($data['baa'][$k]['正常'],explode('|',$xg['cc']));
            unset($xg);
        }
        //维护任务监控
        unset($w['state']);
        $w['date']=array('like',date('Y-m')."%");
        $wei=M('hw001.weihu',null)->where($w)->field('stuid,type,xueguan,state')->select();
        foreach ($wei as $v4) {
            $weihu[$v4['type']][$v4['xueguan']]['全'][]=$v4['stuid'];//所有的任务
            if($v4['state']==1)$weihu[$v4['type']][$v4['xueguan']]['已'][]=$v4['stuid'];//已的任务
            array_unique($weihu[$v4['type']][$v4['xueguan']]['全']);
            array_unique($weihu[$v4['type']][$v4['xueguan']]['已']);
            $data['bdx'][$v4['type']]['全']++;
            if($v4['state']==1)$data['bdx'][$v4['type']]['已']++;
        }
        $data['bda']=$weihu;//所有维护任务

        //讲师维护意见
        $m2=M('hw001.weihu_advice',null)->where($w)->field('state,xueguan')->select();
        foreach ($m2 as $v5) {
            $data['bdc'][$v5['xueguan']]['全']++;
            if($v5['state']==1)$data['bdc'][$v5['xueguan']]['已']++;
            $data['bdx']['意见']['全']++;
            if($v5['state']==1)$data['bdx']['意见']['已']++;
        }
        $this->data=$data;
        $this->week=R('Xueguan/get_week',array(date('Y')));//输出任务添加日期
        $this->display();
    }

//页面调用查询
    public function weihu_api($data,$km=''){
        $w['xueguan']=session('user_name');
        $w['state']=array('in','1,2,3,5');
        switch ($data) {
            case 'aa1'://我的学员
                $stuid=M('hw001.student',null)->where($w)->getField('id',true);
                break;

            case 'aa2'://本月正常在读
                $w['state']=1;
                $s=M('hw001.student',null)->where($w)->getField('id,state,xueguan',true);
                foreach ($s as $val) {
                    $wx['stuid']=$val['id'];
                    $wx['timee']=array('egt',date('Y-m-d'));
                    if(M('hw001.class',null)->where($wx)->find())$stuid[]=$val['id'];
                }
                break;

            case 'ab1'://本月非正常在读，当下往后没有排课的情况
                $w['state']=1;
                $s=M('hw001.student',null)->where($w)->getField('id,state,xueguan',true);
                foreach ($s as $val) {
                    $wx['stuid']=$val['id'];
                    $wx['timee']=array('egt',date('Y-m-d'));
                    if(M('hw001.class',null)->where($wx)->find()){
                    }else{
                        $stuid[]=$val['id'];
                    }
                }
                break;

            case 'ab2'://单科非正常查询
                $w['state']=1;
                $s=M('hw001.student',null)->where($w)->getField('id,state,xueguan,tk',true);
                foreach ($s as $val) {
                    $wx['stuid']=$val['id'];
                    $wx['timee']=array('egt',date('Y-m-d'));
                    if(M('hw001.class',null)->where($wx)->find()){
                        if(!substr_count($val['tk'],$km)){
                            $wx['class']=$km;
                            unset($wx['timee']);
                            $max=M('hw001.class',null)->where($wx)->max('timee');
                            if($max>'2014-05-05' && $max<date('Y-m-d'))$stuid[]=$val['id'];
                            unset($wx['class']);
                        }
                    }
                }
                break;

            case 'ac1'://停课
                $w['state']=2;
                $stuid=M('hw001.student',null)->where($w)->getField('id',true);
                break;

            case 'ac5'://退费
                $w['state']=5;
                $stuid=M('hw001.student',null)->where($w)->getField('id',true);
                break;
            case 'ac9'://结课
                $w['state']=3;
                $stuid=M('hw001.student',null)->where($w)->getField('id',true);
                break;

            case 'ad1':
                $w['type']='普通维护';
                $stuid=M('hw001.weihu',null)->where($w)->getField('stuid',true);
                $stuid=array_unique($stuid);
                break;
            case 'ad3':
                $w['type']='A级维护';
                $stuid=M('hw001.weihu',null)->where($w)->getField('stuid',true);
                $stuid=array_unique($stuid);
                break;
            case 'ad5':
                $w['type']='2A级维护';
                $stuid=M('hw001.weihu',null)->where($w)->getField('stuid',true);
                $stuid=array_unique($stuid);
                break;
            case 'ad7':
                $w['state']=1;
                $w['type']='3A级维护';
                $stuid=M('hw001.weihu',null)->where($w)->getField('stuid',true);
                $stuid=array_unique($stuid);
                break;
            case 'ad9':
                $stuid=M('hw001.weihu_advice',null)->where($w)->getField('stuid',true);
                $stuid=array_unique($stuid);
                break;

        }

            $da=self::stuid($stuid);
            print(json_encode($da));
    }

//校区数据
    public function weihu_apis($data,$nm='',$school=''){
        $w['state']=array('in','1,2,3,5');
        $w['school']=get_school_name();
        if($school)$w['school']=$school;
        if($nm)$w['xueguan']=$nm;
        switch ($data) {
            case 'ba1'://学员总数
                $stuid=M('hw001.student',null)->where($w)->getField('id',true);
                break;

            case 'ba2'://本月正常在读
                $s=M('hw001.student',null)->where($w)->getField('id,state,xueguan',true);
                foreach ($s as $val) {
                    $wx['stuid']=$val['id'];
                    $wx['timee']=array('egt',date('Y-m-d'));
                    if(M('hw001.class',null)->where($wx)->find() && $val['state']==1)$stuid[]=$val['id'];
                }
                break;

            case 'bb1'://非正常在读
                $w['state']=1;
                $s=M('hw001.student',null)->where($w)->getField('id,state,xueguan',true);
                foreach ($s as $val) {
                    $wx['stuid']=$val['id'];
                    $wx['timee']=array('egt',date('Y-m-d'));
                    if(!M('hw001.class',null)->where($wx)->find())$stuid[]=$val['id'];
                }
                break;
            case 'bb2'://单科非正常在读
                $w['state']=1;
                $s=M('hw001.student',null)->where($w)->getField('id,state,xueguan,tk',true);
                foreach ($s as $val) {
                    $wx['stuid']=$val['id'];
                    $km=array('数学','语文','英语','物理','化学','生物','政治','历史','地理');
                    if(M('hw001.class',null)->where($wx)->max('timee') >= date('Y-m-d')){
                        foreach ($km as $value) {
                            if(!substr_count($val['tk'],$value)){//相应科目没有停课
                                $wx['class']=$value;
                                $max=M('hw001.class',null)->where($wx)->max('timee');
                                if($max > '2014-05-06' && $max < date('Y-m-d'))$stuid[]=$val['id'];
                            }
                            unset($wx['class']);
                        }
                    }
                }
                $stuid=array_unique($stuid);
                break;

            case 'bc1'://停课
                $w['state']=2;
                $stuid=M('hw001.student',null)->where($w)->getField('id',true);
                break;

            case 'bc2'://新增停课
                unset($w['state']);
                $cc=explode('|',M('hw001.report_xueguan',null)->where($w)->getField('bb'));
                $w['state']=2;
                $stuid=array_diff(M('hw001.student',null)->where($w)->getField('id',true),$cc);
                break;
            case 'bc3'://停课激活
                unset($w['state']);
                $cc=explode('|',M('hw001.report_xueguan',null)->where($w)->getField('bb'));
                $w['state']=1;
                $stuid=array_diff(M('hw001.student',null)->where($w)->getField('id',true),$cc);
                break;

            case 'bc5'://退费
                $w['state']=5;
                $stuid=M('hw001.student',null)->where($w)->getField('id',true);
                break;
            case 'bc6'://新增退费
                unset($w['state']);
                $cc=explode('|',M('hw001.report_xueguan',null)->where($w)->getField('dd'));
                $w['state']=5;
                $stuid=array_diff(M('hw001.student',null)->where($w)->getField('id',true),$cc);
                break;
            case 'bc7'://退费激活
                unset($w['state']);
                $cc=explode('|',M('hw001.report_xueguan',null)->where($w)->getField('dd'));
                $w['state']=1;
                $stuid=array_diff(M('hw001.student',null)->where($w)->getField('id',true),$cc);
                break;

            case 'bc9'://结课
                $w['state']=3;
                $stuid=M('hw001.student',null)->where($w)->getField('id',true);
                break;
            case 'bc10'://新增结课
                unset($w['state']);
                $cc=explode('|',M('hw001.report_xueguan',null)->where($w)->getField('cc'));
                $w['state']=3;
                $stuid=array_diff(M('hw001.student',null)->where($w)->getField('id',true),$cc);
                break;
            case 'bc11'://结课激活
                unset($w['state']);
                $cc=explode('|',M('hw001.report_xueguan',null)->where($w)->getField('cc'));
                $w['state']=1;
                $stuid=array_diff(M('hw001.student',null)->where($w)->getField('id',true),$cc);
                break;

            case 'bd1':
                unset($w['state']);
                $w['type']='普通维护';
                $w['date']=array('like',date('Y-m')."%");
                $stuid=M('hw001.weihu',null)->where($w)->getField('stuid',true);
                $stuid=array_unique($stuid);
                break;
            case 'bd2':
                $w['type']='普通维护';
                $w['state']=1;
                $w['date']=array('like',date('Y-m')."%");
                $stuid=M('hw001.weihu',null)->where($w)->getField('stuid',true);
                $stuid=array_unique($stuid);
                break;
            case 'bd3':
                unset($w['state']);
                $w['type']='A级维护';
                $w['date']=array('like',date('Y-m')."%");
                $stuid=M('hw001.weihu',null)->where($w)->getField('stuid',true);
                $stuid=array_unique($stuid);
                break;
            case 'bd4':
                $w['type']='A级维护';
                $w['state']=1;
                $w['date']=array('like',date('Y-m')."%");
                $stuid=M('hw001.weihu',null)->where($w)->getField('stuid',true);
                $stuid=array_unique($stuid);
                break;
            case 'bd5':
                unset($w['state']);
                $w['type']='2A级维护';
                $w['date']=array('like',date('Y-m')."%");
                $stuid=M('hw001.weihu',null)->where($w)->getField('stuid',true);
                $stuid=array_unique($stuid);
                break;
            case 'bd6':
                $w['type']='2A级维护';
                $w['state']=1;
                $w['date']=array('like',date('Y-m')."%");
                $stuid=M('hw001.weihu',null)->where($w)->getField('stuid',true);
                $stuid=array_unique($stuid);
                break;
            case 'bd7':
                unset($w['state']);
                $w['type']='3A级维护';
                $w['date']=array('like',date('Y-m')."%");
                $stuid=M('hw001.weihu',null)->where($w)->getField('stuid',true);
                $stuid=array_unique($stuid);
                break;
            case 'bd8':
                $w['state']=1;
                $w['type']='3A级维护';
                $w['date']=array('like',date('Y-m')."%");
                $stuid=M('hw001.weihu',null)->where($w)->getField('stuid',true);
                $stuid=array_unique($stuid);
                break;
            case 'bd9'://老师维护意见
                unset($w['state']);
                $stuid=M('hw001.weihu_advice',null)->where($w)->getField('stuid',true);
                $stuid=array_unique($stuid);
                break;
            case 'bd10'://老师维护意见
                $w['state']=1;
                $stuid=M('hw001.weihu_advice',null)->where($w)->getField('stuid',true);
                $stuid=array_unique($stuid);
                break;
        }
            $da=self::stuid($stuid);
            print(json_encode($da));
    }

//格式化学员数据api
    public static function stuid($stuid){
            foreach ($stuid as $v) {
                $w2['stuid']=$v;
                $dat[$v]['info']=M('hw001.student',null)->find($v);
                $w3['stuid']=$v;
                $w3['timee']=array('egt',date('Y-m-d'));
                $class=M('hw001.class',null)->where($w3)->select();
                foreach ($class as $v2){
                    $c[$v2['class']]+=$v2['count'];
                    $cc+=$v2['count'];
                }
                if($cc && $dat[$v]['info']['state']==1){
                    $dat[$v]['state']='正常';
                }else{
                    if($dat[$v]['info']['state']==1)$dat[$v]['state']='非正常';
                    if($dat[$v]['info']['state']==2)$dat[$v]['state']='停课';
                    if($dat[$v]['info']['state']==3)$dat[$v]['state']='结课';
                    if($dat[$v]['info']['state']==5)$dat[$v]['state']='退费';
                }

                //统计课时量
                $dat[$v]['a']=$c['语文']+0;
                $dat[$v]['b']=$c['数学']+0;
                $dat[$v]['c']=$c['英语']+0;
                $dat[$v]['d']=$c['物理']+$c['地理'];
                $dat[$v]['e']=$c['化学']+$c['历史'];
                $dat[$v]['f']=$c['生物']+$c['政治'];

                //统计是否报名
                $km=array('数学','语文','英语','物理','化学','生物','政治','历史','地理');
                foreach ($km as $k) {
                    $w2['class']=$k;
                    if(!M('hw001.class',null)->where($w2)->find())$pk[$k]='无';
                }
                if($pk['语文']=='无')$dat[$v]['a']='无';
                if($pk['数学']=='无')$dat[$v]['b']='无';
                if($pk['英语']=='无')$dat[$v]['c']='无';
                if($pk['物理']=='无' && $pk['地理']=='无')$dat[$v]['d']='无';
                if($pk['化学']=='无' && $pk['历史']=='无')$dat[$v]['e']='无';
                if($pk['生物']=='无' && $pk['政治']=='无')$dat[$v]['f']='无';
                unset($pk);

                //统计停课
                if(substr_count($dat[$v]['info']['tk'],'语文') && $dat[$v]['a']!='无')$dat[$v]['a']='停课';
                if(substr_count($dat[$v]['info']['tk'],'数学') && $dat[$v]['b']!='无')$dat[$v]['b']='停课';
                if(substr_count($dat[$v]['info']['tk'],'英语') && $dat[$v]['c']!='无')$dat[$v]['c']='停课';
                if(substr_count($dat[$v]['info']['tk'],'物理') && $dat[$v]['d']!='无')$dat[$v]['d']='停课';
                if(substr_count($dat[$v]['info']['tk'],'化学') && $dat[$v]['e']!='无')$dat[$v]['e']='停课';
                if(substr_count($dat[$v]['info']['tk'],'生物') && $dat[$v]['f']!='无')$dat[$v]['f']='停课';

                //临时加课问题
                if(substr_count($dat[$v]['info']['tk'],'临时')){$dat[$v]['tag']='临时加课';}elseif($dat[$v]['state']=='非正常'){$dat[$v]['tag']='未处理';}else{$dat[$v]['tag']=$dat[$v]['state'];}

                //维护任务统计
                $w2['state']=1;
                $dat[$v]['weihu'][0]=M('hw001.weihu',null)->where($w2)->max('date');
                $w2['state']=0;
                $dat[$v]['weihu'][1]=M('hw001.weihu',null)->where($w2)->min('date');
                unset($c);
                unset($cc);
                $da[]=$dat[$v];
            }
        return $da;
    }
//临时挪用
    function school($school){
            $aa['school']=$school;
            $ss=$school;
            if($_POST['time']){
                $day=$_POST['time'];
                $date=date('Y-m',strtotime($day));
            }else{
                $date=date('Y-m');
                $day=date('Y-m-d');
            }
            $aa['timee']=array('like',"$date%");
            $aa['state']=array('NEQ',2);
            $class=M('hw001.class',null)->where($aa)->order('timee asc,grade asc,time1 asc,class asc,teacher asc,state asc')->select();

                    foreach ($class as $classl) {
                        if($classl['timee']==$a&&$classl['time1']==$b&&$classl['time2']==$c&&$classl['class']==$d&&$classl['teacher']==$e){
                        }else{
                            switch ($classl['class']) {
                                case '数学':
                                    $vm["$ss"]['数学']+=$classl['count'];
                                    if($classl['timee']>=$monday && $classl['timee']<$weekend)$vw["$ss"]['数学']+=$classl['count'];
                                    if($classl['timee']==$day)$vd["$ss"]['数学']+=$classl['count'];
                                    break;
                                case '语文':
                                    $vm["$ss"]['语文']+=$classl['count'];
                                    if($classl['timee']>=$monday && $classl['timee']<$weekend)$vw["$ss"]['语文']+=$classl['count'];
                                    if($classl['timee']==$day)$vd["$ss"]['语文']+=$classl['count'];
                                    break;
                                case '英语':
                                    $vm["$ss"]['英语']+=$classl['count'];
                                    if($classl['timee']>=$monday && $classl['timee']<$weekend)$vw["$ss"]['英语']+=$classl['count'];
                                    if($classl['timee']==$day)$vd["$ss"]['英语']+=$classl['count'];
                                    break;
                                case '物理':
                                    $vm["$ss"]['物理']+=$classl['count'];
                                    if($classl['timee']>=$monday && $classl['timee']<$weekend)$vw["$ss"]['物理']+=$classl['count'];
                                    if($classl['timee']==$day)$vd["$ss"]['物理']+=$classl['count'];
                                    break;
                                case '化学':
                                    $vm["$ss"]['化学']+=$classl['count'];
                                    if($classl['timee']>=$monday && $classl['timee']<$weekend)$vw["$ss"]['化学']+=$classl['count'];
                                    if($classl['timee']==$day)$vd["$ss"]['化学']+=$classl['count'];
                                    break;
                                case '生物':
                                    $vm["$ss"]['生物']+=$classl['count'];
                                    if($classl['timee']>=$monday && $classl['timee']<$weekend)$vw["$ss"]['生物']+=$classl['count'];
                                    if($classl['timee']==$day)$vd["$ss"]['生物']+=$classl['count'];
                                    break;
                                case '政治':
                                    $vm["$ss"]['政治']+=$classl['count'];
                                    if($classl['timee']>=$monday && $classl['timee']<$weekend)$vw["$ss"]['政治']+=$classl['count'];
                                    if($classl['timee']==$day)$vd["$ss"]['政治']+=$classl['count'];
                                    break;
                                case '历史':
                                    $vm["$ss"]['历史']+=$classl['count'];
                                    if($classl['timee']>=$monday && $classl['timee']<$weekend)$vw["$ss"]['历史']+=$classl['count'];
                                    if($classl['timee']==$day)$vd["$ss"]['历史']+=$classl['count'];
                                    break;
                                case '地理':
                                    $vm["$ss"]['地理']+=$classl['count'];
                                    if($classl['timee']>=$monday && $classl['timee']<$weekend)$vw["$ss"]['地理']+=$classl['count'];
                                    if($classl['timee']==$day)$vd["$ss"]['地理']+=$classl['count'];
                                    break;
                            }
                        }
                            $a=$classl['timee'];
                            $b=$classl['time1'];
                            $c=$classl['time2'];
                            $d=$classl['class'];
                            $e=$classl['teacher'];
                    }

        //月度每日变化量统计
        $w['date']=date('Y-m-d',strtotime($day)-24*3600);
        $b=M('hw001.tongji',null)->where($w)->select();
        foreach ($b as $val){
            $hj=$val['a']+$val['b']+$val['c']+$val['d']+$val['e']+$val['f']+$val['g']+$val['h']+$val['i'];
            $bh[$val['school']]=array('a'=>$val['a'],'b'=>$val['b'],'c'=>$val['c'],'d'=>$val['d'],'e'=>$val['e'],'f'=>$val['f'],'g'=>$val['g'],'h'=>$val['h'],'i'=>$val['i'],'bh'=>$hj);
        }

        foreach ($vd as $k2 => $v2) {
            $xx[$k2]=$v2['数学']+$v2['语文']+$v2['英语']+$v2['物理']+$v2['化学']+$v2['生物']+$v2['政治']+$v2['历史']+$v2['地理'];
        }
        arsort($xx);
        foreach ($xx as $k3 => $v3) {
            $vdd[$k3]=$vd[$k3];
        }
        foreach ($vm as $k4 => $v4) {
            $xxx[$k4]=$v4['数学']+$v4['语文']+$v4['英语']+$v4['物理']+$v4['化学']+$v4['生物']+$v4['政治']+$v4['历史']+$v4['地理'];
        }
        arsort($xxx);
        foreach ($xxx as $k5 => $v5) {
            $vmm[$k5]=$vm[$k5];
        }
        return $vmm;
    }

/**
运营数据
*/
    // 新招，续费数据统计
    public function run_tongji(){
        $month=I('month')?:session('date');
        $w['oa_consumption.is_del']=0;
        $w['oa_consumption.type']=['egt',10000];
        $w['oa_consumption.month']=$month;
        $data=M('consumption')
            ->join('oa_foo_info ON oa_consumption.emp_school=oa_foo_info.id')
            ->join('LEFT JOIN oa_school_target ON (oa_consumption.emp_school=oa_school_target.school and oa_consumption.month=oa_school_target.month)')
            ->field('oa_consumption.type as type,
                    oa_consumption.belong_type as belong_type,
                    oa_consumption.emp_school as school_id,
                    oa_consumption.value as value,
                    oa_foo_info.name as school_name,
                    oa_school_target.*')
            ->where($w)
            ->select();
            
        foreach ($data as $v) {
            $dat[$v['school_name']]['school_id']=$v['school_id'];
            //新招
            if($v['belong_type']==1){
                $dat[$v['school_name']]['xinzhao_value']+=$v['value'];
                if($v['create_time']>strtotime(date('Y-m-d'))&&$v['create_time']<(strtotime(date('Y-m-d'))+24*3600))$dat[$v['school_name']]['xinzhao_day']+=$v['value'];
            }
            //续费
            if($v['belong_type']==2){
                $dat[$v['school_name']]['xufei_value']+=$v['value'];
                if($v['create_time']>strtotime(date('Y-m-d'))&&$v['create_time']<(strtotime(date('Y-m-d'))+24*3600))$dat[$v['school_name']]['xufei_day']+=$v['value'];
            }
            //获取设定的校区目标
            if($v['xinzhao'])$dat[$v['school_name']]['xinzhao_target']=$v['xinzhao'];
            if($v['xufei'])$dat[$v['school_name']]['xufei_target']=$v['xufei'];
        }

        //获取特训营方案id
        $ids=D('UnitpriceRoleView')->where('F4.group=5')->getField('id',true);
        //查询校区特训营订单
        $texun=M('course')->where(['unit_plan'=>['in',$ids],'is_del'=>0,'create_time'=>['between',[strtotime($month),strtotime($month)+24*3600*date('t')]]])->select();
        if($texun) 
        foreach ($texun as $v) {
            $dat[get_school_name($v['school'])]['TeXunYin']+=$v['price'];
        }

        // var_dump($dat);

        $this->list=$dat;
        $this->display();
    }

    //校区目标设置
    public function school_target(){
        $m=M('school_target');
        if($_GET['delt']){
            $m->delete((int)$_GET['delt']);
        }
        if($_POST){
            $m->create();
            $m->month=session('date');
            $m->add();
        }
        $this->data=$m->where(array('month'=>session('date')))->select();
        $this->display();
    }




}
