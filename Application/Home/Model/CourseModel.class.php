<?php
namespace Home\Model;
 
class CourseModel extends CommonModel{

	private static $rule_discount = [];
	
	function _initialize(){
		$data=M('discount_role')->where(['is_del'=>0])->order('school,bottom asc')->field('school,bottom,top,value')->select();
		foreach($data as $v){
			self::$rule_discount[$v['school']][]=$v;			
		}
		unset($data);
	}
	
    // 订购操作，写入一条
    public function bookOne($course, $orderId ,$state=null){
        $course['state']    = $state ? $state : C('COURSE_STATES')['NORMAL']['id'];
        $course['order_id'] = $orderId;

        $sbt = [];
        if (!is_array($course['subject[]'])) {
            if($course['subject[]']) {
                $sbt[] = ['subject_id' => $course['subject[]'], 'teacher_id' => $course['teacher[]']];
            }
        } else {
            foreach ($course['subject[]'] as $key => $value) {
                $sbt[$key] = ['subject_id' => $value, 'teacher_id' => $course['teacher[]'][$key]];
            }
        }

        $course['hour']*=$course['count'];
		
        $plan=M('UnitpriceRole')->find($course['unit_plan']);
        //针对周月走规则的按is_join==2 流程走，否则的话，就不进行操作
        if($plan['is_join'] != 2){
        	$course['unitprice']/=$course['count'];
        }
//      $course['unitprice']/=$course['count']; //针对按周、月有30课时的单价方案无法正确计算，故此处删除 zhangxm edit at 2016-03-12 14:47
		
        $course['school']=session('school_id');
        if($this->create($course)){
            $courseId = $this->add();
            if (empty($sbt)) {
                return $courseId;
            }

            if ($courseId) {
                return D('CourseSbt')->write($courseId, $sbt);
            }
        }
    }

    // 订购操作，数组
    public function book($payInfo, $courses){
        $flag = 0;
        $consumption = D('Consumption');

        $payInfo['value']   = $payInfo['pay_real'];
        if($consumption->pay($payInfo)){ // 写入缴费记录
            $payInfo['value'] = -$payInfo['pay_due'];
            $consumptionId = $consumption->book($payInfo); // 写入订购消费记录
            if ($consumptionId) {
                foreach ($courses as $val) {
                    if ($this->bookOne($val, $consumptionId)) {
                        ++$flag;
                    }
                }
            }
        }

        return $flag;
    }

    /**
调整课时的订单操作
    */
    public function renewal($payInfo, $data, $oldCourse) {

        //将调课差价写入财务审核
        $consumption = D('Consumption');
        $payInfo['value']  = $payInfo['pay_real'];
        $payInfo['value'] = -$payInfo['pay_due']; 
        $payInfo['ext_id'] = $oldCourse['id'];
        $consumptionId = $consumption->renewal($payInfo);

        //直接调整课时
        $dat['hour']     = $data['hour']*$data['count'];
        $dat['unitprice']    = $data['unitprice'];
        $dat['price']    = $data['price'];
        $dat['remark']   = $data['remark'];
        $dat['subject']  = $data['subject'];
        $dat['teacher']  = $data['teacher'];
        $dat['ext_hour'] = $data['ext_hour'];
        if($consumptionId)$cs=D('Course')->where($oldCourse)->save($dat);

        //重新启用并正常化订单
        $this->changeState($oldCourse['id'], C('COURSE_STATES')['NORMAL']['id']);

        return ($consumptionId && $cs)?true:false;
    }

    public function change($payInfo, $data, $oldId) {
        $newCourse = $data;
        $consumption = D('Consumption');
        $payInfo['value']  = $payInfo['pay_real'];
        if ($consumption->pay($payInfo)) { // 写入缴费记录
            $payInfo['value'] = -$payInfo['pay_due']; // 写入转课消费记录
            $payInfo['ext_id'] = $oldId;
            $consumptionId = $consumption->change($payInfo);
            if ($consumptionId
                && $this->changeState($oldId, C('COURSE_STATES')['CHANGEING']['id'])) {
                return $this->bookOne($newCourse, $consumptionId);
            }
        }

        return false;
    }

