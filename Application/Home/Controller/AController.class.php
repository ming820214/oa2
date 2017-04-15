<?php
namespace Home\Controller;

class AController extends HomeController {


    public function index(){
        $this -> display();
    }

/**
 排课系统数据管理
*/
    public function p(){
        if (!empty($_POST['keyword'])) {
            $w['school|user|position'] = array('like', "%" . $_POST['keyword'] . "%");
        }

        $s=M('hw001.school',null)->select();
        $this->school=$s;
        $User =M('hw001.user',null); // 实例化User对象
        $count      = $User->where($w)->count();// 查询满足要求的总记录数
        $Page       = new \Home\Hongwen\Page($count,25);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();// 分页显示输出
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $list = $User->where($w)->order('school asc,position desc,user asc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('item',$list);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        $this->display(); // 输出模板
    }

    public function search(){
    	$w = $_POST;
    	if (!empty($_POST['keyword'])) {
    		$w['school|user|position'] = array('like', "%" . $_POST['keyword'] . "%");
    	}
    	$w = array_filter($w);
    	$s=M('hw001.school',null)->select();
    	$this->school=$s;
    	$User =M('hw001.user',null); // 实例化User对象
    	$count      = $User->where($w)->count();// 查询满足要求的总记录数
    	$Page       = new \Home\Hongwen\Page($count,25);// 实例化分页类 传入总记录数和每页显示的记录数(25)
    	$show       = $Page->show();// 分页显示输出
    	// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
    	$list = $User->where($w)->order('school asc,position desc,user asc')->limit($Page->firstRow.','.$Page->listRows)->select();
    	$this->assign('item',$list);// 赋值数据集
    	$this->assign('page',$show);// 赋值分页输出
    	$this->display("p"); // 输出模板
    }
    
    public function delt(){
        if($_GET['id'])
            $w['id']=$_GET['id'];
        $m=M('hw001.user',null)->where($w)->delete();
        $this->success('删除成功！');
    }
    public function reset(){
        if($_GET['id'])
            $w['id']=$_GET['id'];
            $d['pw']='444bcb3a3fcf8389296c49467f27e1d6';
        $m=M('hw001.user',null)->where($w)->save($d);
        $this->success('密码已重置为ok！');
    }
    public function add(){
      if($_POST){
        $m=M('hw001.user',null);
        $m->create();
        $m->pw='444bcb3a3fcf8389296c49467f27e1d6';
        if($m->add())
        $this->success('添加成功！');
      }
    }

// 微信帐号信息数据
    public function user(){
        if (!empty($_POST['keyword'])) {
            $w['school|name|userid|position'] = array('like', "%" . $_POST['keyword'] . "%");
        }

        if($_GET['id']){
            $w['id']=$_GET['id'];
            $m=M('hw003.person_all',null)->where($w)->delete();
            $this->success('删除成功！');
        }elseif($_POST['userid']){
            $m=M('hw003.person_all',null);
            $m->create();
            if($m->add())$this->success('添加成功！');
        }else{
            $s=M('hw001.school',null)->select();
            $this->school=$s;

            $User =M('hw003.person_all',null); // 实例化User对象
            $count      = $User->where($w)->count();// 查询满足要求的总记录数
            $Page       = new \Home\Hongwen\Page($count,25);// 实例化分页类 传入总记录数和每页显示的记录数(25)
            $show       = $Page->show();// 分页显示输出
            // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
            $list = $User->where($w)->order('school asc,position desc,name asc')->limit($Page->firstRow.','.$Page->listRows)->select();
            $this->assign('item',$list);// 赋值数据集
            $this->assign('page',$show);// 赋值分页输出
            $this->display(); // 输出模板

        }
    }


 /*//排课系统删除记录
    public function precord(){

        $w['school']=get_school_name();
        if($_POST['school'])$w['school']=$_POST['school'];
        $m=M('hw001.school',null)->where($w)->getField('record');
        $m2=explode('#',$m,300);
        foreach ($m2 as $v2) {
            $data[]=explode(',',$v2);
        }
        foreach ($data as $k => $val) {
            if($i<300){
                $dat[$k]=$val;
                if($val[7]){
                    $dat[$k]['name']=M('hw001.school_grade',null)->field('name')->find($val[7]);
                }else{
                    $dat[$k]['name']=M('hw001.student',null)->field('name')->find($val[3]);
                }
                //查询过滤
                if($_POST['type']&&$dat[$k][0]==$_POST['type'])$a[]=$dat[$k];
                if($_POST['student']&&$dat[$k]['name']['name']==$_POST['student'])$b[]=$dat[$k];
                if($_POST['teacher']&&$dat[$k][9]==$_POST['teacher'])$c[]=$dat[$k];
                if($_POST['cs']&&$dat[$k][26]==$_POST['cs'])$d[]=$dat[$k];
                if($_POST['date']&&$dat[$k][15]==$_POST['date'])$e[]=$dat[$k];
            }
            $i++;
        }

        //查询过滤
        if($a)$dat=self::array_qc($dat,$a);
        if($b)$dat=self::array_qc($dat,$b);
        if($c)$dat=self::array_qc($dat,$c);
        if($d)$dat=self::array_qc($dat,$d);
        if($e)$dat=self::array_qc($dat,$e);

        $this->data=$dat;
        // var_dump($dat[1]);
        $this -> display();
    }*/

    //排课系统删除记录
    public function precord(){

//      $w['school']=get_school_name();

		if((session("school_id") == 4) || (session("school_id") == 12)){
			$w['school']= array('in','阜新二部,阜新实验校区');
		}else{
			$w['school']=get_school_name();
		}
		
        if($_POST['school'])$w['school']=$_POST['school'];
        $m=M('hw001.school',null)->where($w)->field('record')->select();
		$n = 0;
		foreach($m as $ms){
//			$m2=explode('#',$m,300);
			$m2=explode('#',$ms['record'],300);
			unset($data);
	        foreach ($m2 as $v2) {
	            $data[]=explode(',',$v2);
	        }
	        foreach ($data as $k => $val) {
//	            if($i<300){
	                $dat[$n]=$val;
	                if($val[7]){
	                    $dat[$n]['name']=M('hw001.school_grade',null)->field('name')->find($val[7]);
	                }else{
	                    $dat[$n]['name']=M('hw001.student',null)->field('name')->find($val[3]);
	                }
	                //查询过滤
	                if($_POST['type']&&$dat[$n][0]==$_POST['type'])$a[]=$dat[$n];
	                if($_POST['student']&&$dat[$n]['name']['name']==$_POST['student'])$b[]=$dat[$n];
	                if($_POST['teacher']&&$dat[$n][9]==$_POST['teacher'])$c[]=$dat[$n];
	                if($_POST['cs']&&$dat[$n][26]==$_POST['cs'])$d[]=$dat[$n];
	                if($_POST['date']&&$dat[$n][15]==$_POST['date'])$e[]=$dat[$n];
//	            }
//	            $i++;
				$n++;
	        }
			
			//查询过滤
	        if($a)$dat=self::array_qc($dat,$a);
	        if($b)$dat=self::array_qc($dat,$b);
	        if($c)$dat=self::array_qc($dat,$c);
	        if($d)$dat=self::array_qc($dat,$d);
	        if($e)$dat=self::array_qc($dat,$e);
		}
        

        

        $this->data=$dat;
        // var_dump($dat[1]);
        $this -> display();
    }


//==================================
    //数组取交集
    function array_qc($a,$b){
        foreach ($a as $v1) {
            foreach ($b as $v2) {
                if($v1==$v2)$data[]=$v1;
            }
        }
        return $data;
    }

/**
记录系统操作
*/
    public function record($part){
        if($part==1){//人事系统操作
            $m=M('hw003.record',null)->where(array('part'=>'员工管理'))->getField('record');
            $info=explode('|',$m,300);
            foreach ($info as $k=>$v) {
                $list[$k]=explode(',',$v);
                if($_POST['type']&&$list[$k][0]!=$_POST['type'])unset($list[$k]);
                if($_POST['name']&&$list[$k][2]!=$_POST['name'])unset($list[$k]);
                if($_POST['date1']&&$_POST['date2']){
                    $t1=$_POST['date1'].' 00:00:00';
                    $t2=$_POST['date2'].' 23:00:00';
                    if(!($list[$k][4]>=$t1&&$list[$k][4]<=$t2))unset($list[$k]);
                }
                if($k>200)break;
            }
            $this->list=$list;
        }
        $this->display();
    }

/**
匿名进入排课系统
*/
    //默认浏览老师课表，sid为浏览学员的课表
    public function enter_class($sid=0){
        $_SESSION['school']='随机校区';
        $sid?$this->success('学员课表查询中……',"../school/index.php/Class/all/sid/".$sid):
        $this->success('讲师的课表查询中……',"../school/index.php/Class/all/teacher/".session('user_name'));
    }


	public function enter_stuclass($sid=0){
        $_SESSION['school']=get_school_name();
        $this->success('学员课表查询中……',"../school/index.php/Class/all/sid/".$sid);
        
    }
	
    //给有匿名浏览课表的人使用
    public function enter_class2(){
        $_SESSION['school']=get_school_name();
        $this->success('课表查询中……',"../school/index.php");
    }

}
?>
