<?php
namespace Home\Controller;

class TongjiController extends HomeController {
    public function index(){

        //循环校区
        $w['school']=array('neq','集团');
        $school=M('hw001.school',null)->where($w)->select();
        foreach ($school as  $value1) {
            //循环校区查询
            $aa['school']=$value1['school'];
            if($_POST['time']){
                $day=$_POST['time'];
                $date=date('Y-m',strtotime($day));
            }else{
                $date=date('Y-m');
                $day=date('Y-m-d');
            }
            $aa['timee']=array('like',"$date%");
            $aa['state']=array('NEQ',2);
            $aa['stuid'] = array('not in',array('77777','88888','99999'));
            $class=M('hw001.class',null)->where($aa)->order('timee asc,grade asc,time1 asc,class asc,teacher asc,state asc')->select();

                    $ss=$value1['school'];
                    // 阜新二部、阜新校区合并统计为阜新实验校区
//                  if($ss=='阜新二部' || $ss=='阜新校区')$ss='阜新实验校区';
					if($ss=='阜新二部' || $ss=='阜新实验校区')$ss='阜新实验校区';

                    foreach ($class as $classl) {
                        if(!($classl['timee']==$a&&$classl['time1']==$b&&$classl['time2']==$c&&$classl['class']==$d&&$classl['teacher']==$e)){
                            switch ($classl['class']) {
                                case '数学':
                                    $vm["$ss"]['数学']+=$classl['count'];
                                    if($classl['timee']==$day)$vd["$ss"]['数学']+=$classl['count'];
                                    break;
                                case '语文':
                                    $vm["$ss"]['语文']+=$classl['count'];
                                    if($classl['timee']==$day)$vd["$ss"]['语文']+=$classl['count'];
                                    break;
                                case '英语':
                                    $vm["$ss"]['英语']+=$classl['count'];
                                    if($classl['timee']==$day)$vd["$ss"]['英语']+=$classl['count'];
                                    break;
                                case '物理':
                                    $vm["$ss"]['物理']+=$classl['count'];
                                    if($classl['timee']==$day)$vd["$ss"]['物理']+=$classl['count'];
                                    break;
                                case '化学':
                                    $vm["$ss"]['化学']+=$classl['count'];
                                    if($classl['timee']==$day)$vd["$ss"]['化学']+=$classl['count'];
                                    break;
                                case '生物':
                                    $vm["$ss"]['生物']+=$classl['count'];
                                    if($classl['timee']==$day)$vd["$ss"]['生物']+=$classl['count'];
                                    break;
                                case '政治':
                                    $vm["$ss"]['政治']+=$classl['count'];
                                    if($classl['timee']==$day)$vd["$ss"]['政治']+=$classl['count'];
                                    break;
                                case '历史':
                                    $vm["$ss"]['历史']+=$classl['count'];
                                    if($classl['timee']==$day)$vd["$ss"]['历史']+=$classl['count'];
                                    break;
                                case '地理':
                                    $vm["$ss"]['地理']+=$classl['count'];
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

        }
        //月度每日变化量统计
        $w['date']=date('Y-m-d',strtotime($day)-24*3600);
        $b=M('hw001.tongji',null)->where($w)->select();

        foreach ($b as $val){
            $hj=$val['a']+$val['b']+$val['c']+$val['d']+$val['e']+$val['f']+$val['g']+$val['h']+$val['i'];
            // 阜新二部、阜新校区合并统计为阜新实验校区
//          if($val['school']=='阜新二部' || $val['school']=='阜新校区'){
			if($val['school']=='阜新二部' || $val['school']=='阜新实验校区'){
                $bh['阜新实验校区']['a']+=$val['a'];
                $bh['阜新实验校区']['b']+=$val['b'];
                $bh['阜新实验校区']['c']+=$val['c'];
                $bh['阜新实验校区']['d']+=$val['d'];
                $bh['阜新实验校区']['e']+=$val['e'];
                $bh['阜新实验校区']['f']+=$val['f'];
                $bh['阜新实验校区']['g']+=$val['g'];
                $bh['阜新实验校区']['h']+=$val['h'];
                $bh['阜新实验校区']['i']+=$val['i'];
                $bh['阜新实验校区']['hj']+=$hj;
            }else{
                $bh[$val['school']]=array('a'=>$val['a'],'b'=>$val['b'],'c'=>$val['c'],'d'=>$val['d'],'e'=>$val['e'],'f'=>$val['f'],'g'=>$val['g'],'h'=>$val['h'],'i'=>$val['i'],'bh'=>$hj);
            }
        }

        // 排序
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
        $this->bh=$bh;
        $this->ms=$date;
        $this->dayy=$day;
        $this->vd=$vdd;
        $this->vm=$vmm;
        // var_dump($vd);
        // $this->display('index2');
        $this->display();
    }

    public function teacher(){
        // die('功能维护中……');
            //查询校区
            if($_POST['school']=='所有校区'){
            }elseif($_POST['school']){
//              $aa['school']=I('post.school');
			if($_POST['school'] == '阜新实验校区' || $_POST['school'] == '阜新二部'){
				$aa['school']= array('in',('阜新实验校区,阜新二部'));
			}else{
				$aa['school']=I('post.school');
			}
			
            }else{
                $aa['school']='######';
            };
            
        if(get_school_name() != '集团' ){
//			   $aa['school']=get_school_name();    
			if(get_school_name() == '阜新实验校区' || get_school_name() == '阜新二部'){
				$aa['school']= array('in',('阜新实验校区,阜新二部'));
			}else{
				$aa['school']=get_school_name();
			}    	
        }

		
            if($_POST['teacher'])$aa['teacher']=I('post.teacher');

            if($_POST['time']){
                $day=$_POST['time'];
                $date=date('Y-m',strtotime($day));
            }else{
                $date=date('Y-m');
                $day=date('Y-m-d');
            }
            $aa['timee']=array('like',"$date%");
            if($_POST['time']&&$_POST['time2'])$aa['timee']=array('between',[$_POST['time'],$_POST['time2']]);
            $aa['state']=array('NEQ',2);
            $class=M('hw001.class',null)->where($aa)->order('school asc,class asc,teacher asc,grade asc,timee asc,time1 asc')->field('class,teacher,timee,time1,grade,count')->select();
					if(get_school_name() == '阜新实验校区' || get_school_name() == '阜新二部'){
							$aa['school']= '阜新实验校区';
						}else{
							$aa['school']=get_school_name();
						}  
                // $a['class']='数学';
                    $ss=$aa['school'];
                    $tw[$ss]=array();//本周数据====================
                    $tm[$ss]=array();//本月数据====================
                    //获取本周一的时间
                    $p=strtotime($day);
                    $eee=$p-((date('w',$p)==0?7:date('w',$p))-1)*86400;
                    $moday=$eee;
                    $weday=$eee+2*24*3600;
                    $saday=$eee+5*24*3600;
                    $weekend=$eee+7*24*3600;
                    $weekarray=array("0","1","2","3","4","5","6");//为循环判断周几用的

                    foreach ($class as $v){
                        if($v['grade']==0){
                            $tm[$ss][$v['class']][$v['teacher']]['已排课时']+=$v['count'];
                            $tm[$ss][$v['class']][$v['teacher']]['name']=$v['teacher'];
                            $tm[$ss][$v['class']][$v['teacher']]['class']=$v['class'];
                                //================================
                            if($weekarray[date("w",strtotime($v['timee']))] == 1 or $weekarray[date("w",strtotime($v['timee']))]== 2){//周一、周二（月）统计
                                    $tw[$ss][$v['class']][$v['teacher']]['name']=$v['teacher'];
                                    $tw[$ss][$v['class']][$v['teacher']]['class']=$v['class'];
                                if(strtotime($v['timee']) >= $moday && strtotime($v['timee']) < $weday){//周一、周二月（周）统计
                                    $tw[$ss][$v['class']][$v['teacher']]['已排课时']+=$v['count'];
                                    $tw["$ss"][$v['class']][$v['teacher']]['12']+=$v['count'];
                                    if($v['time1']>='21:30')$tw["$ss"][$v['class']][$v['teacher']]['129']+=$v['count'];
                                }
                                $tm[$ss][$v['class']][$v['teacher']]['12']+=$v['count'];
                                if($v['time1']>='21:30')$tm["$ss"][$v['class']][$v['teacher']]['129']+=$v['count'];
                            }elseif($weekarray[date("w",strtotime($v['timee']))] >= 3 && $weekarray[date("w",strtotime($v['timee']))] <= 5){//周一、周二（月）统计
                                    $tw[$ss][$v['class']][$v['teacher']]['name']=$v['teacher'];
                                    $tw[$ss][$v['class']][$v['teacher']]['class']=$v['class'];
                                if(strtotime($v['timee']) >= $weday && strtotime($v['timee']) < $saday){//周一、周二月（周）统计
                                    $tw[$ss][$v['class']][$v['teacher']]['已排课时']+=$v['count'];
                                    $tw["$ss"][$v['class']][$v['teacher']]['345']+=$v['count'];
                                    if($v['time1']>='21:30')$tw["$ss"][$v['class']][$v['teacher']]['3459']+=$v['count'];
                                }
                                $tm[$ss][$v['class']][$v['teacher']]['345']+=$v['count'];
                                if($v['time1']>='21:30')$tm["$ss"][$v['class']][$v['teacher']]['3459']+=$v['count'];
                            }elseif($weekarray[date("w",strtotime($v['timee']))] == 6 or $weekarray[date("w",strtotime($v['timee']))]== 0){//周一、周二（月）统计
                                    $tw[$ss][$v['class']][$v['teacher']]['name']=$v['teacher'];
                                    $tw[$ss][$v['class']][$v['teacher']]['class']=$v['class'];
                                if(strtotime($v['timee']) >= $saday && strtotime($v['timee']) < $weekend){//周一、周二月（周）统计
                                    $tw[$ss][$v['class']][$v['teacher']]['已排课时']+=$v['count'];
                                    $tw["$ss"][$v['class']][$v['teacher']]['67']+=$v['count'];
                                    if($v['time1']>='21:30')$tw["$ss"][$v['class']][$v['teacher']]['679']+=$v['count'];
                                }
                                $tm[$ss][$v['class']][$v['teacher']]['67']+=$v['count'];
                                if($v['time1']>='21:30')$tm["$ss"][$v['class']][$v['teacher']]['679']+=$v['count'];
                            }
                                //======================================
                        }elseif($v['grade'] == $g && $v['time1']==$t1 && $v['timee']==$time && $v['teacher']==$teacher && $v['class']== $clas){
                        }else{
                            $tm[$ss][$v['class']][$v['teacher']]['已排课时']+=$v['count'];
                            $tm[$ss][$v['class']][$v['teacher']]['name']=$v['teacher'];
                            $tm[$ss][$v['class']][$v['teacher']]['class']=$v['class'];
                                //================================
                            if($weekarray[date("w",strtotime($v['timee']))] == 1 or $weekarray[date("w",strtotime($v['timee']))]== 2){//周一、周二（月）统计
                                    $tw[$ss][$v['class']][$v['teacher']]['name']=$v['teacher'];
                                    $tw[$ss][$v['class']][$v['teacher']]['class']=$v['class'];
                                if(strtotime($v['timee']) >= $moday && strtotime($v['timee']) < $weday){//周一、周二月（周）统计
                                    $tw[$ss][$v['class']][$v['teacher']]['已排课时']+=$v['count'];
                                    $tw["$ss"][$v['class']][$v['teacher']]['12']+=$v['count'];
                                    if($v['time1']>='21:30')$tw["$ss"][$v['class']][$v['teacher']]['129']+=$v['count'];
                                }
                                $tm[$ss][$v['class']][$v['teacher']]['12']+=$v['count'];
                                if($v['time1']>='21:30')$tm["$ss"][$v['class']][$v['teacher']]['129']+=$v['count'];
                            }elseif($weekarray[date("w",strtotime($v['timee']))] >= 3 && $weekarray[date("w",strtotime($v['timee']))] <= 5){//周一、周二（月）统计
                                    $tw[$ss][$v['class']][$v['teacher']]['name']=$v['teacher'];
                                    $tw[$ss][$v['class']][$v['teacher']]['class']=$v['class'];
                                if(strtotime($v['timee']) >= $weday && strtotime($v['timee']) < $saday){//周一、周二月（周）统计
                                    $tw[$ss][$v['class']][$v['teacher']]['已排课时']+=$v['count'];
                                    $tw["$ss"][$v['class']][$v['teacher']]['345']+=$v['count'];
                                    if($v['time1']>='21:30')$tw["$ss"][$v['class']][$v['teacher']]['3459']+=$v['count'];
                                }
                                $tm[$ss][$v['class']][$v['teacher']]['345']+=$v['count'];
                                if($v['time1']>='21:30')$tm["$ss"][$v['class']][$v['teacher']]['3459']+=$v['count'];
                            }elseif($weekarray[date("w",strtotime($v['timee']))] == 6 or $weekarray[date("w",strtotime($v['timee']))]== 0){//周一、周二（月）统计
                                    $tw[$ss][$v['class']][$v['teacher']]['name']=$v['teacher'];
                                    $tw[$ss][$v['class']][$v['teacher']]['class']=$v['class'];
                                if(strtotime($v['timee']) >= $saday && strtotime($v['timee']) < $weekend){//周一、周二月（周）统计
                                    $tw[$ss][$v['class']][$v['teacher']]['已排课时']+=$v['count'];
                                    $tw["$ss"][$v['class']][$v['teacher']]['67']+=$v['count'];
                                    if($v['time1']>='21:30')$tw["$ss"][$v['class']][$v['teacher']]['679']+=$v['count'];
                                }
                                $tm[$ss][$v['class']][$v['teacher']]['67']+=$v['count'];
                                if($v['time1']>='21:30')$tm["$ss"][$v['class']][$v['teacher']]['679']+=$v['count'];
                            }
                                //======================================
                        }

                        $g = $v['grade'];
                        $t1 = $v['time1'];
                        $time = $v['timee'];
                        $teacher = $v['teacher'];
                        $clas = $v['class'];
                    }

        $tw=$this->sortx($tw);
        $tm=$this->sortx($tm);

        $this->ms=$date;//输出查询的月份
        $this->dayy=$day;//输出查询的周份
        $sc=M('hw001.school',null)->select();
        $this->sc=$sc;
        $this->tm=$tm;
        if(!($_POST['time']&&$_POST['time2']))$this->tw=$tw;
        $this->school=$aa['school'];

        $this->display();

    }







        //内部排序调用
    public function sortx($tmk){

        //组装成一维数组
        foreach ($tmk as $school) {
                foreach ($school as $val) {
                    foreach ($val as $v) {
                        $arr[]=$v;
                    }
                }
        }
        //数组排序
        for($i=0;$i<count($arr)-1;$i++){//循环比较
            for($j=$i+1;$j<count($arr);$j++){
                if($arr[$j]['已排课时']>$arr[$i]['已排课时']){//执行交换
                $temp=$arr[$i];
                $arr[$i]=$arr[$j];
                $arr[$j]=$temp;
                }
            }
        }
        //重新抽取分组
        $g=array();
        for ($v=0; $v < count($arr); $v++) {
            $g[$arr[$v]['class']][$arr[$v]['name']]=$arr[$v];
        }
        //重新转换成一维数组做排序
        foreach ($g as $a) {
            foreach ($a as $b) {
                if($b['class']=='数学')$gg[0][]=$b;
                if($b['class']=='语文')$gg[1][]=$b;
                if($b['class']=='英语')$gg[2][]=$b;
                if($b['class']=='物理')$gg[3][]=$b;
                if($b['class']=='化学')$gg[4][]=$b;
                if($b['class']=='生物')$gg[5][]=$b;
                if($b['class']=='政治')$gg[6][]=$b;
                if($b['class']=='历史')$gg[7][]=$b;
                if($b['class']=='地理')$gg[8][]=$b;
            }
        }

        for ($i=0; $i < 9; $i++) {
            for ($ii=0; $ii < count($gg[$i]); $ii++) {
                $ggg[]=$gg[$i][$ii];
            }
        }

        return $ggg;

    }

/**
校区各科讲师人均课时统计
*/

    public function classs(){
        if($_POST){
            $m=M('hw003.person_all',null)->where(['position'=>['in','讲师,教学副校长'],'state'=>1])->order('school')->getField('id,school,name',true);
            $m[]=["name"=>"毛健","school"=>"水木清华"];
            $m[]=["name"=>"杨桂超","school"=>"水木清华"];
            $m[]=["name"=>"杨丽娜","school"=>"水木清华"];
            $m[]=["name"=>"孙晓慧","school"=>"水木清华"];
            $m[]=["name"=>"何男","school"=>"水木清华"];
            $m[]=["name"=>"赵阳阳","school"=>"水木清华"];
            $m[]=["name"=>"魏广忠","school"=>"水木清华"];
            $m[]=["name"=>"于斌","school"=>"日月兴城"];
            $m[]=["name"=>"王坤","school"=>"日月兴城"];
            $m[]=["name"=>"杨志新","school"=>"日月兴城"];
            $m[]=["name"=>"于娇梅","school"=>"日月兴城"];
            $m[]=["name"=>"吴冬梅","school"=>"日月兴城"];
            $m[]=["name"=>"张梅","school"=>"日月兴城"];
            $m[]=["name"=>"刘晓越","school"=>"日月兴城"];
            $m[]=["name"=>"李邦源","school"=>"日月兴城"];
            $m[]=["name"=>"李环宇","school"=>"天丽校区"];
            foreach ($m as $v) {
                $km=M('hw001.teacher',null)->where(['name'=>$v['name']])->find();
                $tj=$this->tongji($_POST['t1'],$_POST['t2'],$v['name'],$v['school']);
                $data[$v['school']][$km['class']][$v['name']]=$tj;
                $data[$v['school']][$km['class']]['合计']+=$tj;
            }
            foreach ($data as $k => $v) {
                if($k!='集团')
                foreach ($v as $k2 => $val) {
                    $list[$k][$k2]['a']=count($val)-1;
                    $list[$k][$k2]['b']=round($val['合计']/($list[$k][$k2]['a']),2);
                }
            }
        }
        $this->list=$list;
        $this->display('class');
    }

    public function tongji($t1,$t2,$teacher=0,$school=0){
        $w['timee']=array('between',[$t1,$t2]);
        $w['grade']=0;
        $w['state']=['neq',2];
        $w['stuid'] = array('not in',array('77777','88888','99999'));
        if($school)$w['school']=$school;
        if($teacher)$w['teacher']=$teacher;
        $data1=M('hw001.class',null)->where($w)->sum('count');

        $w['grade']=array('gt',0);
        
        $m=M('hw001.class',null)->where($w)->order('school,timee,grade,time1')->field('school,timee,grade,time1,teacher,count,class')->select();
        foreach ($m as $v) {
            if($v!=$aa)$data2+=$v['count'];
            $aa=$v;
        }
        $data=$data1+$data2;
        return $data;
    }

    
    

}

