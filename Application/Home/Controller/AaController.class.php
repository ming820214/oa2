<?php

// 之前为解决某些数据异常情况临时写的，谨慎使用

namespace Home\Controller;
use Think\Controller;

class AaController extends Controller {
    protected $config = array('app_type' => 'personal');

/**
处理班级课出现只有一个人有课，其它人的课没有的情况
*/
    public function aa1(){//涉及订单的校区将出现错误，谨慎使用

        $date='2015-10-09';//处理的日期起点

        $m=M('hw001.stu_grade',null)->order('gid')->select();
        foreach ($m as $k => $v) {
            $m3[$v['gid']][]=$v['stuid'];
        }
        $class=M('hw001.class',null);

        $m2=$class->where(array('grade'=>array('neq',0),'timee'=>array('gt',$date)))->select();
        foreach ($m2 as $v) {
            $id=$v['id'];
            unset($v['id'],$v['stuid'],$v['timestamp']);
            $con=count($class->where($v)->select());
            if($con==1){
                //var_dump($v);die;
                $class->delete($id);
                foreach ($m3[$v['grade']] as $val) {
                    $d=$v;
                    $d['stuid']=$val;
                    if($d['stuid']&&$d['grade'])$class->add($d);
                    unset($d);echo "1";//处理的效果次数
                }
            }
        }
    }
/**
处理系统里小组学员重复相同排课的操作问题
*/
    public function aa11(){
        $w['timee']=['gt','2015-10-09'];//处理的日期起点
        $w['grade']=['neq',0];
        $mod=M('hw001.class',null);
        $list=$mod->where($w)->select();
        foreach ($list as $v) {
            $ww=$v;
            unset($ww['id'],$ww['timestamp']);
            $ww['id']=['neq',$v['id']];
            $cc=$mod->where($ww)->delete();
            if($cc)echo "1";
        }
    }

/**
处理系统里小组学员订单问题造成的重复交叉排课的操作问题
*/
    public function aa12(){
        // $w['timee']=['egt','2016-01-2'];//处理的日期起点
        $w['timee']='2017-08-18';//处理的日期起点
        $w['school']='初中旗舰校';
        $w['grade']=1999;
        // $w['grade']=['neq',0];
        $mod=M('hw001.class',null);
        $list=$mod->where($w)->order('time1 ,teacher')->select();
        var_dump($list);
        foreach ($list as $v) {
            $course_id=M('hw001.stu_grade',null)->where(['stuid'=>$v['stuid'],'gid'=>$v['grade']])->getField('course_id');
            if($course_id && $course_id!=$v['course_id'])echo $v['id'].'---'.$v['time1'].$v['time2'].$v['teacher'].'<br>';
            if($course_id && $course_id!=$v['course_id']){
                $vv=$v;
                unset($vv['id'],$vv['timestamp']);
                $vv['course_id']=$course_id;
                if($mod->where($vv)->find()){
                    echo "删除";
                    // $mod->where($v)->delete();
                }else{
                    echo "修改";
                }
            }
        }
    }
/**
员工档案处理去掉pid
*/
    public function aa2(){
        $m=M('hw003.person_info2',null)->select();
        foreach ($m as $v) {
            $v['id']=$v['pid'];
            M('hw003.person_info',null)->add($v);
        }
    }

    // public function aa3(){//学员换老师
    //     $w['stuid']=array('in','3578,3579');
    //     $w['timee']=array('gt','2015-05-03');
    //     $w['class']='数学';
    //     $w['teacher']='赵玲';
    //     $d['teacher']='于斌';
    //     $con=M('hw001.class',null)->where($w)->save($d);
    //     echo($con);
    // }

