<?php
/**
*@Author:温加宝
*@Email:wen8555955@163.com
*@DateTime:2016/7/27
*@Description:人员业绩统计
*/
namespace Home\Controller;
use Think\Controller;
use Think\Model;

header('Access-Control-Allow-Origin: *');
header('P3P: CP="ALL ADM DEV PSAi COM OUR OTRo STP IND ONL"');
header("Content-type:text/html;charset=utf-8");
class PersonalCountController extends HomeController {
    //获取json文件数据
    function Json_file_get(){
        $json_str = file_get_contents("Public/json/BaseDataType.json");
//      echo $json_str;
		// 发送给页面的数据
		$this->ajaxReturn(json_decode($json_str));
    }

    //存入json文件数据
    function Json_file_add(){
        if(empty($_GET['json_str'])){
        	// 发送给页面的数据
			$this->ajaxReturn(array('status' => false , 'content' => '数据不存在'));
			exit;//数据不存在
        }
        $content = $_GET['json_str'];
        $file1 = 'Public/json/BaseDataType.json';
        $fp = fopen($file1, 'w');
        fwrite($fp, $content);
        fclose($fp);
		// 发送给页面的数据
		$this->ajaxReturn(array('status' => true , 'content' => '保存成功'));
		exit;//数据不存在
    }
	
	
	//单独查校区函数
	function Campus_find(){
		$model = M('foo_info');
		$campus_arr = array();
		$array = array();
		$campus_arr = $model->where(array('pid' => 15 , 'is_del' => 0))->select();
		foreach($campus_arr as $key => $value){
			$array[$key]['school_id'] = $value['id'];
			$array[$key]['school_name'] = $value['name'];
		}
		$this->ajaxReturn($array);
	}

    //默认显示业绩目标信息
    function Campus_target_find(){
        if(!empty($_GET['date'])){
            $date = $_GET['date'];
        }else{
            $date = date('Y-m',time());
        }
        $model1 = D('campustarget');
        $model2 = D('personaltarget');
        $model3 = M('user');
        $model4 = M('foo_info');
        $model5 = M('position');
        $data = array();
        $data2 = array();
        if(empty($_GET['school_id'])){
            $data_arr = $model1->where(array('date'=>$date))->select();
            if($data_arr){
                $data = array();
                foreach($data_arr as $k => $val){
                    $school_id = $val['campus_id'];
                    $campus_arr = $model4->where(array('id'=>$school_id))->find();
                    $data_arr2 = $model2->where(array('date' => $date , 'campus_id' => $school_id))->select();
                    $data2 = array();
                    if($data_arr2){
                        foreach ($data_arr2 as $key => $value) {
                            $user_arr = $model3->where(array('id' => $value['user_id']))->find();
                            $post_arr = $model5->where(array('id' => $value['post_id']))->find();
                            $data2[$key]['id'] = $value['id'];
                            $data2[$key]['name'] = $user_arr['name'];
                            $data2[$key]['post'] = $post_arr['name'];
                            $data2[$key]['post_id'] = $post_arr['id'];
                            $data2[$key]['level'] = $value['level'];
                            $data2[$key]['personal_num'] = $value['personal_num'];
                            $data2[$key]['target_consume'] = $value['target_consume'];
                            $data2[$key]['target'] = $value['target'];
                            $data2[$key]['upgrade_consume'] = $value['upgrade_consume'];
                            $data2[$key]['upgrade'] = $value['upgrade'];
                            $data2[$key]['relegation_consume'] = $value['relegation_consume'];
                            $data2[$key]['relegation'] = $value['relegation'];
                            $data2[$key]['consume'] = $value['consume'];
                        }
                    }else{
                        $data_arr2 = array();
                    }
                    $data[$k]['id'] = $val['id'];
                    $data[$k]['school_id'] = $val['campus_id'];
                    $data[$k]['school_name'] = $campus_arr['name'];
                    $data[$k]['new_target'] = $val['new_target'];
                    $data[$k]['old_target'] = $val['old_target'];
                    $data[$k]['count_target'] = $val['count_target'];
                    $data[$k]['personal_target'] = $data2;
                }
            }else{
                $data = array();
            }
			
	        if(empty($data[0]['id'])){
	            $campus_arr = $model4->where(array('pid' => 15 , 'is_del' => 0))->select();
	            foreach($campus_arr as $val){
	                $dats['campus_id'] = $val['id'];
	                $dats['date'] = date("Y-m",time());
	                $model1->add($dats);
	            }
	            $this->Campus_target_find();
	        }
        }else{
            $data_arr = $model1->where(array('date' => $date , 'campus_id' => $_GET['school_id']))->find();
            if($data_arr){
                $data = array();
                $school_id = $data_arr['campus_id'];
                $campus_arr = $model4->where(array('id'=>$school_id))->find();
                $data_arr2 = $model2->where(array('date' => $date , 'campus_id' => $school_id))->select();
                if($data_arr2){
                    $data2 = array();
                    foreach ($data_arr2 as $key => &$value) {
                        $user_arr = $model3->where(array('id' => $value['user_id']))->find();
                        $post_arr = $model5->where(array('id' => $value['post_id']))->find();
                        $data2[$key]['id'] = $value['id'];
                        $data2[$key]['name'] = $user_arr['name'];
                        $data2[$key]['post'] = $post_arr['name'];
                        $data2[$key]['post_id'] = $post_arr['id'];
                        $data2[$key]['level'] = $value['level'];
                        $data2[$key]['personal_num'] = $value['personal_num'];
                        $data2[$key]['target_consume'] = $value['target_consume'];
                        $data2[$key]['target'] = $value['target'];
                        $data2[$key]['upgrade_consume'] = $value['upgrade_consume'];
                        $data2[$key]['upgrade'] = $value['upgrade'];
                        $data2[$key]['relegation_consume'] = $value['relegation_consume'];
                        $data2[$key]['relegation'] = $value['relegation'];
                        $data2[$key]['consume'] = $value['consume'];
                    }
                }else{
                    $data_arr2 = array();
                }
                $data['id'] = $val['id'];
                $data['school_id'] = $data_arr['campus_id'];
                $data['school_name'] = $campus_arr['name'];
                $data['new_target'] = $data_arr['new_target'];
                $data['old_target'] = $data_arr['old_target'];
                $data['count_target'] = $data_arr['count_target'];
                $data['personal_target'] = $data2;
                
            }else{
                $data = array();
            }
        }
               // 发送给页面的数据
		$this->ajaxReturn($data);
    }



    //财务系统校区录入业绩目标删除
    function Campus_target_del(){
        if(empty($_GET["id"])){
        	// 发送给页面的数据
			$this->ajaxReturn(array('status' => false , 'content' => '程序出错请联系管理员'));
			exit;//程序出错请联系管理员
        }
        $model = D('personaltarget');
        $state = $model->where(array('id'=>$_GET['id']))->delete();
        if($state){
        	// 发送给页面的数据
			$this->ajaxReturn(array('status' => true , 'content' => '删除成功'));
			exit;//程序出错请联系管理员
        }else{
        	// 发送给页面的数据
			$this->ajaxReturn(array('status' => false , 'content' => '删除失败'));
			exit;//程序出错请联系管理员
        }

    }
    //添加业绩目标信息
    function Campus_target_add(){
        if(empty($_POST['data'])){
        	// 发送给页面的数据
			$this->ajaxReturn(array('status' => false , 'content' => '数据不存在'));
			exit;//数据不存在
        }else{
            $data_arr = json_decode($_POST['data'],true);
        }
        if(!is_array($data_arr)){
        	// 发送给页面的数据
			$this->ajaxReturn(array('status' => false , 'content' => '数据出错'));
			exit;//数据出错
        }
        //echo json_encode($data_arr);exit;
        $model1 = D('campustarget');
        $model2 = D('personaltarget');
        $model3 = M('user');
        $data = array();
        $data2 = array();
        $date = date('Y-m',time());
        $name_str = "";
        $error = "";
        foreach($data_arr as $vals){
            $data = array();
            $school_id = $vals['school_id'];
            $school_name = $vals['school_name'];
            $data['campus_id'] = $vals['school_id'];
            $data['new_target'] = $vals['new_target'];
            $data['old_target'] = $vals['old_target'];
            $data['count_target'] = $vals['count_target'];
            $data['date'] = $date;
            $datas = $model1->where(array('date'=>$date,'campus_id'=>$vals['school_id']))->find();
            if(is_array($datas)){
                $model1->where(array('id'=>$datas['id']))->save($data);
                $tatus = 1;
            }
            if(!$tatus){
            	// 发送给页面的数据
				$this->ajaxReturn(array('status' => false , 'content' => '校区数据存入出错'));
				exit;//数据出错
            }

            $name_str = "";
            $name = "";
            if(is_array($vals['personal_target'])){
                $target_arr = $vals['personal_target'];
                foreach ($target_arr as $key => &$value) {
                    $name = $value['name'];
                    $arr = $model3->where("name = '".$name."'")->find();//
                    if(is_array($arr)){
                        $data2 = array();
                        $data2['user_id'] = $arr['id'];
                        $data2['campus_id'] = $school_id;
                        $data2['post_id'] = $value['post'];
                        $data2['level'] = $value['level'];
                        $data2['personal_num'] = $value['personal_num'];
                        $data2['target_consume'] = $value['target_consume'];
                        $data2['target'] = $value['target'];
                        $data2['upgrade_consume'] = $value['upgrade_consume'];
                        $data2['upgrade'] = $value['upgrade'];
                        $data2['relegation_consume'] = $value['relegation_consume'];
                        $data2['relegation'] = $value['relegation'];
                        $data2['consume'] = $value['consume'];
                        $data2['date'] = $date;
                        $datas = $model2->where(array('date'=>$date,'user_id'=>$arr['id']))->find();
                        if(is_array($datas)){
                            $model2->where(array('id'=>$datas['id']))->save($data2);
                            $tatus = 1;
                        }else{
                            $tatus = $model2->add($data2);
                        }
                        if(!$tatus){
                        	// 发送给页面的数据
							$this->ajaxReturn([
					
								'state'=>'ok',//查询结果
								'data'=>json_encode(array('status' => false , 'content' => '人员数据存入出错'))
					
							]);
							exit;//数据出错
                        }
                        
                    }else{
                        $name_str .= $name.",";
                    }

                }
                if(!empty($name_str)){
                    $error .= $school_name."~".rtrim($name_str,',')." | ";
                }
            }
        }
        if(!empty($error)){
            $error_s = $error."查无此人，请确认名字是否正确";
			// 发送给页面的数据
			$this->ajaxReturn(array('status' => false , 'content' => $error_s));
			exit;//数据出错
        }else{
        	// 发送给页面的数据
			$this->ajaxReturn(array('status' => true , 'content' => "录入成功"));
			exit;
        }
    }


    //个人业绩查询详细
    function personal_select(){
        //echo $_GET['data'];die;
        if(empty($_GET['data'])){
            // 发送给页面的数据
            $this->ajaxReturn(array('status' => false , 'content' => '程序出错，请联系管理员'));
            exit;//数据出错
        }else{
            $array = json_decode($_GET['data'],true);
        }
        $where_arr = array();
        $where_arr['status'] = 2;
        if(empty($array['date'])){
            $date = date("Y-m",time());
            $where_arr['achievement_date'] = array('like' , '%'.$date.'%');
        }else{
            $date = $array['date'];
            $where_arr['achievement_date'] = array('like' , '%'.$date.'%');
        }
        
        $data_day = $this->getAllPersonReturnMoneyDetail('d',$array['date'],$array['name']);
        
        if($array['post_id'] == '11' || $array['post_id'] == '19'){
            $where_arr['teaching_userid'] = $array['user_id'];//教学主任或者咨询主管
            $merge_arr = array_merge($data_day[0],array_merge($data_day[1],array_merge($data_day[4],$data_day[5])));
        }else{
            $where_arr['study_userid'] = $array['user_id'];//学习管理师或者维护主管
            $merge_arr = array_merge($data_day[2],array_merge($data_day[3],array_merge($data_day[4],$data_day[5])));
        }

        $oa_achievement = D('achievement');
        $person_all = M('user');
        $oa_foo_info = M('foo_info');

        $data = array();
        $user_arrs = array();
        $data = $oa_achievement->where($where_arr)->order('id desc')->select();

        $user_arrs = $person_all->select();//
        $school_arr = $oa_foo_info->where(array('pid' => 15 , 'is_del' => 0))->select();
        $count_num = 0;
        foreach($data as &$value){
            $count_num = $count_num + $value['charge_money'];
            foreach($school_arr as $val){
                if($value['campus_id'] == $val['id']){
                    $value['school_name'] = $val['name'];
                }
            }
            $value['money_type'] = "收费";

            foreach($user_arrs as $val){
                if($value['checkout_userid'] == $val['id']){
                    $value['checkout_username'] = $val['name'];
                }
                if($value['teaching_userid'] == $val['id']){
                    $value['teaching_userid'] = $val['name'];
                }
                if($value['study_userid'] == $val['id']){
                    $value['study_userid'] = $val['name'];
                }
            }
            
        }
        foreach($merge_arr as &$value){
            $value['money_type'] = "退费";
        }
        $data_arr = array_merge($data,$merge_arr);
        
        if(!empty($data_arr)){
            $data_arr[0]['count_num'] = $count_num;
        }

        // 发送给页面的数据
        $this->ajaxReturn($data_arr);

    }


    //个人业绩统计表
    function personal_count(){
        // echo $_GET['data'];die;
        if(empty($_GET['data'])){
            // 发送给页面的数据
            $this->ajaxReturn(array('status' => false , 'content' => '程序出错，请联系管理员'));
            exit;//数据出错
        }else{
            $array = json_decode($_GET['data'],true);
        }
        $where = array();
        if(!empty($array['date'])){
            $date = $array['date'];
            $where['date'] = array('like' , "%".$date."%");
        }else{
            // 发送给页面的数据
            $this->ajaxReturn(array('status' => false , 'content' => '程序出错，请确认开始、结束时间正确'));
            exit;//数据出错
        }
        if(!empty($array['school_id'])){
            $school_id = $array['school_id'];
            $where['campus_id'] = $school_id;
        }
        $oa_achievement = D('achievement');
        $oa_personaltarget = D('personaltarget');
        $foo_info = M('foo_info');
        $user = M('user');
        $position = M('position');
        $target_arr = array();
        $target_arr = $oa_personaltarget->where($where)->select();
        // echo $oa_personaltarget->getLastsql();die;
        if(!$target_arr){
            // 发送给页面的数据
            $this->ajaxReturn($target_arr);
            exit;//数据为空
        }else{
            foreach($target_arr as $key => &$value){
                $value['num'] = $key+1;
                $person_arrs = $user->where(array('id' => $value['user_id']))->find();
                $value['name'] = $person_arrs['name'];
                $campus_arrs = $foo_info->where(array('id' => $value['campus_id']))->find();
                $value['school_name'] = $campus_arrs['name'];
                $position_arrs = $position->where(array('id' => $value['post_id']))->find();
                $value['post_name'] = $position_arrs['name'];
                if($value['post_id'] == '11' || $value['post_id'] == '19'){
                    $type = '新签';
                    //退费
                    $data = $this->getAllPersonReturnMoneyDetail('m',$date,$value['name']);
                    if($value['post_id'] == '19'){
                        if(empty($data[0][0]['count_num'])){
                            $value['routine_refund'] = 0;
                        }else{
                            $value['routine_refund'] = $data[0][0]['count_num']+$data[4][0]['count_num'];
                        }
                        if(empty($data[1][0]['count_num'])){
                            $value['not_routine_refund'] = 0;
                        }else{
                            $value['not_routine_refund'] = $data[1][0]['count_num']+$data[5][0]['count_num'];
                        }
                    }else{
                        if(empty($data[0][0]['count_num'])){
                            $value['routine_refund'] = 0;
                        }else{
                            $value['routine_refund'] = $data[0][0]['count_num'];
                        }
                        if(empty($data[1][0]['count_num'])){
                            $value['not_routine_refund'] = 0;
                        }else{
                            $value['not_routine_refund'] = $data[1][0]['count_num'];
                        }
                    }
                    

                    $oa_achievement_num = $oa_achievement->where(array('achievement_date' => array('like' , "%".$date."%") , 'achievement_type' => $type , 'status' => 2 , 'not_curriculum_type' => "" , 'teaching_userid' => $value['user_id']))->sum('charge_money');

                    $oa_achievement_num1 = $oa_achievement->where(array('achievement_date' => array('like' , "%".$date."%") , 'achievement_type' => '转介绍' , 'status' => 2 , 'not_curriculum_type' => "" , 'teaching_userid' => $value['user_id']))->sum('charge_money');

                    $money_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", $oa_achievement_num1));
                    $value['money_num'] = $money_num;
                    //特训营
                    $special_money = $oa_achievement->where(array('achievement_date' => array('like' , "%".$date."%") , 'achievement_type' => $type , 'status' => 2 , 'not_curriculum_type' => array('like' , "%特训营%") , 'teaching_userid' => $value['user_id']))->sum('charge_money');

                    $special_money1 = $oa_achievement->where(array('achievement_date' => array('like' , "%".$date."%") , 'achievement_type' => '转介绍' , 'status' => 2 , 'not_curriculum_type' => array('like' , "%特训营%") , 'teaching_userid' => $value['user_id']))->sum('charge_money');
                    if($special_money == '' || $special_money == 'NULL'){
                        $special_money = '0.00';
                    }
                    $value['special_money'] = sprintf("%.2f", sprintf("%.2f", $special_money)+sprintf("%.2f", $special_money1));

                    //合作项目
                    $cooperation_money = $oa_achievement->where(array('achievement_date' => array('like' , "%".$date."%") , 'achievement_type' => $type , 'status' => 2 , 'not_curriculum_type' => array('like' , "%合作项目%") , 'teaching_userid' => $value['user_id']))->sum('charge_money');

                    $cooperation_money1 = $oa_achievement->where(array('achievement_date' => array('like' , "%".$date."%") , 'achievement_type' => '转介绍' , 'status' => 2 , 'not_curriculum_type' => array('like' , "%合作项目%") , 'teaching_userid' => $value['user_id']))->sum('charge_money');
                    if($cooperation_money == '' || $cooperation_money == 'NULL'){
                        $cooperation_money = '0.00';
                    }
                    $value['cooperation_money'] = sprintf("%.2f", sprintf("%.2f", $cooperation_money)+sprintf("%.2f", $cooperation_money1));

                }else{
                    $type = '续签';
                    //退费
                    $data = $this->getAllPersonReturnMoneyDetail('m',$date,$value['name']);
                    if(empty($data[2][0]['count_num'])){
                        $value['routine_refund'] = 0;
                    }else{
                        $value['routine_refund'] = $data[2][0]['count_num'];
                    }
                    if(empty($data[3][0]['count_num'])){
                        $value['not_routine_refund'] = 0;
                    }else{
                        $value['not_routine_refund'] = $data[3][0]['count_num'];
                    }

                    $oa_achievement_num = $oa_achievement->where(array('achievement_date' => array('like' , "%".$date."%") , 'achievement_type' => $type , 'status' => 2 , 'not_curriculum_type' => "" , 'study_userid' => $value['user_id']))->sum('charge_money');

                    $money_num = sprintf("%.2f", $oa_achievement_num);
                    $value['money_num'] = $money_num;
                    //特训营
                    $special_money = $oa_achievement->where(array('achievement_date' => array('like' , "%".$date."%") , 'achievement_type' => $type , 'status' => 2 , 'not_curriculum_type' => array('like' , "%特训营%") , 'study_userid' => $value['user_id']))->sum('charge_money');
                    if($special_money == '' || $special_money == 'NULL'){
                        $special_money = '0.00';
                    }
                    $value['special_money'] = $special_money;

                    //合作项目
                    $cooperation_money = $oa_achievement->where(array('achievement_date' => array('like' , "%".$date."%") , 'achievement_type' => $type , 'status' => 2 , 'not_curriculum_type' => array('like' , "%合作项目%") , 'study_userid' => $value['user_id']))->sum('charge_money');
                    if($cooperation_money == '' || $cooperation_money == 'NULL'){
                        $cooperation_money = '0.00';
                    }
                    $value['cooperation_money'] = $cooperation_money;

                }
            }
        }


        session('target_arr',$target_arr);


        // 发送给页面的数据
        $this->ajaxReturn($target_arr);

    }