    public function checkOrder($orderId, $state) {
        return $this->where(['order_id' => ['eq', $orderId]])->save(['state' => $state]);
    }

    public function checkDrop($extId, $state) {
        return $this->where(['id' => ['eq', $extId]])->save(['state' => $state]);
    }

    /**
     * 退课
     * @param  int    $id     退课记录的 id
     * @param  string $reason 退课原因
     * @param  string $remark 备注
     */
    public function drop($id, $info) {
        $course = M('course')->find($id);
        $return = $this->plan_price($course['unit_plan'],$course['used_hour'],$course['create_time'],1);

        if (D('Consumption')->drop([
            'student' => $course['name'],
            'std_id'  => $course['std_id'],
            'value'   => ($course['price']-$return['price']),
            'ext_id'  => $id,
            'remark'  => $info['remark'],
            'reason'  => $info['drop_reason'],
        ])) {
                    
            return $this->changeState($id, C('COURSE_STATES')['DROPED']['id']);
        }

        return false;
    }

    /**
     * 改变某条状态
     * @param  int $id    记录的 id
     * @param  int $state 状态的 id
     */
    public function changeState($id, $state) {
        return $this->save(['id' => $id, 'state' => $state]);
    }

/**
计算价格,plan方案(表unitprice_role),hour订购课时数或消耗数,type(0订购，1退费，2调课),create_time订单创建时间
*/
    public function plan_price($plan_id,$hour=0,$create_time=0,$type=0){
        $plan=M('UnitpriceRole')->find($plan_id);
        if($plan['count']>1 && ($type==1 || $type==2))$hour/=$plan['count'];

        $result=[
            'hour'=> $hour,
            'unitprice'=> $plan['price'],
            'label' => $plan['label'],
            'count' => $plan['count'],
            'factor' => $plan['factor'],
            'ext_hour' => 0,
            'price' => 0,
        	'is_join' => $plan['is_join'],
        ];

        //参与赠送的计算
        if($plan['is_join']==1){
            $result['ext_hour']=$this->_getExtHour($hour,$plan['school']);
        }

        //使用特殊规则计算订单
        if($plan['is_join']==2){
            $day=round(time()-$create_time,0);
            eval($plan['rule']);
			
            /*订购全日制课程时，如果按规则走，这里计算单价出现问题，不会走这个位置 zhangxm edit at 2016-03-12 11:21 
			 * if($type){
                $result['unitprice']=$result['unitprice']/$plan['count'];
            }*/
            
             $result['unitprice']=$result['unitprice']/$plan['count'];
        }

        $result['price']=$result['price']?:$result['unitprice']*$result['hour']*$result['factor'];

        // 退课的费用计算
        if($type==1 && $plan['is_join']==1){
            $ret=$this->getReturn($hour,session('school_id'));
            $result['return_hour']=$ret['hour'];
            $result['return_ext_hour']=$ret['ext_hour'];
            $result['return_ext_hour_last']=$ret['ext_hour_last'];
            $result['price']=$result['unitprice']*$result['return_hour']*$result['factor'];
        }

        return $result;
    }



    /**
     * 订购或调课下的赠送策略
     * @param  float $classHour 选课的课时
     * @return float            赠送的课时
     */
    private function _getExtHour($classHour, $school = -1) {
        $DiscountRole = D('DiscountRole');
        $roles = $DiscountRole->where([
            'is_del' => ['eq', 0],
            'pid'    => ['eq', C('DISCOUNT_ID')['HOUR']],
            'school' => ['in', [-1, $school]],
            ])->select();
        $discountHour = 0;
        foreach ($roles as $role) {
            if ($role['bottom'] <= $classHour && $classHour < $role['top']) {
//                 $discountHour = $role['value'];
                $discountHour =  $classHour-$role['bottom']>=$role['value']?$role['value']:$ext_hour_last;//根据实际消耗计算，赠送课时，32增2，65增5，110增10
                break;
            }
            //取上一次的赠送课时量
            $ext_hour_last = $role['value'];
        }

        return $discountHour;
    }