    public function aa4(){//班级课不完整处理
        $class=M('hw001.class',null);
        $grade=M('hw001.stu_grade',null);
        $w['timee']=array('gt',date('Y-m-d'));//需要设置开始时间
        $w['grade']=array('neq',0);
        $m=$class->where($w)->select();
        foreach ($m as $v) {
            unset($v['id'],$v['stuid'],$v['timestamp']);
            $con1=count($class->where($v)->select());
            $sids=$grade->where(array('gid'=>$v['grade']))->getField('stuid',true);
            if(count($sids)!=$con1){
                // var_dump($v);die;
                $class->where($v)->delete();
                foreach ($sids as $val) {
                    $v['stuid']=$val;
                    $class->add($v);
                }
                echo "1";
            }
        }
    }
    
    public function process_bj_class($begin,$end){//班级课不完整处理 add by zhangxm
    	$class=M('hw001.class',null);
    	$grade=M('hw001.stu_grade',null);
    	$stu = M('hw001.student',null);
    	if($begin){
    		$w['timee']=array(array('egt',$begin),array('elt',$end));//需要设置开始时间
    	}else{
    		$w['timee']=array('egt',date('Y-m-d'));//需要设置开始时间
    	}
    	
    	$w['grade']=array('neq',0);
    	$m=$class->where($w)->select();
    	foreach ($m as $v) {
    		//unset($v['id'],$v['stuid'],$v['timestamp'],$v['std_id'],$v['course_id'],$v['state']);
    		$c['school'] = $v['school'];
    		$c['grade'] = $v['grade'];
    		$c['tid'] = $v['tid'];
    		$c['teacher'] = $v['teacher'];
    		$c['class'] = $v['class'];
    		$c['time1'] = $v['time1'];
    		$c['time2'] = $v['time2'];
    		$c['timee'] = $v['timee'];
    		$c['count'] = $v['count'];
    		
    		$con1=count($class->where($c)->select());
    		//$sids=$grade->where(array('gid'=>$v['grade']))->getField('stuid',true);
    		$grade_list = $grade->where(array('gid'=>$v['grade']))->select();
    		//$sids = array_column($grade_list,'stuid');
    		if(count($grade_list)!=$con1){

    			//$class->where($v)->delete();
    			foreach ($grade_list as $val) {
    				if($val['stuid'] != $v['stuid']){
    					unset($v['id'],$v['stuid'],$v['timestamp'],$v['std_id'],$v['course_id'],$v['cwqr']);
    					$v['stuid']=$val['stuid'];
    					$v['course_id'] = $val['course_id'];
    					$v['std_id'] = $stu->where(['id'=>$val['stuid'],'state'=>1])->getField('std_id');
    					$v['cwqr'] = '';
    					if($v['std_id']){
    						$class->add($v);
    					}
    					
    				}
    				
    			}
    			echo "1";
    		}
    	}
    }
    