    //升降级业绩统计表
    function level_count(){
        if(empty($_GET['data'])){
        	// 发送给页面的数据
			$this->ajaxReturn(array('status' => false , 'content' => '程序出错，请联系管理员'));
			exit;//数据出错
        }else{
            $array = json_decode($_GET['data'],true);
        }
        // echo json_encode($array);exit;
        $where = array();
        // $array['begin_date'] = '2016-09';
        // $array['end_date'] = '2016-09';
        // $array['post_id'] = 19;
        // $array['school_id'] = 3;
        if(empty($array['post_id'])){
        	// 发送给页面的数据
			$this->ajaxReturn(array('status' => false , 'content' => '程序出错，请确认职务'));
			exit;//数据出错
        }else{
            $oa_position = M('position');
            $post_id = $array['post_id'];
            if($post_id == '18'){
                $where['_string'] = " oa_personaltarget.post_id='".$post_id."'  or  oa_personaltarget.post_id='12'";
                $where['_logic'] = 'and';
            }else if($post_id == '19'){
                $where['_string'] = " oa_personaltarget.post_id='".$post_id."'  or  oa_personaltarget.post_id='11'";
                $where['_logic'] = 'and';
            }else{
                $where['oa_personaltarget.post_id'] = $post_id;
            }
            
        }
        if(!empty($array['begin_date']) && !empty($array['end_date'])){
            $begin_date = $array['begin_date'];
            $end_date = $array['end_date'];
            $where['oa_personaltarget.date'] = array('between' , $begin_date.','.$end_date);
            $where['oa_campustarget.date'] = array('between' , $begin_date.','.$end_date);
        }else{
        	// 发送给页面的数据
			$this->ajaxReturn(array('status' => false , 'content' => '程序出错，请确认开始、结束时间正确'));
			exit;//数据出错
        }

        $model = new Model();
        $person_all = M('user');
        $oa_foo_info = M('foo_info');
        if(!empty($array['school_id'])){
            $school_id = $array['school_id'];
            $where['oa_personaltarget.campus_id'] = $school_id;
        }

        if(!empty($array['name'])){
            $name = $array['name'];
            $user_arr = array();
            $person_arr = $person_all->where(array('name' => $name))->find();//
            if(!$person_arr){
            	// 发送给页面的数据
				$this->ajaxReturn(array('status' => false , 'content' => '查无此人，请确认校区、职务、名称是否一致'));
				exit;//数据出错
            }
            $where['oa_personaltarget.user_id'] = $person_arr['id'];
        }
        $oa_personaltarget = D('personaltarget');
        $oa_achievement = D('achievement');
        $target_arr = array();
        $target_arr = $oa_personaltarget->join("oa_campustarget ON oa_personaltarget.campus_id = oa_campustarget.campus_id")->where($where)->order('level desc')->select();

        $date_time_arr = $this->year_month_day(strtotime($end_date));
        $month_count = $date_time_arr['month_day'];
        $day_count = $date_time_arr['day_count'];
        if(!$target_arr){
        	// 发送给页面的数据
			$this->ajaxReturn($target_arr);
			exit;//数据为空
        }else{
            if($post_id == "18" || $post_id == "19"){
                foreach($target_arr as &$value){
                    $person_arrs = $person_all->where(array('id' => $value['user_id']))->find();
                    $value['name'] = $person_arrs['name'];
                    $campus_arrs = $oa_foo_info->where(array('id' => $value['campus_id']))->find();
                    $value['school_name'] = $campus_arrs['name'];
                    $value['upgrade_num'] = $value['upgrade']*$value['target'];
                    $value['relegation_num'] = $value['relegation']*$value['target'];
                    if($value['post_id'] == "18" || $value['post_id'] == "12"){
                        $type = '续签';
                        $oa_achievement_num = $oa_achievement->where(array('achievement_date' => array('between' , $begin_date.'-01,'.$end_date."-$day_count") , 'achievement_type' => $type , 'status' => 2 , 'not_curriculum_type' => "" , 'study_userid' => $value['user_id']))->sum('charge_money');
                        $oa_achievement_num2 = $oa_achievement->where(array('achievement_date' => array('between' , $begin_date.'-01,'.$end_date."-$day_count") , 'achievement_type' => $type , 'status' => 2 , 'curriculum_type' => "" , 'not_curriculum_type' => array("like","%高考报考%") , 'study_userid' => $value['user_id']))->sum('charge_money');
                        $oa_achievement_num3 = $oa_achievement->where(array('achievement_date' => array('between' , $begin_date.'-01,'.$end_date."-$day_count") , 'achievement_type' => $type , 'status' => 2 , 'curriculum_type' => "" , 'not_curriculum_type' => array("like","%自主招生%") , 'study_userid' => $value['user_id']))->sum('charge_money');

                        $oa_achievement_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", $oa_achievement_num2)+sprintf("%.2f", $oa_achievement_num3));
                        $oa_achievement_num1 = 0;
                    }else if($value['post_id'] == "19" || $value['post_id'] == "11"){
                        $type = '新签';
                        $oa_achievement_num = $oa_achievement->where(array('achievement_date' => array('between' , $begin_date.'-01,'.$end_date."-$day_count") , 'achievement_type' => $type , 'status' => 2 , 'not_curriculum_type' => "" , 'teaching_userid' => $value['user_id']))->sum('charge_money');

                        $oa_achievement_num2 = $oa_achievement->where(array('achievement_date' => array('between' , $begin_date.'-01,'.$end_date."-$day_count") , 'achievement_type' => $type , 'status' => 2 , 'curriculum_type' => "" , 'not_curriculum_type' => array("like","%高考报考%") , 'teaching_userid' => $value['user_id']))->sum('charge_money');
                        $oa_achievement_num3 = $oa_achievement->where(array('achievement_date' => array('between' , $begin_date.'-01,'.$end_date."-$day_count") , 'achievement_type' => $type , 'status' => 2 , 'curriculum_type' => "" , 'not_curriculum_type' => array("like","%自主招生%") , 'teaching_userid' => $value['user_id']))->sum('charge_money');

                        $oa_achievement_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", $oa_achievement_num2)+sprintf("%.2f", $oa_achievement_num3));
                        
                        $oa_achievement_num1 = $oa_achievement->where(array('achievement_date' => array('between' , $begin_date.'-01,'.$end_date."-$day_count") , 'achievement_type' => '转介绍' , 'status' => 2 , 'not_curriculum_type' => "" , 'teaching_userid' => $value['user_id']))->sum('charge_money');
                    }
                    $money_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", $oa_achievement_num1));

                    $value['money_num'] = $money_num;
                    if($value['money_num'] >= $value['upgrade_num']){
                        $value['upgrade_difference'] = 0;
                    }else{
                        $value['upgrade_difference'] = sprintf("%.2f", sprintf("%.2f", $value['upgrade_num'])-sprintf("%.2f", $value['money_num']));
                    }
                    if($value['money_num'] >= $value['relegation_num']){
                        $value['relegation_difference'] = 0;
                    }else{
                        $value['relegation_difference'] = $value['relegation_num']-$value['money_num'];
                    }
                    $value['upgrade_num_num'] = sprintf("%01.2f", $value['money_num']/$value['upgrade_num']*100).'%';
                    $value['relegation_num_num'] = sprintf("%01.2f", $value['money_num']/$value['relegation_num']*100).'%';

                    $value['upgrade'] = sprintf("%01.2f", $value['upgrade']*100).'%';
                    $value['relegation'] = sprintf("%01.2f", $value['relegation']*100).'%';

                }
            }else if($post_id == '11'){
                foreach($target_arr as &$value){
                    $person_arrs = $person_all->where(array('id' => $value['user_id']))->find();
                    $value['name'] = $person_arrs['name'];
                    $campus_arrs = $oa_foo_info->where(array('id' => $value['campus_id']))->find();
                    $value['school_name'] = $campus_arrs['name'];


                    $type = '新签';
                    $oa_achievement_num = $oa_achievement->where(array('achievement_date' => array('between' , $begin_date.'-01,'.$end_date."-$day_count") , 'achievement_type' => $type , 'status' => 2 , 'not_curriculum_type' => "" , 'teaching_userid' => $value['user_id']))->sum('charge_money');

                    $oa_achievement_num2 = $oa_achievement->where(array('achievement_date' => array('between' , $begin_date.'-01,'.$end_date."-$day_count") , 'achievement_type' => $type , 'status' => 2 , 'curriculum_type' => "" , 'not_curriculum_type' => array("like","%高考报考%") , 'teaching_userid' => $value['user_id']))->sum('charge_money');
                    $oa_achievement_num3 = $oa_achievement->where(array('achievement_date' => array('between' , $begin_date.'-01,'.$end_date."-$day_count") , 'achievement_type' => $type , 'status' => 2 , 'curriculum_type' => "" , 'not_curriculum_type' => array("like","%自主招生%") , 'teaching_userid' => $value['user_id']))->sum('charge_money');

                    $oa_achievement_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", $oa_achievement_num2)+sprintf("%.2f", $oa_achievement_num3));

                    $oa_achievement_num1 = $oa_achievement->where(array('achievement_date' => array('between' , $begin_date.'-01,'.$end_date."-$day_count") , 'achievement_type' => '转介绍' , 'status' => 2 , 'not_curriculum_type' => "" , 'teaching_userid' => $value['user_id']))->sum('charge_money');

                    $money_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", $oa_achievement_num1));
                    $value['money_num'] = $money_num;//个人业绩

                    $personal = sprintf("%.4f", $money_num/sprintf("%.4f", $value['new_target']));
                    $value['personal'] = sprintf("%01.2f", $personal*100).'%';//个人完成率
                    
                    //退费
                    $count_num_new = $model->query("SELECT tab1. NAME AS school_name, CASE WHEN round(sum(tab2.je), 2) IS NULL THEN '0.00' ELSE round(sum(tab2.je), 2) END AS count_num FROM ( SELECT * FROM hongwen_oa.oa_foo_info AS info WHERE info.pid = 15 AND info.id != 174 AND info.is_del = 0 ) AS tab1 LEFT JOIN ( SELECT * FROM hw003.money_return AS moneyRe WHERE 1 = 1 AND moneyRe.time3 between '".$begin_date."-01' and '".$end_date."-".$day_count."' AND moneyRe.kf_type = '新签' AND moneyRe.state = 6 AND moneyRe.class1 NOT IN (5, 7, 8, 9, 10)) AS tab2 ON tab1. NAME = tab2.school WHERE tab1.id = ".$value['campus_id']." group by school_name");

                    //转介绍退费
                    $count_num_con = $model->query("SELECT tab1. NAME AS school_name, CASE WHEN round(sum(tab2.je), 2) IS NULL THEN '0.00' ELSE round(sum(tab2.je), 2) END AS count_num FROM ( SELECT * FROM hongwen_oa.oa_foo_info AS info WHERE info.pid = 15 AND info.id != 174 AND info.is_del = 0 ) AS tab1 LEFT JOIN ( SELECT * FROM hw003.money_return AS moneyRe WHERE 1 = 1 AND moneyRe.time3 between '".$begin_date."-01' and '".$end_date."-".$day_count."' AND moneyRe.kf_type = '转介绍' AND moneyRe.state = 6 AND moneyRe.class1 NOT IN (5, 7, 8, 9, 10)) AS tab2 ON tab1. NAME = tab2.school WHERE tab1.id = ".$value['campus_id']." group by school_name");


                    $oa_achievement_num = $oa_achievement->where(array('achievement_date' => array('between' , $begin_date.'-01,'.$end_date."-$day_count") , 'achievement_type' => $type , 'campus_id' => $value['campus_id'] , 'status' => 2 , 'not_curriculum_type' => ""))->sum('charge_money');

                    $oa_achievement_num2 = $oa_achievement->where(array('achievement_date' => array('between' , $begin_date.'-01,'.$end_date."-$day_count") , 'achievement_type' => $type , 'campus_id' => $value['campus_id'] , 'status' => 2 , 'curriculum_type' => "" , 'not_curriculum_type' => array("like","%高考报考%")))->sum('charge_money');
                    $oa_achievement_num3 = $oa_achievement->where(array('achievement_date' => array('between' , $begin_date.'-01,'.$end_date."-$day_count") , 'achievement_type' => $type , 'campus_id' => $value['campus_id'] , 'status' => 2 , 'curriculum_type' => "" , 'not_curriculum_type' => array("like","%自主招生%")))->sum('charge_money');

                    $oa_achievement_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", $oa_achievement_num2)+sprintf("%.2f", $oa_achievement_num3));

                    $oa_achievement_num1 = $oa_achievement->where(array('achievement_date' => array('between' , $begin_date.'-01,'.$end_date."-$day_count") , 'achievement_type' => '转介绍' , 'campus_id' => $value['campus_id'] , 'status' => 2 ,'not_curriculum_type' => ""))->sum('charge_money');
                    $personal_rate = sprintf("%.4f", sprintf("%.4f", $oa_achievement_num)+sprintf("%.4f", $oa_achievement_num1/2));


                    $count_num = sprintf("%.2f", $personal_rate - sprintf("%.2f", $count_num_new[0]['count_num']) - sprintf("%.2f", ($count_num_con[0]['count_num']/2)));
                    $value['count_num'] = $count_num;//团队所有人业绩

                    $count_rate = sprintf("%.4f", $count_num/sprintf("%.4f", $value['new_target']));
                    $value['count_rate'] = sprintf("%01.2f", $count_rate*100).'%';//团队所有人完成率



                    $relegation_complete = 0;//保级所需完成率
                    $upgrade_complete = 0;//升级所需完成率
                    $other_complete = 0;//团队其他所需完成率
                    $all_complete = 0;//团队所需完成率
                    $other_num = 0;//团队其他人业绩
                    $other_rate = 0;//团队其他人完成率


                    if(!empty($value['personal_num']) && $value['personal_num']){
                        $other_num = sprintf("%.2f", $count_num - $money_num);

                        $other_rate = sprintf("%.4f", $other_num/sprintf("%.4f", $value['new_target']));
                        if($value['level'] == 1){
                            $relegation_complete = "";
                            $all_complete = 1.00;
                            if($value['personal_num'] == 1){
                                $upgrade_complete = 1.0;

                                if($money_num >= $value['new_target']){
                                    $content = "升级";
                                }else{
                                    $content = "保级";
                                }
                            }else if($value['personal_num'] == 2){
                                $upgrade_complete = 0.30;
                                $other_complete = 0.40;
                                if($personal >=$upgrade_complete && $other_num >= $other_complete && $count_rate >= $all_complete){
                                    $content = "升级";
                                }else{
                                    $content = "保级";
                                }
                            }else if($value['personal_num'] == 3){
                                $upgrade_complete = 0.25;
                                $other_complete = 0.60;
                                if($personal >=$upgrade_complete && $other_num >= $other_complete && $count_rate >= $all_complete){
                                    $content = "升级";
                                }else{
                                    $content = "保级";
                                }
                            }else if($value['personal_num'] == 4){
                                $upgrade_complete = 0.20;
                                $other_complete = 0.70;
                                if($personal >=$upgrade_complete && $other_num >= $other_complete && $count_rate >= $all_complete){
                                    $content = "升级";
                                }else{
                                    $content = "保级";
                                }
                            }
                        }else if($value['level'] == 2){
                            $relegation_complete = 1.00;
                            $all_complete = 1.10;
                            if($value['personal_num'] == 1){
                                $upgrade_complete = "";
                                $other_complete = "";
                                if($count_rate >= $upgrade_complete){
                                    $content = "升级";
                                }else if($count_rate >= $relegation_complete){
                                    $content = "保级";
                                }else{
                                    $content = "降级";
                                }
                            }else if($value['personal_num'] == 2){
                                $upgrade_complete = 0.30;
                                $other_complete = 0.40;
                                if($personal >=$upgrade_complete && $other_num >= $other_complete && $count_rate >= $all_complete){
                                    $content = "升级";
                                }else if($count_rate >= $relegation_complete){
                                    $content = "保级";
                                }else{
                                    $content = "降级";
                                }
                            }else if($value['personal_num'] == 3){
                                $upgrade_complete = 0.25;
                                $other_complete = 0.60;
                                if($personal >=$upgrade_complete && $other_num >= $other_complete && $count_rate >= $all_complete){
                                    $content = "升级";
                                }else if($count_rate >= $relegation_complete){
                                    $content = "保级";
                                }else{
                                    $content = "降级";
                                }
                            }else if($value['personal_num'] == 4){
                                $upgrade_complete = 0.20;
                                $other_complete = 0.70;
                                if($personal >=$upgrade_complete && $other_num >= $other_complete && $count_rate >= $all_complete){
                                    $content = "升级";
                                }else if($count_rate >= $relegation_complete){
                                    $content = "保级";
                                }else{
                                    $content = "降级";
                                }
                            }
                        }else if($value['level'] == 3){
                            $relegation_complete = 1.10;
                            $all_complete = 1.20;
                            if($value['personal_num'] == 1){
                                if($count_rate >= 1.20){
                                    $content = "升级";
                                }else if($count_rate >= $relegation_complete){
                                    $content = "保级";
                                }else{
                                    $content = "降级";
                                }
                            }else if($value['personal_num'] == 2){
                                $upgrade_complete = 0.30;
                                $other_complete = 0.40;
                                if($personal >=$upgrade_complete && $other_num >= $other_complete && $count_rate >= $all_complete){
                                    $content = "升级";
                                }else if($count_rate >= $relegation_complete){
                                    $content = "保级";
                                }else{
                                    $content = "降级";
                                }
                            }else if($value['personal_num'] == 3){
                                $upgrade_complete = 0.25;
                                $other_complete = 0.60;
                                if($personal >=$upgrade_complete && $other_num >= $other_complete && $count_rate >= $all_complete){
                                    $content = "升级";
                                }else if($count_rate >= $relegation_complete){
                                    $content = "保级";
                                }else{
                                    $content = "降级";
                                }
                            }else if($value['personal_num'] == 4){
                                $upgrade_complete = 0.20;
                                $other_complete = 0.70;
                                if($personal >=$upgrade_complete && $other_num >= $other_complete && $count_rate >= $all_complete){
                                    $content = "升级";
                                }else if($count_rate >= $relegation_complete){
                                    $content = "保级";
                                }else{
                                    $content = "降级";
                                }
                            }
                        }else if($value['level'] == 4){
                            $relegation_complete = 1.20;
                            if($value['personal_num'] == 1){
                                if($count_rate >= $relegation_complete){
                                    $content = "保级";
                                }else{
                                    $content = "降级";
                                }
                            }else if($value['personal_num'] == 2){
                                if($count_rate >= $relegation_complete){
                                    $content = "保级";
                                }else{
                                    $content = "降级";
                                }
                            }else if($value['personal_num'] == 3){
                                if($count_rate >= $relegation_complete){
                                    $content = "保级";
                                }else{
                                    $content = "降级";
                                }
                            }else if($value['personal_num'] == 4){
                                if($count_rate >= $relegation_complete){
                                    $content = "保级";
                                }else{
                                    $content = "降级";
                                }
                            }
                        }
                    }else{
                        $content = "暂无";
                    }
                    $value['other_num'] = $other_num;//团队其他人业绩
                    $value['other_rate'] = sprintf("%01.2f", $other_rate*100).'%'; //团队其他人完成率
                    $value['relegation_complete'] = sprintf("%01.2f", $relegation_complete*100).'%';
                    $value['upgrade_complete'] = sprintf("%01.2f", $upgrade_complete*100).'%';
                    $value['other_complete'] = sprintf("%01.2f", $other_complete*100).'%';
                    $value['all_complete'] = sprintf("%01.2f", $all_complete*100).'%';


                    /*if(!empty($value['upgrade']) || !empty($value['relegation'])){
                        if($value['personal_rate'] < $value['relegation']){
                            $content = "降级";
                        }else if($value['count_rate'] < $value['upgrade']){
                            $content = "保级";
                        }else if($value['count_rate'] >= $value['upgrade']){
                            $content = "升级";
                        }
                    }else{
                        $content = "暂无";
                    }*/
                    $value['content_str'] = $content;
                    $value['level_last'] = "";
                    $value['content'] = "";
                }
            }else{
                foreach($target_arr as &$value){
                    $person_arrs = $person_all->where(array('id' => $value['user_id']))->find();
                    $value['name'] = $person_arrs['name'];
                    $campus_arrs = $oa_foo_info->where(array('id' => $value['campus_id']))->find();
                    $value['school_name'] = $campus_arrs['name'];
                    $type = '续签';

                    $oa_achievement_num = $oa_achievement->where(array('achievement_date' => array('between' , $begin_date.'-01,'.$end_date."-$day_count") , 'achievement_type' => $type , 'campus_id' => $value['campus_id'] , 'status' => 2 , 'not_curriculum_type' => ""))->sum('charge_money');

                    $oa_achievement_num2 = $oa_achievement->where(array('achievement_date' => array('between' , $begin_date.'-01,'.$end_date."-$day_count") , 'achievement_type' => $type , 'campus_id' => $value['campus_id'] , 'status' => 2 , 'curriculum_type' => "" , 'not_curriculum_type' => array("like","%高考报考%")))->sum('charge_money');
                    $oa_achievement_num3 = $oa_achievement->where(array('achievement_date' => array('between' , $begin_date.'-01,'.$end_date."-$day_count") , 'achievement_type' => $type , 'campus_id' => $value['campus_id'] , 'status' => 2 , 'curriculum_type' => "" , 'not_curriculum_type' => array("like","%自主招生%")))->sum('charge_money');

                    $oa_achievement_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", $oa_achievement_num2)+sprintf("%.2f", $oa_achievement_num3));

                    $oa_achievement_num1 = $oa_achievement->where(array('achievement_date' => array('between' , $begin_date.'-01,'.$end_date."-$day_count") , 'achievement_type' => '转介绍' , 'campus_id' => $value['campus_id'] , 'status' => 2 , 'not_curriculum_type' => ""))->sum('charge_money');

                    $count_num_new = $model->query("SELECT tab1. NAME AS school_name, CASE WHEN round(sum(tab2.je), 2) IS NULL THEN '0.00' ELSE round(sum(tab2.je), 2) END AS count_num FROM ( SELECT * FROM hongwen_oa.oa_foo_info AS info WHERE info.pid = 15 AND info.id != 174 AND info.is_del = 0 ) AS tab1 LEFT JOIN ( SELECT * FROM hw003.money_return AS moneyRe WHERE 1 = 1 AND moneyRe.time3 between '".$begin_date."-01' and '".$end_date."-".$day_count."' AND moneyRe.kf_type = '续签' AND moneyRe.state = 6 AND moneyRe.class1 NOT IN (5, 7, 8, 9, 10)) AS tab2 ON tab1. NAME = tab2.school WHERE tab1.id = ".$value['campus_id']." group by school_name");




                    $count_num_con = $model->query("SELECT tab1. NAME AS school_name, CASE WHEN round(sum(tab2.je), 2) IS NULL THEN '0.00' ELSE round(sum(tab2.je), 2) END AS count_num FROM ( SELECT * FROM hongwen_oa.oa_foo_info AS info WHERE info.pid = 15 AND info.id != 174 AND info.is_del = 0 ) AS tab1 LEFT JOIN ( SELECT * FROM hw003.money_return AS moneyRe WHERE 1 = 1 AND moneyRe.time3 between '".$begin_date."-01' and '".$end_date."-".$day_count."' AND moneyRe.kf_type = '转介绍' AND moneyRe.state = 6 AND moneyRe.class1 NOT IN (5, 7, 8, 9, 10)) AS tab2 ON tab1. NAME = tab2.school WHERE tab1.id = ".$value['campus_id']." group by school_name");



                    $count_rate = sprintf("%.4f", ($oa_achievement_num + ($oa_achievement_num1 / 2) - $count_num_new[0]['count_num'] - ($count_num_con[0]['count_num']/2))/$value['old_target']);
                    $value['count_rate'] = sprintf("%01.2f", $count_rate*100).'%';//业绩完成率

                    if(!empty($value['upgrade']) || !empty($value['relegation']) || !empty($value['upgrade_consume']) || !empty($value['relegation_consume']) || !empty($value['consume'])){
                        if($value['consume'] >= $value['upgrade_consume'] || $count_rate >= $value['upgrade']){
                            $content = "升级";
                        }else if($value['consume'] < $value['relegation_consume'] || $count_rate < $value['relegation']){
                            $content = "降级";
                        }else if($value['consume'] >= $value['relegation_consume'] || $count_rate >= $value['relegation']){
                            $content = "保级";
                        }
                    }else{
                        $content = "暂无";
                    }

                    $value['relegation_consume'] = sprintf("%01.2f", $relegation_consume*100).'%';
                    $value['relegation'] = sprintf("%01.2f", $relegation*100).'%';
                    $value['upgrade_consume'] = sprintf("%01.2f", $upgrade_consume*100).'%';
                    $value['upgrade'] = sprintf("%01.2f", $upgrade*100).'%';
                    $value['consume'] = sprintf("%01.2f", $consume*100).'%';
                    $value['content_str'] = $content;
                    $value['level_last'] = "";
                    $value['content'] = "";
                    
                }
            }
            if($post_id == '12'){
                $data_data = $target_arr;
            }else{
                $data_data = $this->my_sort($target_arr,'money_num',SORT_DESC);
            }

            $i = 1;
            foreach($data_data as &$value){
                $value['num'] = $i;
                $i++;
            }
            
            session('data_data',$data_data);
            session('level_post_id',$post_id);
            // echo $oa_personaltarget->getLastsql();
            // echo "<pre>";
            // print_r($target_arr);die;
            // 发送给页面的数据
			$this->ajaxReturn($data_data);
			exit;//数据
        }
    }