    /**
     * 退费情况下的赠送策略
     * @param  float $usedHour 已经上过的课时数
     */
    public function getReturn($usedHour, $school = -1) {
        $roles = D('DiscountRole')->where([
            'is_del' => ['eq', 0],
            'pid'    => ['eq', C('DISCOUNT_ID')['HOUR']],
            'school' => ['in', [-1, $school]],
            ])->select();

        $returnHour    = $usedHour;
        foreach ($roles as $role) {
            /* if ($role['bottom'] <= $usedHour && $usedHour < $role['top']) {
                $returnHour    = max($usedHour-$role['value'], $role['bottom']);
                $ext_hour_last = max($role['bottom']+$role['value']-$usedHour, 0);
                $ext_hour = $role['value'];//赠送剩余
            } */
            if ($role['bottom'] <= $usedHour && $usedHour < $role['top']) {
                //$returnHour    = max($usedHour-$role['value'], $role['bottom']);
                //消耗课时
                $returnHour = $usedHour-$role['bottom']>=$role['value']?($usedHour-$role['value']):($usedHour-$ext_hour_last);
                $ext_hour =$usedHour-$role['bottom']>=$role['value']?$role['value']:$ext_hour_last;//赠送课时
                //未消耗课时
                $ext_hour_last = 0;//$usedHour-$role['bottom']>=$role['value']?0:(($usedHour-$role['bottom'])>$ext_hour_last?0:($ext_hour_last-($usedHour-$role['bottom'])));
                break;
            }
            //取上一次的赠送课时量
            $ext_hour_last = $role['value'];
        }

        return [
            'hour'    => $returnHour,
            'ext_hour' => $ext_hour,
            'ext_hour_last' => $ext_hour_last
        ];
    }


/**  类似于该规则：30赠2 60赠5 100赠10，赠送不叠加。，返回该订单目前已经消耗的金额；
根据已上课时计算课程订单已经消耗的金额
*/
    public function course_xiaohao_old($data,$cur_hour=0){

    // 带赠送情况,分按正常校区赠送规则走和赠送要大于校区赠送规则的特殊优惠
	if($data['ext_hour']){
            if(self::$rule_discount[$data['school']]){
            	$is_sy=(($this->_getExtHour($data['hour'],$data['school'])+$data['hour']-$data['ext_hour'])>0)?1:0;
            	if($data['type']==1 && $is_sy){
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
							
							return ($hour-$cur_hour-$rl['value']) * $data['unitprice']*$data['factor'];
							
						}
						
						$prev_ext = $rl['value'];
	                }
            	}
            }
        }else{
            return ($data['used_hour']-$cur_hour)*$data['unitprice']*$data['factor'];
        }

 }



/**  类似于该规则：30赠2 60赠5 100赠10，赠送不叠加。，返回该订单目前已经消耗的金额；
	根据已上课时计算课程订单已经消耗的金额
	*/
   function course_xiaohao($data,$cur_hour=0){

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
	                    		return ($rl['bottom'])*$data['unitprice']*$data['factor'];
	                    	}else{
	                    		return (($data['used_hour']-$cur_hour-$prev_ext))*$data['unitprice']*$data['factor'];	
	                    	}
						
						}elseif(($hour-$cur_hour)>($rl['bottom']+$prev_ext) && ($hour-$cur_hour)<=($rl['bottom']+$rl['value'])){ //30-32
						
							return ($rl['bottom'])*$data['unitprice']*$data['factor'];
							
						}elseif(($hour-$cur_hour)>($rl['bottom']+$rl['value']) && ($hour-$cur_hour)<=($rl['top']+$rl['value'])){ //33-62
							
							return ($hour-$cur_hour-$rl['value']) * $data['unitprice']*$data['factor'];
							
						}
						
						$prev_ext = $rl['value'];
	                }
            	}
            }
        }else{
            return ($data['used_hour']-$cur_hour)*$data['unitprice']*$data['factor'];
        }

 }


}