    //针对具体校区，具体班级的班级课不完整处理,缺课的补上即可，不缺课的不进行处理 add by zhangxm
   /*  public function process_bj_class_school($begin,$end,$graded,$school){
      
        $class=M('hw001.class',null);
        $grade=M('hw001.stu_grade',null);
        $stu = M('hw001.student',null);
        if($begin){
            $w['timee']=array(array('egt',$begin),array('elt',$end));//需要设置开始时间
        }else{
            $w['timee']=array('egt',date('Y-m-d'));//需要设置开始时间
        }
        
        $w['grade']=$graded;
        $w['school']=$school;
        $m=$class->where($w)->select();
        foreach ($m as $v) {
            //unset($v['id'],$v['stuid'],$v['timestamp'],$v['std_id'],$v['course_id'],$v['state']);
            $c['school'] = $v['school'];
            $c['grade'] = $v['grade'];
            $c['tid'] = $v['tid'];
            $c['teacher'] = $v['teacher'];
            $c['class'] = $v['class'];
            $c['time1'] = $v['time1'];
            $c['time2'] = $v['time2'];
            $c['timee'] = $v['timee'];
            $c['count'] = $v['count'];
            
            $con1=count($class->where($c)->select());
            
            $stuids = $class->where($c)->getField('stuid',true);
            //$sids=$grade->where(array('gid'=>$v['grade']))->getField('stuid',true);
            $grade_list = $grade->where(array('gid'=>$v['grade']))->select();
            //$sids = array_column($grade_list,'stuid');
            if(count($grade_list)!=$con1){
                
                //$class->where($v)->delete();
                foreach ($grade_list as $val) {
                    if($val['stuid'] != $v['stuid'] && !in_array($val['stuid'],$stuids)){
                        unset($v['id'],$v['stuid'],$v['timestamp'],$v['std_id'],$v['course_id'],$v['cwqr']);
                        $v['stuid']=$val['stuid'];
                        $v['course_id'] = $val['course_id'];
                        $v['std_id'] = $stu->where(['id'=>$val['stuid'],'state'=>1])->getField('std_id');
                        $v['cwqr'] = '';
                        if($v['std_id']){
                            $class->add($v);
                            echo "2";
                        }
                        
                    }
                    
                }
                echo "1";
            }
        }
    }
    
    
    //针对具体校区，具体班级的班级课不完整处理,缺课的补上即可，不缺课的不进行处理 add by zhangxm
    public function process_bj_classSchool($graded,$school){
        
        $class=M('hw001.class',null);
        $grade=M('hw001.stu_grade',null);
        $stu = M('hw001.student',null);
        
        
        $w['grade']=$graded;
        $w['school']=$school;
        $m=$class->where($w)->select();
        foreach ($m as $v) {
            //unset($v['id'],$v['stuid'],$v['timestamp'],$v['std_id'],$v['course_id'],$v['state']);
            $c['school'] = $v['school'];
            $c['grade'] = $v['grade'];
            $c['tid'] = $v['tid'];
            $c['teacher'] = $v['teacher'];
            $c['class'] = $v['class'];
            $c['time1'] = $v['time1'];
            $c['time2'] = $v['time2'];
            $c['timee'] = $v['timee'];
            $c['count'] = $v['count'];
            
            $con1=count($class->where($c)->select());
            
            $stuids = $class->where($c)->getField('stuid',true);
            //$sids=$grade->where(array('gid'=>$v['grade']))->getField('stuid',true);
            $grade_list = $grade->where(array('gid'=>$v['grade']))->select();
            //$sids = array_column($grade_list,'stuid');
            if(count($grade_list)!=$con1){
                
                //$class->where($v)->delete();
                foreach ($grade_list as $val) {
                    if($val['stuid'] != $v['stuid'] && !in_array($val['stuid'],$stuids)){
                        unset($v['id'],$v['stuid'],$v['timestamp'],$v['std_id'],$v['course_id'],$v['cwqr']);
                        $v['stuid']=$val['stuid'];
                        $v['course_id'] = $val['course_id'];
                        $v['std_id'] = $stu->where(['id'=>$val['stuid'],'state'=>1])->getField('std_id');
                        $v['cwqr'] = '';
                        if($v['std_id']){
                            $class->add($v);
                            echo "2";
                        }
                        
                    }
                    
                }
                echo "1";
            }
        }
    }
     */
/**
员工薪酬主表添加
*/
    public function aa5(){
        $md=M('hw003.person_xc1',null);
        $pid=M('hw003.person_all',null)->getField('id',true);
        foreach ($pid as $v) {
            $w['pid']=$v;
            $w['date']='2015-03';
            if(!$md->where($w)->find())$md->add($w);
            $w['date']='2015-04';
            if(!$md->where($w)->find())$md->add($w);
            $w['date']='2015-05';
            if(!$md->where($w)->find())$md->add($w);
        }
    }

/**
高三学生批量添加退费原因
*/
    public function aa6(){
        $w['grade']='高三';
        $w['school']='天丽校区';
        $w['state']=3;
        $w['why3']='';
        $w['other']=array('notlike',"%家长未签字%");
        $w['date']='2015-06';
        $d['why3']='高三结课';
        $con=M('hw003.money_return',null)->where($w)->save($d);
        echo($con);
    }

/**
系统排课数据统计
*/
    public function aa7(){
        $w['timee']=array('between','2014-07-01,2015-07-01');
        $w['grade']=0;
        // $w['state']=1;
        $km=array('数学','物理','化学','生物','政治','历史','地理','英语','语文');
        foreach ($km as $v) {
            $w['class']=$v;
            $data1[$v]=M('hw001.class',null)->where($w)->sum('count');
        }

        $w2['timee']=array('between','2014-07-01,2015-07-01');
        $w2['grade']=array('gt',0);
        // $w2['state']=1;
        $m=M('hw001.class',null)->where($w2)->order('school,timee,grade,time1')->field('school,timee,grade,time1,teacher,count,class')->select();
        foreach ($m as $v) {
            if($v!=$aa)$data2[$v['class']]+=$v['count'];
            $aa=$v;
        }

        foreach ($km as $v) {
            $data[$v]=$data1[$v]+$data2[$v];
        }

        print_r($data);
    }

/**
给ask申请表中的pid绑定
*/
    public function aa8(){
        $m=M('hw003.person_ask',null)->where(array('pid'=>0))->select();
        foreach ($m as $v) {
            $pid=M('hw003.person_all',null)->where(array('name'=>$v['name']))->getField('id');
            if(!is_array($pid)&&$pid){
                M('hw003.person_ask',null)->where(array('id'=>$v['id']))->setField('pid',$pid);
            }else{
                var_dump($pid);
                var_dump($v['name']);
            }
        }
    }
/**
帮老师去掉部分授课反馈
*/
    public function aa9(){
        die;
        // $w['grade']=1104;
        // $w['timee']=array('lt','2015-09-01');
        // $w['school']=['like','%'.'特训营'];
        $w['fankui']=0;
        $con=M('hw001.class',null)->where($w)->setField('fankui',1);
        var_dump($con);
    }

/**
同步考勤系统的员工考勤id
*/
    public function kq_user(){
        $ch = curl_init();
        $time = time();
        $data = array(
        'account'=>'21c4a357f585a1a50ea794fcf96fad73',//API帐号
        'requesttime'=>$time,//请求时间，与服务器时间差不能超过60秒
        );
        //接口参数
        ksort($data);
        $sign = md5(join('',$data).'hongwenhr001');
        $data['sign'] = $sign;
        curl_setopt($ch, CURLOPT_URL, "http://kq.qycn.com/index.php/Api/Api/getEmployee?".http_build_query($data));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $retjson = curl_exec($ch); //返回的数据，json格式
        curl_close($ch);
        $m=json_decode($retjson)->data->totalpage;
        $m2=array();
        for ($i=1; $i <= $m ; $i++) {

            $ch = curl_init();
            $time = time();
            $data = array(
            'account'=>'21c4a357f585a1a50ea794fcf96fad73',//API帐号
            'requesttime'=>$time,//请求时间，与服务器时间差不能超过60秒
            );
            $data['page']=$i;
            //接口参数
            ksort($data);
            $sign = md5(join('',$data).'hongwenhr001');
            $data['sign'] = $sign;
            curl_setopt($ch, CURLOPT_URL, "http://kq.qycn.com/index.php/Api/Api/getEmployee?".http_build_query($data));
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $retjson = curl_exec($ch); //返回的数据，json格式
            curl_close($ch);
            $m3=json_decode($retjson)->data->userData;
            if($m3)$m2=array_merge($m2,$m3);
        }

        foreach ($m2 as $v) {
            $w['name']=$v->realname;
            $d['cc']=$v->account;
            M('hw003.person_all',null)->where($w)->save($d);
        }
    }

/**

*/