    /**
     * 二维数组排序函数
     */
    function my_sort($arrays,$sort_key,$sort_order=SORT_ASC,$sort_type=SORT_NUMERIC ){   
        if(is_array($arrays)){   
            foreach ($arrays as $array){   
                if(is_array($array)){   
                    $key_arrays[] = $array[$sort_key];   
                }else{   
                    return false;   
                }   
            }   
        }else{   
            return false;   
        }  
        array_multisort($key_arrays,$sort_order,$sort_type,$arrays);   
        return $arrays;   
    }


	/**
	 * 根据月份、课程类型统计出各个校区的退费金额
	 * $month= '2016-04'
	 * $classtype =1 代表常规退费，$classtype =2 非常规退费
	 * $yjtype 新签、续签、转介绍、激活
	 * 0=>一对一教学,1=>个性化小组,2=>高考全日制,3=>中考全日制,4=>特色课堂,5=>高考特训营,6=>精品小班课,7=>百年育才合作项目,8=>高考志愿填报指导,9=>港澳台院校申请,10=>自主招生申请,11=>贵宾卡
	 */
//  public function getReturnMonthRecords($month,$classtype,$yjtype){
    public function getReturnMonthRecords($month,$yjtype){
			
    	$return = M('hw003.return','money_');

		//设置业绩类型
		if($yjtype != '招生'){
//			$w['yj_type'] = $yjtype;
 			$yj = " AND moneyRe.kf_type = '" . $yjtype . "' ";
		}else{
			$yj = " AND (moneyRe.kf_type = '新签' OR moneyRe.kf_type = '续签') ";
		}
		
		
		//常规与非常规项目退费筛选；
		/*if($classtype == 1){
			$w['re.class1'] = array('not in', '5,7,8,9,10');
		}else{
			$w['re.class1'] = array('in', '5,7,8,9,10');
		}*/
		
		$w['class1'] = array('not in', '5,7,8,9,10'); //常规
		
		
		$model = new Model();
		$result1 = $model->query("select  tab1.id,tab1.name AS school_name, case  when round(sum(tab2.je), 2) is null then '0.00' else round(sum(tab2.je), 2) end AS count_num from (select * from hongwen_oa.oa_foo_info as info where info.pid=15 and info.id != 174 and info.is_del = 0) as tab1 left join (select * from hw003.money_return  as moneyRe where moneyRe.time3 like '" . $month . "%' " . $yj . " AND moneyRe.state = 6 AND moneyRe.class1 NOT IN (5,7,8,9,10)) as tab2 on tab1.name = tab2.school GROUP BY tab1.name ORDER BY tab1.id");
		
		$w['class1'] = array('in', '5,7,8,9,10'); //非常规
    	
		$result2 = $model->query("select  tab1.id,tab1.name AS school_name, case  when round(sum(tab2.je), 2) is null then '0.00' else round(sum(tab2.je), 2) end AS count_num from (select * from hongwen_oa.oa_foo_info as info where info.pid=15 and info.id != 174 and info.is_del = 0) as tab1 left join (select * from hw003.money_return  as moneyRe where moneyRe.time3 like '" . $month . "%' " . $yj . " AND moneyRe.state = 6 AND moneyRe.class1 IN (5,7,8,9,10)) as tab2 on tab1.name = tab2.school GROUP BY tab1.name ORDER BY tab1.id");
		
		$result[0] = $result1;
		$result[1] = $result2;
		
		return $result;
		
    }
    
    /**
	 * 统计出每个人每天的退费金额，按照常规、非常规的新签、续签进行统计
	 * $yj_type 新签、续签
	 * 0=>一对一教学,1=>个性化小组,2=>高考全日制,3=>中考全日制,4=>特色课堂,5=>高考特训营,6=>精品小班课,7=>百年育才合作项目,8=>高考志愿填报指导,9=>港澳台院校申请,10=>自主招生申请,11=>贵宾卡
	 * $md_type 月数据、日数据类型，m代表月数据,d代表日数据
	 */
    public function getAllPersonReturnMoney($md_type,$month){
    	$return = M('hw003.return','money_');
		
		$model = new Model();
//		$result1 = $model->query("SELECT student,DATE_FORMAT(timestamp,'%Y-%m-%d') as `day`,CASE  WHEN round(sum(je), 2) IS NULL THEN '0.00' ELSE round(sum(je), 2) END AS money FROM  hw003.money_return WHERE 1=1 " . $yj . " AND state = 6 AND class1 NOT IN (5,7,8,9,10) group by student,`day` order by school,`date`,`day` ");
//		
//		$w['class1'] = array('in', '5,7,8,9,10'); //非常规
//  	
//		$result2 = $model->query("SELECT student,DATE_FORMAT(timestamp,'%Y-%m-%d') as `day`,CASE  WHEN round(sum(je), 2) IS NULL THEN '0.00' ELSE round(sum(je), 2) END AS money FROM  hw003.money_return WHERE 1=1 " . $yj . " AND state = 6 AND class1 IN (5,7,8,9,10) group by student,`day` order by school,`date`,`day` ");

		if($md_type == 'd'){
			
			//每天
			//教学主任
			//常规
			$result1 = $model->query("select  tab1.name AS school_name,tab2.aa,substr(tab2.time3,1,10) as `day`, case  when round(sum(tab2.je), 2) is null then '0.00' else round(sum(tab2.je), 2) end AS count_num from (select * from hongwen_oa.oa_foo_info as info where info.pid=15 and info.id != 174 and info.is_del = 0) as tab1 left join (select * from hw003.money_return  as moneyRe where 1=1 AND moneyRe.date = '" . $month . "' AND moneyRe.yj_type = '新签' AND moneyRe.state = 6 AND moneyRe.class1 NOT IN (5,7,8,9,10)) as tab2 on tab1.name = tab2.school where tab2.school is not null group by tab2.aa,`day` order by school_name,tab2.date,`day` ");
			
			$result[] = $result1;
	    	//非常规
			$result2 = $model->query("select  tab1.name AS school_name, tab2.aa,substr(tab2.time3,1,10) as `day`, case  when round(sum(tab2.je), 2) is null then '0.00' else round(sum(tab2.je), 2) end AS count_num from (select * from hongwen_oa.oa_foo_info as info where info.pid=15 and info.id != 174 and info.is_del = 0) as tab1 left join (select * from hw003.money_return  as moneyRe where 1=1 AND moneyRe.date = '" . $month . "' AND moneyRe.yj_type = '新签' AND moneyRe.state = 6 AND moneyRe.class1 IN (5,7,8,9,10)) as tab2 on tab1.name = tab2.school where tab2.school is not null group by tab2.aa,`day` order by school_name,tab2.date,`day` ");
			
			$result[] = $result2;
			
	//====================================================================================================================================================================================================================================================================
			//学管
			//常规
			$result3 = $model->query("select  tab1.name AS school_name,tab2.bb,substr(tab2.time3,1,10) as `day`, case  when round(sum(tab2.je), 2) is null then '0.00' else round(sum(tab2.je), 2) end AS count_num from (select * from hongwen_oa.oa_foo_info as info where info.pid=15 and info.id != 174 and info.is_del = 0) as tab1 left join (select * from hw003.money_return  as moneyRe where 1=1 AND moneyRe.date = '" . $month . "' AND moneyRe.yj_type = '续签' AND moneyRe.state = 6 AND moneyRe.class1 NOT IN (5,7,8,9,10)) as tab2 on tab1.name = tab2.school where tab2.school is not null group by tab2.bb,`day` order by school_name,tab2.date,`day` ");
			
			$result[] = $result3;
	    	
	    	//非常规
			$result4 = $model->query("select  tab1.name AS school_name, tab2.bb,substr(tab2.time3,1,10) as `day`, case  when round(sum(tab2.je), 2) is null then '0.00' else round(sum(tab2.je), 2) end AS count_num from (select * from hongwen_oa.oa_foo_info as info where info.pid=15 and info.id != 174 and info.is_del = 0) as tab1 left join (select * from hw003.money_return  as moneyRe where 1=1 AND moneyRe.date = '" . $month . "' AND moneyRe.yj_type = '续签' AND moneyRe.state = 6 AND moneyRe.class1 IN (5,7,8,9,10)) as tab2 on tab1.name = tab2.school where tab2.school is not null group by tab2.bb,`day` order by school_name,tab2.date,`day` ");
			
			$result[] = $result4;
			
	//====================================================================================================================================================================================================================================================================
			//学管,教学主任，这项统计不分组，所有的都查询出来，之后进行逐个对比查找
			//常规
			$result5 = $model->query("select  tab1.name AS school_name,tab2.aa,tab2.bb,substr(tab2.time3,1,10) as `day`, case  when round(tab2.je, 2) is null then '0.00' else round(tab2.je, 2) end AS count_num from (select * from hongwen_oa.oa_foo_info as info where info.pid=15 and info.id != 174 and info.is_del = 0) as tab1 left join (select * from hw003.money_return  as moneyRe where 1=1 AND moneyRe.date = '" . $month . "' AND moneyRe.yj_type = '转介绍' AND moneyRe.state = 6 AND moneyRe.class1 NOT IN (5,7,8,9,10)) as tab2 on tab1.name = tab2.school where tab2.school is not null order by school_name,tab2.date,`day` ");
			
			$result[] = $result5;
	    	
	    	//非常规
			$result6 = $model->query("select  tab1.name AS school_name,tab2.aa,tab2.bb,substr(tab2.time3,1,10) as `day`, case  when round(tab2.je, 2) is null then '0.00' else round(tab2.je, 2) end AS count_num from (select * from hongwen_oa.oa_foo_info as info where info.pid=15 and info.id != 174 and info.is_del = 0) as tab1 left join (select * from hw003.money_return  as moneyRe where 1=1 AND moneyRe.date = '" . $month . "' AND moneyRe.yj_type = '转介绍' AND moneyRe.state = 6 AND moneyRe.class1 IN (5,7,8,9,10)) as tab2 on tab1.name = tab2.school where tab2.school is not null order by school_name,tab2.date,`day` ");
			
			$result[] = $result6;
			
			
		}else if($md_type == 'm'){
						
			//每月
			//教学主任
			$result1 = $model->query("select  tab1.name AS school_name,tab2.aa,tab2.date, case  when round(sum(tab2.je), 2) is null then '0.00' else round(sum(tab2.je), 2) end AS count_num from (select * from hongwen_oa.oa_foo_info as info where info.pid=15 and info.id != 174 and info.is_del = 0) as tab1 left join (select * from hw003.money_return  as moneyRe where 1=1 AND moneyRe.date = '" . $month . "' AND moneyRe.yj_type = '新签' AND moneyRe.state = 6 AND moneyRe.class1 NOT IN (5,7,8,9,10)) as tab2 on tab1.name = tab2.school where tab2.school is not null group by tab2.date,tab2.aa order by school_name,tab2.date ");
			
			$result[] = $result1;
	    	
			$result2 = $model->query("select  tab1.name AS school_name, tab2.aa,tab2.date, case  when round(sum(tab2.je), 2) is null then '0.00' else round(sum(tab2.je), 2) end AS count_num from (select * from hongwen_oa.oa_foo_info as info where info.pid=15 and info.id != 174 and info.is_del = 0) as tab1 left join (select * from hw003.money_return  as moneyRe where 1=1 AND moneyRe.date = '" . $month . "' AND moneyRe.yj_type = '新签' AND moneyRe.state = 6 AND moneyRe.class1 IN (5,7,8,9,10)) as tab2 on tab1.name = tab2.school where tab2.school is not null group by tab2.date,tab2.aa order by school_name,tab2.date ");
			
			$result[] = $result2;
			
			//====================================================================================================================================================================================================================================================================
			//学管
			$result3 = $model->query("select  tab1.name AS school_name,tab2.bb,tab2.date, case  when round(sum(tab2.je), 2) is null then '0.00' else round(sum(tab2.je), 2) end AS count_num from (select * from hongwen_oa.oa_foo_info as info where info.pid=15 and info.id != 174 and info.is_del = 0) as tab1 left join (select * from hw003.money_return  as moneyRe where 1=1 AND moneyRe.date = '" . $month . "' AND moneyRe.yj_type = '续签' AND moneyRe.state = 6 AND moneyRe.class1 NOT IN (5,7,8,9,10)) as tab2 on tab1.name = tab2.school where tab2.school is not null group by tab2.date,tab2.bb order by school_name,tab2.date ");
			
			$result[] = $result3;
	    	
			$result4 = $model->query("select  tab1.name AS school_name, tab2.bb,tab2.date, case  when round(sum(tab2.je), 2) is null then '0.00' else round(sum(tab2.je), 2) end AS count_num from (select * from hongwen_oa.oa_foo_info as info where info.pid=15 and info.id != 174 and info.is_del = 0) as tab1 left join (select * from hw003.money_return  as moneyRe where 1=1 AND moneyRe.date = '" . $month . "' AND moneyRe.yj_type = '续签' AND moneyRe.state = 6 AND moneyRe.class1 IN (5,7,8,9,10)) as tab2 on tab1.name = tab2.school where tab2.school is not null group by tab2.date,tab2.bb order by school_name,tab2.date ");
			
			$result[] = $result4;		
			
			//====================================================================================================================================================================================================================================================================
			//学管,教学主任
			//常规
			$result5 = $model->query("select  tab1.name AS school_name,tab2.aa,tab2.bb,tab2.date, case  when round(tab2.je, 2) is null then '0.00' else round(tab2.je, 2) end AS count_num from (select * from hongwen_oa.oa_foo_info as info where info.pid=15 and info.id != 174 and info.is_del = 0) as tab1 left join (select * from hw003.money_return  as moneyRe where 1=1 AND moneyRe.date = '" . $month . "' AND moneyRe.yj_type = '转介绍' AND moneyRe.state = 6 AND moneyRe.class1 NOT IN (5,7,8,9,10)) as tab2 on tab1.name = tab2.school where tab2.school is not null order by school_name,tab2.date ");
			
			$result[] = $result5;
	    	
	    	//非常规
			$result6 = $model->query("select  tab1.name AS school_name,tab2.aa,tab2.bb,tab2.date, case  when round(tab2.je, 2) is null then '0.00' else round(tab2.je, 2) end AS count_num from (select * from hongwen_oa.oa_foo_info as info where info.pid=15 and info.id != 174 and info.is_del = 0) as tab1 left join (select * from hw003.money_return  as moneyRe where 1=1 AND moneyRe.date = '" . $month . "' AND moneyRe.yj_type = '转介绍' AND moneyRe.state = 6 AND moneyRe.class1 IN (5,7,8,9,10)) as tab2 on tab1.name = tab2.school where tab2.school is not null order by school_name,tab2.date ");
			
			$result[] = $result6;
		}
		
		return $result;
    }


     /**
     * 统计出每个人每天的退费金额，按照常规、非常规的新签、续签进行统计
     * $yj_type 新签、续签
     * 0=>一对一教学,1=>个性化小组,2=>高考全日制,3=>中考全日制,4=>特色课堂,5=>高考特训营,6=>精品小班课,7=>百年育才合作项目,8=>高考志愿填报指导,9=>港澳台院校申请,10=>自主招生申请,11=>贵宾卡
     * $md_type 月数据、日数据类型，m代表月数据,d代表日数据
     */
    public function getAllPersonReturnMoneyDetail($md_type,$month,$userName){
        $return = M('hw003.return','money_');
    
        $model = new Model();
        //      $result1 = $model->query("SELECT student,DATE_FORMAT(timestamp,'%Y-%m-%d') as `day`,CASE  WHEN round(sum(je), 2) IS NULL THEN '0.00' ELSE round(sum(je), 2) END AS money FROM  hw003.money_return WHERE 1=1 " . $yj . " AND state = 6 AND class1 NOT IN (5,7,8,9,10) group by student,`day` order by school,`date`,`day` ");
        //
        //      $w['class1'] = array('in', '5,7,8,9,10'); //非常规
        //
        //      $result2 = $model->query("SELECT student,DATE_FORMAT(timestamp,'%Y-%m-%d') as `day`,CASE  WHEN round(sum(je), 2) IS NULL THEN '0.00' ELSE round(sum(je), 2) END AS money FROM  hw003.money_return WHERE 1=1 " . $yj . " AND state = 6 AND class1 IN (5,7,8,9,10) group by student,`day` order by school,`date`,`day` ");
    
        if($md_type == 'd'){
                
            //每天
            //教学主任
            //常规
            $result1 = $model->query("SELECT  tab2.id ,tab2.timestamp as checkout_date,'' as receipt_card,tab2.aa as teaching_userid,tab2.bb as study_userid,tab2.kf_type as achievement_type,tab2.student as student_name,tab2.grade,tab2.time3 as achievement_date,case class1 when 0 then '一对一教学' when 1 then '个性化小组' when 2 then '高考全日制' when 3 then '中考全日制' when 4 then '特色课堂' when 5 then '高考特训营' when 6 then '精品小班课'  when 7 then '百年育才合作项目'  when 8 then '高考志愿填报指导'   when 9 then '港澳台院校申请'  when 10 then '自主招生申请' when 11 then '贵宾卡' end as curriculum_type,'' as not_curriculum_type,(select name from hongwen_oa.oa_foo_info where id=tab2.class2) as curriculum_name,tab2.je as charge_money,tab2.other as content ,substr(tab2.time3,1,10) as `day` FROM (SELECT * FROM hongwen_oa.oa_foo_info AS info WHERE info.pid = 15 AND info.id != 174 AND info.is_del = 0) AS tab1 LEFT JOIN (SELECT * FROM hw003.money_return AS moneyRe WHERE     1 = 1 AND moneyRe.time3 like '" . $month . "%' AND moneyRe.kf_type = '新签' AND moneyRe.state = 6 AND moneyRe.class1 NOT IN (5, 7, 8, 9, 10)) AS tab2 ON tab1.name = tab2.school WHERE tab2.aa = '" . $userName . "' AND  tab2.school is not null ORDER BY tab2.school, tab2.date, `day` ");
                
            $result[] = $result1;
            //非常规
            $result2 = $model->query("SELECT  tab2.id ,tab2.timestamp as checkout_date,'' as receipt_card,tab2.aa as teaching_userid,tab2.bb as study_userid,tab2.kf_type as achievement_type,tab2.student as student_name,tab2.grade,tab2.time3 as achievement_date,'' as curriculum_type,case class1 when 0 then '一对一教学' when 1 then '个性化小组' when 2 then '高考全日制' when 3 then '中考全日制' when 4 then '特色课堂' when 5 then '高考特训营' when 6 then '精品小班课'  when 7 then '百年育才合作项目'  when 8 then '高考志愿填报指导'   when 9 then '港澳台院校申请'  when 10 then '自主招生申请' when 11 then '贵宾卡' end as not_curriculum_type,(select name from hongwen_oa.oa_foo_info where id=tab2.class2) as curriculum_name,tab2.je as charge_money,tab2.other as content ,substr(tab2.time3,1,10) as `day` FROM (SELECT * FROM hongwen_oa.oa_foo_info AS info WHERE info.pid = 15 AND info.id != 174 AND info.is_del = 0) AS tab1 LEFT JOIN (SELECT * FROM hw003.money_return AS moneyRe WHERE     1 = 1 AND moneyRe.time3 like '" . $month . "%' AND moneyRe.kf_type = '新签' AND moneyRe.state = 6 AND moneyRe.class1 IN (5, 7, 8, 9, 10)) AS tab2 ON tab1.name = tab2.school WHERE tab2.aa = '" . $userName . "' AND  tab2.school is not null  ORDER BY tab2.school, tab2.date, `day` ");
                
            $result[] = $result2;
                
            //====================================================================================================================================================================================================================================================================
            //学管
            //常规
            $result3 = $model->query("SELECT  tab2.id ,tab2.timestamp as checkout_date,'' as receipt_card,tab2.aa as teaching_userid,tab2.bb as study_userid,tab2.kf_type as achievement_type,tab2.student as student_name,tab2.grade,tab2.time3 as achievement_date,case class1 when 0 then '一对一教学' when 1 then '个性化小组' when 2 then '高考全日制' when 3 then '中考全日制' when 4 then '特色课堂' when 5 then '高考特训营' when 6 then '精品小班课'  when 7 then '百年育才合作项目'  when 8 then '高考志愿填报指导'   when 9 then '港澳台院校申请'  when 10 then '自主招生申请' when 11 then '贵宾卡' end as curriculum_type,'' as not_curriculum_type,(select name from hongwen_oa.oa_foo_info where id=tab2.class2) as curriculum_name,tab2.je as charge_money,tab2.other as content ,substr(tab2.time3,1,10) as `day` FROM (SELECT * FROM hongwen_oa.oa_foo_info AS info WHERE info.pid = 15 AND info.id != 174 AND info.is_del = 0) AS tab1 LEFT JOIN (SELECT * FROM hw003.money_return AS moneyRe WHERE     1 = 1 AND moneyRe.time3 like '" . $month . "%' AND moneyRe.kf_type = '新签' AND moneyRe.state = 6 AND moneyRe.class1 NOT IN (5, 7, 8, 9, 10)) AS tab2 ON tab1.name = tab2.school WHERE tab2.bb = '" . $userName . "' AND  tab2.school is not null ORDER BY tab2.school, tab2.date, `day` ");
                
            $result[] = $result3;
    
            //非常规
            $result4 = $model->query("SELECT  tab2.id ,tab2.timestamp as checkout_date,'' as receipt_card,tab2.aa as teaching_userid,tab2.bb as study_userid,tab2.kf_type as achievement_type,tab2.student as student_name,tab2.grade,tab2.time3 as achievement_date,'' as curriculum_type,case class1 when 0 then '一对一教学' when 1 then '个性化小组' when 2 then '高考全日制' when 3 then '中考全日制' when 4 then '特色课堂' when 5 then '高考特训营' when 6 then '精品小班课'  when 7 then '百年育才合作项目'  when 8 then '高考志愿填报指导'   when 9 then '港澳台院校申请'  when 10 then '自主招生申请' when 11 then '贵宾卡' end as not_curriculum_type,(select name from hongwen_oa.oa_foo_info where id=tab2.class2) as curriculum_name,tab2.je as charge_money,tab2.other as content ,substr(tab2.time3,1,10) as `day` FROM (SELECT * FROM hongwen_oa.oa_foo_info AS info WHERE info.pid = 15 AND info.id != 174 AND info.is_del = 0) AS tab1 LEFT JOIN (SELECT * FROM hw003.money_return AS moneyRe WHERE     1 = 1 AND moneyRe.time3 like '" . $month . "%' AND moneyRe.kf_type = '新签' AND moneyRe.state = 6 AND moneyRe.class1 IN (5, 7, 8, 9, 10)) AS tab2 ON tab1.name = tab2.school WHERE tab2.bb = '" . $userName . "' AND  tab2.school is not null ORDER BY tab2.school, tab2.date, `day` ");
                
            $result[] = $result4;
                
            //====================================================================================================================================================================================================================================================================
            //学管,教学主任，这项统计不分组，所有的都查询出来，之后进行逐个对比查找
            //常规
            $result5 = $model->query("SELECT  tab2.id ,tab2.timestamp as checkout_date,'' as receipt_card,tab2.aa as teaching_userid,tab2.bb as study_userid,tab2.kf_type as achievement_type,tab2.student as student_name,tab2.grade,tab2.time3 as achievement_date,case class1 when 0 then '一对一教学' when 1 then '个性化小组' when 2 then '高考全日制' when 3 then '中考全日制' when 4 then '特色课堂' when 5 then '高考特训营' when 6 then '精品小班课'  when 7 then '百年育才合作项目'  when 8 then '高考志愿填报指导'   when 9 then '港澳台院校申请'  when 10 then '自主招生申请' when 11 then '贵宾卡' end as curriculum_type,'' as not_curriculum_type,(select name from hongwen_oa.oa_foo_info where id=tab2.class2) as curriculum_name,tab2.je as charge_money,tab2.other as content ,substr(tab2.time3,1,10) as `day` FROM (SELECT * FROM hongwen_oa.oa_foo_info AS info WHERE info.pid = 15 AND info.id != 174 AND info.is_del = 0) AS tab1 LEFT JOIN (SELECT * FROM hw003.money_return AS moneyRe WHERE     1 = 1 AND moneyRe.time3 like '" . $month . "%' AND moneyRe.kf_type = '转介绍' AND moneyRe.state = 6 AND moneyRe.class1 NOT IN (5, 7, 8, 9, 10)) AS tab2 ON tab1.name = tab2.school WHERE (tab2.aa = '" . $userName . "'  OR tab2.bb = '" . $userName . "' ) AND  tab2.school is not null ORDER BY tab2.school, tab2.date, `day` ");
                
            $result[] = $result5;
    
            //非常规
            $result6 = $model->query("SELECT  tab2.id ,tab2.timestamp as checkout_date,'' as receipt_card,tab2.aa as teaching_userid,tab2.bb as study_userid,tab2.kf_type as achievement_type,tab2.student as student_name,tab2.grade,tab2.time3 as achievement_date,'' as curriculum_type,case class1 when 0 then '一对一教学' when 1 then '个性化小组' when 2 then '高考全日制' when 3 then '中考全日制' when 4 then '特色课堂' when 5 then '高考特训营' when 6 then '精品小班课'  when 7 then '百年育才合作项目'  when 8 then '高考志愿填报指导'   when 9 then '港澳台院校申请'  when 10 then '自主招生申请' when 11 then '贵宾卡' end as not_curriculum_type,(select name from hongwen_oa.oa_foo_info where id=tab2.class2) as curriculum_name,tab2.je as charge_money,tab2.other as content ,substr(tab2.time3,1,10) as `day` FROM (SELECT * FROM hongwen_oa.oa_foo_info AS info WHERE info.pid = 15 AND info.id != 174 AND info.is_del = 0) AS tab1 LEFT JOIN (SELECT * FROM hw003.money_return AS moneyRe WHERE     1 = 1 AND moneyRe.time3 like '" . $month . "%' AND moneyRe.kf_type = '转介绍' AND moneyRe.state = 6 AND moneyRe.class1 IN (5, 7, 8, 9, 10)) AS tab2 ON tab1.name = tab2.school WHERE (tab2.aa = '" . $userName . "'  OR tab2.bb = '" . $userName . "' ) AND  tab2.school is not null ORDER BY tab2.school, tab2.date, `day` ");
                
            $result[] = $result6;
                
                
        }else if($md_type == 'm'){
    
            //每月
            //教学主任
            $result1 = $model->query("SELECT tab2.aa as jxzr, substr(tab2.time3,1,7) as `qc`, CASE WHEN round(sum(tab2.je), 2) IS NULL THEN '0.00'ELSE round(sum(tab2.je), 2) END AS count_num FROM (SELECT * FROM hongwen_oa.oa_foo_info AS info WHERE info.pid = 15 AND info.id != 174 AND info.is_del = 0) AS tab1 LEFT JOIN (SELECT * FROM hw003.money_return AS moneyRe WHERE     1 = 1 AND moneyRe.time3 like '" . $month . "%' AND moneyRe.kf_type = '新签' AND moneyRe.state = 6 AND moneyRe.class1 NOT IN (5, 7, 8, 9, 10)) AS tab2 ON tab1.name = tab2.school WHERE tab2.aa = '" . $userName . "' AND  tab2.school is not null GROUP BY `qc`, tab2.aa ");
                
            $result[] = $result1;
    
            $result2 = $model->query("SELECT tab2.aa as jxzr, substr(tab2.time3,1,7) as `qc`, CASE WHEN round(sum(tab2.je), 2) IS NULL THEN '0.00'ELSE round(sum(tab2.je), 2) END AS count_num FROM (SELECT * FROM hongwen_oa.oa_foo_info AS info WHERE info.pid = 15 AND info.id != 174 AND info.is_del = 0) AS tab1 LEFT JOIN (SELECT * FROM hw003.money_return AS moneyRe WHERE     1 = 1 AND moneyRe.time3 like '" . $month . "%' AND moneyRe.kf_type = '新签' AND moneyRe.state = 6 AND moneyRe.class1 IN (5, 7, 8, 9, 10)) AS tab2 ON tab1.name = tab2.school WHERE tab2.aa = '" . $userName . "' AND  tab2.school is not null GROUP BY `qc`, tab2.aa ");
                
            $result[] = $result2;
                
            //====================================================================================================================================================================================================================================================================
            //学管
            $result3 = $model->query("SELECT tab2.bb as xg, substr(tab2.time3,1,7) as `qc`, CASE WHEN round(sum(tab2.je), 2) IS NULL THEN '0.00'ELSE round(sum(tab2.je), 2) END AS count_num FROM (SELECT * FROM hongwen_oa.oa_foo_info AS info WHERE info.pid = 15 AND info.id != 174 AND info.is_del = 0) AS tab1 LEFT JOIN (SELECT * FROM hw003.money_return AS moneyRe WHERE     1 = 1 AND moneyRe.time3 like '" . $month . "%' AND moneyRe.kf_type = '续签' AND moneyRe.state = 6 AND moneyRe.class1 NOT IN (5, 7, 8, 9, 10)) AS tab2 ON tab1.name = tab2.school WHERE tab2.bb = '" . $userName . "' AND  tab2.school is not null GROUP BY `qc`, tab2.bb ");
                
            $result[] = $result3;
    
            $result4 = $model->query("SELECT tab2.bb as xg, substr(tab2.time3,1,7) as `qc`, CASE WHEN round(sum(tab2.je), 2) IS NULL THEN '0.00'ELSE round(sum(tab2.je), 2) END AS count_num FROM (SELECT * FROM hongwen_oa.oa_foo_info AS info WHERE info.pid = 15 AND info.id != 174 AND info.is_del = 0) AS tab1 LEFT JOIN (SELECT * FROM hw003.money_return AS moneyRe WHERE     1 = 1 AND moneyRe.time3 like '" . $month . "%' AND moneyRe.kf_type = '新签' AND moneyRe.state = 6 AND moneyRe.class1 IN (5, 7, 8, 9, 10)) AS tab2 ON tab1.name = tab2.school WHERE tab2.bb = '" . $userName . "' AND  tab2.school is not null GROUP BY `qc`, tab2.bb ");
                
            $result[] = $result4;
                
            //====================================================================================================================================================================================================================================================================
            //学管,教学主任
            //常规
            $result5 = $model->query("SELECT tab1.name AS school_name, tab2.aa as jxzr, tab2.bb as xg, substr(tab2.time3,1,7) as qc, CASE WHEN round(tab2.je, 2) IS NULL THEN '0.00'ELSE round(tab2.je, 2) END AS count_num FROM (SELECT * FROM hongwen_oa.oa_foo_info AS info WHERE info.pid = 15 AND info.id != 174 AND info.is_del = 0) AS tab1 LEFT JOIN (SELECT * FROM hw003.money_return AS moneyRe WHERE     1 = 1 AND moneyRe.time3 = '" . $month . "%' AND moneyRe.kf_type = '转介绍' AND moneyRe.state = 6 AND moneyRe.class1 NOT IN (5, 7, 8, 9, 10)) AS tab2 ON tab1.name = tab2.school WHERE (tab2.aa = '" . $uerName . "' OR tab2.bb = '" . $userName . "' ) AND  tab2.school is not null ORDER BY tab2.time3 ");
                
            $result[] = $result5;
    
            //非常规
            $result6 = $model->query("SELECT tab1.name AS school_name, tab2.aa as jxzr, tab2.bb as xg, substr(tab2.time3,1,7) as qc, CASE WHEN round(tab2.je, 2) IS NULL THEN '0.00'ELSE round(tab2.je, 2) END AS count_num FROM (SELECT * FROM hongwen_oa.oa_foo_info AS info WHERE info.pid = 15 AND info.id != 174 AND info.is_del = 0) AS tab1 LEFT JOIN (SELECT * FROM hw003.money_return AS moneyRe WHERE     1 = 1 AND moneyRe.time3 = '" . $month . "%' AND moneyRe.kf_type = '转介绍' AND moneyRe.state = 6 AND moneyRe.class1 IN (5, 7, 8, 9, 10)) AS tab2 ON tab1.name = tab2.school WHERE (tab2.aa = '" . $uerName . "' OR tab2.bb = '" . $userName . "' ) AND  tab2.school is not null ORDER BY tab2.time3 ");
                
            $result[] = $result6;
        }
    
        return $result;
    }
	
	
	//个人业绩统计
    function personal_count_find(){
        $achievement_where = array();
        $data = array();
        $personaltarget_where = array();
        $oa_user_where = array();
        $oa_position_where = array();
        $campus_where = array("pid" => 15 , 'is_del' =>0);
        $model1 = M('achievement');
        $model2 = M('personaltarget');
        $model3 = M('user');
        $model4 = M('foo_info');
        $model5 = M('position');
        if(!empty($_GET['date'])){
            $date = $_GET['date'];
        }else{
            $date = date('Y-m',time());
        }
        $personaltarget_where['date'] = array("like","%".$date."%");
        $achievement_where['achievement_date'] = array("like","%".$date."%");
        if(!empty($_GET['school_id'])){
            $campus_id = $_GET['school_id'];
            $achievement_where['campus_id'] = $campus_id;
            $campus_where['id'] = $campus_id;
            $personaltarget_where['campus_id'] = $campus_id;
        }
        $school_array = $model4->where($campus_where)->order("id")->select();
        $achievement_array = $model1->where($achievement_where)->select();
        $personaltarget_array = $model2->where($personaltarget_where)->select();
        $oa_user_array = $model3->where($oa_user_where)->select();
        $oa_position_array = $model5->where($oa_position_where)->select();
				
		$records_m = $this->getAllPersonReturnMoney('m',$date); //月数据；
		$records_d = $this->getAllPersonReturnMoney('d',$date); //日数据；
		
        foreach ($school_array as $keys => $values) {
            foreach($personaltarget_array as $key => $val){
                if($values['id'] == $val['campus_id']){
                    foreach($oa_user_array as $k => $v){
                        if($val['user_id'] == $v['id']){
                            $data[$keys][$key]['user_id'] = $v['id'];
                            $data[$keys][$key]['user_name'] = $v['name'];
                        }
                    }
					
					
                    foreach($oa_position_array as $k => $v){
                        if($val['post_id'] == $v['id']){
                            $data[$keys][$key]['post_id'] = $v['id'];
                            $data[$keys][$key]['post_name'] = $v['name'];
                        }
                    }
                    $data[$keys][$key]['target'] = $val['target'];
                    $data[$keys][$key]['upgrade'] = $val['upgrade'];
                    $data[$keys][$key]['relegation'] = $val['relegation'];
                    $data[$keys][$key]['school_id'] = $values['id'];
                    $data[$keys][$key]['school_name'] = $values['name'];
                    if($val['post_id'] == "18" || $val['post_id'] == "12"){
                        $type = '续签';
                        $types = 'old_target';
                        $oa_achievement_num = $model1->where(array('achievement_date' => array('like' , "%".$date."%") , 'achievement_type' => $type , 'status' => 2 , 'not_curriculum_type' => "" , 'study_userid' => $val['user_id']))->sum('charge_money');
                        $oa_achievement_num1 = $model1->where(array('achievement_date' => array('like' , "%".$date."%") , 'achievement_type' => '转介绍' , 'status' => 2 , 'not_curriculum_type' => "" , 'study_userid' => $val['user_id']))->sum('charge_money');
                        $special_target = $model1->where(array('achievement_date' => array('like' , "%".$date."%") , 'status' => 2 , 'not_curriculum_type' => array("like","%特训营%") , 'study_userid' => $val['user_id']))->sum('charge_money');
                        $cooperation_target = $model1->where(array('achievement_date' => array('like' , "%".$date."%") , 'status' => 2 , 'not_curriculum_type' => array("like","%合作项目%") , 'study_userid' => $val['user_id']))->sum('charge_money');
                    }else{
                        $type = '新签';
                        $types = 'new_target';
                        $oa_achievement_num = $model1->where(array('achievement_date' => array('like' , "%".$date."%") , 'achievement_type' => $type , 'status' => 2 , 'not_curriculum_type' => "" , 'teaching_userid' => $val['user_id']))->sum('charge_money');
                        $oa_achievement_num1 = $model1->where(array('achievement_date' => array('like' , "%".$date."%") , 'achievement_type' => '转介绍' , 'status' => 2 , 'not_curriculum_type' => "" , 'teaching_userid' => $val['user_id']))->sum('charge_money');
                        $special_target = $model1->where(array('achievement_date' => array('like' , "%".$date."%") , 'status' => 2 , 'not_curriculum_type' => array("like","%特训营%") , 'teaching_userid' => $val['user_id']))->sum('charge_money');
                        $cooperation_target = $model1->where(array('achievement_date' => array('like' , "%".$date."%") , 'status' => 2 , 'not_curriculum_type' => array("like","%合作项目%") , 'teaching_userid' => $val['user_id']))->sum('charge_money');
                    }


                    $data[$keys][$key]['type'] = $type;
                    $data[$keys][$key]['count_target'] = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", ($oa_achievement_num1/2)));
                    $data[$keys][$key]['special_target'] = $special_target;
                    $data[$keys][$key]['cooperation_target'] = $cooperation_target;
					
					
					if($type == '新签'){
						$cg_result = array_slice($records_m,0,1); //常规
						$fcg_result = array_slice($records_m,1,1); //非常规
						
						//常规退费金额计算
						foreach($cg_result[0] as $m=>$n){
							if(($data[$keys][$key]['school_name'] == $n['school_name']) && ($data[$keys][$key]['user_name'] == $n['aa'])){
									$data[$keys][$key]['count_cg'] = $n['count_num'];
									break;
							}	
						}
						
						//非常规退费金额计算
						foreach($fcg_result[0] as $mb=>$nb){
							if(($data[$keys][$key]['school_name'] == $nb['school_name']) && ($data[$keys][$key]['user_name'] == $nb['aa'])){
									$data[$keys][$key]['count_fcg'] = $nb['count_num'];
									break;
							}	
						}
						
					}elseif($type == '续签'){
						
						$cg_result = array_slice($records_m,2,1);//常规
						$fcg_result = array_slice($records_m,3,1);//非常规
						
						//常规退费金额计算
						foreach($cg_result[0] as $m=>$n){
							if(($data[$keys][$key]['school_name'] == $n['school_name']) && ($data[$keys][$key]['user_name'] == $n['bb'])){
									$data[$keys][$key]['count_cg'] = $n['count_num'];
							}	
						}
						//非常规退费金额计算
						foreach($fcg_result[0] as $mk=>$nb){
							if(($data[$keys][$key]['school_name'] == $nb['school_name']) && ($data[$keys][$key]['user_name'] == $nb['bb'])){
									$data[$keys][$key]['count_fcg'] = $nb['count_num'];
							}	
						}
						
					}
					
					$zjsc_result = array_slice($records_m,4,1);//转介绍常规
					$zjsf_result = array_slice($records_m,5,1);//转介绍非常规
					//常规转介绍退费金额计算
					foreach($zjsc_result[0] as $rk=>$rd){
						if(($data[$keys][$key]['school_name'] == $rd['school_name']) && (($data[$keys][$key]['user_name'] == $rd['aa']) || ($data[$keys][$key]['user_name'] == $rd['bb']))){
								$data[$keys][$key]['count_cg'] += round($rd['count_num']/2,2);
						}		
					}
					//非常规转介绍退费金额计算
					foreach($zjsf_result[0] as $rm=>$rg){
						if(($data[$keys][$key]['school_name'] == $rg['school_name']) && (($data[$keys][$key]['user_name'] == $rg['aa']) || ($data[$keys][$key]['user_name'] == $rg['bb']))){
								$data[$keys][$key]['count_fcg'] += round($rg['count_num']/2,2);
						}		
					}	
						
						
						
					$cg_result_n = array_slice($records_d,0,1); //新签常规
					$fcg_result_n = array_slice($records_d,1,1);//新签非常规
					
					$cg_result_x = array_slice($records_d,2,1);//续签常规
					$fcg_result_x = array_slice($records_d,3,1);//续签非常规
					
					$zjsdc_result = array_slice($records_d,4,1);//转介绍常规
					$zjsdfc_result = array_slice($records_d,5,1);//转介绍非常规
							
                    foreach($achievement_array as $k => $v){
                        if(strstr($v['not_curriculum_type'],"合作项目") || strstr($v['not_curriculum_type'],"特训营")){
                            if(( strstr($v['not_curriculum_type'],"特训营") || strstr($v['not_curriculum_type'],"合作项目") ) && ( $v['teaching_userid'] == $val['user_id'] || $v['study_userid'] == $val['user_id'] )){
                                $data[$keys][$key]['content'][$k]['type'] = $v['not_curriculum_type'];
                                $data[$keys][$key]['content'][$k]['date'] = $v['achievement_date'];
                                $data[$keys][$key]['content'][$k]['money'] = $v['charge_money'];
							
								if($v['achievement_type'] == '新签'){
									//新签退费
									foreach($fcg_result_n[0] as $mf=>$nf){
										if(($nf['school_name'] == $data[$keys][$key]['school_name']) && ($v['achievement_date'] == $nf['day']) && ($nf['aa'] == $data[$keys][$key]['user_name'])){
											$data[$keys][$key]['content'][$k]['money_fcg'] = $nf['count_num'];	
										}
										
									}
								}	
								
								if($v['achievement_type'] == '续签'){
									//续签退费
									foreach($fcg_result_x[0] as $mg=>$ng){
										if(($ng['school_name'] == $data[$keys][$key]['school_name']) && ($v['achievement_date'] == $ng['day']) && ($ng['bb'] == $data[$keys][$key]['user_name'])){
											$data[$keys][$key]['content'][$k]['money_fcg'] = $ng['count_num'];	
										}
									}
								}
								//转介绍
								foreach($zjsdfc_result[0] as $mm=>$nn){
									if(($nn['school_name'] == $data[$keys][$key]['school_name']) && ($v['achievement_date'] == $nn['day']) && (($nn['aa'] == $data[$keys][$key]['user_name']) || ($nn['bb'] == $data[$keys][$key]['user_name']))){
										$data[$keys][$key]['content'][$k]['money_fcg'] += round($nn['count_num']/2,2);	
									}
									
								}
                            }
                        }else{
                            if(( $v['teaching_userid'] == $val['user_id'] || $v['study_userid'] == $val['user_id'] ) && $v['achievement_type'] == '转介绍'){
                                $data[$keys][$key]['content'][$k]['type'] = $v['achievement_type'];
                                $data[$keys][$key]['content'][$k]['date'] = $v['achievement_date'];
                                $data[$keys][$key]['content'][$k]['money'] = $v['charge_money'];
								
								//转介绍
								foreach($zjsdc_result[0] as $mc=>$nc){
									if(($nc['school_name'] == $data[$keys][$key]['school_name']) && ($v['achievement_date'] == $nn['day']) && (($nc['aa'] == $data[$keys][$key]['user_name']) || ($nc['bb'] == $data[$keys][$key]['user_name']))){
										$data[$keys][$key]['content'][$k]['money_zjscg'] += round($nc['count_num']/2,2);	
									}
									
								}
								
                            }
                            if($v['teaching_userid'] == $val['user_id'] && $v['achievement_type'] == '新签'){
                                $data[$keys][$key]['content'][$k]['type'] = $v['achievement_type'];
                                $data[$keys][$key]['content'][$k]['date'] = $v['achievement_date'];
                                $data[$keys][$key]['content'][$k]['money'] = $v['charge_money'];
								//新签退费
								foreach($cg_result_n[0] as $mp=>$ny){
									if(($ny['school_name'] == $data[$keys][$key]['school_name']) && ($v['achievement_date'] == $ny['day']) && ($ny['aa'] == $data[$keys][$key]['user_name'])){
										$data[$keys][$key]['content'][$k]['money_cg'] = $ny['count_num'];	
									}
									
								}
                            }
                            if($v['study_userid'] == $val['user_id'] && $v['achievement_type'] == '续签'){
                                $data[$keys][$key]['content'][$k]['type'] = $v['achievement_type'];
                                $data[$keys][$key]['content'][$k]['date'] = $v['achievement_date'];
                                $data[$keys][$key]['content'][$k]['money'] = $v['charge_money'];
								//续签退费
								foreach($cg_result_x[0] as $mx=>$nz){
									if(($ny['school_name'] == $data[$keys][$key]['school_name']) && ($v['achievement_date'] == $ny['day']) && ($ny['bb'] == $data[$keys][$key]['user_name'])){
										$data[$keys][$key]['content'][$k]['money_cg'] = $ny['count_num'];	
									}
								}
                            }
                            
                        }
                    }
                }
            }
        }
        // echo "<pre>";
        // print_r($data);
        $this->ajaxReturn($data);


    }
	