    //打卡明细核准开始--截止
    public function ccc(){
        $month='2015-08';
        $w['date']=array('like',"$month%");

        $dd=M('hw003.person_kq2',null)->where($w)->delete();
        $m=M('hw003.person_kq',null)->where($w)->order('date,cc,time')->select();

        $all=M('hw003.person_all',null)->getField('cc,id,school,position,name',true);
        foreach ($m as $v){
            if($v['cc']==$cc&&$v['date']==$day&&$v['time']>$time){
                $dat[$v['cc']][$v['date']]['t2']=$v['time'];
            }else{
                $dat[$v['cc']][$v['date']]['date']=$v['date'];
                $dat[$v['cc']][$v['date']]['t1']=$v['time'];
                $dat[$v['cc']][$v['date']]['t2']=$v['time'];
            }
            $cc=$v['cc'];
            $day=$v['date'];
            $time=$v['time'];
        }
        // echo(count($dat));die;

        foreach ($dat as $k => $v) {
            foreach ($v as $val) {
                if($all[$k]['id'])
                $ww[]=array('pid'=>$all[$k]['id'],'cc'=>$k,'date'=>$val['date'],'t1'=>$val['t1'],'t2'=>$val['t2']);
            }
        }
        // $cs=count($ww);
        // var_dump($cs);die;
                M('hw003.person_kq2',null)->addAll($ww);
    }