	/*//个人业绩统计
    function personal_count_find(){
        $achievement_where = array();
        $data = array();
        $personaltarget_where = array();
        $oa_user_where = array();
        $oa_position_where = array();
        $campus_where = array("pid" => 15 , 'is_del' =>0);
        $model1 = M('achievement');
        $model2 = M('personaltarget');
        $model3 = M('user');
        $model4 = M('foo_info');
        $model5 = M('position');
        if(!empty($_GET['date'])){
            $date = $_GET['date'];
        }else{
            $date = date('Y-m',time());
        }
        $personaltarget_where['date'] = array("like","%".$date."%");
        $achievement_where['achievement_date'] = array("like","%".$date."%");
        if(!empty($_GET['school_id'])){
            $campus_id = $_GET['school_id'];
            $achievement_where['campus_id'] = $campus_id;
            $campus_where['id'] = $campus_id;
            $personaltarget_where['campus_id'] = $campus_id;
        }
        $school_array = $model4->where($campus_where)->order("id")->select();
        $achievement_array = $model1->where($achievement_where)->select();
        $personaltarget_array = $model2->where($personaltarget_where)->select();
        $oa_user_array = $model3->where($oa_user_where)->select();
        $oa_position_array = $model5->where($oa_position_where)->select();
		
        foreach ($school_array as $keys => $values) {
            foreach($personaltarget_array as $key => $val){
                if($values['id'] == $val['campus_id']){
                    foreach($oa_user_array as $k => $v){
                        if($val['user_id'] == $v['id']){
                            $data[$keys][$key]['user_id'] = $v['id'];
                            $data[$keys][$key]['user_name'] = $v['name'];
                        }
                    }

                    foreach($oa_position_array as $k => $v){
                        if($val['post_id'] == $v['id']){
                            $data[$keys][$key]['post_id'] = $v['id'];
                            $data[$keys][$key]['post_name'] = $v['name'];
                        }
                    }
                    $data[$keys][$key]['target'] = $val['target'];
                    $data[$keys][$key]['upgrade'] = $val['upgrade'];
                    $data[$keys][$key]['relegation'] = $val['relegation'];
                    $data[$keys][$key]['school_id'] = $values['id'];
                    $data[$keys][$key]['school_name'] = $values['name'];
                    if($val['post_id'] == "18" || $val['post_id'] == "12"){
                        $type = '续签';
                        $types = 'old_target';
                        $oa_achievement_num = $model1->where(array('achievement_date' => array('like' , "%".$date."%") , 'achievement_type' => $type , 'status' => 2 , 'not_curriculum_type' => "" , 'study_userid' => $val['user_id']))->sum('charge_money');
                        $oa_achievement_num1 = $model1->where(array('achievement_date' => array('like' , "%".$date."%") , 'achievement_type' => '转介绍' , 'status' => 2 , 'not_curriculum_type' => "" , 'study_userid' => $val['user_id']))->sum('charge_money');
                        $special_target = $model1->where(array('achievement_date' => array('like' , "%".$date."%") , 'status' => 2 , 'not_curriculum_type' => array("like","%特训营%") , 'study_userid' => $val['user_id']))->sum('charge_money');
                        $cooperation_target = $model1->where(array('achievement_date' => array('like' , "%".$date."%") , 'status' => 2 , 'not_curriculum_type' => array("like","%合作项目%") , 'study_userid' => $val['user_id']))->sum('charge_money');
                    }else{
                        $type = '新签';
                        $types = 'new_target';
                        $oa_achievement_num = $model1->where(array('achievement_date' => array('like' , "%".$date."%") , 'achievement_type' => $type , 'status' => 2 , 'not_curriculum_type' => "" , 'teaching_userid' => $val['user_id']))->sum('charge_money');
                        $oa_achievement_num1 = $model1->where(array('achievement_date' => array('like' , "%".$date."%") , 'achievement_type' => '转介绍' , 'status' => 2 , 'not_curriculum_type' => "" , 'teaching_userid' => $val['user_id']))->sum('charge_money');
                        $special_target = $model1->where(array('achievement_date' => array('like' , "%".$date."%") , 'status' => 2 , 'not_curriculum_type' => array("like","%特训营%") , 'teaching_userid' => $val['user_id']))->sum('charge_money');
                        $cooperation_target = $model1->where(array('achievement_date' => array('like' , "%".$date."%") , 'status' => 2 , 'not_curriculum_type' => array("like","%合作项目%") , 'teaching_userid' => $val['user_id']))->sum('charge_money');
                    }
                    $data[$keys][$key]['type'] = $type;
                    $data[$keys][$key]['count_target'] = sprintf("%.4f", sprintf("%.4f", $oa_achievement_num)+sprintf("%.4f", ($oa_achievement_num1/2)));
                    $data[$keys][$key]['special_target'] = $special_target;
                    $data[$keys][$key]['cooperation_target'] = $cooperation_target;

                    foreach($achievement_array as $k => $v){
                        if(strstr($v['not_curriculum_type'],"合作项目") || strstr($v['not_curriculum_type'],"特训营")){
                            if(( strstr($v['not_curriculum_type'],"特训营") || strstr($v['not_curriculum_type'],"合作项目") ) && ( $v['teaching_userid'] == $val['user_id'] || $v['study_userid'] == $val['user_id'] )){
                                $data[$keys][$key]['content'][$k]['type'] = $v['not_curriculum_type'];
                                $data[$keys][$key]['content'][$k]['date'] = $v['achievement_date'];
                                $data[$keys][$key]['content'][$k]['money'] = $v['charge_money'];
                            }
                        }else{
                            if(( $v['teaching_userid'] == $val['user_id'] || $v['study_userid'] == $val['user_id'] ) && $v['achievement_type'] == '转介绍'){
                                $data[$keys][$key]['content'][$k]['type'] = $v['achievement_type'];
                                $data[$keys][$key]['content'][$k]['date'] = $v['achievement_date'];
                                $data[$keys][$key]['content'][$k]['money'] = $v['charge_money'];
                            }
                            if($v['teaching_userid'] == $val['user_id'] && $v['achievement_type'] == '新签'){
                                $data[$keys][$key]['content'][$k]['type'] = $v['achievement_type'];
                                $data[$keys][$key]['content'][$k]['date'] = $v['achievement_date'];
                                $data[$keys][$key]['content'][$k]['money'] = $v['charge_money'];
                            }
                            if($v['study_userid'] == $val['user_id'] && $v['achievement_type'] == '续签'){
                                $data[$keys][$key]['content'][$k]['type'] = $v['achievement_type'];
                                $data[$keys][$key]['content'][$k]['date'] = $v['achievement_date'];
                                $data[$keys][$key]['content'][$k]['money'] = $v['charge_money'];
                            }
                            
                        }
                    }
                }
            }
        }
        // echo "<pre>";
        // print_r($data);
        $this->ajaxReturn($data);


    }*/

    //统计表接口
    function target_count(){
        if(empty($_GET['data'])){
        	// 发送给页面的数据
			$this->ajaxReturn(array('status' => false , 'content' => '程序出错，请联系管理员'));
			exit;//数据出错
        }else{
            $array = json_decode($_GET['data'],true);
        }
        if(empty($array['type'])){
        	// 发送给页面的数据
			$this->ajaxReturn(array('status' => false , 'content' => '程序出错，请选择类型'));
			exit;//数据出错
        }else{
            $type = $array['type'];
        }
        // $array['date'] = '2016-09';
        // $type = '招生';
        if(empty($array['date'])){
            $date = date('Y-m',time());
            $year_day = Intval(date('Y',time()));
            $month_count = date('m',time());
            $day_count = Intval(date('d',time()));

        }else{
            if($array['date'] == date("Y-m",time())){
                $date = date('Y-m',time());
                $year_day = Intval(date('Y',time()));
                $month_count = date('m',time());
                $day_count = Intval(date('d',time()));
            }else{
                $date = $array['date'];
                $date_time_arr = $this->year_month_day(strtotime($date));
                $year_day = $date_time_arr['year_day'];
                $month_count = $date_time_arr['month_day'];
                $day_count = $date_time_arr['day_count'];
            }
        }
        $oa_foo_info = M('foo_info');
        $oa_campustarget = D('campustarget');
        $oa_achievement = D('achievement');
        $campus_arr = array();
        $data = array();
        $campus_arr = $oa_foo_info->where(array('pid' => 15 , 'is_del' => 0,'id' => array('neq',174)))->select();
        $campus_arr[] = array('id' => 100000 , 'name' => '集团总业绩');
		$return_money = $this->getReturnMonthRecords($date,$type);
        foreach($return_money as &$value){
            $numss = 0.00;
            foreach($value as $val){
                $numss = sprintf("%.2f", sprintf("%.2f", $numss)+sprintf("%.2f", $val['count_num']));
            }
            $value[] = array('school_name' => '集团总业绩' , 'count_num' => $numss);
        }
        

        $oa_campustarget_arr = $oa_campustarget->where(array('date' => $date))->select();
        if($oa_campustarget_arr){
            if($type == '新签' || $type == '续签'){
                $top = array(
                    'target' =>  array('name' => $type.'总目标' , 'count' => array()) , 
                    'achievement' => array('name' => $type.'总业绩(常规)' , 'count' => array()) , 
                    'refund' => array('name' => '常规课程退费' , 'count' => $return_money[0]) , 
                    'complete' => array('name' => $type.'完成率(常规)' , 'count' => array()) , 
                    'not_refund' => array('name' => '非常规课程退费' , 'count' => $return_money[1]) , 
                    'special' => array('name' => '特训营业绩' , 'count' => array()) , 
                    'cooperation' => array('name' => '合作项目业绩' , 'count' => array()) ,  
                    'theHigh' => array('name' => '高考报考业绩' , 'count' => array()) ,  
                    'achievement_count' => array('name' => '月'.$type.'总业绩' , 'count' => array()));
                foreach($top as &$value){
                    foreach($campus_arr as $k=> $val){
                        $school_name = $val['name'];
                        $value['count'][$k]['school_name'] = $val['name'];
                        if($value['name'] == $type.'总目标'){
                            if($val['id'] == '100000'){
                                $count_num = '';
                                if($type == '新签'){
                                    $count_num = $oa_campustarget->where(array('date' => $date))->sum('new_target');
                                    $value['count'][$k]['count_num'] = $count_num;
                                }else if($type == '续签'){
                                    $count_num = $oa_campustarget->where(array('date' => $date))->sum('old_target');
                                    $value['count'][$k]['count_num'] = $count_num;
                                }
                            }else{
                                foreach($oa_campustarget_arr as $oa_campustarget_val){
                                    $count_num = '';
                                    if($oa_campustarget_val['campus_id'] == $val['id']){
                                        if($type == '新签'){
                                            $count_num = $oa_campustarget_val['new_target'];
                                            $value['count'][$k]['count_num'] = $count_num;
                                        }else if($type == '续签'){
                                            $count_num = $oa_campustarget_val['old_target'];
                                            $value['count'][$k]['count_num'] = $count_num;
                                        }
                                        
                                    }
                                }
                            }
                        }else if($value['name'] == $type.'总业绩(常规)'){
                            if($val['id'] == '100000'){
                                $oa_achievement_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $type , 'status' => 2 , 'not_curriculum_type' => ""))->sum('charge_money');

                                $oa_achievement_num2 = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $type , 'status' => 2 , 'curriculum_type' => "" , 'not_curriculum_type' => array("like","%高考报考%")))->sum('charge_money');
                                $oa_achievement_num3 = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $type , 'status' => 2 , 'curriculum_type' => "" , 'not_curriculum_type' => array("like","%自主招生%")))->sum('charge_money');

                                $oa_achievement_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", $oa_achievement_num2)+sprintf("%.2f", $oa_achievement_num3));

                                $oa_achievement_num1 = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => '转介绍' , 'status' => 2 , 'not_curriculum_type' => ""))->sum('charge_money');
                                if($type == '新签'){
                                    $money_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", ($oa_achievement_num1/2)));
                                    $value['count'][$k]['count_num'] = $money_num;
                                }else if($type == '续签'){
                                    $money_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", ($oa_achievement_num1/2)));
                                    $value['count'][$k]['count_num'] = $money_num;
                                }
                            }else{
                                $oa_achievement_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $type , 'campus_id' => $val['id'] , 'status' => 2 , 'not_curriculum_type' => ""))->sum('charge_money');


                                $oa_achievement_num2 = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $type , 'campus_id' => $val['id'] , 'status' => 2 , 'curriculum_type' => "" , 'not_curriculum_type' => array("like","%高考报考%")))->sum('charge_money');
                                $oa_achievement_num3 = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $type , 'campus_id' => $val['id'] , 'status' => 2 , 'curriculum_type' => "" , 'not_curriculum_type' => array("like","%自主招生%")))->sum('charge_money');

                                $oa_achievement_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", $oa_achievement_num2)+sprintf("%.2f", $oa_achievement_num3));

                                $oa_achievement_num1 = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => '转介绍' , 'campus_id' => $val['id'] , 'status' => 2 ,'not_curriculum_type' => ""))->sum('charge_money');
                                $money_num = '';
                                if($type == '新签'){
                                    $money_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", ($oa_achievement_num1/2)));
                                    $value['count'][$k]['count_num'] = $money_num;
                                }else if($type == '续签'){
                                    $money_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", ($oa_achievement_num1/2)));
                                    $value['count'][$k]['count_num'] = $money_num;
                                }
                            }
                        }else if($value['name'] == $type.'完成率(常规)'){
                            $value['count'][$k]['count_num'] = sprintf("%01.2f", sprintf("%.4f", ( $top['achievement']['count'][$k]['count_num'] - $top['refund']['count'][$k]['count_num'] )/$top['target']['count'][$k]['count_num'])*100).'%';
                        }else if($value['name'] == '特训营业绩'){
	                        if($val['id'] == '100000'){
	                            $special_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $type , 'not_curriculum_type' => array("like","%特训营%") , 'status' => 2))->sum('charge_money');
	                        }else{
	                            $special_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $type , 'not_curriculum_type' => array("like","%特训营%") , 'campus_id' => $val['id'] , 'status' => 2))->sum('charge_money');
	                        }
                            $value['count'][$k]['count_num'] = sprintf("%.2f", $special_num);
                        }else if($value['name'] == '合作项目业绩'){
                            if($val['id'] == '100000'){
                                $cooperation_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $type , 'not_curriculum_type' => array("like","%合作项目%") , 'status' => 2))->sum('charge_money');
                            }else{
                                $cooperation_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $type , 'not_curriculum_type' => array("like","%合作项目%") , 'campus_id' => $val['id'] , 'status' => 2))->sum('charge_money');
                            }
                            $value['count'][$k]['count_num'] = sprintf("%.2f", $cooperation_num);
                        }else if($value['name'] == '高考报考业绩'){
                            if($val['id'] == '100000'){
                                $theHigh_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $type , 'not_curriculum_type' => array("like","%高考报考%") , 'status' => 2))->sum('charge_money');
                                $theHigh_num1 = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $type , 'not_curriculum_type' => array("like","%自主招生%") , 'status' => 2))->sum('charge_money');
                            }else{
                                $theHigh_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $type , 'not_curriculum_type' => array("like","%高考报考%") , 'campus_id' => $val['id'] , 'status' => 2))->sum('charge_money');
                                $theHigh_num1 = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $type , 'not_curriculum_type' => array("like","%自主招生%") , 'campus_id' => $val['id'] , 'status' => 2))->sum('charge_money');
                            }
                            $value['count'][$k]['count_num'] = sprintf("%.2f", $theHigh_num) + sprintf("%.2f", $theHigh_num1);
                        }else if($value['name'] == '月'.$type.'总业绩'){
    						
    						if($val['id'] == '100000'){
                                $oa_achievement_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $type , 'status' => 2))->sum('charge_money');
                                $oa_achievement_num1 = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => '转介绍' , 'status' => 2))->sum('charge_money');
                                if($type == '新签'){
                                    $money_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", ($oa_achievement_num1/2)));
                                    $achievement_num = $money_num;
                                }else if($type == '续签'){
                                    $money_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", ($oa_achievement_num1/2)));
                                    $achievement_num = $money_num;
                                }
                            }else{
                                $oa_achievement_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $type , 'campus_id' => $val['id'] , 'status' => 2))->sum('charge_money');
                                $oa_achievement_num1 = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => '转介绍' , 'campus_id' => $val['id'] , 'status' => 2))->sum('charge_money');
                                $money_num = '';
                                if($type == '新签'){
                                    $money_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", ($oa_achievement_num1/2)));
                                    $achievement_num = $money_num;
                                }else if($type == '续签'){
                                    $money_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", ($oa_achievement_num1/2)));
                                    $achievement_num = $money_num;
                                }
                            }
                            
                            if($achievement_num){
                                $value['count'][$k]['count_num'] = sprintf("%.2f", $achievement_num - $top['refund']['count'][$k]['count_num'] - $top['not_refund']['count'][$k]['count_num']);
                            }else{
                                $value['count'][$k]['count_num'] = sprintf("%.2f", 0);
                            }
                        
                        }
                        
                    }
                }
            }else{
                $return_money_new = $this->getReturnMonthRecords($date,'新签');
                foreach($return_money_new as &$value){
                    $numss = 0.00;
                    foreach($value as $val){
                        $numss = sprintf("%.2f", sprintf("%.2f", $numss)+sprintf("%.2f", $val['count_num']));
                    }
                    $value[] = array('school_name' => '集团总业绩' , 'count_num' => $numss);
                }
                $return_money_old = $this->getReturnMonthRecords($date,'续签');
                foreach($return_money_old as &$value){
                    $numss = 0.00;
                    foreach($value as $val){
                        $numss = sprintf("%.2f", sprintf("%.2f", $numss)+sprintf("%.2f", $val['count_num']));
                    }
                    $value[] = array('school_name' => '集团总业绩' , 'count_num' => $numss);
                }
                $top = array(
                    'target_new' =>  array('name' => '新签总目标' , 'count' => array() , 'type' => '新签') , 
                    'achievement_new' => array('name' => '新签总业绩(常规)' , 'count' => array() , 'type' => '新签') , 
                    'refund_new' => array('name' => '新签常规课程退费' , 'count' => $return_money_new[0] , 'type' => '新签') , 
                    'complete_new' => array('name' => '新签完成率(常规)' , 'count' => array() , 'type' => '新签') , 
                    'not_refund_new' => array('name' => '新签非常规课程退费' , 'count' => $return_money_new[1] , 'type' => '新签') , 
                    'special_new' => array('name' => '新签特训营业绩' , 'count' => array() , 'type' => '新签') , 
                    'cooperation_new' => array('name' => '新签合作项目业绩' , 'count' => array() , 'type' => '新签') , 
                    'theHigh_new' => array('name' => '新签高考报考业绩' , 'count' => array() , 'type' => '新签') ,  
                    'achievement_count_new' => array('name' => '月新签总业绩' , 'count' => array() , 'type' => '新签'),
                    'null1' => array('name' => '' , 'count' => array() , 'type' => '新签'),

                    'target_old' =>  array('name' => '续签总目标' , 'count' => array() , 'type' => '续签') , 
                    'achievement_old' => array('name' => '续签总业绩(常规)' , 'count' => array() , 'type' => '续签') , 
                    'refund_old' => array('name' => '续签常规课程退费' , 'count' => $return_money_old[0] , 'type' => '续签') , 
                    'complete_old' => array('name' => '续签完成率(常规)' , 'count' => array() , 'type' => '续签') , 
                    'not_refund_old' => array('name' => '续签非常规课程退费' , 'count' => $return_money_old[1] , 'type' => '续签') , 
                    'special_old' => array('name' => '续签特训营业绩' , 'count' => array() , 'type' => '续签') , 
                    'cooperation_old' => array('name' => '续签合作项目业绩' , 'count' => array() , 'type' => '续签') , 
                    'theHigh_old' => array('name' => '续签高考报考业绩' , 'count' => array() , 'type' => '续签') , 
                    'achievement_count_old' => array('name' => '月续签总业绩' , 'count' => array() , 'type' => '续签'),
                    'null2' => array('name' => '' , 'count' => array() , 'type' => '续签'),

                    'target' =>  array('name' => $type.'总目标' , 'count' => array() , 'type' => '招生') , 
                    'achievement' => array('name' => $type.'总业绩(常规)' , 'count' => array() , 'type' => '招生') , 
                    'refund' => array('name' => $type.'常规课程退费' , 'count' => $return_money[0] , 'type' => '招生') , 
                    'complete' => array('name' => $type.'完成率(常规)' , 'count' => array() , 'type' => '招生') , 
                    'not_refund' => array('name' => $type.'非常规课程退费' , 'count' => $return_money[1] , 'type' => '招生') , 
                    'special' => array('name' => $type.'特训营业绩' , 'count' => array() , 'type' => '招生') , 
                    'cooperation' => array('name' => $type.'合作项目业绩' , 'count' => array() , 'type' => '招生') , 
                    'theHigh' => array('name' => $type.'高考报考业绩' , 'count' => array() , 'type' => '招生') , 
                    'achievement_count' => array('name' => '月'.$type.'总业绩' , 'count' => array() , 'type' => '招生')
                );
                foreach($top as &$value){
                    foreach($campus_arr as $k=> $val){
                        $school_name = $val['name'];
                        $value['count'][$k]['school_name'] = $val['name'];
                        if($value['name'] == $value['type'].'总目标'){
                            if($val['id'] == '100000'){
                                $count_num = '';
                                if($value['type'] == '新签'){
                                    $count_num = $oa_campustarget->where(array('date' => $date))->sum('new_target');
                                    $value['count'][$k]['count_num'] = $count_num;
                                }else if($value['type'] == '续签'){
                                    $count_num = $oa_campustarget->where(array('date' => $date))->sum('old_target');
                                    $value['count'][$k]['count_num'] = $count_num;
                                }else{
                                    $count_num = $oa_campustarget->where(array('date' => $date))->sum('count_target');
                                    $value['count'][$k]['count_num'] = $count_num;
                                }
                            }else{
                                foreach($oa_campustarget_arr as $oa_campustarget_val){
                                    $count_num = '';
                                    if($oa_campustarget_val['campus_id'] == $val['id']){
                                        if($value['type'] == '新签'){
                                            $count_num = $oa_campustarget_val['new_target'];
                                            $value['count'][$k]['count_num'] = $count_num;
                                        }else if($value['type'] == '续签'){
                                            $count_num = $oa_campustarget_val['old_target'];
                                            $value['count'][$k]['count_num'] = $count_num;
                                        }else{
                                            $count_num = $oa_campustarget->where(array('date' => $date , 'campus_id' => $val['id']))->sum('count_target');
                                            $value['count'][$k]['count_num'] = $count_num;
                                        }
                                        
                                    }
                                }
                            }
                        }else if($value['name'] == $value['type'].'总业绩(常规)'){
                            if($val['id'] == '100000'){
                                $oa_achievement_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $value['type'] , 'status' => 2 , 'not_curriculum_type' => ""))->sum('charge_money');

                                $oa_achievement_num2 = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $value['type'] , 'status' => 2 , 'curriculum_type' => "" , 'not_curriculum_type' => array("like","%高考报考%")))->sum('charge_money');
                                $oa_achievement_num3 = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $value['type'] , 'status' => 2 , 'curriculum_type' => "" , 'not_curriculum_type' => array("like","%自主招生%")))->sum('charge_money');

                                $oa_achievement_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", $oa_achievement_num2)+sprintf("%.2f", $oa_achievement_num3));
                                
                                $oa_achievement_num1 = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => '转介绍' , 'status' => 2 , 'not_curriculum_type' => ""))->sum('charge_money');
                                if($value['type'] == '新签'){
                                    $money_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", ($oa_achievement_num1/2)));
                                    $value['count'][$k]['count_num'] = $money_num;
                                }else if($value['type'] == '续签'){
                                    $money_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", ($oa_achievement_num1/2)));
                                    $value['count'][$k]['count_num'] = $money_num;
                                }else{
                                    $oa_achievement_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'status' => 2, 'not_curriculum_type' => ""))->sum('charge_money');

                                    $oa_achievement_num2 = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'status' => 2 , 'curriculum_type' => "" , 'not_curriculum_type' => array("like","%高考报考%")))->sum('charge_money');
                                    $oa_achievement_num3 = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'status' => 2 , 'curriculum_type' => "" , 'not_curriculum_type' => array("like","%自主招生%")))->sum('charge_money');

                                    $oa_achievement_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", $oa_achievement_num2)+sprintf("%.2f", $oa_achievement_num3));
                                
                                    $money_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", $oa_achievement_num1));
                                    $value['count'][$k]['count_num'] = $money_num;
                                }
                            }else{
                                $oa_achievement_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $value['type'] , 'campus_id' => $val['id'] , 'status' => 2 , 'not_curriculum_type' => ""))->sum('charge_money');

                                $oa_achievement_num2 = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $value['type'] , 'campus_id' => $val['id'] , 'status' => 2 , 'curriculum_type' => "" , 'not_curriculum_type' => array("like","%高考报考%")))->sum('charge_money');
                                $oa_achievement_num3 = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $value['type'] , 'campus_id' => $val['id'] , 'status' => 2 , 'curriculum_type' => "" , 'not_curriculum_type' => array("like","%自主招生%")))->sum('charge_money');

                                $oa_achievement_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", $oa_achievement_num2)+sprintf("%.2f", $oa_achievement_num3));
                                
                                $oa_achievement_num1 = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => '转介绍' , 'campus_id' => $val['id'] , 'status' => 2 ,'not_curriculum_type' => ""))->sum('charge_money');
                                $money_num = '';
                                if($value['type'] == '新签'){
                                    $money_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", ($oa_achievement_num1/2)));
                                    $value['count'][$k]['count_num'] = $money_num;
                                }else if($value['type'] == '续签'){
                                    $money_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", ($oa_achievement_num1/2)));
                                    $value['count'][$k]['count_num'] = $money_num;
                                }else{
                                    $oa_achievement_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'campus_id' => $val['id'] , 'status' => 2, 'not_curriculum_type' => ""))->sum('charge_money');

                                    $oa_achievement_num2 = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'campus_id' => $val['id'] , 'status' => 2 , 'curriculum_type' => "" , 'not_curriculum_type' => array("like","%高考报考%")))->sum('charge_money');
                                    $oa_achievement_num3 = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'campus_id' => $val['id'] , 'status' => 2 , 'curriculum_type' => "" , 'not_curriculum_type' => array("like","%自主招生%")))->sum('charge_money');

                                    $oa_achievement_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", $oa_achievement_num2)+sprintf("%.2f", $oa_achievement_num3));
                                
                                    $money_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", $oa_achievement_num1));
                                    $value['count'][$k]['count_num'] = $money_num;
                                }
                            }
                        }else if($value['name'] == $value['type'].'完成率(常规)'){
                            if($value['type'] == '新签'){
                                $value['count'][$k]['count_num'] = sprintf("%01.2f", sprintf("%.4f", ( $top['achievement_new']['count'][$k]['count_num'] - $top['refund_new']['count'][$k]['count_num'] )/$top['target_new']['count'][$k]['count_num'])*100).'%';
                            }else if($value['type'] == '续签'){
                                $value['count'][$k]['count_num'] = sprintf("%01.2f", sprintf("%.4f", ( $top['achievement_old']['count'][$k]['count_num'] - $top['refund_old']['count'][$k]['count_num'] )/$top['target_old']['count'][$k]['count_num'])*100).'%';
                            }else{
                                $value['count'][$k]['count_num'] = sprintf("%01.2f", sprintf("%.4f", ( $top['achievement']['count'][$k]['count_num'] - $top['refund']['count'][$k]['count_num'] )/$top['target']['count'][$k]['count_num'])*100).'%';
                            }
                            
                        }else if($value['name'] == $value['type'].'特训营业绩'){
                            if($value['type'] == '招生'){
                                if($val['id'] == '100000'){
                                    $special_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'not_curriculum_type' => array("like","%特训营%") , 'status' => 2))->sum('charge_money');
                                }else{
                                    $special_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'not_curriculum_type' => array("like","%特训营%") , 'campus_id' => $val['id'] , 'status' => 2))->sum('charge_money');
                                }
                            }else{
                                if($val['id'] == '100000'){
                                    $special_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $value['type'] , 'not_curriculum_type' => array("like","%特训营%") , 'status' => 2))->sum('charge_money');
                                }else{
                                    $special_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $value['type'] , 'not_curriculum_type' => array("like","%特训营%") , 'campus_id' => $val['id'] , 'status' => 2))->sum('charge_money');
                                }
                            }
                            $value['count'][$k]['count_num'] = sprintf("%.2f", $special_num);
                        }else if($value['name'] == $value['type'].'合作项目业绩'){
                            if($value['type'] == '招生'){
                                if($val['id'] == '100000'){
                                    $cooperation_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'not_curriculum_type' => array("like","%合作项目%") , 'status' => 2))->sum('charge_money');
                                }else{
                                    $cooperation_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'not_curriculum_type' => array("like","%合作项目%") , 'campus_id' => $val['id'] , 'status' => 2))->sum('charge_money');
                                }
                            }else{
                                if($val['id'] == '100000'){
                                    $cooperation_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $value['type'] , 'not_curriculum_type' => array("like","%合作项目%") , 'status' => 2))->sum('charge_money');
                                }else{
                                    $cooperation_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $value['type'] , 'not_curriculum_type' => array("like","%合作项目%") , 'campus_id' => $val['id'] , 'status' => 2))->sum('charge_money');
                                }
                            }
                            $value['count'][$k]['count_num'] = sprintf("%.2f", $cooperation_num);
                        }else if($value['name'] == $value['type'].'高考报考业绩'){
                            if($value['type'] == '招生'){
                                if($val['id'] == '100000'){
                                    $theHigh_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'not_curriculum_type' => array("like","%高考报考%") , 'status' => 2))->sum('charge_money');
                                    $theHigh_num1 = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'not_curriculum_type' => array("like","%自主招生%") , 'status' => 2))->sum('charge_money');
                                }else{
                                    $theHigh_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'not_curriculum_type' => array("like","%高考报考%") , 'campus_id' => $val['id'] , 'status' => 2))->sum('charge_money');
                                    $theHigh_num1 = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'not_curriculum_type' => array("like","%自主招生%") , 'campus_id' => $val['id'] , 'status' => 2))->sum('charge_money');
                                }
                            }else{
                                if($val['id'] == '100000'){
                                    $theHigh_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $value['type'] , 'not_curriculum_type' => array("like","%高考报考%") , 'status' => 2))->sum('charge_money');
                                    $theHigh_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $value['type'] , 'not_curriculum_type' => array("like","%自主招生%") , 'status' => 2))->sum('charge_money');
                                }else{
                                    $theHigh_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $value['type'] , 'not_curriculum_type' => array("like","%高考报考%") , 'campus_id' => $val['id'] , 'status' => 2))->sum('charge_money');
                                    $theHigh_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $value['type'] , 'not_curriculum_type' => array("like","%自主招生%") , 'campus_id' => $val['id'] , 'status' => 2))->sum('charge_money');
                                }
                            }
                            $value['count'][$k]['count_num'] = sprintf("%.2f", $theHigh_num);
                        }else if($value['name'] == '月'.$value['type'].'总业绩'){
                            $money_num = '';
                            if($val['id'] == '100000'){
                                $oa_achievement_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $value['type'] , 'status' => 2))->sum('charge_money');
                                $oa_achievement_num1 = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => '转介绍' , 'status' => 2))->sum('charge_money');
                                if($value['type'] == '新签'){
                                    $money_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", ($oa_achievement_num1/2)));
                                    $achievement_num = $money_num - $top['refund_new']['count'][$k]['count_num'] - $top['not_refund_new']['count'][$k]['count_num'];
                                }else if($value['type'] == '续签'){
                                    $money_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", ($oa_achievement_num1/2)));
                                    $achievement_num = $money_num - $top['refund_old']['count'][$k]['count_num'] - $top['not_refund_old']['count'][$k]['count_num'];
                                }else{
                                    $oa_achievement_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'status' => 2))->sum('charge_money');
                                    $money_num = sprintf("%.2f", $oa_achievement_num);
                                    $achievement_num = $money_num - $top['refund']['count'][$k]['count_num'] - $top['not_refund']['count'][$k]['count_num'];
                                }
                            }else{
                                $oa_achievement_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => $value['type'] , 'campus_id' => $val['id'] , 'status' => 2))->sum('charge_money');
                                $oa_achievement_num1 = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'achievement_type' => '转介绍' , 'campus_id' => $val['id'] , 'status' => 2))->sum('charge_money');
                                
                                if($value['type'] == '新签'){
                                    $money_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", ($oa_achievement_num1/2)));
                                    $achievement_num = $money_num - $top['refund_new']['count'][$k]['count_num'] - $top['not_refund_new']['count'][$k]['count_num'];
                                }else if($value['type'] == '续签'){
                                    $money_num = sprintf("%.2f", sprintf("%.2f", $oa_achievement_num)+sprintf("%.2f", ($oa_achievement_num1/2)));
                                    $achievement_num = $money_num - $top['refund_old']['count'][$k]['count_num'] - $top['not_refund_old']['count'][$k]['count_num'];
                                }else{
                                    $oa_achievement_num = $oa_achievement->where(array('achievement_date' => array('like' , $date.'%') , 'campus_id' => $val['id'] , 'status' => 2))->sum('charge_money');
                                    $money_num = sprintf("%.2f", $oa_achievement_num);
                                    $achievement_num = $money_num - $top['refund']['count'][$k]['count_num'] - $top['not_refund']['count'][$k]['count_num'];
                                }
                            }
                            
                            if($achievement_num){
                                $value['count'][$k]['count_num'] = sprintf("%.2f", $achievement_num);
                            }else{
                                $value['count'][$k]['count_num'] = sprintf("%.2f", 0);
                            }
                        
                        }else if($value['name'] == ''){
                            $value['count'][$k]['count_num'] = "";
                        }
                        
                    }
                }
            }
            for($i = 1; $i <= $day_count; $i++){
                if($i<10){
                    $day_num = '0'.$i;
                }else{
                    $day_num = $i;
                }
                $bottom[$i]['name'] = $i.'日'.$type.'总业绩';
                foreach($campus_arr as $key => $val){
                    $school_name = $val['name'];
                    $bottom[$i][$key]['school_name'] = $val['name'];
					if($type == '招生'){
						if($val['id'] == '100000'){
	                        $num_day_count = $oa_achievement->where(array('achievement_date' => $year_day.'-'.$month_count.'-'.$day_num , 'status' => 2))->sum('charge_money');
	                    }else{
	                        $num_day_count = $oa_achievement->where(array('achievement_date' => $year_day.'-'.$month_count.'-'.$day_num , 'campus_id' => $val['id'] , 'status' => 2))->sum('charge_money');
	                    }
					}else{
						if($val['id'] == '100000'){
	                        $num_day_count = $oa_achievement->where(array('achievement_date' => $year_day.'-'.$month_count.'-'.$day_num , 'status' => 2 , 'achievement_type' => $type))->sum('charge_money');
	                    }else{
	                        $num_day_count = $oa_achievement->where(array('achievement_date' => $year_day.'-'.$month_count.'-'.$day_num , 'campus_id' => $val['id'] , 'status' => 2 , 'achievement_type' => $type))->sum('charge_money');
	                    }
					}
                    
                    $bottom[$i][$key]['num_day_count'] = sprintf("%.2f", $num_day_count);
                }
            }

            $data['top'] = $top;
            $data['bottom'] = $bottom;
            $data['status'] = true;
        }else{
            $data['status'] = false;
        }
        $array['target_data_arr'] = $data;
        $array['target_type'] = $type;
        $array['campus_arr'] = $campus_arr;	
        session('target_array',$array);
        // echo "<pre>";
        // print_r($data);die;
		$this->ajaxReturn($data);
		exit;//数据出错
    }


    //每到月一号计算上个月年，月，日，总共多少天
    function year_month_day($time){
        //下面是获取指定月的数据
        //获取月份当前
        $data = array();
        $year_day = Intval(date("Y",$time));
        $month_day = Intval(date("m",$time));
        $data['year_day'] = $year_day;
        $data['month_day'] = $month_day;
        //计算月一共有多少天
        $data['day_count']=date('j',mktime(0,0,1,($month_day==12?1:$month_day+1),1,($month_day==12?$year_day+1:$year_day))-24*3600);
        //var_dump($data);
        return $data;
    }


    //个人业绩状态修改
    function Personal_target_save(){
        if(empty($_POST['data'])){
        	// 发送给页面的数据
			$this->ajaxReturn(array('status' => false , 'content' => '程序出错，请联系管理员'));
			exit;//数据出错
        }else{
            $array = json_decode($_POST['data'],true);
        }
        //echo json_encode($array);exit;
        $model = D('achievement');
        if(empty($array['id'])){
            foreach($array as $val){
                $state = $model->where(array('id' => $val['id']))->save(array("status" => $val['status']));
                if(!$state){
                	// 发送给页面的数据
					$this->ajaxReturn(array('status' => false , 'content' => '程序出错，请联系管理员'));
					exit;//数据出错
                }
            }
        }else{
            if($array['status'] == '3'){
                $ayy = $model->where(array('id' => $array['id']))->find();
                if($ayy['checkout_date'] == date("Y-m-d" , time())){
                    $state = $model->where(array('id' => $array['id']))->save(array("status" => 4));
                }else{
                    $state = $model->where(array('id' => $array['id']))->save(array("status" => $array['status']));
                }
            }else{
                $state = $model->where(array('id' => $array['id']))->save(array("status" => $array['status']));
            }
            
            if(!$state){
            	// 发送给页面的数据
				$this->ajaxReturn(array('status' => false , 'content' => '程序出错，请联系管理员'));
				exit;//数据出错
            }
        }
		// 发送给页面的数据
		$this->ajaxReturn(array('status' => true , 'content' => '成功'));
		exit;//数据出错
    }


    //个人业绩录入页面显示数据程序
    function Personal_target_find(){
        if(empty($_GET['status'])){
            echo json_encode(array('status' => false , 'content' => '程序出错，请联系管理员'));exit;//数据出错
        }else{
            $status = $_GET['status'];
        }
        $oa_foo_info = M('foo_info');
        $status_arr = explode(",",$status);
        if(count($status_arr) > 1){
            if($status_arr[1] == 4){
                $where_arr['_string'] = " (`campus_id` = ".$_GET['school_id']." ) and (status='".$status_arr[1]."' )) or ( status='".$status_arr[0]."'";
                $where_arr['_logic'] = 'and';
            }else{
                $where_arr['status'] = array('between' , $status);
            }
        }else{
            $where_arr['status'] = $status;
        }

        if(empty($_GET['school_id'])){
            if(empty($_GET['begin_date']) && empty($_GET['end_date'])){
                $date = date("Y-m",time());
                $where_arr['achievement_date'] = array('like' , '%'.$date.'%');
            }else{
                $begin_date = $_GET['begin_date'];
                $end_date = $_GET['end_date'];
                $where_arr['achievement_date'] = array('between' , $begin_date.','.$end_date);
            }
            $where_user = array();
        }else{
            $school_name = array();
            $campus_id = $_GET['school_id'];
            if(empty($_GET['begin_date']) && empty($_GET['end_date'])){
                $date = date("Y-m",time());
                $where_arr['campus_id'] = $campus_id;
                $where_arr['achievement_date'] = array('like' , '%'.$date.'%');
            }else{
                $begin_date = $_GET['begin_date'];
                $end_date = $_GET['end_date'];
                $where_arr['campus_id'] = $campus_id;
                $where_arr['achievement_date'] = array('between' , $begin_date.','.$end_date);
            }
            $where_school = array('id' => $campus_id);
            $school_name = $oa_foo_info->where($where_school)->find();
            $where_user = array('school' => $school_name['id']);
        }

        $person_all = M('user');

        //收据编号
        if(!empty($_GET['receipt_card'])){
            $where_arr['receipt_card'] = $_GET['receipt_card'];
        }

        //教学主任
        if(!empty($_GET['teaching_userid'])){
            if($_GET['teaching_userid'] == 'All'){
                $where_arr['teaching_userid'] = array('NEQ','');
            }else{
                $where_user['name'] = $_GET['teaching_userid'];
                $user_arr = $person_all->where($where_user)->find();
                $where_arr['teaching_userid'] = $user_arr['id'];
            }
        }

        //学习管理师
        if(!empty($_GET['study_userid'])){
            if($_GET['study_userid'] == 'All'){
                $where_arr['study_userid'] = array('NEQ','');
            }else{
                $where_user['name'] = $_GET['teaching_userid'];
                $user_arr = $person_all->where($where_user)->find();
                $where_arr['study_userid'] = $user_arr['id'];
            }
        }

        //业绩类型
        if(!empty($_GET['achievement_type'])){
            $where_arr['achievement_type'] = $_GET['achievement_type'];
        }

        //收费类型
        if(!empty($_GET['charge_type'])){
            $where_arr['charge_type'] = $_GET['charge_type'];
        }

        //学员姓名
        if(!empty($_GET['student_name'])){
            $where_arr['student_name'] = $_GET['student_name'];
        }

        //收费日期
        if(!empty($_GET['achievement_date'])){
            $where_arr['achievement_date'] = $_GET['achievement_date'];
        }

        //课程名称
        if(!empty($_GET['curriculum_name'])){
            $where_arr['curriculum_name'] = $_GET['curriculum_name'];
        }

        //讲师姓名
        if(!empty($_GET['teacher_name'])){
            $where_arr['teacher_name'] = $_GET['teacher_name'];
        }

        //收费类型
        if(!empty($_GET['receivables_type'])){
            $where_arr['receivables_type'] = $_GET['receivables_type'];
        }
        
        $model = M('achievement');
        $data = array();
        $user_arrs = array();
        $data = $model->where($where_arr)->order('id desc')->select();

        $user_arrs = $person_all->select();//
        $school_arr = $oa_foo_info->where(array('pid' => 15 , 'is_del' => 0))->select();
        $count_num = 0;
        foreach($data as &$value){
            $count_num = $count_num + $value['charge_money'];
        	foreach($school_arr as $val){
        		if($value['campus_id'] == $val['id']){
        			$value['school_name'] = $val['name'];
        		}
        	}
            foreach($user_arrs as $val){
                if($value['checkout_userid'] == $val['id']){
                    $value['checkout_username'] = $val['name'];
                }
                if($value['teaching_userid'] == $val['id']){
                    $value['teaching_userid'] = $val['name'];
                }
                if($value['study_userid'] == $val['id']){
                    $value['study_userid'] = $val['name'];
                }
            }
            
        }
		
		
        session('school_target_content',$data);
        if(!empty($data)){
            $data[0]['count_num'] = $count_num;
        }
        echo json_encode($data);exit;
    }

    

    //个人业绩录入接口程序
    function Personal_target_add(){
        if(empty($_GET['data'])){
        	// 发送给页面的数据
			$this->ajaxReturn(array('status' => false , 'content' => '请确认数据传输正确'));
			exit;//数据出错
        }else{
            $array = json_decode($_GET['data'],true);
        }
        if($array['curriculum_type'] == '0' && $array['not_curriculum_type'] == '0'){
        	// 发送给页面的数据
			$this->ajaxReturn(array('status' => false , 'content' => '请选择课程类型'));
			exit;//数据出错
        }
        $data = array();
        $date = date('Y-m-d',time());
        $teaching_userid = $array['teaching_userid'];
        $study_userid = $array['study_userid'];
        $person_all = M('user');
        $oa_foo_info = M('foo_info');
        $model = D('achievement');
        $school_name = $oa_foo_info->where(array('id' => $array['school_id']))->find();

        if(empty($array['teaching_userid']) && empty($array['study_userid'])){
        	// 发送给页面的数据
			$this->ajaxReturn(array('status' => false , 'content' => '请填写学管或者教主'));
			exit;//数据出错
        }else{
            if($array['teaching_userid']!=""){
                $teaching_user = $person_all->where(array('name' => $teaching_userid))->find();//
                if(!$teaching_user){
                	// 发送给页面的数据
					$this->ajaxReturn(array('status' => false , 'content' => $teaching_userid.' | '.$school_name['name'].' 查无此人，请确认名字是否正确'));
					exit;//数据出错
                }
            }
            if($array['study_userid']!=""){
                $study_user = $person_all->where(array('name' => $study_userid))->find();//
                if(!$study_user){
                	// 发送给页面的数据
					$this->ajaxReturn(array('status' => false , 'content' => $teaching_userid.' | '.$school_name['name'].' 查无此人，请确认名字是否正确'));
					exit;//数据出错
                }
            }
        }
        
        $data['campus_id'] = $array['school_id'];
        $data['checkout_date'] = $date;
        $data['receipt_card'] = $array['receipt_card'];
        $data['checkout_userid'] = $array['checkout_userid'];
        $data['teaching_userid'] = $teaching_user['id'];
        $data['study_userid'] = $study_user['id'];
        $data['achievement_type'] = $array['achievement_type'];
        $data['charge_type'] = $array['charge_type'];
        $data['student_name'] = $array['student_name'];
        $data['grade'] = $array['grade'];
        $data['achievement_date'] = $array['achievement_date'];
        $data['curriculum_type'] = $array['curriculum_type'];
        $data['not_curriculum_type'] = $array['not_curriculum_type'];
        $data['curriculum_name'] = $array['curriculum_name'];
        $data['teacher_name'] = $array['teacher_name'];
        $data['charge_class_num'] = $array['charge_class_num'];
        $data['charge_money'] = $array['charge_money'];
        $data['new_signing_ratio'] = $array['new_signing_ratio'];
        $data['old_signing_ratio'] = $array['old_signing_ratio'];
        $data['receivables_type'] = $array['receivables_type'];
        $data['content'] = $array['content'];

        if(empty($array['id'])){
            //添加操作（要是没有id传值）
            $state = $model->add($data);
            if($state){
                $array['id'] = $state;
                $array['checkout_date'] = $date;
                $array['status'] = 1;
				// 发送给页面的数据
				$this->ajaxReturn(array('status' => true , 'content' => '保存成功' , 'data' => $array));
				exit;
            }else{
            	// 发送给页面的数据
				$this->ajaxReturn(array('status' => false , 'content' => '保存失败，请联系管理员'));
				exit;//数据出错
            }
        }else{
            //修改操作（如果有id传值）
            $id = $array['id'];
            $state = $model->where(array("id" => $id))->save($data);
            if($state){
                $array['id'] = $id;
                $array['checkout_date'] = $date;
                $array['status'] = 1;
				// 发送给页面的数据
				$this->ajaxReturn(array('status' => true , 'content' => '修改成功' , 'data' => $array));
				exit;
            }else{
            	// 发送给页面的数据
				$this->ajaxReturn(array('status' => false , 'content' => '修改失败，请确认数据是否修改'));
				exit;//数据出错
            }
        }
    }


    //统计表excel生成函数
    function target_excel(){
        if(empty($_SESSION['target_array'])){
            $this->error("生成页面错误，请联系管理员！");exit;
        }
        $content_arr = array();

        $content_arr = $_SESSION['target_array']['target_data_arr'];
        $campus_arr = $_SESSION['target_array']['campus_arr'];
        $target_type = $_SESSION['target_array']['target_type'];
        import("Vendor.PHPExcel");
        //创建对象
        $excel = new \PHPExcel();
        //Excel表格式
        $letter = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W');
        //表头数组
        $tableheader[] = '单位（元）';
        foreach($campus_arr as $value){
            $tableheader[] = $value['name'];
        }
        //填充表头信息
        for($i = 0;$i < count($tableheader);$i++) {
            $excel->getActiveSheet()->setCellValue("$letter[$i]1","$tableheader[$i]");
        }
        //表格数组
        $i = 0;
        foreach($content_arr['top'] as $key => $val){
            $data[$i][0] = $val['name'];
            foreach($val['count'] as $k => $v){
                $data[$i][$k+1] = $v["count_num"];
            }
            $i++;
        }
        foreach($content_arr['bottom'] as $key => $val){
            $data[$i][0] = $val['name'];
            foreach($val as $k => $v){
                $data[$i][$k+1] = $v['num_day_count'];
            }
            $i++;
        }
        //填充表格信息
        for ($i = 2;$i <= count($data) + 1;$i++) {
            $j = 0;
            foreach ($data[$i - 2] as $key=>$value) {
                $excel->getActiveSheet()->setCellValue("$letter[$j]$i","$value");
                $j++;
            }
        }
        //创建Excel输入对象
        $write = new \PHPExcel_Writer_Excel5($excel);
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename="'.$target_type.'统计表.xls"');
        header("Content-Transfer-Encoding:binary");
        $write->save('php://output');
    }



    //统计表excel生成函数
    function personal_target_excel(){
        if(empty($_SESSION['target_arr'])){
            $this->error("生成页面错误，请联系管理员！");exit;
        }
        $content_arr = array();
        $content_arr = $_SESSION['target_arr'];
        import("Vendor.PHPExcel");
        //创建对象
        $excel = new \PHPExcel();
        //Excel表格式
        $letter = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W');
        //表头数组
        $tableheader = array('序号','姓名','校区名称','职务','业绩目标','业绩类型','招生业绩','常规课程退费','非常规课程退费','特训营业绩','合作项目业绩');
        //填充表头信息
        for($i = 0;$i < count($tableheader);$i++) {
            $excel->getActiveSheet()->setCellValue("$letter[$i]1","$tableheader[$i]");
        }
        //表格数组
        foreach($content_arr as $key => $val){
            $data[$key][0] = $val["id"];
            $data[$key][1] = $val["name"];
            $data[$key][2] = $val["school_name"];
            $data[$key][3] = $val["post_name"];
            $data[$key][4] = $val["target"];
            $data[$key][5] = $val["target_type"];
            $data[$key][6] = $val["money_num"];
            $data[$key][7] = $val["routine_refund"];
            $data[$key][8] = $val["not_routine_refund"];
            $data[$key][9] = $val["special_money"];
            $data[$key][10] = $val["cooperation_money"];
        }
        //填充表格信息
        for ($i = 2;$i <= count($data) + 1;$i++) {
            $j = 0;
            foreach ($data[$i - 2] as $key=>$value) {
                $excel->getActiveSheet()->setCellValue("$letter[$j]$i","$value");
                $j++;
            }
        }
        //创建Excel输入对象
        $write = new \PHPExcel_Writer_Excel5($excel);
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename="个人业绩统计表.xls"');
        header("Content-Transfer-Encoding:binary");
        $write->save('php://output');
    }


	//统计表excel生成函数
    function school_target_excel(){
        if(empty($_SESSION['school_target_content'])){
			$this->error("生成页面错误，请联系管理员！");exit;
		}
		$content_arr = array();
		$content_arr = $_SESSION['school_target_content'];
		import("Vendor.PHPExcel");
		//创建对象
		$excel = new \PHPExcel();
		//Excel表格式
		$letter = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W');
		//表头数组
		$tableheader = array('序号','校区','结算日期','记录ID','收据编号','结账人','教学主任','学习管理师','业绩类型','收费类型','学员姓名','年级','收费日期','常规课程类型','非常规课程类型','课程名称','讲师','交费课时数','交费金额', '归属新签比例','归属续签比例','收款类型','备注');
		//填充表头信息
		for($i = 0;$i < count($tableheader);$i++) {
			$excel->getActiveSheet()->setCellValue("$letter[$i]1","$tableheader[$i]");
		}
		//表格数组
		foreach($content_arr as $key => $val){
			$data[$key][0] = ($key+1);
			$data[$key][1] = $val["school_name"];
			$data[$key][2] = $val["checkout_date"];
			$data[$key][3] = $val["id"];
			$data[$key][4] = $val["receipt_card"];
			$data[$key][5] = $val["checkout_username"];
			$data[$key][6] = $val["teaching_userid"];
			$data[$key][7] = $val["study_userid"];
			$data[$key][8] = $val["achievement_type"];
			$data[$key][9] = $val["charge_type"];
			$data[$key][10] = $val["student_name"];
			$data[$key][11] = $val["grade"];
			$data[$key][12] = $val["achievement_date"];
			$data[$key][13] = $val["curriculum_type"];
			$data[$key][14] = $val["not_curriculum_type"];
			$data[$key][15] = $val["curriculum_name"];
			$data[$key][16] = $val["teacher_name"];
			$data[$key][17] = $val["charge_class_num"];
			$data[$key][18] = $val["charge_money"];
			$data[$key][19] = $val["new_signing_ratio"];
			$data[$key][20] = $val["old_signing_ratio"];
			$data[$key][21] = $val["receivables_type"];
			$data[$key][22] = $val["content"];
		}
		//填充表格信息
		for ($i = 2;$i <= count($data) + 1;$i++) {
			$j = 0;
			foreach ($data[$i - 2] as $key=>$value) {
				$excel->getActiveSheet()->setCellValue("$letter[$j]$i","$value");
				$j++;
			}
		}
		//创建Excel输入对象
		$write = new \PHPExcel_Writer_Excel5($excel);
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
		header("Content-Type:application/force-download");
		header("Content-Type:application/vnd.ms-execl");
		header("Content-Type:application/octet-stream");
		header("Content-Type:application/download");;
		header('Content-Disposition:attachment;filename="提交业绩统计表.xls"');
		header("Content-Transfer-Encoding:binary");
		$write->save('php://output');
	}





    //统计表excel生成函数
    function level_excel(){
        if(empty($_SESSION['data_data'])){
            $this->error("生成页面错误，请联系管理员！");exit;
        }
        $content_arr = array();
        $content_arr = $_SESSION['data_data'];
        import("Vendor.PHPExcel");
        //创建对象
        $excel = new \PHPExcel();
        //Excel表格式
        $letter = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W');
        //表头数组
        $tableheader = array('集团排名' ,'姓名','校区','职务级别','升级所需总业绩','保级所需总业绩','个人业绩(不含独立项目)','距升级差额业绩','距保级差额业绩','月业绩目标','升级所需要完成率','保级所需要完成率','升级业绩完成率',' 保级业绩完成率');
        //表格数组
        foreach($content_arr as $key => $val){
            $data[$key][0] = $val["num"];
            $data[$key][1] = $val["name"];
            $data[$key][2] = $val["school_name"];
            $data[$key][3] = $val["level"];
            $data[$key][4] = $val["upgrade_num"];
            $data[$key][5] = $val["relegation_num"];
            $data[$key][6] = $val["money_num"];
            $data[$key][7] = $val["upgrade_difference"];
            $data[$key][8] = $val["relegation_difference"];
            $data[$key][9] = $val["new_target"];
            $data[$key][10] = $val["upgrade"];
            $data[$key][11] = $val["relegation"];
            $data[$key][12] = $val["upgrade_num_num"];
            $data[$key][13] = $val["relegation_num_num"];
        }

        if($_SESSION['level_post_id'] == '18'){
            //表格数组
            foreach($content_arr as $key => $val){
                $data[$key][0] = $val["num"];
                $data[$key][1] = $val["name"];
                $data[$key][2] = $val["school_name"];
                $data[$key][3] = $val["level"];
                $data[$key][4] = $val["upgrade_num"];
                $data[$key][5] = $val["relegation_num"];
                $data[$key][6] = $val["money_num"];
                $data[$key][7] = $val["upgrade_difference"];
                $data[$key][8] = $val["relegation_difference"];
                $data[$key][9] = $val["target"];
                $data[$key][10] = $val["upgrade"];
                $data[$key][11] = $val["relegation"];
                $data[$key][12] = $val["upgrade_num_num"];
                $data[$key][13] = $val["relegation_num_num"];
            }
        }
        if($_SESSION['level_post_id'] == '11'){
            //表头数组
            $tableheader = array('集团排名' ,'姓名','校区','职务级别','校区月业绩目标','配置人数','保级所需团队完成率','升级所需个人完成率','升级所需团队其他人完成率','升级所需团队完成率','个人完成业绩','个人完成率','团队完成业绩','团队是完成率','团队其他人完成业绩','团队其他人完成率','升降情况','调整后级别','备注');
            //表格数组
            foreach($content_arr as $key => $val){
                $data[$key][0] = $val["num"];
                $data[$key][1] = $val["name"];
                $data[$key][2] = $val["school_name"];
                $data[$key][3] = $val["level"];
                $data[$key][4] = $val["new_target"];
                $data[$key][5] = $val["personal_num"];
                $data[$key][6] = $val["relegation_complete"];
                $data[$key][7] = $val["upgrade_complete"];
                $data[$key][8] = $val["other_complete"];
                $data[$key][9] = $val["all_complete"];
                $data[$key][10] = $val["money_num"];
                $data[$key][11] = $val["personal"];
                $data[$key][12] = $val["count_num"];
                $data[$key][13] = $val["count_rate"];
                $data[$key][14] = $val["other_num"];
                $data[$key][15] = $val["other_rate"];
                $data[$key][16] = $val["content_str"];
                $data[$key][17] = $val["level_last"];
                $data[$key][18] = $val["content"];
            }
        }
        if($_SESSION['level_post_id'] == '12'){
            //表头数组
            $tableheader = array('集团排名' ,'姓名','校区','职务级别','消耗业绩目标','业绩目标','保级所需消耗完成率','保级所需完成率','升级所需消耗完成率','升级所需完成率','消耗完成率','业绩完成率','升降情况',' 调整后级别','备注 ');
            //表格数组
            foreach($content_arr as $key => $val){
                $data[$key][0] = $val["num"];
                $data[$key][1] = $val["name"];
                $data[$key][2] = $val["school_name"];
                $data[$key][3] = $val["level"];
                $data[$key][4] = $val["target_consume"];
                $data[$key][5] = $val["old_target"];
                $data[$key][6] = $val["relegation_consume"];
                $data[$key][7] = $val["relegation"];
                $data[$key][8] = $val["upgrade_consume"];
                $data[$key][9] = $val["upgrade"];
                $data[$key][10] = $val["consume"];
                $data[$key][11] = $val["count_rate"];
                $data[$key][12] = $val["content_str"];
                $data[$key][13] = $val["level_last"];
                $data[$key][14] = $val["content"];
            }
        }

        //填充表头信息
        for($i = 0;$i < count($tableheader);$i++) {
            $excel->getActiveSheet()->setCellValue("$letter[$i]1","$tableheader[$i]");
        }
        //填充表格信息
        for ($i = 2;$i <= count($data) + 1;$i++) {
            $j = 0;
            foreach ($data[$i - 2] as $key=>$value) {
                $excel->getActiveSheet()->setCellValue("$letter[$j]$i","$value");
                $j++;
            }
        }
        //创建Excel输入对象
        $write = new \PHPExcel_Writer_Excel5($excel);
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename="升降级统计表.xls"');
        header("Content-Transfer-Encoding:binary");
        $write->save('php://output');
    }


    


}
?>