    //获取某月的考勤规则
    public function kq_kq_rule($pid,$month){
        $ruled=M('hw003.person_kq_ruled',null)->where(array('pid'=>$pid,'date'=>$month))->getField('rules');//获取考勤组id
        $ruleid=M('hw003.person_kq_rules',null)->find($ruled);
        $rules=explode(',',$ruleid['ruleid']);//获取各周的考勤规则id
        foreach ($rules as $v) {
            $rule[]=M('hw003.person_kq_rule',null)->find($v);
        }
        $c=strtotime($month.'-01');
        $monday=$c-((date('w',$c)==0?7:date('w',$c))-1)*24*3600;
        foreach ($rule as $v) {
            $kq[date('Y-m-d',$monday)]['t1']=($v['m1a']!='00:00:00')?strtotime(date('Y-m-d',$monday).' '.$v['m1a']):0;
            $kq[date('Y-m-d',$monday)]['t2']=($v['m1b']!='00:00:00')?strtotime(date('Y-m-d',$monday).' '.$v['m1b']):0;
                $monday+=24*3600;
            $kq[date('Y-m-d',$monday)]['t1']=($v['m2a']!='00:00:00')?strtotime(date('Y-m-d',$monday).' '.$v['m2a']):0;
            $kq[date('Y-m-d',$monday)]['t2']=($v['m2b']!='00:00:00')?strtotime(date('Y-m-d',$monday).' '.$v['m2b']):0;
                $monday+=24*3600;
            $kq[date('Y-m-d',$monday)]['t1']=($v['m3a']!='00:00:00')?strtotime(date('Y-m-d',$monday).' '.$v['m3a']):0;
            $kq[date('Y-m-d',$monday)]['t2']=($v['m3b']!='00:00:00')?strtotime(date('Y-m-d',$monday).' '.$v['m3b']):0;
                $monday+=24*3600;
            $kq[date('Y-m-d',$monday)]['t1']=($v['m4a']!='00:00:00')?strtotime(date('Y-m-d',$monday).' '.$v['m4a']):0;
            $kq[date('Y-m-d',$monday)]['t2']=($v['m4b']!='00:00:00')?strtotime(date('Y-m-d',$monday).' '.$v['m4b']):0;
                $monday+=24*3600;
            $kq[date('Y-m-d',$monday)]['t1']=($v['m5a']!='00:00:00')?strtotime(date('Y-m-d',$monday).' '.$v['m5a']):0;
            $kq[date('Y-m-d',$monday)]['t2']=($v['m5b']!='00:00:00')?strtotime(date('Y-m-d',$monday).' '.$v['m5b']):0;
                $monday+=24*3600;
            $kq[date('Y-m-d',$monday)]['t1']=($v['m6a']!='00:00:00')?strtotime(date('Y-m-d',$monday).' '.$v['m6a']):0;
            $kq[date('Y-m-d',$monday)]['t2']=($v['m6b']!='00:00:00')?strtotime(date('Y-m-d',$monday).' '.$v['m6b']):0;
                $monday+=24*3600;
            $kq[date('Y-m-d',$monday)]['t1']=($v['m7a']!='00:00:00')?strtotime(date('Y-m-d',$monday).' '.$v['m7a']):0;
            $kq[date('Y-m-d',$monday)]['t2']=($v['m7b']!='00:00:00')?strtotime(date('Y-m-d',$monday).' '.$v['m7b']):0;
                $monday+=24*3600;
        }
        return $kq;
    }

    //考勤记录计算
    function kq($pid,$month){
        $t=date('t',strtotime($month.'-01'));
        $rule=$this->kq_kq_rule($pid,$month);
        $kq=M('hw003.person_kq2',null)->where(array('pid'=>$pid,'date'=>array('like',$month."%")))->getField('date,t1,t2');
        $month=strtotime($month.'-01');
        for ($i=0; $i < $t; $i++) {
            $day=date('Y-m-d',$month+$i*24*3600);
            if($rule[$day]['t1']){//有上班时间规则开始计算
                $data['r']++;//应出勤天数
                $data['rt']++;//应出勤秒
                if(!isset($kq[$day])){//都没打卡，旷工
                    $data['dd'][]=$day;
                }elseif ($kq[$day]['t1']==$kq[$day]['t2']) {//打一次卡，未打卡
                    $data['cc'][]=$day;
                }elseif ($kq[$day['t1']]>$rule[$day]['t1']) {//首次打卡时间大于规则，迟到（也会包含早退的情况）
                    $data['aa'][]=$day;
                }elseif ($kq[$day]['t1']<=$rule[$day]['t1']&&$kq[$day]['t2']<$rule[$day]['t2']) {//首次正常，第二次早退
                    $data['bb'][]=$day;
                }
            }
        }
        return $data;
    }

    //相关申请数据,type:1请假，2加班，3灵活作息，4意外事项
    function kq_apply($pid,$date,$type=0){
        // $w['state']='审核通过';
        $w['pid']=$pid;
        $mod=M('hw003.person_ask',null);
        if($type){
            switch ($type) {
                case '1':
                    $w['class']='请假';
                    $w['time1']=array('lt',$date.' 23:59:59');
                    $w['time2']=array('gt',$date.' 23:59:59');
                    return $mod->where($w)->find();
                case '2':
                    $w['class']='加班';
                    $w['time1']=array('lt',$date.' 23:59:59');
                    $w['time2']=array('gt',$date.' 23:59:59');
                    return $mod->where($w)->find();
                case '3':
                    $w['class']='灵活作息';
                    $w['date']=$date;
                    return $mod->where($w)->find();
                case '4':
                    $w['class']='意外事项';
                    $w['date']=$date;
                    return $mod->where($w)->find();
                default:
                    break;
            }
        }else{
            $w['class']='请假';
            $w['time1']=array('lt',$date.' 23:59:59');
            $w['time2']=array('gt',$date.' 23:59:59');
            $data[]=$mod->where($w)->select();
            $w['class']='加班';
            $data[]=$mod->where($w)->select();
            unset($w['time1'],$w['time2']);
            $w['class']='灵活作息';
            $w['date']=$date;
            $data[]=$mod->where($w)->select();
            $w['class']='意外事项';
            $data[]=$mod->where($w)->select();
            return $data;
        }
    }

    public function kq_kq(){
        $w['state']=1;//之后需要考虑当月离职人员
        $w['school']='日月兴城';
        $m=M('hw003.person_all',null)->where($w)->getfield('id,name,school,part,position',true);
        foreach ($m as $k => $v) {
            $kq=M('hw003.person_kq_ruled',null)->where(array('date'=>session('date'),'pid'=>$k))->find();
            if($kq){
                $m['kq']=$kq;
            }else{
                $d=$this->kq($k,session('date'));
                $d['pid']=$k;
                $d['date']=session('date');
                M('hw003.person_kq_ruled',null)->add($d);
                $m['kq']=$d;
            }
        }
    }


    
    //当班级成员变更时课表调整重置
     function reset_grade_class($gid){

        $date='2016-01-25';

        $stuids=M('hw001.stu_grade',null)->where(['gid'=>$gid])->select();//获取班级的成员
        foreach ($stuids as $k=>$v) {
            $stuids[$k]['std_id']=M('hw001.student',null)->where(['id'=>$v['stuid']])->getField('std_id');
            unset($stuids[$k]['id'],$stuids[$k]['name'],$stuids[$k]['gid'],$stuids[$k]['school'],$stuids[$k]['timestamp']);
        }
        // var_dump($stuids);die;
        if(empty($stuids))return;
        $class=M('hw001.class',null)->where([
            'grade'=>$gid,
            // 'state'=>0,
            'timee'=>['gt',$date]
            ])->select();
        if(!$class)return;
        //取出不重复的一组排课记录
        foreach ($class as $k=>&$v) {
            $ids[]=$v['id'];
            unset($v['id'],$v['stuid'],$v['std_id'],$v['course_id'],$v['timestamp']);
            if(in_array($v['timee'].$v['time1'],$cc))unset($class[$k]);
            if($v)$cc[]=$v['timee'].$v['time1'];
        }
        //全体删除一次
        M('hw001.class',null)->where(['id'=>['in',$ids]])->delete();
        // 给全体成员重新添加一次
        foreach ($class as $c) {
            foreach ($stuids as $s) {
                if($s['std_id'])
                $data[]=array_merge($c,$s);
            }
        }
        M('hw001.class',null)->addAll($data);
        return true;
    }

/**
bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb
*/

    // 去除业务模块批量导入产生的重复数据，删除一下
    public function bb1(){
        $mod=M('yewu_students');
        $aa=$mod->Field('tel1')->limit(1000)->select();
        //把重复的电话数据提取出来
        foreach ($aa as $v) {
            if($v['tel1']){
                if(in_array($v['tel1'],$tmp))$dd[]=$v['tel1'];
                $tmp[]=$v['tel1'];
            }
        }
        $dd=array_unique($dd);
        var_dump($dd);die;
        foreach ($dd as $v) {
            $ee=$mod->where(['tel1'=>$v])->field('id,name,other,grade')->select();
            

            unset($ee);
        }


    }

    //去除试听课的大量重复问题
    public function bb2(){
        $w['school']='初中旗舰校';
        $w['stuid']=88888;
        $w['other']='徐梓熙';
        $w['state']=0;
        $w['timeee']=['gt','2015-09-17'];
        $m=M('hw001.class',null)->where($w)->select();
        foreach ($m as $v) {
            $v['id']=['neq',$v['id']];
            unset($v['timestamp']);
            M('hw001.class',null)->where($v)->delete();
        }
    }

    public function bb3(){
        die;
        $w=[            
            'timee'=>['gt','2016-02-25'],
            'school'=>['in','松原校区']
        ];
        $data=M('hw001.class',null)->where($w)->select();
        // var_dump($data);die;
        if(M('test.class',null)->addAll($data))
            M('hw001.class',null)->where($w)->delete();
    }

    public function bb4(){

        $data=M('hw001.student',null)->where('state=1')->getField('id,school,name');
        foreach ($data as $k=>$v) {
            if(M('hw001.student',null)->where(['id'=>['neq',$k],'state'=>1,'name'=>$v['name'],'school'=>$v['school']])->find()){
                echo($v['name']);
                echo "..............";
                echo($v['school']);
                echo "<br/>";
            }
        }

    }



}
?>
