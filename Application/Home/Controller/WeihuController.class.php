<?php
namespace Home\Controller;

class WeihuController extends HomeController {

	private $pageNumber=0;
	private $pageCount=10;

	//分页数据计算规则：起始记录(n-1)*count - 结尾记录n*count-1

	/**
	 * 二维数组毗邻滤重
	 */
	private function uniqueArray(&$data){
		$i=0;
			foreach($data as $vo){ //逐行排查重复的记录
				if($vo['grade'] != 0){
					if(isset($temp_arr)){
						$arr1 = array('t'=>$vo['timee'],'t0'=>$vo['class'],'t1'=>$vo['time1'],'t2'=>$vo['time2'],'tc'=>$vo['teacher']);
						if(empty(array_diff_assoc($arr1, $temp_arr))){
							unset($data[$i]);//去掉重复的记录
						}else{
							$temp_arr=$arr1;
						}
					}else{
						unset($temp_arr);
						unset($arr1);
						$temp_arr = array('t'=>$vo['timee'],'t0'=>$vo['class'],'t1'=>$vo['time1'],'t2'=>$vo['time2'],'tc'=>$vo['teacher']);
					}

				}
				$i++;
			}
		unset($temp_arr); //销毁$temp变量
		unset($vo); //销毁$vo变量
		unset($arr1); //销毁$arr1变量
	}




	/**
	 * 获取学生排课信息
	 */
	private function getStudentClassData($mod,$condition,&$data_count,&$result,$pageNum,$pageCount){

			$data=$mod->where($condition)->order('timee DESC,time1 DESC,time2 DESC')->select();

			$this->uniqueArray($data);

			array_empty_delt($data);

			$result = array_slice($data, $pageNum,$pageCount);

			$data_count = count($data);

			unset($data); //销毁$data临时变量
	}


    public function index(){
        $this->display();
    }

	/**
	 * 教学反馈 页面初始化
	 */
	public function jxfankui(){

		//$condition['school']=get_school_name();  //教师所在校区的名字
        $condition['teacher']=session('user_name'); //教师姓名，直接从session中获取
//		$condition['state'] = 1;      //已经上的课程
		$condition['concat_ws(" ",timee,time2)'] = array('elt',date('Y-m-d H:i:s')); //截至到目前为止所有应该上过的课都可以反馈了
		$condition['fankui'] = array('neq',1); //未进行反馈的课程
		$mod = M('hw001.class',null);


		if(IS_AJAX && I('get.pageCount')){
			$page=I('get.pageNumber');//请求第几条开始
            $page_count=I('get.pageCount');//一页多少条记录


    		$this->getStudentClassData($mod,$condition,$data_count,$result,$page,$page_count);

    		// 发送给页面的数据
			$this->ajaxReturn([

				'state'=>'ok',//查询结果
				'maxCount'=>$data_count,//查询到数据库有多少条满足条件记录
				'data'=>$result

			  ]);
		}else{

			$this->getStudentClassData($mod,$condition,$data_count,$result,$this->pageNumber,$this->pageCount);
			$this->maxCount = $data_count;
			$this->data = $result;
	        $this->display();
		}


    }

	/**
	 * 查询选择该课程的所有学生的信息
	 */
	public function studentClass(){

		$classid = I('post.classid');

		$timee = I('post.timee');
		$time1 = I('post.time1');
		$time2 = I('post.time2');
		$class = I('post.class');
		$teacher = I('post.teacher');

		$page=I('get.pageNumber');//请求第几条开始
        $page_count=I('get.pageCount');//一页多少条记录

        $mod = M('hw001.class',null);

		//查询选择该课程的所有学生的信息（分页）
		$data = $mod->alias('cl')
					->field('st.id as id,st.std_id xuehao,st.name,st.type,st.wl,st.grade,cl.timee,cl.time1,cl.time2,cl.teacher,cl.class,cl.id as classid ')
					->join('left join hw001.student st on cl.stuid = st.id ')
					->where("cl.fankui<> '1' and cl.timee='%s' and cl.teacher='%s' and cl.class='%s' and cl.time1='%s' and cl.time2='%s'",array($timee,$teacher,$class,$time1,$time2))->limit($page,$page_count)
					->select();

		//查询选择该课程的所有学生的数量（全部）
		$count = $mod->alias('cl')
					->join('left join hw001.student st on cl.stuid = st.id ')
					->where("cl.fankui<> '1' and cl.timee='%s' and cl.teacher='%s' and cl.class='%s' and cl.time1='%s' and cl.time2='%s'",array($timee,$teacher,$class,$time1,$time2))
					->count();

		// 发送给页面的数据
			$this->ajaxReturn([

				'state'=>'ok',//查询结果
				'maxCount'=>$count,//查询到数据库有多少条满足条件记录
				'data'=>$data
			  ]);

	}


	/**
	 * 保存一些列学生的教学反馈内容
	 */
	public function savejxfkInfo(){

			$sid = I('get.sid');
			$cid = I('get.cid');

			$sids = split(',',$sid);
			$cids = split(',',$cid);


			$cl_mod = M('hw001.class',NULL);
			$mod=M('hw001.fankui',NULL);
			try{
				$cl_mod->startTrans();

				for($i=0;$i<count($sids);$i++){

            	$mod->create();
//				$mod->fk_c = serialize($mod->fk_c); //数组转换成字符串； 停课状态由老师自己填写，改成文本框类型

				$mod->stuid=$sids[$i];
				$mod->cid=$cids[$i];
				$data['fankui']= 1; // 1 代表完成教学反馈
				$cl_mod->where("id=%d",$cids[$i])->save($data);
				$mod->school = get_school_name();  //教师所在校区的名字;

//				 throw new \Exception("激活账户失败，请稍后重试");   //抛出异常，注意这里异常类的命名空间引用；

				if($mod->add()){
					//增加反馈信息后不做任何
				}else{
					E('教学反馈失败,请及时与系统管理员联系！');
				}
			}

             $cl_mod->execute('commit');

            $this->ajaxReturn(['state'=>'ok']);

			}catch(\Exception $e)
			{
			$cl_mod->rollback();
			//var_dump($e->getMessage());

			}


	}

	//获取最后一个叶子节点的数组并返回该数组及整棵树的节点数量
	private function getPositionTreeArrayData($arrs,&$arr_count){

		if(empty($arrs[0])){
			//一维数组处理
			if(is_array($arrs['_child']) && (count($arrs['_child']) > 0)){
				++$arr_count;
				return $this->getPositionTreeArrayData($arrs['_child'],$arr_count);
			}else{
				++$arr_count;
				return $arrs;
			}
		}else{
			//二维数组处理
			foreach($arrs as $k => $v){
			$data[$k]=$v;

			if(is_array($data[$k]['_child']) && (count($data[$k]['_child']) > 0)){
				++$arr_count;
				return $this->getPositionTreeArrayData($data[$k]['_child'],$arr_count);
			}else{
				++$arr_count;
				return $data[$k];
			}

		}
		}

	}

	/**
	 * 学生意见维护初始化页面
	 */
	public function advice(){

//		var_dump(I('session.'));
		if(session('?position_id')){
			$position = get_position_name(session('position_id'));
		}
		 if($position == '讲师'){
		 	//初始化学生选择数据源
			$cl=M('hw001.class',null);
            $w['school']=get_school_name(session('school_id'));
            $w['teacher']=session('user_name');
            $w['timee']=array('gt',date('Y-m-d',time()-10*24*3600));
			$w['state']=array('neq',0); //1 代表确认了的，已经上的课；2代表旷课的
            $m=M('hw001.class',null)->where($w)->getField('stuid',true);
            $v=array_unique($m);
			if(!empty($v)){
            	$w2['id']=array('in',$v);
				$w2['state']=1; //正常学生
				$s=M('hw001.student',null)->where($w2)->field('id,name')->select();
	            $this->student=$s;
			}else{
				$this->student=null;
			}
            
		 }

		//默认情况下把一个月内的所有老师未做答复的意见数据显示出来，超过一个月的不再显示；
		$w3['teacher']=session('user_name');
        $w3['state']=0; //没有回复的状态
        $w3['timee']=array('gt',date('Y-m-d',time()-30*24*3600));
		$w3['pid'] = '0'; //只获取父节点，显示教师的建议列表即可；

		$adlst = D("WeihuAdvice");

		$pageNum = I('get.pageNumber');
		$pageCount = I('get.pageCount');

		$pageNum = empty($pageNum)?$this->pageNumber:$pageNum;
		$pageCount = empty($pageCount)?$this->pageCount:$pageCount;


		$m3=$adlst->where($w3)->limit($pageNum,$pageCount)->select();
		$adviceCount = $adlst->where($w3)->count();


		//所有建议及回复的节点都查询出来，之后组装成一棵树
		$m4=$adlst->select();
		//所有建议及回复的节点组装成树
		$tree_arr = list_to_tree($m4,0);

		//获取该教师教的是哪门学科，用于下面数据的组装；
		$w4['teacher']=session('user_name');
		$class=M('hw001.teacher',null)->where($w4)->getField('class');

		//组装数据
        foreach ($m3 as $k => $v) {
            $data[$k]=$v;
            $ss=M('hw001.student',null)->find($v['stuid']);
            $data[$k]['name']=$ss['name'];
			$data[$k]['school']=$ss['school'];
			$data[$k]['class']=$class;
			$data[$k]['xueguan']=$ss['xueguan'];

			try{

			foreach($tree_arr as $m =>$n){
				$tdata[$m] = $n;
				$tree_node_counts = 0;
				if($tdata[$m]['id'] == $data[$k]['id']){
					if(is_array($tdata[$m]['_child']) && count($tdata[$m]['_child']) > 0){
						$data[$k]['flag']='有答复';
					}

					$temp = $this->getPositionTreeArrayData($n,$tree_node_counts);
					$data[$k]['timee']=$temp['timee'];
					break;
				}
			}



			}catch(\Exception $e)
			{
			//var_dump($e->getMessage());

			}


        }

		if(IS_AJAX && I('get.pageCount')){
			// 发送给页面的数据
			$this->ajaxReturn([

				'state'=>'ok',//查询结果
				'maxCount'=>$adviceCount,//查询到数据库有多少条满足条件记录
				'data'=>$data

			  ]);
		}else{
			 $this->data = $data;
			 $this->maxCount = $adviceCount;
	         $this->display();
		}


    }



	//显示建议与处理结果讨论页面
	public function showAdviceAndApply(){

		$rid = I('post.id');

		$adlst = D("WeihuAdvice");

		$m=$adlst->select();

		$tree_arr = list_to_tree($m,0);

		if(!empty($rid)){
			foreach($tree_arr as $k=>$v){
				$data[$k] = $v;
				if($data[$k]['id'] === $rid){
					$result = $data[$k];
				}
			}
		}

		$tree_node_counts = 0;

		$temp = $this->getPositionTreeArrayData($result,$tree_node_counts);

		// 发送给页面的数据
			$this->ajaxReturn([

				'state'=>'ok',//查询结果
				'maxCount'=>$tree_node_counts,//查询到数据库有多少条满足条件记录
				'data'=>$result,
				'pid' => $temp['id']
			  ]);

	}


	//学生维护建议保存
	public function adviceSave(){
		try{
			$ad = M('hongwen_oa.weihu_advice','oa_');
			$ad->create();
			$ad->teacher=session('user_name');
			$ad->state = 0;
			$ad->advice = '原因：'.I('post.advice1').'；建议：'.I('post.advice2');
			$ad->timee = date("Y-m-d H:i:s");
			$ad->pid = 0;
			$ad->node_type = 0;
			$ad->add();
			$this->ajaxReturn(['state'=>'ok']);
		}catch(\Exception $e)
			{
			//var_dump($e->getMessage());

			}
	}


	//对学习管理师反馈的内容进行回复保存
	public function adviceReplySave(){
		try{
			$ad = M('hongwen_oa.weihu_advice','oa_');
			$ad->create();
			$ad->teacher=session('user_name');
			$ad->state = 0;
			$ad->advice = '原因：'.I('post.advice1').'；建议：'.I('post.advice2');
			$ad->timee = date("Y-m-d H:i:s");
			$ad->node_type = 0;
			$ad->add();
			$this->ajaxReturn(['state'=>'ok']);
		}catch(\Exception $e)
			{
			//var_dump($e->getMessage());

			}
	}


	//学习管理师进行回复
	public function xueguanAdviceReply(){
		try{
			$ad = M('hongwen_oa.weihu_advice','oa_');
			$ad->create();
			$ad->teacher=session('user_name');
			$ad->state = 0;
			$ad->timee = date("Y-m-d H:i:s");
			$ad->node_type = 1;
			$ad->type = '';
			$ad->add();
			$this->ajaxReturn(['state'=>'ok']);
		}catch(\Exception $e)
			{
			//var_dump($e->getMessage());

			}
	}

	public function adviceResovleSure(){
			$ad = M('hongwen_oa.weihu_advice','oa_');
			$aid = I('post.id');
			$data['state'] = 1;
			$ad->where('id=%d',$aid)->save($data);
			$this->ajaxReturn(['state'=>'ok']);
	}


	//学管处理意见列表初始化
	public function treatAdvicelst(){
		//默认情况下把一个月内的所有老师未做答复的意见数据显示出来，超过一个月的不再显示；

        $w3['state']=0; //没有回复的状态
        $w3['timee']=array('gt',date('Y-m-d',time()-30*24*3600));
		$w3['pid'] = '0'; //只获取父节点，显示教师的建议列表即可；

		$adlst = D("WeihuAdvice");

		$pageNum = I('get.pageNumber');
		$pageCount = I('get.pageCount');

		$pageNum = empty($pageNum)?$this->pageNumber:$pageNum;
		$pageCount = empty($pageCount)?$this->pageCount:$pageCount;


		$m3=$adlst->where($w3)->select();


		//所有建议及回复的节点都查询出来，之后组装成一棵树
		$m4=$adlst->select();
		//所有建议及回复的节点组装成树
		$tree_arr = list_to_tree($m4,0);

		//组装数据
        foreach ($m3 as $k => $v) {
            $ss=M('hw001.student',null)->find($v['stuid']);
			if( $ss['xueguan']== session('user_name')){

				foreach($tree_arr as $m =>$n){
					$tdata[$m] = $n;
					$tree_node_counts = 0;
					if($tdata[$m]['id'] == $m3[$k]['id']){

						$temp = $this->getPositionTreeArrayData($n,$tree_node_counts);
						if($temp['node_type'] == 1)
						{
							unset($m3[$k]);
							break;

						}else{
							$m3[$k]['timee']=$temp['timee'];
							$w4['teacher']=$m3[$k]['teacher'];
							$class=M('hw001.teacher',null)->where($w4)->getField('class');

				            $m3[$k]['name']=$ss['name'];
							$m3[$k]['school']=$ss['school'];
							$m3[$k]['class']=$class;
							break;
						}

					}
				}

	        }else{
	        	unset($m3[$k]);
	        }
		}

		array_empty_delt($m3);
		if(empty($m3)){
			$result = null;
		}else{
			$result = array_slice($m3, $pageNum,$pageCount);
		}


		if(IS_AJAX && I('get.pageCount')){
				// 发送给页面的数据
				$this->ajaxReturn([

					'state'=>'ok',//查询结果
					'maxCount'=>count($m3),//查询到数据库有多少条满足条件记录
					'data'=>$result

				  ]);
		}else{
			 $this->data = $result;
			 $this->maxCount = count($m3);
	         $this->display('process_advice');
		}
	}


	//学员信息表初始化查询操作
	public function studentInfo(){

		$teacher = session('user_name'); //学管姓名；'温馨';测试数据

		$course = D('CourseView');

//		$w['u3.name'] = array('eq',$teacher);
		
		
		$school_id = session('school_id');
		$param['school'] = $school_id;
		$stu_state = I('post.state_s');

		if(!empty($stu_state)){
			$param['stu_state'] = $stu_state;
		}

		$param['state'] = 1; //查询全部信息
		$param['teacher'] = $teacher;

		$arr = $course->getXueguanStudentInfoByCache($w,$param,'std_id',$this->pageNumber,$this->pageCount,$maxCount,NULL);

		$this->list = $arr;
		$this->maxCount = (int)$maxCount;
		
		$this->assign('gradeList', C('SCHOOL_GRADE'));
		$grade_lst = C('SCHOOL_GRADE');
		$gradelst = array_column($grade_lst,'name','id');
		$this->gradelst = $gradelst;
		
		$this->display('xueguan_student');
	}

	//学管学生主页筛选查询操作
	public function studentInfoFilter(){

		$params = I('post.');

		$this->pageNumber = I('get.pageNumber');
		$this->pageCount = I('get.pageCount');

		$teacher = session('user_name'); //学管姓名；'魏春晓';测试数据

		$params['teacher'] = $teacher;

		$course = D('CourseView');

//		$w['u3.name'] = array('eq',$teacher);

		$keyword = I('get.keyword');

		$arr = $course->getXueguanStudentInfoByCache($w,$params,'std_id',$this->pageNumber,$this->pageCount,$maxCount,$keyword);




		if(IS_AJAX && I('get.pageCount')){
				// 发送给页面的数据
				$this->ajaxReturn([

					'state'=>'ok',//查询结果
					'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
					'data'=>$arr
				  ]);
		}else{
			 $this->list = $arr;
			 $this->maxCount = $maxCount;
			 $this->display('xueguan_student');
		}


	}

	//学生课时信息中的基本信息页面
	public function xg_base_info(){

//		var_dump(I('get.'));
		$name = I('get.name');
		$std_id = I('get.std_id');

		$info = M('hw001.student_info',NULL);

		$data = $info->getByStdId($std_id);

		if(empty($data)){
			$data['std_id'] = $std_id;
		}

		$this->assign('gradeList', C('SCHOOL_GRADE'));
//		$grade_lst = C('SCHOOL_GRADE');
//		$gradelst = array_column($grade_lst,'name','id');
//		$this->gradelst = $gradelst;
		
		$this->assign('name',$name);
		$this->assign('data',$data);
		$this->assign('maxCount',100);
		$this->display('xg_page');
	}

	//学管反馈页面初始化
	public function xg_skfankui(){

		$pageNum = I('get.pageNumber');
		$pageCount = I('get.pageCount');

		$this->pageNumber = empty($pageNum)?$this->pageNumber:$pageNum;
		$this->pageCount = empty($pageCount)?$this->pageCount:$pageCount;

		$xueguan = session('user_name');
		$std_id = I('get.std_id');
		$keyword = I('post.keyword');
		$stu = M('hw001.student',NULL);
		$data = $stu->getByStdId($std_id);
		$fankui = D('Fankui');

		$w['stuid'] = $data['id'];
		$w['timestamp'] = array('gt',date('Y-m-d',time()-30*24*3600));

		if(!empty($keyword)){
			$w['fk_f'] = $keyword;
		}

		$result =$fankui->relation(TRUE)->where($w)->limit($this->pageNumber,$this->pageCount)->select();

		/*foreach($result as &$item){
			$item['fk_c'] = implode(' ', unserialize($item['fk_c']));
		}*/
		
		foreach($result as $key=>$value){
			if(empty($value['class_set'])){
				unset($result[$key]);
			}
		}

		$result = array_values($result);
		
		$maxCount =$fankui->where($w)->count();


		if(IS_AJAX && I('get.pageCount')){
				// 发送给页面的数据
				$this->ajaxReturn([

					'state'=>'ok',//查询结果
					'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
					'data'=>$result
				  ]);
		}else{
			 $this->data = $result;
			 $this->maxCount = $maxCount;
			 $this->display('xg_page');
		}

	}

	//学管删除学生考试成绩信息
	public function xg_scoredel(){
		$score = M('score');
		$param = I('post.');

		$flag = $score->delete($param['id']);

		if($flag){
			if(IS_AJAX){
				// 发送给页面的数据
				$this->ajaxReturn([

					'state'=>'ok'
				  ]);
			}
		}
	}

	//学管添加学生考试成绩信息
	public function xg_scoreadd(){

		$score = M('score');

		$param = I('post.');

		$score->create();

		$score->create_time = time();
		$score->creator = session('user_name');

		if(!empty($param['yuwen1'])){
			$score->yuwen.= '/'. $param['yuwen1'] .'/'. $param['yuwen2'].'/'.$param['yuwen3'];
		}

		if(!empty($param['shuxue1'])){
			$score->shuxue.= '/'. $param['shuxue1'] .'/'. $param['shuxue2'].'/'.$param['shuxue3'];
		}

		if(!empty($param['english1'])){
			$score->english.= '/'. $param['english1'] .'/'. $param['english2'].'/'.$param['english3'];
		}

		if(!empty($param['wuli1'])){
			$score->wuli.= '/'. $param['wuli1'] .'/'. $param['wuli2'].'/'.$param['wuli3'];
		}

		if(!empty($param['huaxue1'])){
			$score->huaxue.= '/'. $param['huaxue1'] .'/'. $param['huaxue2'].'/'.$param['huaxue3'];
		}

		if(!empty($param['shengwu1'])){
			$score->shengwu.= '/'. $param['shengwu1'] .'/'. $param['shengwu2'].'/'.$param['shengwu3'];
		}

		if(!empty($param['dili1'])){
			$score->dili.= '/'. $param['dili1'] .'/'. $param['dili2'].'/'.$param['dili3'];
		}

		if(!empty($param['zhengzhi1'])){
			$score->zhengzhi.= '/'. $param['zhengzhi1'] .'/'. $param['zhengzhi2'].'/'.$param['zhengzhi3'];
		}

		if(!empty($param['history1'])){
			$score->history.= '/'. $param['history1'] .'/'. $param['history2'].'/'.$param['history3'];
		}

		if(!empty($param['sport1'])){
			$score->sport.= '/'. $param['sport1'].'/'. $param['sport2'].'/'.$param['sport3'];
		}

		if(!empty($param['all1'])){
			$score->all_score.= '/'. $param['all1'].'/'. $param['all2'].'/'.$param['all3'];
		}

		if($score->add()){
			if(IS_AJAX){
				// 发送给页面的数据
				$this->ajaxReturn([

					'state'=>'ok'
				  ]);
			}
		}

	}

	//学管反馈筛选查询操作
	public function xg_scoreslist(){

		$pageNum = I('get.pageNumber');
		$pageCount = I('get.pageCount');

		$this->pageNumber = empty($pageNum)?$this->pageNumber:$pageNum;
		$this->pageCount = empty($pageCount)?$this->pageCount:$pageCount;

		$std_id = I('get.std_id');

		$w['std_id'] = $std_id;

		$flag = I('get.flag');
		if($flag == 'search'){
			$exam_type = I('post.exam_type');
			$time1 = I('post.score_date1');
			$time2 = I('post.score_date2');
			if(!empty($exam_type)){
				$w['exam_type'] = $exam_type;
			}

			if(!empty($time1)){
				$w['score_date'] = array('egt',$time1);
			}

			if(!empty($time2)){
				$w['score_date '] = array('elt',$time2);
			}

		}


		$stu = M('hw001.student',NULL);
		$student = $stu->getByStdId($std_id);

		$score = M('score');

		$maxCount = $score->where($w)->order('score_date DESC')->count();

		$result = $score->where($w)->limit($this->pageNumber,$this->pageCount)->order('score_date DESC')->select();

		foreach($result as &$item){
			$item['name'] = $student['name'];
			if(strpos($item['yuwen'],'/') === 0){
				$item['yuwen'] = substr($item['yuwen'], 1);
			}

			if(strpos($item['shuxue'],'/') === 0){
				$item['shuxue'] = substr($item['shuxue'], 1);
			}

			if(strpos($item['english'],'/') === 0){
				$item['english'] = substr($item['english'], 1);
			}

			if(strpos($item['wuli'],'/') === 0){
				$item['wuli'] = substr($item['wuli'], 1);
			}

			if(strpos($item['huaxue'],'/') === 0){
				$item['huaxue'] = substr($item['huaxue'], 1);
			}

			if(strpos($item['shengwu'],'/') === 0){
				$item['shengwu'] = substr($item['shengwu'], 1);
			}

			if(strpos($item['dili'],'/') === 0){
				$item['dili'] = substr($item['dili'], 1);
			}

			if(strpos($item['zhengzhi'],'/') === 0){
				$item['zhengzhi'] = substr($item['zhengzhi'], 1);
			}

			if(strpos($item['history'],'/') === 0){
				$item['history'] = substr($item['history'], 1);
			}

			if(strpos($item['sport'],'/') === 0){
				$item['sport'] = substr($item['sport'], 1);
			}

			if(strpos($item['all_score'],'/') === 0){
				$item['all_score'] = substr($item['all_score'], 1);
			}
		}

		unset($item);

		if(IS_AJAX && I('get.pageCount')){
				// 发送给页面的数据
				$this->ajaxReturn([
					'state'=>'ok',//查询结果
					'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
					'data'=>$result
				  ]);
		}

	}

	//上课记录页面初始化
	public function lesson_search(){

		$pageNum = I('get.pageNumber');
		$pageCount = I('get.pageCount');

		$this->pageNumber = empty($pageNum)?$this->pageNumber:$pageNum;
		$this->pageCount = empty($pageCount)?$this->pageCount:$pageCount;

		$std_id = I('get.std_id');

		$clas = D('class');
		$stu = D('students');

		$student = $stu->getByStdId($std_id);

		$w['std_id'] = $std_id; //学生的学员编号

		$w['state'] = 1; //教师的上课状态为上课
		$w['trim(cwqr)'] = array('neq',''); //财务确认上课后会进行签名录入，只要该字段不为空，则说明该节课程已经上了

		$sub_type = I('post.subject_type');
		$time1 = I('post.lesson_date1');
		$time2 = I('post.lesson_date2');

		if(!empty($sub_type)){
			$w['class'] = array('eq',$sub_type);
		}

		if(!empty($time1)){
			$w['timee'] = array('egt',$time1);
		}

		if(!empty($time2)){
			$w['timee '] = array('elt',$time2);
		}

		$maxCount = $clas->where($w)->count();

		if($maxCount != 0){
			$result = $clas->where($w)->order('timee')->limit($this->pageNumber,$this->pageCount)->select();
		}else{
			$result = NULL;
		}


		foreach($result as &$item){
			$item['name'] = $student['name'];
		}

		unset($item);

		if(IS_AJAX && I('get.pageCount')){
			// 发送给页面的数据
			$this->ajaxReturn([
				'state'=>'ok',//查询结果
				'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
				'data'=>$result
			  ]);
		}
	}

	//维护回访添加信息
	public function visit_add(){

		$wh = M('weihu');
		$wh->create();
		$wh->create_time = date('Y-m-d H:i:s');
		$wh->creator = session('user_name');

		$type = I('get.type');
		if(!empty($type)){
			if($type == 'add'){
				$wh->id = NULL;
				if($wh->add()){
					if(IS_AJAX){
						// 发送给页面的数据
						$this->ajaxReturn(['state'=>'ok']);
					}
				}
			}elseif($type == 'edit'){
				if($wh->save()){
					if(IS_AJAX){
						// 发送给页面的数据
						$this->ajaxReturn(['state'=>'ok']);
					}
				}
			}
		}else{
			if($wh->add()){
				if(IS_AJAX){
					// 发送给页面的数据
					$this->ajaxReturn(['state'=>'ok']);
				}
			}else{
				if(IS_AJAX){
					// 发送给页面的数据
					$this->ajaxReturn(['state'=>'failure']);
				}
			}
		}

	}


	//维护回访页面初始化及查询操作
	public function visit_search(){

		$pageNum = I('get.pageNumber');
		$pageCount = I('get.pageCount');

		$this->pageNumber = empty($pageNum)?$this->pageNumber:$pageNum;
		$this->pageCount = empty($pageCount)?$this->pageCount:$pageCount;

		$std_id = I('get.std_id');

		$stu = D('students');

		$student = $stu->getByStdId($std_id);

		$w['std_id'] = $std_id;

		$pk = I('post.id');
		if(!empty($pk)){
			$w['id'] = $pk;
		}else{
			//查询条件获取
			$weihu_type = I('post.weihu_type');
			$visit_type = I('post.visit_type');
			$date1 = I('post.date1');
			$date2 = I('post.date2');

			 if(!empty($weihu_type)){
	    	$w['weihu_type'] = $weihu_type;
		    }

			if(!empty($visit_type)){
		    	$w['weihu_way'] = $visit_type;
		    }

			if(!empty($date1)){
		    	$w['weihu_timee '] = array('egt',$date1);
		    }

			if(!empty($date2)){
		    	$w['weihu_timee'] = array('elt',$date2);
		    }

		}



		$wh = M('weihu');

		$maxCount = $wh->where($w)->count();
		if($maxCount >0){
			$result = $wh->where($w)->limit($this->pageNumber,$this->pageCount)->select();
		}else{
			$result = NULL;
		}

		foreach($result as &$item){
			$item['name'] = $student['name'];
			$item['weihu_timee'] = empty($item['weihu_timee'])? NULL:substr($item['weihu_timee'],0,10);
		}

		unset($item);

		if(IS_AJAX){
			// 发送给页面的数据
			$this->ajaxReturn([
				'state'=>'ok',//查询结果
				'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
				'data'=>$result
			  ]);
		}

	}


	private function filterStatusInfo($arr,$grade,$v1,$v2,$filter_type,$name){
		foreach($arr as $m =>$n){
			if(!empty($grade)){
				if($n['grade'] != $grade){
					unset($arr[$m]);
					continue;
				}
			}

			if(!empty($name)){
				if(strpos($n['name'], $name) === FALSE){
					unset($arr[$m]);
					continue;
				}
			}

			if(!empty($v1) || !empty($v2)){
				if($filter_type == 'sy'){
					if(($n['remain_hour'] < $v1) || ($n['remain_hour']>$v2)){
						unset($arr[$m]);
						continue;
					}
				}elseif($filter_type == 'unused'){
					if(($n['unused_hour'] < $v1) || ($n['unused_hour']>$v2)){
						unset($arr[$m]);
						continue;
					}
				}elseif($filter_type == 'used'){
					if(($n['used_hour'] < $v1) || ($n['used_hour']>$v2)){
						unset($arr[$m]);
						continue;
					}
				}elseif($filter_type == 'day'){
					if(($n['day_hour'] < $v1) || ($n['day_hour']>$v2)){
						unset($arr[$m]);
						continue;
					}
				}

			}
		}

		return $arr;
	}


	/**
	 * 今日学生状况
	 */
	public function student_status(){


		$pageNum = I('get.pageNumber');
		$pageCount = I('get.pageCount');

		$this->pageNumber = empty($pageNum)?$this->pageNumber:$pageNum;
		$this->pageCount = empty($pageCount)?$this->pageCount:$pageCount;


		$stu_xueguan = session('user_name');

		$school_id =  session('school_id');

//		$w['u3.name'] = array('eq',$stu_xueguan);

		$param['state'] = 1;
		$param['teacher'] = $stu_xueguan;
		$param['school'] = $school_id;

		$course = D('CourseView');

		$session_id = session_id();
		$cache_data = S($session_id.'student_status');

		//筛选条件参数值获取
		$grade = I('post.grade_w');
		$v1 = I('post.val1');
		$v2 = I('post.val2');
		$filter_type = I('post.lession_type');
		$name = I('post.keyword');

		if(empty($cache_data)){
			$arr = $course->getXueguanStudentInfoByCache($w,$param,'std_id',NULL,NULL,$maxCount,NULL);

			//缓存数据,缓存时间15分钟
			S($session_id.'student_status',$arr,300);

			//查询筛选操作
			$arr_temp = $this->filterStatusInfo($arr,$grade,$v1,$v2,$filter_type,$name);

			$this->allCount = count($arr_temp);
			if($this->allCount > 0){
				//设置所有学员的课时数据；
				$day_hour = array_sum(array_column($arr_temp,'day_hour'));
				$used_hour = array_sum(array_column($arr_temp,'used_hour'));
				$unused_hour = array_sum(array_column($arr_temp,'unused_hour'));
				$remain_hour = array_sum(array_column($arr_temp,'remain_hour'));
				$wl_tj = array_count_values(array_column($arr_temp,'wl'));
				$grade_tj = array_count_values(array_column($arr_temp,'grade'));

				$this->wl_tj = $wl_tj;
				$this->grade_tj = $grade_tj;

				$this->day_hour = $day_hour;
				$this->used_hour = $used_hour;
				$this->unused_hour = $unused_hour;
				$this->remain_hour = $remain_hour;


				//根据剩余课时进行排序
					$order_remain = [];
					$order_day = [];
					$order_used = [];
					$order_unused = [];

					foreach($arr_temp as $key=>$value){
						$order_remain[$key] = $value['remain_hour'];
						$order_day[$key] = $value['day_hour'];
						$order_used[$key] = $value['used_hour'];
						$order_unused[$key] = $value['unused_hour'];
					}

					//前段页面字段排序操作
					$sy_sort_type = I('post.sy_sort_type');

					$day_sort_type = I('post.day_sort_type');

					$used_sort_type = I('post.used_sort_type');

					$unused_sort_type = I('post.unused_sort_type');


					if($sy_sort_type == 'DESC'){
						array_multisort($order_remain,SORT_DESC,$arr_temp);
					}elseif($sy_sort_type == 'ASC'){
					    array_multisort($order_remain,SORT_ASC,$arr_temp);
					}

					if($day_sort_type == 'DESC'){
						array_multisort($order_day,SORT_DESC,$arr_temp);
					}elseif($day_sort_type == 'ASC'){
					    array_multisort($order_day,SORT_ASC,$arr_temp);
					}

					if($used_sort_type == 'DESC'){
						array_multisort($order_used,SORT_DESC,$arr_temp);
					}elseif($used_sort_type == 'ASC'){
					    array_multisort($order_used,SORT_ASC,$arr_temp);
					}

					if($unused_sort_type == 'DESC'){
						array_multisort($order_unused,SORT_DESC,$arr_temp);
					}elseif($unused_sort_type == 'ASC'){
					    array_multisort($order_unused,SORT_ASC,$arr_temp);
					}

					if(empty($sy_sort_type) && empty($day_sort_type)&& empty($used_sort_type)&& empty($unused_sort_type)){
						array_multisort($order_remain,SORT_ASC,$arr_temp);
					}

					$maxCount = count($arr_temp);
					$arr = array_slice(array_values($arr_temp), $this->pageNumber,$this->pageCount);


					//设置所有学员的课时数据；
					$page_day_hour = array_sum(array_column($arr,'day_hour'));
					$page_used_hour = array_sum(array_column($arr,'used_hour'));
					$page_unused_hour = array_sum(array_column($arr,'unused_hour'));
					$page_remain_hour = array_sum(array_column($arr,'remain_hour'));
					$page_wl_tj = array_count_values(array_column($arr,'wl'));
					$page_grade_tj = array_count_values(array_column($arr,'grade'));

					$this->counts = count($arr);
					$this->page_wl_tj = $page_wl_tj;
					$this->page_grade_tj = $page_grade_tj;
					$this->page_day_hour = $page_day_hour;
					$this->page_used_hour = $page_used_hour;
					$this->page_unused_hour = $page_unused_hour;
					$this->page_remain_hour = $page_remain_hour;
			}
		}else{

			//查询筛选操作
			$arr_temp = $this->filterStatusInfo($cache_data,$grade,$v1,$v2,$filter_type,$name);
			$this->allCount = count($arr_temp);
			if($this->allCount >0){
				$day_hour = array_sum(array_column($arr_temp,'day_hour'));
				$used_hour = array_sum(array_column($arr_temp,'used_hour'));
				$unused_hour = array_sum(array_column($arr_temp,'unused_hour'));
				$remain_hour = array_sum(array_column($arr_temp,'remain_hour'));
				$wl_tj = array_count_values(array_column($arr_temp,'wl'));
				$grade_tj = array_count_values(array_column($arr_temp,'grade'));

				$this->wl_tj = $wl_tj;
				$this->grade_tj = $grade_tj;
				$this->day_hour = $day_hour;
				$this->used_hour = $used_hour;
				$this->unused_hour = $unused_hour;
				$this->remain_hour = $remain_hour;

				//根据剩余课时进行排序
					$order_remain = [];
					$order_day = [];
					$order_used = [];
					$order_unused = [];

					foreach($arr_temp as $key=>$value){
						$order_remain[$key] = $value['remain_hour'];
						$order_day[$key] = $value['day_hour'];
						$order_used[$key] = $value['used_hour'];
						$order_unused[$key] = $value['unused_hour'];
					}

					//前段页面字段排序操作
					$sy_sort_type = I('post.sy_sort_type');

					$day_sort_type = I('post.day_sort_type');

					$used_sort_type = I('post.used_sort_type');

					$unused_sort_type = I('post.unused_sort_type');


					if($sy_sort_type == 'DESC'){
						array_multisort($order_remain,SORT_DESC,$arr_temp);
					}elseif($sy_sort_type == 'ASC'){
					    array_multisort($order_remain,SORT_ASC,$arr_temp);
					}

					if($day_sort_type == 'DESC'){
						array_multisort($order_day,SORT_DESC,$arr_temp);
					}elseif($day_sort_type == 'ASC'){
					    array_multisort($order_day,SORT_ASC,$arr_temp);
					}

					if($used_sort_type == 'DESC'){
						array_multisort($order_used,SORT_DESC,$arr_temp);
					}elseif($used_sort_type == 'ASC'){
					    array_multisort($order_used,SORT_ASC,$arr_temp);
					}

					if($unused_sort_type == 'DESC'){
						array_multisort($order_unused,SORT_DESC,$arr_temp);
					}elseif($unused_sort_type == 'ASC'){
					    array_multisort($order_unused,SORT_ASC,$arr_temp);
					}

					if(empty($sy_sort_type) && empty($day_sort_type)&& empty($used_sort_type)&& empty($unused_sort_type)){
						array_multisort($order_remain,SORT_ASC,$arr_temp);
					}

					$maxCount = count($arr_temp);
					$arr = array_slice(array_values($arr_temp), $this->pageNumber,$this->pageCount);


					//设置所有学员的课时数据；
					$page_day_hour = array_sum(array_column($arr,'day_hour'));
					$page_used_hour = array_sum(array_column($arr,'used_hour'));
					$page_unused_hour = array_sum(array_column($arr,'unused_hour'));
					$page_remain_hour = array_sum(array_column($arr,'remain_hour'));
					$page_wl_tj = array_count_values(array_column($arr,'wl'));
					$page_grade_tj = array_count_values(array_column($arr,'grade'));

					$this->counts = count($arr);
					$this->page_wl_tj = $page_wl_tj;
					$this->page_grade_tj = $page_grade_tj;
					$this->page_day_hour = $page_day_hour;
					$this->page_used_hour = $page_used_hour;
					$this->page_unused_hour = $page_unused_hour;
					$this->page_remain_hour = $page_remain_hour;

			}

		}


		if($this->allCount > 0){
			if(IS_AJAX && I('get.pageCount')){
				// 发送给页面的数据
				$this->ajaxReturn([
						'wl_tj' => $this->wl_tj,
						'grade_tj' => $this->grade_tj,
						'day_hour' => $this->day_hour,
						'used_hour' => $this->used_hour,
						'unused_hour' => $this->unused_hour,
						'remain_hour' => $this->remain_hour,
						'counts' => $this->counts,
						'page_wl_tj' => $this->page_wl_tj,
						'page_grade_tj' => $this->page_grade_tj,
						'page_day_hour' => $this->page_day_hour,
						'page_used_hour' => $this->page_used_hour,
						'page_unused_hour' => $this->page_unused_hour,
						'page_remain_hour' => $this->page_remain_hour,
						'allCount' => $this->allCount,
						'state'=>'ok',//查询结果
						'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
						'data'=>$arr
					  ]);
			}else{
				 $this->assign('gradeList', C('SCHOOL_GRADE'));
				 $grade_lst = C('SCHOOL_GRADE');
				 $gradelst = array_column($grade_lst,'name','id');
				 $this->gradelst = $gradelst;
				 $this->maxCount = $maxCount;
				 $this->data = $arr;
				 $this->display();
			}
		}else{
			if(IS_AJAX && I('get.pageCount')){
				// 发送给页面的数据
					$this->ajaxReturn([
						'wl_tj' => 0,
						'grade_tj' => 0,
						'day_hour' => 0,
						'used_hour' => 0,
						'unused_hour' => 0,
						'remain_hour' => 0,
						'counts' => 0,
						'page_wl_tj' => 0,
						'page_grade_tj' => 0,
						'page_day_hour' => 0,
						'page_used_hour' => 0,
						'page_unused_hour' => 0,
						'page_remain_hour' => 0,
						'allCount' => 0,
						'state'=>'ok',//查询结果
						'maxCount'=>0,//查询到数据库有多少条满足条件记录
						'data'=>NULL
					  ]);
			}else{
				
				 $this->assign('gradeList', C('SCHOOL_GRADE'));
				 $grade_lst = C('SCHOOL_GRADE');
				 $gradelst = array_column($grade_lst,'name','id');
				 $this->gradelst = $gradelst;
				 $this->maxCount = 0;
				 $this->data = NULL;
				 $this->display();
			}
		}
	}

	//查询学生今日课时列表
	public function student_lession(){

		$pageNum = I('get.pageNumber');
		$pageCount = I('get.pageCount');

		$this->pageNumber = empty($pageNum)?$this->pageNumber:$pageNum;
		$this->pageCount = empty($pageCount)?$this->pageCount:$pageCount;

		$std_id = I('post.std_id');

		$cls = D('class');
		$stu = D('students');

		$w['std_id'] = $std_id;
		$w['timee'] = date('Y/m/d');
		$maxCount = $cls->where($w)->count();

		if($maxCount >0){
			$student = $stu->where('std_id=%s',$std_id)->select();
			$arr = $cls->where($w)->order('time1 ASC,time2 ASC')->limit($this->pageNumber,$this->pageCount)->select();
			foreach($arr as &$item){
				$item['name'] = $student[0]['name'];
				$item['grade'] = $student[0]['grade'];
				$item['wl'] = $student[0]['wl'];
			}
			unset($item);
		}else{
			$arr = NULL;
		}


		if(IS_AJAX && I('get.pageCount')){
				// 发送给页面的数据
				$this->ajaxReturn([

					'state'=>'ok',//查询结果
					'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
					'data'=>$arr
				  ]);
		}
	}

	private function filterWarningInfo($arr,$grade,$v1,$v2,$filter_type,$name){
		foreach($arr as $m =>$n){
			if(!empty($grade)){
				if($n['grade'] != $grade){
					unset($arr[$m]);
					continue;
				}
			}

			if(!empty($name)){
				if(strpos($n['name'], $name) === FALSE){
					unset($arr[$m]);
					continue;
				}
			}

			if(!empty($v1) || !empty($v2)){
				if($filter_type == 'sy'){
					if(($n['remain_hour'] < $v1) || ($n['remain_hour']>$v2)){
						unset($arr[$m]);
						continue;
					}
				}elseif($filter_type == 'yp'){
					if(($n['lession_hour'] < $v1) || ($n['lession_hour']>$v2)){
						unset($arr[$m]);
						continue;
					}
				}

			}
		}

		return $arr;
	}


	/**
	 * 今日预警任务
	 */
	public function warning_task(){

		$positionId = session('position_id');

		//学习管理师才有该项权利，其他人直接跳过，显示空白页面
		if($positionId == '18'){

			$pageNum = I('get.pageNumber');
			$pageCount = I('get.pageCount');

			$this->pageNumber = empty($pageNum)?$this->pageNumber:$pageNum;
			$this->pageCount = empty($pageCount)?$this->pageCount:$pageCount;


			$stu_xueguan = session('user_name');//'魏春晓';//

			$school_id =  session('school_id'); //13;//

			//筛选条件参数值获取
			$grade = I('post.grade_w');
			$v1 = I('post.val1');
			$v2 = I('post.val2');
			$filter_type = I('post.lession_type');
			$name = I('post.keyword');

			$param['state'] = 1;
			$param['teacher'] = $stu_xueguan;
			$param['school'] = $school_id;

//			$w['u3.name'] = array('eq',$stu_xueguan);

			$course = D('CourseView');

			$session_id = session_id();
			$cache_count = $session_id .'warning_task_maxCount';
			$cache_data = S($session_id .'warning_task');


			$arr_temp = [];

			if(empty($cache_data)){

				//查询所有数据，存入thinkphp缓存中，方便后期提高查询和筛选的速度
				$arr = $course->getXueguanStudentInfoByCache($w,$param,'std_id',NULL,NULL,$maxCount,NULL);


				//缓存数据
				S($session_id.'warning_task',$arr,300);
				S($cache_count,$maxCount,300);

				//查询筛选操作
				$arr_temp = $this->filterWarningInfo($arr,$grade,$v1,$v2,$filter_type,$name);

				//根据剩余课时进行排序
				$order_remain = [];
				$order_lession = [];
				foreach($arr_temp as $key=>$value){
					$order_remain[$key] = $value['remain_hour'];
					$order_lession[$key] = $value['lession_hour'];
				}

				//前段页面字段排序操作
				$sy_sort_type = I('post.sy_sort_type');

				if($sy_sort_type == 'DESC'){
					array_multisort($order_remain,SORT_DESC,$arr_temp);
				}elseif($sy_sort_type == 'ASC'){
				    array_multisort($order_remain,SORT_ASC,$arr_temp);
				}

				$yp_sort_type = I('post.yp_sort_type');
				if($yp_sort_type == 'DESC'){
					array_multisort($order_lession,SORT_DESC,$arr_temp);
				}elseif($yp_sort_type == 'ASC'){
				    array_multisort($order_lession,SORT_ASC,$arr_temp);
				}

				if(empty($sy_sort_type) && empty($yp_sort_type)){
					array_multisort($order_remain,SORT_ASC,$arr_temp);
				}

				$maxCount = count($arr_temp);
				$arr = array_slice(array_values($arr_temp), $this->pageNumber,$this->pageCount);
			}else{
				//查询筛选操作
				$arr_temp = $this->filterWarningInfo($cache_data,$grade,$v1,$v2,$filter_type,$name);

				//根据剩余课时进行排序
				$order_remain = [];
				$order_lession = [];
				foreach($arr_temp as $key=>$value){
					$order_remain[$key] = $value['remain_hour'];
					$order_lession[$key] = $value['lession_hour'];
				}

				//前段页面字段排序操作
				$sy_sort_type = I('post.sy_sort_type');

				if($sy_sort_type == 'DESC'){
					array_multisort($order_remain,SORT_DESC,$arr_temp);
				}elseif($sy_sort_type == 'ASC'){
				    array_multisort($order_remain,SORT_ASC,$arr_temp);
				}


				$yp_sort_type = I('post.yp_sort_type');
				if($yp_sort_type == 'DESC'){
					array_multisort($order_lession,SORT_DESC,$arr_temp);
				}elseif($yp_sort_type == 'ASC'){
				    array_multisort($order_lession,SORT_ASC,$arr_temp);
				}

				if(empty($sy_sort_type) && empty($yp_sort_type)){
					array_multisort($order_remain,SORT_ASC,$arr_temp);
				}

				$arr = array_slice($arr_temp, $this->pageNumber,$this->pageCount);

				$maxCount = count($arr_temp);
			}

			unset($arr_temp);

			if(IS_AJAX && I('get.pageCount')){
				// 发送给页面的数据
				$this->ajaxReturn([

					'state'=>'ok',//查询结果
					'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
					'data'=>$arr
				  ]);
			}else{
				 $this->list = $arr;
				 //设置年级列表
				 $this->assign('gradeList'        , C('SCHOOL_GRADE'));   
				 $grade_lst = C('SCHOOL_GRADE');
				 $gradelst = array_column($grade_lst,'name','id');
				 $this->gradelst = $gradelst;
				 $this->maxCount = $maxCount;
				 $this->display();
			}

		}else{
			//无权限的人员，显示空白页面
			$this->maxCount = 0;
			//设置年级列表
			$this->assign('gradeList'        , C('SCHOOL_GRADE'));
			$grade_lst = C('SCHOOL_GRADE');
			$gradelst = array_column($grade_lst,'name','id');
			$this->gradelst = $gradelst;
			$this->display();
		}
	}


	/**
	 * 今日维护任务
	 */
	public function maintain_task(){

		$pageNum = I('get.pageNumber');
		$pageCount = I('get.pageCount');

		$this->pageNumber = empty($pageNum)?$this->pageNumber:$pageNum;
		$this->pageCount = empty($pageCount)?$this->pageCount:$pageCount;


		$stu = D('students');

		$pk = I('post.id');
		if(!empty($pk)){
			$w['id'] = $pk;
		}else{

			$xueguan = session('user_name');

			$wt['xueguan'] = $xueguan; //该学管管理的学生
			$wt['state'] = 1; //正常学员

			$stus = $stu->where($wt)->select();

			$std_lst = array_column($stus,'std_id');

			$stu_list = array_column($stus,'name','std_id');

			$this->stu_list = $stu_list; //前端页面select数据list填充


			if(empty($std_lst)){
				if(IS_AJAX){
					// 发送给页面的数据
					$this->ajaxReturn([
						'state'=>'ok',//查询结果
						'maxCount'=>0,//查询到数据库有多少条满足条件记录
						'data'=>NULL
					  ]);
				}else{
					$this->state = 'ok';
					$this->data = NULL;
					$this->maxCount = 0;
					$this->display();
					die;
				}
			}else{
				$w['std_id'] = array('in',$std_lst);
			}



			//查询条件获取
			$weihu_type = I('post.weihutype');
			$visit_type = I('post.visit_type');
			$date1 = I('post.date1');
			$date2 = I('post.date2');

			if(!empty($weihu_type)){
	    	$w['weihu_type'] = $weihu_type;
		    }

			if(!empty($visit_type)){
		    	$w['weihu_way'] = $visit_type;
		    }

			if(!empty($date1)){
		    	$w['weihu_timee '] = array('egt',$date1);
		    }

			if(!empty($date2)){
		    	$w['weihu_timee'] = array('elt',$date2);
		    }
		}


		$wh = M('weihu');

		$maxCount = $wh->where($w)->count();
		if($maxCount >0){
			$result = $wh->where($w)->limit($this->pageNumber,$this->pageCount)->order('weihu_timee DESC,create_time DESC')->select();
		}else{
			$result = NULL;
		}

		if(!empty($pk) && $maxCount>0){
			$wt['std_id'] = $result[0]['std_id'];
			$stus = $stu->where($wt)->select();
		}

		foreach($result as &$item){
			foreach($stus as $vo){
				if($vo['std_id'] == $item['std_id']){
					$item['name'] = $vo['name'];
					break;
				}
			}

			$item['weihu_timee'] = substr($item['weihu_timee'],0,10);
		}

		unset($item);

		if(IS_AJAX){
			// 发送给页面的数据
			$this->ajaxReturn([
				'state'=>'ok',//查询结果
				'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
				'data'=>$result
			  ]);
		}else{
			$this->state = 'ok';
			$this->data = $result;
			$this->maxCount = $maxCount;
			$this->display();
		}


	}

	/**
	 * 教学主管（教学副校长）学员维护方式修改
	 */
	public function teaching_supervisor(){

		$school_id = session('school_id');
		$positionId = session('position_id');

		//维护副校长才有该项权利，其他人直接跳过，显示空白页面
//		if($positionId == '12'){

			$pageNum = I('get.pageNumber');
			$pageCount = I('get.pageCount');

			$this->pageNumber = empty($pageNum)?$this->pageNumber:$pageNum;
			$this->pageCount = empty($pageCount)?$this->pageCount:$pageCount;

			$stu_type = I('post.type_s');
			$stu_grade = I('post.grade_s');
			$stu_xueguan = I('post.xueguan_s');
			$stu_frequency = I('post.frequency_s');
			$stu_visit_type = I('post.visit_type_s');
			$stu_state = I('post.state_s');
			$stu_school = I('post.school_s');

			$keywords = I('post.keyword');

			if(!empty($stu_type)){
				$param['student_type'] = $stu_type;
			}

			if(!empty($stu_state)){
				$param['stu_state'] = $stu_state;
			}

			if(!empty($stu_xueguan)){
				$param['teacher'] = $stu_xueguan;
			}

			if(!empty($stu_grade)){
				$param['grade'] = $stu_grade;
			}

			if(!empty($stu_frequency)){
				$param['frequency'] = $stu_frequency;
			}

			if(!empty($stu_visit_type)){
				$param['visit_type'] = $stu_visit_type;
			}

			if(!empty($keywords)){
				$param['name'] = $keywords;
			}



			$course = D('CourseView');

//			$param['state'] = 1; 去掉学生状态为正常的情况，也就是查询所有学生的信息

			if(!empty($stu_school) || ($stu_school === '0')){
				$param['school'] = $stu_school;
			}else{

				$param['school'] = $school_id;

			}


			$arr = $course->getXueguanStudentInfoByCache($w,$param,'std_id',$this->pageNumber,$this->pageCount,$maxCount,NULL);

			$xg = M('user');

			$xg_lst = $xg->where(['school'=>$school_id,'position_id'=>18,'is_del'=>0])->getField('name as id,name');
			//设置页面select组件内容
			$this->xueguan=$xg_lst;

			if(IS_AJAX && I('get.pageCount')){
				// 发送给页面的数据
				$this->ajaxReturn([

					'state'=>'ok',//查询结果
					'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
					'data'=>$arr
				  ]);
			}else{
				//设置年级列表
				$this->assign('gradeList'        , C('SCHOOL_GRADE'));
				$grade_lst = C('SCHOOL_GRADE');
				$gradelst = array_column($grade_lst,'name','id');
				$this->gradelst = $gradelst;
			
				 $this->list = $arr;
				 $this->maxCount = $maxCount;
				 $this->display();
			}

		/*}else{
			$this->maxCount = 0;
			//无权限的人员，显示空白页面
			$this->display();
		}*/
	}

	//维护频率/维护方式更改
	public function frequency_update(){

		$stuid = I('post.id');
		$frequency = I('post.frequency');
		$visit_type = I('post.visit_type');

		$stu = D('Students');

		$stu->create();
		if($stu->save()){
			$this->ajaxReturn(['state'=>'ok']);
		}else{
			$this->ajaxReturn(['state'=>'no']);
		}

	}

	//维护内容的历史记录查询
	public function searchAllMaintainInfoByStdId(){
		$std_id = I('post.std_id');

		$mt = M('weihu');
		$data = $mt->where('std_id=%s',$std_id)->order('create_time DESC')->select();

		if(IS_AJAX){
			// 发送给页面的数据
			$this->ajaxReturn([
			'state'=>'ok',//查询结果
			'data'=>$data
		  ]);
		}

	}


	//获取最后一个叶子节点的数组并返回该数组及整棵树的节点数量
	private function getLastNodeData($arrs,&$arr_count){

		if(empty($arrs[0])){
			//一维数组处理
			if(is_array($arrs['WeihuAdvice']) && (count($arrs['WeihuAdvice']) > 0)){
				++$arr_count;
				return $this->getLastNodeData($arrs['WeihuAdvice'],$arr_count);
			}else{
				++$arr_count;
				return $arrs;
			}
		}else{
			//二维数组处理
			foreach($arrs as $k => $v){
			$data[$k]=$v;

			if(is_array($data[$k]['WeihuAdvice']) && (count($data[$k]['WeihuAdvice']) > 0)){
				++$arr_count;
				return $this->getLastNodeData($data[$k]['WeihuAdvice'],$arr_count);
			}else{
				++$arr_count;
				return $data[$k];
			}

		}
		}

	}

	//意见记录
	public function advice_record(){

		$school_id = session('school_id');
		$positionId = session('position_id');
		$maxCount = 10;
		//维护副校长才有该项权利，其他人直接跳过，显示空白页面
//		if($positionId == '12'){

			$pageNum = I('get.pageNumber');
			$pageCount = I('get.pageCount');

			$this->pageNumber = empty($pageNum)?$this->pageNumber:$pageNum;
			$this->pageCount = empty($pageCount)?$this->pageCount:$pageCount;

			$advice_type = I('post.type');
			$stu_xueguan = I('post.xueguan');
			$advice_state = I('post.state');
			$keywords = I('post.keyword');

			$xg = M('user');
			$xg_lst = $xg->where('school=%d',$school_id)->getField('name as id,name');
			//设置页面select组件内容
			$this->xueguan=$xg_lst;

			$ad = D('WeihuAdvice');

			$w['pid'] = 0;

			$data = $ad->relation(TRUE)->select();

			foreach($data as $key=>$vo){
				if($vo['pid'] != 0){
					unset($data[$key]);
				}
			}

			$data = array_values($data);

			$foo = M('foo_info');

			$schoolName = $foo->getFieldById($school_id,'name');

			$stu = D('Students');

			$ws['school'] = $schoolName;

			if(!empty($stu_xueguan)){
				$ws['xueguan'] = $stu_xueguan;
			}

			if(!empty($keywords)){
				$ws['name'] = array('like','%'.$keywords.'%');
			}


			$stu_lst = $stu->where($ws)->select();

			$flag = false;
			$wt = NULL;
			$last_node = NULL;
			$arr_count = 0;
			foreach($data as $key=>$vo){

				if(!empty($advice_type)){
				    if($vo['type'] != $advice_type){
				    	unset($data[$key]);
						continue;
				    }
				}


				if($advice_state != NULL){
					if($vo['state'] != $advice_state){
						unset($data[$key]);
						continue;
					}
				}

				/*if(!empty($keywords)){
					if(strpos($vo['teacher'], $keywords) === FALSE){
						unset($data[$key]);
						continue;
					}
				}*/

				$flag = false;
				foreach($stu_lst as $st){
					if($vo['stuid'] == $st['id']){

						//获取该教师教的是哪门学科，用于下面数据的组装；
						$wt['teacher'] = $vo['teacher'];
						$class=M('hw001.teacher',null)->where($wt)->getField('class');
						$data[$key]['class'] = $class;
						$data[$key]['xueguan'] = $st['xueguan'];
						$data[$key]['name'] = $st['name'];
						$last_node = $this->getLastNodeData($vo, $arr_count);

						if(!empty($last_node)){
							$data[$key]['last_timee'] = $last_node['timee'];
						}

						$flag = true;
						break;
					}
				}
				if(!$flag){
					unset($data[$key]);
				}else{
					if($arr_count>0){

							if($vo['state'] == 1){
								$data[$key]['last_state'] = '处理完毕';
							}else{
								$now_date = date('Y-m-d H:i:s',time());
								$date1=date_create($now_date);
								$date2=date_create($vo['timee']);
								$diff=date_diff($date1,$date2);

								if(($vo['state'] == 0) && ($diff->days>30)){
									$data[$key]['last_state'] = '只处理了一部分就暂停了';
								}else{
									$data[$key]['last_state'] = '处理中……';
								}
							}

						}else{
							$now_date = date('Y-m-d H:i:s',time());
							$date1=date_create($now_date);
							$date2=date_create($vo['timee']);
							$diff=date_diff($date1,$date2);

							if($diff->days>30){
								$data[$key]['last_state'] = '未做处理';
							}else{
								$data[$key]['last_state'] = '等待处理';
							}
						}
				}
			}

			$data = array_values($data);




			if(IS_AJAX && I('get.pageCount')){
				// 发送给页面的数据
				$this->ajaxReturn([

					'state'=>'ok',//查询结果
					'maxCount'=>count($data),//查询到数据库有多少条满足条件记录
					'data'=>$data
				  ]);
			}else{
				 $this->data = $data;
				 $this->maxCount = count($data);
				 $this->display();
			}
		/*}else{
			$this->maxCount = $maxCount;
			//无权限的人员，显示空白页面
			$this->display();
		}*/
	}

	public function save_month_static($statistic,$yftk,$tf,$wfjk,$school,$xueguan,$i,$data2){
		$yftk_str = implode('@', $yftk);
		$tf_str = implode('@',$tf);
		$wfjk_str = implode('@', $wfjk);


		$data['school_id'] = $school;
		$data['xueguan'] = $xueguan;
		$data['year'] = date('Y');
		$data['month'] = date('m');
		$data['create_time'] = date('Y-m-d H:i:s');
		$data['creator'] = 'system';
		$data['yftk'] = $yftk_str;
		$data['tf'] = $tf_str;
		$data['wfjk'] = $wfjk_str;

		$statistic->add($data);
	}
	//每月第一个登陆的人自动添加统计信息，之后不再执行
	public function month_static(){
		$yftk = NULL;
		$tf = NULL;
		$wfjk = NULL;
		$statistic = M('weihu_month_statistic');

		$param_year = date('Y');
		$param_month = date('m');
		$lst = $statistic->where(['year'=>$param_year,'month'=>$param_month])->select();

		if(empty($lst)){
			$this->data_static('save_month_static',$yftk,$tf,$wfjk,$data);
		}
	}

	public function data_static(callable $callback,&$yftk,&$tf,&$wfjk,&$data){

		 $statistic = M('weihu_month_statistic');

		 $school = M('foo_info');
		 $xg = M('user');
		 $stu = M('hw001.student',NULL);
		 $consump = M('consumption');
		 $course = M('course');


		 $sch_lst = $school->where(['pid'=>15])->order('id')->select();

		 $xueguan = NULL;
		 $std_lst = NULL;
		 $i = 0;
		 foreach($sch_lst as $key => $value){
		 	$xueguan = $xg->field('name')->where(['is_del'=>0,'school'=>$value['id'],'position_id'=>18])->order('id')->select();

			 foreach($xueguan as $m=>$n){

				//该学管管理的学员
			 	$std_lst = $stu->where(['school'=>$value['name'],'xueguan'=>$n['name']])->getField('std_id',TRUE);
				if(!empty($std_lst)){
					//有费停课的学员筛选
					$yf1 = $consump->where(['std_id'=> array('in',$std_lst)])->group('std_id')->having('sum(value)>0')->getField('std_id',TRUE);
					$yf2 = $course->where(['std_id'=> array('in',$std_lst),'state' => array('in',[200,250])])->group('std_id')->having('sum(hour+ext_hour-used_hour)>0')->getField('std_id',TRUE);

					if(!empty($yf1) && !empty($yf2)){
						$yf = array_unique(array_merge($yf1,$yf2));
					}else if(empty($yf1) && !empty($yf2)){
						$yf = $yf2;
					}else if(empty($yf2) && !empty($yf1)){
						$yf = $yf1;
					}

					if(!empty($yf)){
						$tk = $stu->where(['school'=>$value['name'],'xueguan'=>$n['name'],'state'=>2])->getField('std_id',TRUE);
						$yftk = array_intersect($yf, $tk); //有费停课的学员
					}else{
						$yftk = NULL;
					}

					unset($yf1);
					unset($yf2);
					unset($yf);
					unset($tk);


					//退费人数
					$tf = $stu->where(['school'=>$value['name'],'xueguan'=>$n['name'],'state'=>5])->getField('std_id',TRUE);


					$wf1 = $consump->where(['std_id'=> array('in',$std_lst)])->group('std_id')->having('sum(value)<=0')->getField('std_id',TRUE);
					$wf2 = $course->where(['std_id'=> array('in',$std_lst),'state' => array('in',[200,250])])->group('std_id')->having('sum(hour+ext_hour-used_hour)=0')->getField('std_id',TRUE);

					if(!empty($wf1) && !empty($wf2)){
						$wf3 = array_intersect($wf1, $wf2);
					}else{
						$wf3 = NULL;
					}


					$yf = $consump->where(['std_id'=> array('in',$std_lst)])->group('std_id')->getField('std_id',TRUE);

					if(!empty($yf)){
						$wf4 = array_diff($std_lst, $yf);
					}else{
						$wf4 = $std_lst;
					}

					if(!empty($wf3)){
						$wf = array_merge($wf4,$wf3);
					}else{
						$wf = $wf4;
					}


					//结课学员人数
					$jk = $stu->where(['school'=>$value['name'],'xueguan'=>$n['name'],'state'=>3])->getField('std_id',TRUE);


					$wfjk = array_intersect($wf, $jk);

					unset($wf1);
					unset($wf2);
					unset($wf3);
					unset($wf4);
					unset($wf);
					unset($jk);


					$this->$callback($statistic,$yftk,$tf,$wfjk,$value['id'],$n['name'],$i++,$data);

				}else{
					continue;
				}
			 }
		 }

	}

	public function getXgCurrentMonthData($statistic,$yftk,$tf,$wfjk,$school,$xueguan,$i,&$data){

		$data[$i]['school_id'] = $school;
		$data[$i]['xueguan'] = $xueguan;
		$data[$i]['year'] = date('Y');
		$data[$i]['month'] = date('m');
		$data[$i]['create_time'] = date('Y-m-d H:i:s');
		$data[$i]['creator'] = 'system';
		$data[$i]['yftk'] = $yftk;
		$data[$i]['tf'] = $tf;
		$data[$i]['wfjk'] = $wfjk;
	}

	/**
	 * 本月学管数据统计
	 */
	public function statistic_month(){

		 $school = M('foo_info');
		 $xg = M('user');
		 $stu = M('hw001.student',NULL);
		 $consump = M('consumption');
		 $course = M('course');

		 $school_id= session('school_id');
		 $xueguan = session('user_name');

		 $school_name = $school->where(['id'=>$school_id])->getField('name');

		 $std_lst = NULL;

		//该学管管理的学员
	 	$std_lst = $stu->where(['school'=>$school_name,'xueguan'=>$xueguan])->getField('std_id',TRUE);
		if(!empty($std_lst)){
			//有费停课的学员筛选
			$yf1 = $consump->where(['std_id'=> array('in',$std_lst)])->group('std_id')->having('sum(value)>0')->getField('std_id',TRUE);
			$yf2 = $course->where(['std_id'=> array('in',$std_lst),'state' => array('in',[200,250])])->group('std_id')->having('sum(hour+ext_hour-used_hour)>0')->getField('std_id',TRUE);

			if(!empty($yf1) && !empty($yf2)){
				$yf = array_unique(array_merge($yf1,$yf2));
			}else if(empty($yf1) && !empty($yf2)){
				$yf = $yf2;
			}else if(empty($yf2) && !empty($yf1)){
				$yf = $yf1;
			}

			if(!empty($yf)){
				$tk = $stu->where(['school'=>$school_name,'xueguan'=>$xueguan,'state'=>2])->getField('std_id',TRUE);
				$yftk = array_intersect($yf, $tk); //有费停课的学员
			}else{
				$yftk = NULL;
			}

			unset($yf1);
			unset($yf2);
			unset($yf);
			unset($tk);


			//退费人数
			$tf = $stu->where(['school'=>$school_name,'xueguan'=>$xueguan,'state'=>5])->getField('std_id',TRUE);


			$wf1 = $consump->where(['std_id'=> array('in',$std_lst)])->group('std_id')->having('sum(value)<=0')->getField('std_id',TRUE);
			$wf2 = $course->where(['std_id'=> array('in',$std_lst),'state' => array('in',[200,250])])->group('std_id')->having('sum(hour+ext_hour-used_hour)=0')->getField('std_id',TRUE);

			if(!empty($wf1) && !empty($wf2)){
				$wf3 = array_intersect($wf1, $wf2);
			}else{
				$wf3 = NULL;
			}


			$yf = $consump->where(['std_id'=> array('in',$std_lst)])->group('std_id')->getField('std_id',TRUE);

			if(!empty($yf)){
				$wf4 = array_diff($std_lst, $yf);
			}else{
				$wf4 = $std_lst;
			}

			if(!empty($wf3)){
				$wf = array_merge($wf4,$wf3);
			}else{
				$wf = $wf4;
			}


			//结课学员人数
			$jk = $stu->where(['school'=>$school_name,'xueguan'=>$xueguan,'state'=>3])->getField('std_id',TRUE);


			$wfjk = array_intersect($wf, $jk);

			unset($wf1);
			unset($wf2);
			unset($wf3);
			unset($wf4);
			unset($wf);
			unset($jk);


			$statistic = M('weihu_month_statistic');

			$year = date('Y');
			$month = date('m');

			$old = $statistic->where(['year'=>$year,'month'=>$month,'school_id'=>$school_id,'xueguan'=>$xueguan])->select();

			if(empty($old)){
				$yftk_tj = empty($yftk)? 0:count($yftk);
				$tf_tj = empty($tf)? 0:count($tf);
				$wfjk_tj = empty($wfjk)? 0:count($wfjk);

				$this->yftk_num = $yftk_tj;
				$this->tf_num = $tf_tj;
				$this->wfjk_num = $wfjk_tj;

				if(!empty($yftk)){
					$yftk_data = $stu->where(['std_id'=>array('in',$yftk)])->select();
					$yf1_data = $consump->field('std_id,sum(value) as all_money')->where(['std_id'=> array('in',$yftk)])->group('std_id')->select();
					$yf2_data = $course->field('std_id,sum(hour+ext_hour) as all_hour,sum(hour+ext_hour-used_hour) as remain_hour')->where(['std_id'=> array('in',$yftk),'state' => array('in',[200,250])])->group('std_id')->select();

					foreach($yftk_data as &$item){
						foreach($yf1_data as $key=>$value){
							if($item['std_id'] == $value['std_id']){
								$item['all_money'] = $value['all_money'];
								break;
							}
						}

						foreach($yf2_data as $key=>$value){
							if($item['std_id'] == $value['std_id']){
								$item['all_hour'] = $value['all_hour'];
								$item['remain_hour'] = $value['remain_hour'];
								break;
							}
						}
					}
					unset($item);

				}else{
					$yftk_data = NULL;
				}


				if(!empty($tf)){
					$tf_data = $stu->where(['std_id'=>array('in',$tf)])->select();
				}else{
					$tf_data = NULL;
				}

				if(!empty($wfjk)){
					$wfjk_data = $stu->where(['std_id'=>array('in',$wfjk)])->select();
				}else{
					$wfjk_data = NULL;
				}

				$this->yftk_std_l = $yftk_data;
				$this->yftk_std = json_encode($yftk_data);
				$this->tf_std_l = $tf_data;
				$this->tf_std = json_encode($tf_data);
				$this->wfjk_std_l = $wfjk_data;
				$this->wfjk_std = json_encode($wfjk_data);

			}else{
				if(!empty($old[0]['yftk'])){
					$old_yftk = explode('@', $old[0]['yftk']);
					$yftk_temp = array_diff($yftk,$old_yftk);
				}else{
					$old_yftk = NULL;
					$yftk_temp = $yftk;
				}

				if(!empty($old[0]['tf'])){
					$old_tf = explode('@', $old[0]['tf']);
					$tf_temp = array_diff($tf,$old_tf);
				}else{
					$old_tf = NULL;
					$yftk_temp = $tf;
				}

				if(!empty($old[0]['wfjk'])){
					$old_wfjk = explode('@', $old[0]['wfjk']);
					$wfjk_temp = array_diff($wfjk,$old_wfjk);
				}else{
					$old_wfjk = NULL;
					$tf_temp = $wfjk;
				}

				$yftk_tj = empty($yftk_temp)? 0:count($yftk_temp);
				$tf_tj = empty($tf_temp)? 0:count($tf_temp);
				$wfjk_tj = empty($wfjk_temp)? 0:count($wfjk_temp);

				$this->yftk_num = $yftk_tj;
				$this->tf_num = $tf_tj;
				$this->wfjk_num = $wfjk_tj;


				if(!empty($yftk_temp)){
					$yftk_temp_data = $stu->where(['std_id'=>array('in',$yftk_temp)])->select();

					$yf1_temp_data = $consump->field('std_id,sum(value) as all_money')->where(['std_id'=> array('in',$yftk_temp)])->group('std_id')->select();
					$yf2_temp_data = $course->field('std_id,sum(hour+ext_hour) as all_hour,sum(hour+ext_hour-used_hour) as remain_hour')->where(['std_id'=> array('in',$yftk_temp),'state' => array('in',[200,250])])->group('std_id')->select();

					foreach($yftk_temp_data as &$item){
						foreach($yf1_temp_data as $key=>$value){
							if($item['std_id'] == $value['std_id']){
								$item['all_money'] = $value['all_money'];
								break;
							}
						}

						foreach($yf2_temp_data as $key=>$value){
							if($item['std_id'] == $value['std_id']){
								$item['all_hour'] = $value['all_hour'];
								$item['remain_hour'] = $value['remain_hour'];
								break;
							}
						}
					}
					unset($item);
				}else{
					$yftk_temp_data = NULL;
				}


				if(!empty($tf_temp)){
					$tf_temp_data = $stu->where(['std_id'=>array('in',$tf_temp)])->select();
				}else{
					$tf_temp_data = NULL;
				}

				if(!empty($wfjk_temp)){
					$wfjk_temp_data = $stu->where(['std_id'=>array('in',$wfjk_temp)])->select();
				}else{
					$wfjk_temp_data = NULL;
				}
				$this->yftk_std_l =  $yftk_temp_data;
				$this->yftk_std =  json_encode($yftk_temp_data);
				$this->tf_std_l = $tf_temp_data;
				$this->tf_std = json_encode($tf_temp_data);
				$this->wfjk_std_l = $wfjk_temp_data;
				$this->wfjk_std = json_encode($wfjk_temp_data);
			}

	//		$yftk_stdlst = explode('@', $this->$yftk_std);
	//		$tf_stdlst = explode('@', $this->$tf_std);
	//		$wfjk_stdlst = explode('@', $this->$wfjk_std);

			unset($stu_lst);

			$stu = M('hw001.student',NULL);
			//有效在读人数
			$stu_lst = $stu->where(['school'=>$school_name,'xueguan'=>$xueguan,'state'=>1])->select();
			$this->validNum = empty($stu_lst)? 0:count($stu_lst);

			$std_lst2 = array_column($stu_lst,'std_id');

			if(!empty($std_lst2)){
				$cls = M('hw001.class',NULL);
				$beginDate = date('Y-m-01', strtotime(date("Y-m-d")));
				$endDate = date('Y-m-d', strtotime("$beginDate +1 month -1 day"));
				$nowDate = date('Y-m-d');
				$lession = $cls->where(['std_id'=>array('in',$std_lst2),'timee'=>array(array('egt',$beginDate),array('elt',$endDate))])->order('std_id')->select();

				if(!empty($lession)){
					$lession_num = array_sum(array_column($lession,'count'));

					$less_std_lst = array_unique(array_column($lession,'std_id'));
					$less_stu_lst = $stu->where(['std_id'=>array('in',$less_std_lst)])->select();

					foreach($lession as &$item){
						foreach($less_stu_lst as $key=>$value){
							if($item['std_id'] == $value['std_id']){
								$item['name'] = $value['name'];
								$item['grade'] = $value['grade'];
								$item['wl'] = $value['wl'];
								$item['school'] = $value['school'];
								$item['tel'] = $value['tel'];
								break;
							}
						}
					}
					unset($item);
				}else{
					$lession_num = 0;
				}

				$lession_used = $cls->where(['std_id'=>array('in',$std_lst2),'timee'=>array(array('egt',$beginDate),array('elt',$nowDate))])->order('std_id')->select();
				if(!empty($lession_used)){
					$lession_used_num = array_sum(array_column($lession_used,'count'));

					$used_std_lst = array_unique(array_column($lession,'std_id'));
					$used_stu_lst = $stu->where(['std_id'=>array('in',$used_std_lst)])->select();

					foreach($lession_used as &$item){
						foreach($used_stu_lst as $key=>$value){
							if($item['std_id'] == $value['std_id']){
								$item['name'] = $value['name'];
								$item['grade'] = $value['grade'];
								$item['wl'] = $value['wl'];
								$item['school'] = $value['school'];
								$item['tel'] = $value['tel'];
								break;
							}
						}
					}
					unset($item);
				}else{
					$lession_used_num = 0;
				}

			}else{
				$lession = NULL;
				$lession_used = NULL;
				$lession_num = 0;
				$lession_used_num = 0;
			}

			$this->lession_l = $lession;
			$this->lession = json_encode($lession);

			$this->lession_used_l = $lession_used;
			$this->lession_used = json_encode($lession_used);

			$this->lession_num = empty($lession_num)? 0:$lession_num;
			$this->lession_used_num = empty($lession_used_num)? 0:$lession_used_num;
			$this->stu_lst_l = $stu_lst;
			$this->stu_lst = json_encode($stu_lst);
			$this->maxCount = count($stu_lst);

		 }else{
		 	$this->maxCount = 1;
		 }



		$this->display('statistic_month');
	}


	public function jx_month_statistic(){

		 $school = M('foo_info');
		 $xg = M('user');
		 $stu = M('hw001.student',NULL);
		 $consump = M('consumption');
		 $course = M('course');

		 $school_id= session('school_id');


		 $school_name = $school->where(['id'=>$school_id])->getField('name');
		 $xg_lst = $xg->where(['school'=>$school_id,'position_id'=>18,'is_del'=>0])->getField('name',TRUE);

		 for($i=0;$i<count($xg_lst);$i++){
		 	$xueguan = $xg_lst[$i];


			$std_lst = NULL;

			//该学管管理的学员
		 	$std_lst = $stu->where(['school'=>$school_name,'xueguan'=>$xueguan])->getField('std_id',TRUE);
			if(!empty($std_lst)){
				$result[$i]['xueguan'] = $xueguan;
				//有费停课的学员筛选
				$yf1 = $consump->where(['std_id'=> array('in',$std_lst)])->group('std_id')->having('sum(value)>0')->getField('std_id',TRUE);
				$yf2 = $course->where(['std_id'=> array('in',$std_lst),'state' => array('in',[200,250])])->group('std_id')->having('sum(hour+ext_hour-used_hour)>0')->getField('std_id',TRUE);

				if(!empty($yf1) && !empty($yf2)){
					$yf = array_unique(array_merge($yf1,$yf2));
				}else if(empty($yf1) && !empty($yf2)){
					$yf = $yf2;
				}else if(empty($yf2) && !empty($yf1)){
					$yf = $yf1;
				}

				if(!empty($yf)){
					$tk = $stu->where(['school'=>$school_name,'xueguan'=>$xueguan,'state'=>2])->getField('std_id',TRUE);
					$yftk = array_intersect($yf, $tk); //有费停课的学员
				}else{
					$yftk = NULL;
				}

				unset($yf1);
				unset($yf2);
				unset($yf);
				unset($tk);


				//退费人数
				$tf = $stu->where(['school'=>$school_name,'xueguan'=>$xueguan,'state'=>5])->getField('std_id',TRUE);


				$wf1 = $consump->where(['std_id'=> array('in',$std_lst)])->group('std_id')->having('sum(value)<=0')->getField('std_id',TRUE);
				$wf2 = $course->where(['std_id'=> array('in',$std_lst),'state' => array('in',[200,250])])->group('std_id')->having('sum(hour+ext_hour-used_hour)=0')->getField('std_id',TRUE);

				if(!empty($wf1) && !empty($wf2)){
					$wf3 = array_intersect($wf1, $wf2);
				}else{
					$wf3 = NULL;
				}


				$yf = $consump->where(['std_id'=> array('in',$std_lst)])->group('std_id')->getField('std_id',TRUE);

				if(!empty($yf)){
					$wf4 = array_diff($std_lst, $yf);
				}else{
					$wf4 = $std_lst;
				}

				if(!empty($wf3)){
					$wf = array_merge($wf4,$wf3);
				}else{
					$wf = $wf4;
				}


				//结课学员人数
				$jk = $stu->where(['school'=>$school_name,'xueguan'=>$xueguan,'state'=>3])->getField('std_id',TRUE);


				$wfjk = array_intersect($wf, $jk);

				unset($wf1);
				unset($wf2);
				unset($wf3);
				unset($wf4);
				unset($wf);
				unset($jk);


				$statistic = M('weihu_month_statistic');

				$year = date('Y');
				$month = date('m');

				$old = $statistic->where(['year'=>$year,'month'=>$month,'school_id'=>$school_id,'xueguan'=>$xueguan])->select();

				if(empty($old)){
					$yftk_tj = empty($yftk)? 0:count($yftk);
					$tf_tj = empty($tf)? 0:count($tf);
					$wfjk_tj = empty($wfjk)? 0:count($wfjk);

					$result[$i]['yftk_num'] = $yftk_tj;
					$result[$i]['tf_num'] = $yftk_tj;
					$result[$i]['wfjk_num'] = $wfjk_tj;

	//				$this->yftk_num = $yftk_tj;
	//				$this->tf_num = $tf_tj;
	//				$this->wfjk_num = $wfjk_tj;

					if(!empty($yftk)){
						$yftk_data = $stu->where(['std_id'=>array('in',$yftk)])->select();
						$yf1_data = $consump->field('std_id,sum(value) as all_money')->where(['std_id'=> array('in',$yftk)])->group('std_id')->select();
						$yf2_data = $course->field('std_id,sum(hour+ext_hour) as all_hour,sum(hour+ext_hour-used_hour) as remain_hour')->where(['std_id'=> array('in',$yftk),'state' => array('in',[200,250])])->group('std_id')->select();

						foreach($yftk_data as &$item){
							foreach($yf1_data as $key=>$value){
								if($item['std_id'] == $value['std_id']){
									$item['all_money'] = $value['all_money'];
									break;
								}
							}

							foreach($yf2_data as $key=>$value){
								if($item['std_id'] == $value['std_id']){
									$item['all_hour'] = $value['all_hour'];
									$item['remain_hour'] = $value['remain_hour'];
									break;
								}
							}
						}
						unset($item);

					}else{
						$yftk_data = NULL;
					}


					if(!empty($tf)){
						$tf_data = $stu->where(['std_id'=>array('in',$tf)])->select();
					}else{
						$tf_data = NULL;
					}

					if(!empty($wfjk)){
						$wfjk_data = $stu->where(['std_id'=>array('in',$wfjk)])->select();
					}else{
						$wfjk_data = NULL;
					}

					$result[$i]['yftk_std_l'] = $yftk_data;
					$result[$i]['yftk_std'] = json_encode($yftk_data);
					$result[$i]['tf_std_l'] = $tf_data;
					$result[$i]['tf_std'] = json_encode($tf_data);
					$result[$i]['wfjk_std_l'] = $wfjk_data;
					$result[$i]['wfjk_std'] = json_encode($wfjk_data);




	//				$this->yftk_std_l = $yftk_data;
	//				$this->yftk_std = json_encode($yftk_data);
	//				$this->tf_std_l = $tf_data;
	//				$this->tf_std = json_encode($tf_data);
	//				$this->wfjk_std_l = $wfjk_data;
	//				$this->wfjk_std = json_encode($wfjk_data);

				}else{
					if(!empty($old[0]['yftk'])){
						$old_yftk = explode('@', $old[0]['yftk']);
						$yftk_temp = array_diff($yftk,$old_yftk);
					}else{
						$old_yftk = NULL;
						$yftk_temp = $yftk;
					}

					if(!empty($old[0]['tf'])){
						$old_tf = explode('@', $old[0]['tf']);
						$tf_temp = array_diff($tf,$old_tf);
					}else{
						$old_tf = NULL;
						$yftk_temp = $tf;
					}

					if(!empty($old[0]['wfjk'])){
						$old_wfjk = explode('@', $old[0]['wfjk']);
						$wfjk_temp = array_diff($wfjk,$old_wfjk);
					}else{
						$old_wfjk = NULL;
						$tf_temp = $wfjk;
					}

					$yftk_tj = empty($yftk_temp)? 0:count($yftk_temp);
					$tf_tj = empty($tf_temp)? 0:count($tf_temp);
					$wfjk_tj = empty($wfjk_temp)? 0:count($wfjk_temp);


					$result[$i]['yftk_num'] = $yftk_tj;
					$result[$i]['tf_num'] = $tf_tj;
					$result[$i]['wfjk_num'] = $wfjk_tj;


	//				$this->yftk_num = $yftk_tj;
	//				$this->tf_num = $tf_tj;
	//				$this->wfjk_num = $wfjk_tj;


					if(!empty($yftk_temp)){
						$yftk_temp_data = $stu->where(['std_id'=>array('in',$yftk_temp)])->select();

						$yf1_temp_data = $consump->field('std_id,sum(value) as all_money')->where(['std_id'=> array('in',$yftk_temp)])->group('std_id')->select();
						$yf2_temp_data = $course->field('std_id,sum(hour+ext_hour) as all_hour,sum(hour+ext_hour-used_hour) as remain_hour')->where(['std_id'=> array('in',$yftk_temp),'state' => array('in',[200,250])])->group('std_id')->select();

						foreach($yftk_temp_data as &$item){
							foreach($yf1_temp_data as $key=>$value){
								if($item['std_id'] == $value['std_id']){
									$item['all_money'] = $value['all_money'];
									break;
								}
							}

							foreach($yf2_temp_data as $key=>$value){
								if($item['std_id'] == $value['std_id']){
									$item['all_hour'] = $value['all_hour'];
									$item['remain_hour'] = $value['remain_hour'];
									break;
								}
							}
						}
						unset($item);
					}else{
						$yftk_temp_data = NULL;
					}


					if(!empty($tf_temp)){
						$tf_temp_data = $stu->where(['std_id'=>array('in',$tf_temp)])->select();
					}else{
						$tf_temp_data = NULL;
					}

					if(!empty($wfjk_temp)){
						$wfjk_temp_data = $stu->where(['std_id'=>array('in',$wfjk_temp)])->select();
					}else{
						$wfjk_temp_data = NULL;
					}

					$result[$i]['yftk_std_l'] = $yftk_temp_data;
					$result[$i]['yftk_std'] = json_encode($yftk_temp_data);
					$result[$i]['tf_std_l'] = $tf_temp_data;
					$result[$i]['tf_std'] = json_encode($tf_temp_data);
					$result[$i]['wfjk_std_l'] = $wfjk_temp_data;
					$result[$i]['wfjk_std'] = json_encode($wfjk_temp_data);

	//				$this->yftk_std_l =  $yftk_temp_data;
	//				$this->yftk_std =  json_encode($yftk_temp_data);
	//				$this->tf_std_l = $tf_temp_data;
	//				$this->tf_std = json_encode($tf_temp_data);
	//				$this->wfjk_std_l = $wfjk_temp_data;
	//				$this->wfjk_std = json_encode($wfjk_temp_data);
				}


				unset($stu_lst);

				$stu = M('hw001.student',NULL);
				//有效在读人数
				$stu_lst = $stu->where(['school'=>$school_name,'xueguan'=>$xueguan,'state'=>1])->select();

				$result[$i]['validNum'] = empty($stu_lst)? 0:count($stu_lst);

	//			$this->validNum = empty($stu_lst)? 0:count($stu_lst);

				$std_lst2 = array_column($stu_lst,'std_id');

				if(!empty($std_lst2)){
					$cls = M('hw001.class',NULL);
					$beginDate = date('Y-m-01', strtotime(date("Y-m-d")));
					$endDate = date('Y-m-d', strtotime("$beginDate +1 month -1 day"));
					$nowDate = date('Y-m-d');
					$lession = $cls->where(['std_id'=>array('in',$std_lst2),'timee'=>array(array('egt',$beginDate),array('elt',$endDate))])->order('std_id')->select();

					if(!empty($lession)){
						$lession_num = array_sum(array_column($lession,'count'));

						$less_std_lst = array_unique(array_column($lession,'std_id'));
						$less_stu_lst = $stu->where(['std_id'=>array('in',$less_std_lst)])->select();

						foreach($lession as &$item){
							foreach($less_stu_lst as $key=>$value){
								if($item['std_id'] == $value['std_id']){
									$item['name'] = $value['name'];
									$item['grade'] = $value['grade'];
									$item['wl'] = $value['wl'];
									$item['school'] = $value['school'];
									$item['tel'] = $value['tel'];
									break;
								}
							}
						}
						unset($item);
					}else{
						$lession_num = 0;
					}

					$lession_used = $cls->where(['std_id'=>array('in',$std_lst2),'timee'=>array(array('egt',$beginDate),array('elt',$nowDate))])->order('std_id')->select();
					if(!empty($lession_used)){
						$lession_used_num = array_sum(array_column($lession_used,'count'));

						$used_std_lst = array_unique(array_column($lession,'std_id'));
						$used_stu_lst = $stu->where(['std_id'=>array('in',$used_std_lst)])->select();

						foreach($lession_used as &$item){
							foreach($used_stu_lst as $key=>$value){
								if($item['std_id'] == $value['std_id']){
									$item['name'] = $value['name'];
									$item['grade'] = $value['grade'];
									$item['wl'] = $value['wl'];
									$item['school'] = $value['school'];
									$item['tel'] = $value['tel'];
									break;
								}
							}
						}
						unset($item);
					}else{
						$lession_used_num = 0;
					}

				}else{
					$lession = NULL;
					$lession_used = NULL;
					$lession_num = 0;
					$lession_used_num = 0;
				}

				$result[$i]['lession_l'] = $lession;
				$result[$i]['lession'] = json_encode($lession);

				$result[$i]['lession_used_l'] = $lession_used;
				$result[$i]['lession_used'] = json_encode($lession_used);

				$result[$i]['lession_num'] = empty($lession_num)? 0:$lession_num;
				$result[$i]['lession_used_num'] = empty($lession_used_num)? 0:$lession_used_num;

				$result[$i]['stu_lst_l'] = $stu_lst;
				$result[$i]['stu_lst'] = json_encode($stu_lst);
				$result[$i]['maxCount'] = count($stu_lst);


	//			$this->lession_l = $lession;
	//			$this->lession = json_encode($lession);
	//
	//			$this->lession_used_l = $lession_used;
	//			$this->lession_used = json_encode($lession_used);
	//
	//			$this->lession_num = empty($lession_num)? 0:$lession_num;
	//			$this->lession_used_num = empty($lession_used_num)? 0:$lession_used_num;
	//			$this->stu_lst_l = $stu_lst;
	//			$this->stu_lst = json_encode($stu_lst);
	//			$this->maxCount = count($stu_lst);

			 }else{
			 	$result[$i]['maxCount'] = 1;
	//		 	$this->maxCount = 1;
			 }
		 }



		$this->result = $result;
		$this->display();
	}

   //所有学习管理师月排课时排行
   public function xueguan_ks_statistic(){

		$pageNum = I('get.pageNumber');
		$pageCount = I('get.pageCount');

		$pageNum = empty($pageNum)?$this->pageNumber:$pageNum;
		$pageCount = empty($pageCount)?$this->pageCount:$pageCount;


		$school_id = session('school_id');

		$keshi_xueguan = I('post.xueguan_s');
		$keshi_school = I('post.school_s');
		$keywords = I('post.keyword');
		$keshi_month = I('post.year_month');


		$temp_str = "";

		if(!empty($keshi_school) || $keshi_school === '0')
		{
			$w['school'] = get_school_name($keshi_school);
			$temp_str .= " and st.school='". $w['school'] ."'";
		}


		$cls = M('hw001.class',NULL);

		if(!empty($keshi_month)){
			$month = substr($keshi_month, 5);
			$datexx = $keshi_month;
		}else{
			$month = date('m');
			$datexx=date('Y-m');
		}

        $ee=$datexx.'-'.'01';
        $c=strtotime(date('Y-m-01'));//获取月初时间戳
        $cc=$c+date('t',$c)*24*3600;//获取月末时间戳
		$cc = date('Y-m-d',$cc);


		$temp_cl = " and cl.timee>='". $ee ."' and cl.timee<'" . $cc ."'";

		if(!empty($keshi_xueguan))
		{
			$temp_str .= " and us.name='" . $keshi_xueguan . "'";
		}

		if(!empty($keywords))
		{
			$temp_str .= " and us.name like '%" . $keywords . "%'";
		}


		$ws['position_id'] = 18;
		$ws['is_del'] = 0;

		if($school_id !== '0' ){
			$w['school'] = get_school_name($school_id);
			$ws['school'] = $school_id;
			$temp_str .= " and st.school='". $w['school'] ."'";
		}

		$xg = M('user');

		$xg_lst = $xg->where($ws)->getField('name as id,name');
		//设置页面select组件内容
		$this->xueguan=$xg_lst;

		$model = new \Think\Model();

		$sql_str = "select stus.xueguan,stus.school,count(cl.count) as keshi,$month as month from hw001.class cl ,";
		$sql_str .= " (select st.std_id,st.xueguan,st.school from hw001.student st ,hongwen_oa.oa_user us where st.xueguan = us.name and us.position_id=18 and us.is_del=0" . $temp_str . ") as stus";
		$sql_str .= " where cl.std_id = stus.std_id and cl.std_id != '' " . $temp_cl . " group by stus.xueguan order by keshi desc";

		$data_all = $model->query($sql_str);

		$sql_str .= " limit " . $pageNum . "," . $pageCount;

		$data_lst = $model->query($sql_str);


		if(IS_AJAX && I('get.pageCount')){
			// 发送给页面的数据
			$this->ajaxReturn([
				'state'=>'ok',//查询结果
				'maxCount'=>count($data_all),//查询到数据库有多少条满足条件记录
				'data'=>$data_lst
			  ]);
		}else{
			$this->list = $data_lst;
			$this->maxCount = count($data_all);
			$this->display();
		}


	}

	//所有学习管理师月续费业绩排行
	public function xueguan_yj_statistic(){

		$pageNum = I('get.pageNumber');
		$pageCount = I('get.pageCount');

		$pageNum = empty($pageNum)?$this->pageNumber:$pageNum;
		$pageCount = empty($pageCount)?$this->pageCount:$pageCount;

		$school_id = session('school_id');

		$xg = M('user');
		$wg['position_id'] = array('in',[12,13,18,19]);
		$wg['is_del'] = 0;

		if($school_id !== '0'){
			$wg['school'] = $school_id;
			$wc['emp_school'] = $school_id;
		}

		$yj_xueguan = I('post.xueguan_s');
		$yj_school = I('post.school_s');
		$keywords = I('post.keyword');
		$yj_month = I('post.year_month');
		$yj_day = I('post.year_month_day');

		if(!empty($yj_school) || $yj_school === '0')
		{
			$wg['school'] = $yj_school;
			$wc['emp_school'] = $yj_school;
		}



		$xg_lst = $xg->where($wg)->getField('name',TRUE);

		$consump = M('consumption');

		$wc['belong_type'] = 2;

		if(!empty($yj_xueguan)){
			$wc['belong_user_name']= $yj_xueguan;
		}else if(!empty($keywords)){
			$wc['belong_user_name'] = array('like','%'.$keywords.'%');
		}else{
			$wc['belong_user_name']= array('in',$xg_lst);
		}

		if(!empty($yj_month)){
			$month = $yj_month;
		}else{
			$month = date('Y-m');
		}

		if(!empty($yj_day)){
			$wc["FROM_UNIXTIME(create_time,'%Y-%m-%d')"]= $yj_day;
		}

		$wc['month'] = $month;

		$data_all = $consump->field('belong_user_name,sum(value) as money')->where($wc)->group('belong_user_name')->select();
		$data_lst = $consump->field("emp_school,belong_user_name,month,sum(value) as money")->where($wc)->group('belong_user_name')->order('emp_school,money DESC')->limit($pageNum,$pageCount)->select();

		foreach($data_lst as &$item){
			$item['emp_school'] = get_school_name($item['emp_school']);
		}

		unset($item);

		$xg_front_lst = $xg->where($wg)->getField('name as id,name');
		//设置页面select组件内容
		$this->xueguan=$xg_front_lst;

		if(IS_AJAX && I('get.pageCount')){
			// 发送给页面的数据
			$this->ajaxReturn([
				'state'=>'ok',//查询结果
				'maxCount'=>count($data_all),//查询到数据库有多少条满足条件记录
				'data'=>$data_lst
			  ]);
		}else{
			$this->list = $data_lst;
			$this->maxCount = count($data_all);
			$this->display();
		}

	}

//学习管理师各项数据统计

	public function xg_all_statistic(){
					
		if((session('position_id') != 18) || ($school_id == '0')){
			$this->statis_count = 0;
			$this->dk_count = 0;
			$this->dtk_tk_count = 0;
			$this->xf_count = 0;
			$this->tk_count = 0;
			$this->jk_count = 0;
			$this->tf_count = 0;
			$this->wh_count = 0;
			$this->yx_count = 0;
			$this->hwh_count = 0;
			$this->hjh_count = 0;
			$this->ten_count = 0;
			$this->display();
			die;
		}
		$school = M('foo_info');
		$xg = M('user');
		$stu = M('hw001.student',NULL);
		$consump = M('consumption');
		$course = M('course');
		$class = M('hw001.class',NULL);
		
		$position_id = session('position_id');
		$xueguan = session('user_name');
		
		$this->xueguan = $xueguan; //页面赋值
		
		$school_id= session('school_id');

		if($school_id !== '0'){
			$wx['school'] = $school_id;
			
			$w_ks_str = " and us.school='" . $school_id . "' ";
			
			$school_name = get_school_name($school_id);
			$school_str = " and st.school='".$school_name."' ";
		}

		if($position_id == '18' && $school_id !== '0'){
			//学管账户进来
			$xg_lst = ['name'=>$xueguan];
			$xg_w = " and st.xueguan='".$xueguan."' ";
		}

		$cycle = I('get.cycle');
		if(empty($cycle)){
			$cycle = 1;
		}
		$this->cycle= $cycle;
		$nowDate = date('Y-m-d');
		
		$week = get_date($nowDate,'w'); //本周
		$month = get_date($nowDate,'m'); //本月
		
		$model = new \Think\Model();

		//统计课时数及上课人数
		$sql_str = "select stus.xueguan,sum(count) as ksnum,count(distinct stus.std_id) as pnum from hw001.class as cl, ";
		$sql_str .=" (select st.std_id,st.name,st.xueguan from hw001.student as st,hongwen_oa.oa_user as us where st.xueguan = us.name and us.position_id=18 " . $w_ks_str . " and us.is_del=0 and st.state=1 " . $xg_w . $school_str . ") as stus";
		
		if($cycle == 1){
			$w_ks = " and date_format(cl.timee, '%Y-%m-%d')='".$nowDate."' ";
		}else if($cycle == 2){
			$w_ks = " and date_format(cl.timee, '%Y-%m-%d')>='". date('Y-m-d',strtotime($week[0])) . "' ";
			$w_ks .= " and date_format(cl.timee, '%Y-%m-%d')<='".date('Y-m-d',strtotime($week[1])) . "' ";		
		}elseif($cycle == 3){
			$w_ks = " and date_format(cl.timee, '%Y-%m-%d')>='". date('Y-m-d',strtotime($month[0])) . "' ";
			$w_ks .= " and date_format(cl.timee, '%Y-%m-%d')<='".date('Y-m-d',strtotime($month[1])) . "' ";
		}
			
		
		$sql_str .=" where cl.std_id=stus.std_id " . $w_ks . " group by stus.xueguan";

		$data_statis = $model->query($sql_str);
		$this->statis = $data_statis; //页面赋值；

		//获取上课人的详情

		$sql_dt_str =  "select cl.id,stus.std_id,stus.name,stus.grade,cl.class,cl.time1,cl.time2,cl.timee,cl.teacher,cl.count,stus.xueguan from hw001.class as cl, ";
		$sql_dt_str .=" (select st.std_id,st.name,st.grade,st.xueguan from hw001.student as st,hongwen_oa.oa_user as us where st.xueguan = us.name and us.position_id=18 " . $w_ks_str . " and us.is_del=0 and st.state=1 " . $xg_w . $school_str . ") as stus";
		$sql_dt_str .=" where cl.std_id=stus.std_id " . $w_ks . " order by stus.xueguan,stus.std_id,cl.count";


		$data_lst = $model->query($sql_dt_str);
		$this->statis_data = $data_lst;//页面赋值；
		$this->statis_json = json_encode($data_lst);
		$this->statis_count = count($data_lst)?count($data_lst):0;
		
		
		if($cycle == 1){
			$w_dk = " and DATE_FORMAT(dtk_time,'%Y-%m-%d')='" . date('Y-m-d'). "' ";
		}else if($cycle == 2){
			$w_dk = " and DATE_FORMAT(dtk_time,'%Y-%m-%d') >='" . date('Y-m-d',strtotime($week[0])) . "' ";
			$w_dk .= " and DATE_FORMAT(dtk_time,'%Y-%m-%d') <='" . date('Y-m-d',strtotime($week[1])) . "' ";
					
		}elseif($cycle == 3){
			$w_dk = " and DATE_FORMAT(dtk_time,'%Y-%m-%d') >='" . date('Y-m-d',strtotime($month[0])) . "' ";
			$w_dk .= " and DATE_FORMAT(dtk_time,'%Y-%m-%d') <='" . date('Y-m-d',strtotime($month[1])) . "' ";
		}
		
		
		//删课时统计
		$sql_str1 = "select st.name,st.grade,odr.class,odr.time1,odr.time2,odr.timee,odr.teacher,odr.count,st.xueguan,odr.cid,odr.dtk_time,odr.dtk_type,odr.reason from oa_dt_record as odr,hw001.student as st where odr.std_id = st.std_id and dtk_type like '%删除%'" . $xg_w . $w_dk;
		$sql_str1 .=  " group by cid having count(*)>=2 order by dtk_time DESC,count"; //一门课程多次删除，记为一次；
		
		$sql_str2 = "select st.name,st.grade,odr.class,odr.time1,odr.time2,odr.timee,odr.teacher,odr.count,st.xueguan,odr.cid,odr.dtk_time,odr.dtk_type,odr.reason from oa_dt_record as odr,hw001.student as st where odr.std_id = st.std_id and dtk_type like '%删除%'" . $xg_w . $w_dk;
 
		$sql_str2 .=  " group by cid having count(*)<2 order by dtk_time,count";
		
		$dk_tmp1 = $model->query($sql_str1);
		$dk_tmp2 = $model->query($sql_str2);
		//删课详情
		$dk_lst = array_merge($dk_tmp1,$dk_tmp2);
		
		$sort_xg = [];
		$sort_std = [];
		
		foreach($dk_lst as $key=>$val){
			$sort_xg[$key] = $val['xueguan'];
			$sort_std[$key] = $val['std_id'];
		}
		
		array_multisort($sort_xg,SORT_ASC,$sort_std,SORT_ASC,$dk_lst);
		
		
		$this->dk_lst = $dk_lst; //页面赋值；
		$this->dk_json = json_encode($dk_lst);
		$this->dk_count = count($dk_lst)?count($dk_lst):0;
		
		$dk_count_lst = array_column($dk_lst,'count');
		$dk_count = array_sum($dk_count_lst); //当日删课课时统计
		
		$this->dk_sum = $dk_count; //页面赋值；
		
		//调课统计
		unset($sql_str1);
		unset($sql_str2);
		
		//调课时统计
		$sql_str1 = "select st.name,st.grade,odr.class,odr.time1,odr.time2,odr.timee,odr.teacher,odr.count,st.xueguan,odr.cid,odr.dtk_time,odr.dtk_type,odr.reason from oa_dt_record as odr,hw001.student as st where odr.std_id = st.std_id and dtk_type like '%调课%'" . $xg_w . $w_dk;
		$sql_str1 .=  " group by cid having count(*)>=2 order by dtk_time DESC,count";
		
		$sql_str2 = "select st.name,st.grade,odr.class,odr.time1,odr.time2,odr.timee,odr.teacher,odr.count,st.xueguan,odr.cid,odr.dtk_time,odr.dtk_type,odr.reason from oa_dt_record as odr,hw001.student as st where odr.std_id = st.std_id and dtk_type like '%调课%'" . $xg_w . $w_dk;
 
		$sql_str2 .=  " group by cid having count(*)<2 order by dtk_time,count";
		
		$tk_tmp1 = $model->query($sql_str1);
		$tk_tmp2 = $model->query($sql_str2);
		
		//调课详情
		$tk_lst = array_merge($tk_tmp1,$tk_tmp2);
		
		
		$sort_xg = [];
		$sort_std = [];
		
		foreach($tk_lst as $key=>$val){
			$sort_xg[$key] = $val['xueguan'];
			$sort_std[$key] = $val['std_id'];
		}
		
		array_multisort($sort_xg,SORT_ASC,$sort_std,SORT_ASC,$tk_lst);
		
		
		$this->tk_lst = $tk_lst; //页面赋值；
		$this->tk_json = json_encode($tk_lst);
		$this->dtk_tk_count = count($tk_lst)?count($tk_lst):0;
		
		$tk_count_lst = array_column($tk_lst,'count');
		$dtk_tk_count = array_sum($tk_count_lst); //当日调课课时统计
	
		$this->tk_sum = $dtk_tk_count; //页面赋值；
		
		
		if($cycle == 1){
			$w_xf = "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') = date_format(NOW(), '%Y-%m-%d') ";
		}else if($cycle == 2){
			$w_xf = "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($week[0])) . "' ";
			$w_xf .= "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($week[1])) . "' ";
					
		}elseif($cycle == 3){
			$w_xf = "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($month[0])) . "' ";
			$w_xf .= "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($month[1])) . "' ";
		}
		
		//今日续费人数
		unset($sql_str);
		$sql_str = "SELECT st.name,st.grade,st.xueguan,cs.std_id, sum(cs.value) as money FROM  hongwen_oa.oa_consumption as cs,hw001.student as st ";
		$sql_str .= " where cs.std_id = st.std_id " . $w_xf . " and st.state=1 AND cs.belong_type = 2 " . $xg_w . " group by st.school,st.xueguan, cs.std_id";

	
		$xf_lst = $model->query($sql_str); //续费详情
		$xf_count = count($xf_lst)?count($xf_lst):0; //续费总人数
		$xf_value_lst = array_column($xf_lst,'money');
		$xf_value = array_sum($xf_value_lst); //续费总金额
		
		$this->xf_lst = $xf_lst; //页面赋值；
		$this->xf_json = json_encode($xf_lst);
		$this->xf_count = $xf_count; //页面赋值；
		$this->xf_money = $xf_value; //页面赋值；
		
		//今日停课人数
		unset($sql_str);
		unset($sql_str_yf);
		unset($sql_str_tk);
		
		
		$xg_stat = M('xg_statistic');
		
		
		if($cycle == 1){
			$w_tk['ocur_date'] = date('Y-m-d');
			$w_tk['xueguan'] = $xueguan;
			$w_tk['type'] = '停课';
			$yf_tk_data = $xg_stat->where($w_tk)->order('xueguan,std_id')->select();	
		}elseif($cycle == 2){
			$w_tk['ocur_date'] = date('Y-m-d');
			$w_tk['xueguan'] = $xueguan;
			$w_tk['type'] = '停课';
			$yf_tk_data1 = $xg_stat->where($w_tk)->order('xueguan,std_id')->select();
			
			$w_tk['ocur_date'] = date('Y-m-d',strtotime('-1 day',strtotime($week[0]))); 
			$yf_tk_data2 = $xg_stat->where($w_tk)->order('xueguan,std_id')->select();
			
			
			$std_lst_tk = array_column($yf_tk_data2,'std_id');
			foreach($yf_tk_data1 as $k=>$v){
				if(array_search($v['std_id'], $std_lst_tk) !== FALSE){
					unset($yf_tk_data1[$k]);
				}
			}
			
			$yf_tk_data = array_values($yf_tk_data1);
			
			
			
		}elseif($cycle == 3){
			$w_tk['ocur_date'] = date('Y-m-d');
			$w_tk['xueguan'] = $xueguan;
			$w_tk['type'] = '停课';
			$yf_tk_data1 = $xg_stat->where($w_tk)->order('xueguan,std_id')->select();
			
			$w_tk['ocur_date'] = date('Y-m-d',strtotime('-1 day',strtotime($month[0]))); 
			$yf_tk_data2 = $xg_stat->where($w_tk)->order('xueguan,std_id')->select();
			

			$std_lst_tk = array_column($yf_tk_data2,'std_id');
			foreach($yf_tk_data1 as $k=>$v){
				if(array_search($v['std_id'], $std_lst_tk) !== FALSE){
					unset($yf_tk_data1[$k]);
				}
			}
			
			$yf_tk_data = array_values($yf_tk_data1);
			
			
		}
		
		$yf_tk_count = count($yf_tk_data);

		$this->tk_data = $yf_tk_data; //页面赋值；
		$this->tk_json = json_encode($yf_tk_data);
		$this->tk_count = $yf_tk_count?$yf_tk_count:0; //页面赋值；
				      	
		//今日结课人数
		unset($sql_str);
		unset($sql_str_wf);
		unset($sql_str_tk);
		
		if($cycle == 1){
			$w_jk['ocur_date'] = date('Y-m-d');
			$w_jk['xueguan'] = $xueguan;
			$w_jk['type'] = '结课';
			$wf_tk_data1 = $xg_stat->where($w_jk)->order('xueguan,std_id')->select();
			
			$w_jk['ocur_date'] = date('Y-m-d',strtotime('-1 day',strtotime(date('Y-m-d')))); 
			$wf_tk_data2 = $xg_stat->where($w_jk)->order('xueguan,std_id')->select();
			
			$std_lst_jk = array_column($wf_tk_data2,'std_id');
			foreach($wf_tk_data1 as $k=>$v){
				if(array_search($v['std_id'], $std_lst_jk) !== FALSE){
					unset($wf_tk_data1[$k]);
				}
			}
			
			$wf_tk_data = array_values($wf_tk_data1);	
		}elseif($cycle == 2){
			$w_jk['ocur_date'] = date('Y-m-d');
			$w_jk['xueguan'] = $xueguan;
			$w_jk['type'] = '结课';
			$wf_tk_data1 = $xg_stat->where($w_jk)->order('xueguan,std_id')->select();
			
			$w_jk['ocur_date'] = date('Y-m-d',strtotime('-1 day',strtotime($week[0]))); 
			$wf_tk_data2 = $xg_stat->where($w_jk)->order('xueguan,std_id')->select();
			
			$std_lst_jk = array_column($wf_tk_data2,'std_id');
			foreach($wf_tk_data1 as $k=>$v){
				if(array_search($v['std_id'], $std_lst_jk) !== FALSE){
					unset($wf_tk_data1[$k]);
				}
			}
			
			$wf_tk_data = array_values($wf_tk_data1);
			
		}elseif($cycle == 3){
			$w_jk['ocur_date'] = date('Y-m-d');
			$w_jk['xueguan'] = $xueguan;
			$w_jk['type'] = '结课';
			$wf_tk_data1 = $xg_stat->where($w_jk)->order('xueguan,std_id')->select();
			
			$w_jk['ocur_date'] = date('Y-m-d',strtotime('-1 day',strtotime($month[0]))); 
			$wf_tk_data2 = $xg_stat->where($w_jk)->order('xueguan,std_id')->select();
			
			$std_lst_jk = array_column($wf_tk_data2,'std_id');
			foreach($wf_tk_data1 as $k=>$v){
				if(array_search($v['std_id'], $std_lst_jk) !== FALSE){
					unset($wf_tk_data1[$k]);
				}
			}
			
			$wf_tk_data = array_values($wf_tk_data1);
		}
		
		$wf_tk_count = count($wf_tk_data);
		
		$this->jk_data = $wf_tk_data; //页面赋值；
		$this->jk_json = json_encode($wf_tk_data);
		$this->jk_count = $wf_tk_count?$wf_tk_count:0; //页面赋值；
		
		
		if($cycle == 1){
			$w_tf = "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') = DATE_FORMAT(now(), '%Y-%m-%d')) ";
		}else if($cycle == 2){
			$w_tf = "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($week[0])) . "' ";
			$w_tf .= "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($week[1])) . "' ) ";
					
		}elseif($cycle == 3){
			$w_tf = "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($month[0])) . "' ";
			$w_tf .= "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($month[1])) . "' ) ";
		}
		
		//今日退费学员
		unset($sql_str);
		$sql_str = "select std_id,name,grade,school,xueguan,DATE_FORMAT(now(), '%Y-%m-%d') from hw001.student as st";
		$sql_str .= " where std_id in (select std_id from hongwen_oa.oa_consumption where type = 200 " . $w_tf . $xg_w;

		$sql_str .= " group by school,xueguan,std_id order by school,xueguan,std_id ";
		
		$tf_lst = $model->query($sql_str);
		$tf_count = count($tf_lst);
		
		$this->tf_lst = $tf_lst; //页面赋值；
		$this->tf_json = json_encode($tf_lst);
		$this->tf_count = $tf_count?$tf_count:0; //页面赋值；
		
		
		if($cycle == 1){
			$w_wh = "   and DATE_FORMAT(weihu_timee, '%Y-%m-%d') =  DATE_FORMAT(NOW(), '%Y-%m-%d') ";
		}else if($cycle == 2){
			$w_wh = "  and DATE_FORMAT(weihu_timee, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($week[0])) . "' ";
			$w_wh .= "  and DATE_FORMAT(weihu_timee, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($week[1])) . "' ";
					
		}elseif($cycle == 3){
			$w_wh = "  and DATE_FORMAT(weihu_timee, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($month[0])) . "' ";
			$w_wh .= "  and DATE_FORMAT(weihu_timee, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($month[1])) . "' ";
		}
		
		//今日维护人数
		unset($sql_str);
		$sql_str = "select st.name,st.grade,st.xueguan,DATE_FORMAT(wh.weihu_timee,'%Y-%m-%d') as weihu_timee,wh.weihu_content,wh.std_id from hongwen_oa.oa_weihu as wh,hw001.student as st where wh.std_id = st.std_id " . $w_wh  . $xg_w . " group by wh.std_id";

		
		$wh_lst = $model->query($sql_str);
		$wh_count = count($wh_lst);
		
		$this->wh_lst = $wh_lst; //页面赋值；
		$this->wh_json = json_encode($wh_lst);
		$this->wh_count = $wh_count?$wh_count:0; //页面赋值；
		
		//截至本月有效学员数
		
			unset($sql_str);
			/*所有有费学员，但不包含只有特色课堂的学员*/
			$sql_str = "select std_id from hongwen_oa.oa_consumption where state != 50 and is_del != 1 and std_id not in ( ";
			/*只有特色课堂的学员*/
			$sql_str .= " select std_id from oa_course where  oa_course.state in (200,250) and  oa_course.std_id in ( ";
			
			$sql_str .= " select std_id from oa_course where oa_course.unit_plan in ( ";
			
			$sql_str .= " select our.id ";
			
			$sql_str .= " from oa_unitprice_role as our,oa_foo_info as F1,oa_foo_info as F2,oa_foo_info as F3,oa_foo_info as F4 ";
			
			$sql_str .= " where our.school = F1.id and our.grade = F2.id and our.level = F3.id and our.course = F4.id and F4.group = 4)) ";
			
			$sql_str .= " group by oa_course.std_id having sum(oa_course.hour+oa_course.ext_hour-oa_course.used_hour)>0 and count(oa_course.id)=1) ";
			
			$sql_str .= " group by std_id having sum(value)>0 ";
			
			$yf_all = $model->query($sql_str);

			unset($sql_str);
			/*特色课堂有订单学员*/
			$sql_str = "select oa_course.id,std_id,unit_plan from oa_course where oa_course.state in (200,250) and oa_course.std_id in ( ";
			
			$sql_str .= " select std_id from oa_course where oa_course.unit_plan in ( ";
			
			$sql_str .= " select our.id from oa_unitprice_role as our,oa_foo_info as F1,oa_foo_info as F2,oa_foo_info as F3,oa_foo_info as F4 ";
			
			$sql_str .= " where our.school = F1.id and our.grade = F2.id and our.level = F3.id and our.course = F4.id and F4.group = 4 ))";
			
			$sql_str .= " group by oa_course.std_id having sum(oa_course.hour+oa_course.ext_hour-oa_course.used_hour)>0 and count(oa_course.id)>1 ";
			
			$ydd1 = $model->query($sql_str);
			
			unset($sql_str);
			
			/*有订单学员，但不包含有特色课堂的学员*/
			$sql_str = "select std_id from oa_course where  oa_course.state in (200,250) and oa_course.unit_plan not in ( ";
			
			$sql_str .= " select our.id from oa_unitprice_role as our,oa_foo_info as F1,oa_foo_info as F2,oa_foo_info as F3,oa_foo_info as F4 ";
			
			$sql_str .= " where our.school = F1.id and our.grade = F2.id and our.level = F3.id and our.course = F4.id and F4.group = 4 ) ";
			
			$sql_str .= " group by oa_course.std_id having sum(oa_course.hour+oa_course.ext_hour-oa_course.used_hour)>0 ";
			
			$ydd2 = $model->query($sql_str);
			
			$yx_lst = array_merge(array_column($yf_all,'std_id'),array_column($ydd1,'std_id'),array_column($ydd2,'std_id'));
			
			
			/*$w_st['st.std_id'] = array('in',$yx_lst);
			$w_st['st.xueguan'] = $xueguan;
			$w_st['st.school'] = $school_name;
			$yx_stu_lst = $stu->alias('st')
							  ->field('st.std_id,st.name,st.grade,st.xueguan,st.school,sum(cp.value) as money,sum(cs.hour+cs.ext_hour-cs.used_hour) as lession')
							  ->join('oa_consumption as cp on st.std_id = cp.std_id')
							  ->join('oa_course as cs on st.std_id = cs.std_id')
					          ->where($w_st)
							  ->group('st.std_id')
							  ->order('lession DESC,money DESC')
					          ->select();
			
			$this->yx_data = $yx_stu_lst;
			$this->yx_json = json_encode($yx_stu_lst);
			$this->yx_count = count($yx_stu_lst);*/
			
			
			$w_st['st.std_id'] = array('in',$yx_lst); //有效学员std_id编号列表，有效学员包括在读学员和停课学员；
		
		
		$std_lst_str = implode("','", $yx_lst);		
		
		
		unset($sql_str);
		$sql_str = "select school,xueguan,count(distinct std_id) as pnum from hw001.student where xueguan='" . $xueguan . "' and school='" . $school_name . "' and std_id in ('" . $std_lst_str . "')  group by xueguan";
		$yxrscount = $model->query($sql_str);
		$this->yxrscount = $yxrscount[0]['pnum']?$yxrscount[0]['pnum']:0; //学管有效人数统计；  
		
		
		unset($sql_str);
		$sql_str = "select std_id,name,grade,school,xueguan,case state when 1 then '正常' when 2 then '停课' when 3 then '结课' when 4 then '毕业' when 5 then '退费' else '未知' end as state from hw001.student where xueguan='" . $xueguan . "' and school='" . $school_name . "' and std_id in ('" . $std_lst_str . "')  order by xueguan,std_id";
		$yxrs_lst = $model->query($sql_str);
		
		$this->yx_data = $yxrs_lst;
		$this->yx_count = count($yxrs_lst)?count($yxrs_lst):0;
		$this->yx_json = json_encode($yxrs_lst); //学管有效人数统计；  
				  
		$sql_str_rm = "select school,xueguan,count(distinct std_id) as pnum from (";
		$sql_str_rm .= " select st.*,(cs.hour+cs.ext_hour-cs.used_hour) as remain_hour from hw001.student as st,hongwen_oa.oa_course as cs where st.xueguan='" . $xueguan . "' and st.school='" . $school_name . "' and st.std_id = cs.std_id ";
		$sql_str_rm .= " and st.std_id in ('" . $std_lst_str . "')";
		$sql_str_rm .= " and st.state in (1,2) and cs.state in ('200','250') group by st.school,cs.std_id ";
		$sql_str_rm .= " ) as course where remain_hour<=10 group by school,xueguan ";
		
		$ten_count = $model->query($sql_str_rm);
		
		$this->tencount = $ten_count[0]['pnum']?$ten_count[0]['pnum']:0;
		unset($sql_str_rm);
		
		$sql_str_rm = "select std_id,name,grade,xueguan,courseid,school,remain_hour,case state when 1 then '正常' when 2 then '停课' when 3 then '结课' when 4 then '毕业' when 5 then '退费' else '未知' end as state  from (";
		$sql_str_rm .= " select st.std_id,st.state,st.name,st.grade,st.xueguan,cs.id as courseid,st.school,(cs.hour+cs.ext_hour-cs.used_hour) as remain_hour from hw001.student as st,hongwen_oa.oa_course as cs where st.xueguan='" . $xueguan . "' and st.school='" . $school_name . "' and st.std_id = cs.std_id ";
		$sql_str_rm .= " and st.std_id in ('" . $std_lst_str . "')";
		$sql_str_rm .= " and st.state in (1,2) and cs.state in ('200','250') group by st.school,cs.std_id ";
		$sql_str_rm .= " ) as course where remain_hour<=10  order by school,xueguan,std_id ";
		
		$ten_lst = $model->query($sql_str_rm);
		
		$this->ten_count = count($ten_lst)?count($ten_lst):0;
		$this->ten_lst = $ten_lst;		
		$this->ten_json = json_encode($ten_lst);	//课时<=10小时人数数据，页面详情
		
		
		unset($sql_str_t);
		unset($yf_tk_lst);
		if($cycle == 1){
			$w_tk_date= date('Y-m-d');
			$sql_str_t = "select std_id from hongwen_oa.oa_xg_statistic where xueguan='" . $xueguan . "' and school='" . $school_name . "' and ocur_date = '" . $w_tk_date . "' and type='停课' group by std_id";
			$yf_tk_lst = $model->query($sql_str_t);
		}elseif($cycle == 2){
			$sql_str_t = "select std_id from hongwen_oa.oa_xg_statistic where xueguan='" . $xueguan . "' and school='" . $school_name . "' and ocur_date = '" . $w_tk_date . "' and type='停课' group by std_id";
			$yf_tk_lst1 = $model->query($sql_str_t);
			
			$sql_str_t = "select std_id from hongwen_oa.oa_xg_statistic where xueguan='" . $xueguan . "' and school='" . $school_name . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($week[0]))) . "' and type='停课' group by std_id";
			$yf_tk_lst2 = $model->query($sql_str_t);
			
			
			$std_lst_tk = array_column($yf_tk_lst2,'std_id');
			foreach($yf_tk_lst1 as $k=>$v){
				if(array_search($v['std_id'], $std_lst_tk) !== FALSE){
					unset($yf_tk_lst1[$k]);
				}
			}
			
			$yf_tk_lst = array_values($yf_tk_lst1);
			
			
		}elseif($cycle == 3){
			$sql_str_t = "select std_id from hongwen_oa.oa_xg_statistic where xueguan='" . $xueguan . "' and school='" . $school_name . "' and ocur_date = '" . $w_tk_date . "' and type='停课' group by std_id";
			$yf_tk_lst1 = $model->query($sql_str_t);
			
			$sql_str_t = "select std_id from hongwen_oa.oa_xg_statistic where xueguan='" . $xueguan . "' and school='" . $school_name . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($month[0]))) . "' and type='停课' group by std_id";
			$yf_tk_lst2 = $model->query($sql_str_t);
			
			$std_lst_tk = array_column($yf_tk_lst2,'std_id');
			foreach($yf_tk_lst1 as $k=>$v){
				if(array_search($v['std_id'], $std_lst_tk) !== FALSE){
					unset($yf_tk_lst1[$k]);
				}
			}
			
			$yf_tk_lst = array_values($yf_tk_lst1);
		}
		

		//从所有有效学员中获取在读学员
		foreach($yx_lst as $k=>$v){
			if(array_search($v['std_id'], $yf_tk_lst) !== FALSE){
				unset($yx_lst[$k]);
			}
		}

		$std_lst = array_values($yx_lst);
		$zd_std_lst = implode("','", $std_lst);
		//半月未维护人数；
		unset($sql_str);
		
		$sql_str = " SELECT school,xueguan,count(distinct std_id) as pnum from ( ";
		$sql_str .= " select st.std_id,st.school,st.xueguan,wh.weihu_timee,st.state,datediff(DATE_FORMAT(now(), '%Y-%m-%d'),DATE_FORMAT(max(weihu_timee), '%Y-%m-%d')) dif_date FROM hongwen_oa.oa_weihu AS wh, hw001.student AS st";
		$sql_str .= " WHERE st.xueguan='" . $xueguan . "' and st.school='" . $school_name . "' and st.state = 1 AND wh.std_id = st.std_id and st.std_id in ('" . $zd_std_lst . "') ";
		$sql_str .= " group by st.school,st.std_id  order by weihu_timee DESC  ) as weihu ";
		$sql_str .= " where  (dif_date>=15 or dif_date is null or dif_date = '')  group by school,xueguan ";		
		
		$hwh_count = $model->query($sql_str);
		
		$this->hwhcount = $hwh_count[0]['pnum']?$hwh_count[0]['pnum']:0;
		
		unset($sql_str);
		$sql_str = " SELECT std_id,name,xueguan,grade,school,weihu_timee,case state when 1 then '正常' when 2 then '停课' when 3 then '结课' when 4 then '毕业' when 5 then '退费' else '未知' end as state,dif_date from ( ";
		$sql_str .= " select st.std_id,st.xueguan,st.grade,st.name,st.school,wh.weihu_timee,st.state,datediff(DATE_FORMAT(now(), '%Y-%m-%d'),DATE_FORMAT(max(weihu_timee), '%Y-%m-%d')) dif_date FROM hongwen_oa.oa_weihu AS wh, hw001.student AS st";
		$sql_str .= " WHERE st.xueguan='" . $xueguan . "' and st.school='" . $school_name . "' and st.state = 1 AND wh.std_id = st.std_id and st.std_id in ('" . $zd_std_lst . "') ";
		$sql_str .= " group by st.school,st.std_id  order by weihu_timee DESC  ) as weihu ";
		$sql_str .= " where  (dif_date>=15 or dif_date is null or dif_date = '') order by school,std_id,weihu_timee DESC";		
		
		$hwh_lst = $model->query($sql_str);
		
		$this->hwh_count = count($hwh_lst)?count($hwh_lst):0;
		$this->hwh_lst = $hwh_lst;
		$this->hwh_json = json_encode($hwh_lst);
		
		
		$yf_std_lst = array_column($yf_tk_lst,'std_id');
		$tk_std_lst = implode("','", $yf_std_lst); //停课学员的学员编号；	
		//半月未激活人数；
		unset($sql_str);
		
		$sql_str = " SELECT school,xueguan,count(distinct std_id) as pnum from ( ";
		$sql_str .= " select st.std_id,st.school,st.xueguan,wh.weihu_timee,st.state,datediff(DATE_FORMAT(now(), '%Y-%m-%d'),DATE_FORMAT(max(weihu_timee), '%Y-%m-%d')) dif_date FROM hongwen_oa.oa_weihu AS wh, hw001.student AS st";
		$sql_str .= " WHERE st.school='" . $school_name . "' and st.xueguan='" . $xueguan . "' and st.state in(1,2) AND wh.std_id = st.std_id and st.std_id in ('" . $tk_std_lst . "') ";
		$sql_str .= " group by st.school,st.std_id  order by weihu_timee DESC  ) as weihu ";
		$sql_str .= " where  (dif_date>=15 or dif_date is null or dif_date = '')  group by school,xueguan ";	
		
		$hjh_count = $model->query($sql_str);
		
		$this->hjhcount = $hjh_count[0]['pnum']?$hjh_count[0]['pnum']:0;
		
		if($hjh_count){
			unset($sql_str);
			$sql_str = " SELECT std_id,name,xueguan,grade,school,weihu_timee,case state when 1 then '正常' when 2 then '停课' when 3 then '结课' when 4 then '毕业' when 5 then '退费' else '未知' end  as state,dif_date from ( ";
			$sql_str .= " select st.std_id,st.name,st.xueguan,st.grade,st.school,wh.weihu_timee,st.state,datediff(DATE_FORMAT(now(), '%Y-%m-%d'),DATE_FORMAT(max(weihu_timee), '%Y-%m-%d')) dif_date FROM hongwen_oa.oa_weihu AS wh, hw001.student AS st";
			$sql_str .= " WHERE st.school='" . $school_name . "' and st.xueguan='" . $xueguan . "' and st.state in (1,2) AND wh.std_id = st.std_id and st.std_id in ('" . $tk_std_lst . "') ";
			$sql_str .= " group by st.school,st.std_id  order by weihu_timee DESC  ) as weihu ";
			$sql_str .= " where  (dif_date>=15 or dif_date is null or dif_date = '') order by school,std_id,weihu_timee DESC " ;	
			
			$hjh_lst = $model->query($sql_str);	
			$this->hjh_lst = $hjh_lst;
			$this->hjh_count = count($hjh_lst)?count($hjh_lst):0;
			$this->hjh_json=json_encode($hjh_lst);
		}else{
			$this->hjh_json="";
			$this->hjh_count = 0;
			$this->hjh_lst = NULL;
		}
		
		$skv = round($this->statis[0]['pnum']/$this->yxrscount,4)*100; //上课率
		$tkv = round($this->tk_count/$this->yxrscount,4)*100; //停课率
		$jkv = round($this->jk_count/$this->yxrscount,4)*100; //结课率
		$xfv = round($this->xf_count/$this->yxrscount,4)*100; //续费率
		$tsv = round($this->tf_count/$this->yxrscount,4)*100; //退生率
		
		$this->skv = $skv . '%';
		$this->tkv = $tkv . '%';
		$this->jkv = $jkv . '%';
		$this->xfv = $xfv . '%';
		$this->tsv = $tsv . '%';
		
		$grade_lst = C('SCHOOL_GRADE');
		$gradelst = array_column($grade_lst,'name','id');
		$this->gradelst = $gradelst;
		
		$this->display();
		
		/*//统计调课/删课课时
		$sql_str = "";
		$sql_dt_str = "";

		$school = M('hw001.school',NULL);

		if(!empty($school_name)){
			$ws['school'] = $school_name;
		}else{
			$ws['1'] = '1';
		}

		$sql_tmp_str = $school->where($ws)->getField('record',TRUE);
		
		$tmp = M('dt_record');
		foreach($sql_tmp_str as $key=>$value){
			if(!empty($value)){
				$record = split('#',$value);
				if(!empty($record)){
					foreach($record as $k=>$v){
						$vo = split(',',$v);

						$cl = new \stdClass();

						$cl->cid =     $vo[1];
						$cl->school = $vo[2];
						$cl->stuid = $vo[3];
						$cl->std_id = $vo[4];
						$cl->course_id = $vo[5];
						$cl->grade = $vo[6];
						$cl->state = $vo[7];
						$cl->tid = $vo[8];
						$cl->teacher = $vo[9];
						$cl->fankui = $vo[10];
						$cl->class = $vo[11];
						$cl->time1 = $vo[12];
						$cl->time2 = $vo[13];
						$cl->count = $vo[14];
						$cl->timee = $vo[15];
						$cl->other = $vo[16];
						$cl->why = $vo[17];
						$cl->add = $vo[18];
						$cl->qr = $vo[19];
						$cl->tqr = $vo[20];
						$cl->cwqr = $vo[21];
						$cl->timestamp = $vo[22];
						$cl->reason = $vo[24];
						$cl->operator = $vo[26];
						$cl->dtk_time = $vo[28];
						$cl->dtk_type = $vo[0];

						$tmp->data($cl)->add();

					}
				}
			}else{
				break;
			}

		}*/
	}

	public function xueguan_teacher_search(){
		
		$pageNum = I('get.pageNumber');
		$pageCount = I('get.pageCount');

		$pageNum = empty($pageNum)?$this->pageNumber:$pageNum;
		$pageCount = empty($pageCount)?$this->pageCount:$pageCount;
		
		
		$school_id = session('school_id');
		$school_name = get_school_name($school_id);
		
		$model = new \Think\Model();
		
		$subject = I('post.subject');
		
		$teacher = I('post.teacher');
		
		$keyword = I('post.keyword');
		
		if(!empty($subject)){
			$sub = " and foo.id = " . $subject;
		}
		
		if(!empty($teacher)){
			$tea = " and us.name = '" . $teacher . "' ";
		}
		
		if(!empty($keyword)){
			$keyw = " and us.name like '%" . $keyword . "%' ";
		}
		
		
		$t1 = I('post.t1');
		
		$t2 = I('post.t2');
		
		$tt = I('post.timee');
		
		if(!empty($t1)){
			$time1 = " and time1 = '" . $t1 . "' ";	
		}
		
		if(!empty($t2)){
			$time2 = "  and time2 = '" . $t2 . "' ";	
		}
		
		if(!empty($tt)){
			$timee = $tt;
		}else{
			$timee = Date('Y-m-d');
		}
		
		$sql_str = "select us.school,us.name,foo.name as subject,us.mobile_tel from hongwen_oa.oa_user us,hongwen_oa.oa_teach_role as otr,hongwen_oa.oa_foo_info foo where us.id = otr.uid and otr.subject = foo.id and us.is_del = 0 and us.position_id IN (10,14,17,21) and us.name not in (";
		
		$sql_str .= "select teacher from hw001.class where DATE_FORMAT(timee,'%Y-%m-%d')='" . $timee . "' " . $time1 . $time2 . " group by teacher) " . $sub . $tea . $keyw . " group by us.name order by us.school";
		
		$data_lst = $model->query($sql_str);
		
		$maxCount = count($data_lst);
		
		unset($sql_str);
		
		$sql_str = "select us.school,us.name,foo.name as subject,us.mobile_tel from hongwen_oa.oa_user us,hongwen_oa.oa_teach_role as otr,hongwen_oa.oa_foo_info foo where us.id = otr.uid and otr.subject = foo.id and us.is_del = 0 and us.position_id IN (10,14,17,21) and us.name not in (";
		$sql_str .= "select teacher from hw001.class where DATE_FORMAT(timee,'%Y-%m-%d')='" . $timee . "' " . $time1 . $time2 . " group by teacher) " . $sub . $tea . $keyw . " group by us.name order by us.school,foo.name limit " . $pageNum . "," . $pageCount;
		
		$lst = $model->query($sql_str);
		
		foreach($lst as &$item){
			$item['school'] = get_school_name($item['school']);
		}
		unset($item);
		
		
		if(IS_AJAX && I('get.pageCount')){
				// 发送给页面的数据
				$this->ajaxReturn([

					'state'=>'ok',//查询结果
					'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
					'data'=>$lst
				  ]);
			}else{
				$this->data = $lst;
				$this->maxCount = $maxCount;
				
				$this->display();
			}
			
		
	}
	
	
	public function get_teacher($subject){
		
		$model = new \Think\Model();
		//test git now ;
		$tc = M('user');
		
		$sql_str = "select us.name ,us.name as vl from hongwen_oa.oa_user us,hongwen_oa.oa_teach_role as otr,hongwen_oa.oa_foo_info foo where us.id = otr.uid and otr.subject = foo.id and us.is_del = 0 and  us.position_id IN (10,14,17,21) and foo.id=" . $subject . " group by us.name ";
		
		$data = $model->query($sql_str);
		
		$this->ajaxReturn([
				'state'=>'ok',//查询结果
				'data'=>$data
			  ]);
	}
	
	
	
	//校区各学管统计数据日、周、月
	public function xq_all_statistic(){
		
		$school_id= session('school_id');
		
		if($school_id == 0 ){
			$this->display();die;
		}
		
		$this->statis_count = 10;
		$this->dk_count = 10;
		$this->dtk_tk_count = 10;
		$this->xf_count = 10;
		$this->tk_count = 10;
		$this->jk_count = 10;
		$this->tf_count = 10;
		$this->wh_count = 10;
		$this->yx_count = 10;


		$school = M('foo_info');
		$xg = M('user');
		$stu = M('hw001.student',NULL);
		$consump = M('consumption');
		$course = M('course');
		$class = M('hw001.class',NULL);
				
		
		
		$school_name = get_school_name($school_id);
		
		//校区账户进来, 首先获取所有学管师的名字；
		$wx['position_id'] = 18;
		$wx['is_del'] = 0;
		$wx['school'] = $school_id;
		$xg_lst = $xg->where($wx)->getField('name',TRUE);
		
		$xueguan_lst = $xg->where($wx)->select();
		$this->xg = json_encode($xueguan_lst); //页面赋值学管师列表
		
		if(count($xg_lst)){
			$xglst = implode("','",$xg_lst);
			$xg_w = " and st.xueguan in ('" . $xglst . "') ";	
		}
			   
		
		//获取统计的周期；
		$cycle = I('get.cycle');
		if(empty($cycle)){
			$cycle = 1;
		}
		//前端页面周期回显；
		$this->cycle= $cycle;
		$nowDate = date('Y-m-d');
		
		//获取统计周的时间段
		$week = get_date($nowDate,'w'); //本周
		//获取统计月的时间段
		$month = get_date($nowDate,'m'); //本月

		/*============================================================以下开始统计各种数据==================================================================*/
		$model = new \Think\Model();

		//统计课时数及上课人数
		$sql_str = "select stus.xueguan,sum(count) as ksnum,count(distinct stus.std_id) as pnum from hw001.class as cl, ";
		$sql_str .=" (select st.std_id,st.xueguan from hw001.student as st,hongwen_oa.oa_user as us where us.school=" . $school_id . " and st.state=1 and st.xueguan = us.name and us.position_id=18 and us.is_del=0 ) as stus";
		
		if($cycle == 1){
			$w_ks = " and date_format(cl.timee, '%Y-%m-%d')='".$nowDate."' ";
		}else if($cycle == 2){
			$w_ks = " and date_format(cl.timee, '%Y-%m-%d')>='". date('Y-m-d',strtotime($week[0])) . "' ";
			$w_ks .= " and date_format(cl.timee, '%Y-%m-%d')<='".date('Y-m-d',strtotime($week[1])) . "' ";		
		}elseif($cycle == 3){
			$w_ks = " and date_format(cl.timee, '%Y-%m-%d')>='". date('Y-m-d',strtotime($month[0])) . "' ";
			$w_ks .= " and date_format(cl.timee, '%Y-%m-%d')<='".date('Y-m-d',strtotime($month[1])) . "' ";
		}
			
		
		$sql_str .=" where cl.std_id=stus.std_id " . $w_ks . " group by stus.xueguan";

		$ks_count = $model->query($sql_str);
		$this->kscount = json_encode($ks_count); //页面赋值；

		
		
		if($cycle == 1){
			$w_dk = " and DATE_FORMAT(dtk_time,'%Y-%m-%d')='" . date('Y-m-d'). "' ";
		}else if($cycle == 2){
			$w_dk = " and DATE_FORMAT(dtk_time,'%Y-%m-%d') >='" . date('Y-m-d',strtotime($week[0])) . "' ";
			$w_dk .= " and DATE_FORMAT(dtk_time,'%Y-%m-%d') <='" . date('Y-m-d',strtotime($week[1])) . "' ";
					
		}elseif($cycle == 3){
			$w_dk = " and DATE_FORMAT(dtk_time,'%Y-%m-%d') >='" . date('Y-m-d',strtotime($month[0])) . "' ";
			$w_dk .= " and DATE_FORMAT(dtk_time,'%Y-%m-%d') <='" . date('Y-m-d',strtotime($month[1])) . "' ";
		}
		
		
		//删课时统计
		$sql_str = "select school,xueguan,sum(count) as ksnum,count(distinct std_id) as pnum from (";
		
		$sql_str_t = " select * from ( ";
		$sql_str_t .= " select st.school,st.std_id,st.name,st.grade,odr.class,odr.time1,odr.time2,odr.timee,odr.teacher,odr.count,st.xueguan,odr.cid,odr.dtk_time,odr.dtk_type,odr.reason from oa_dt_record as odr,hw001.student as st where odr.school='" . $school_name . "' and odr.std_id = st.std_id and dtk_type like '%删除%' " . $xg_w . $w_dk;
		$sql_str_t .=  " group by st.school,odr.cid having count(*)>=2 order by st.school,odr.timee DESC,dtk_time DESC,count"; //一门课程多次删除，记为一次；
		$sql_str_t .= " ) as del1 ";
		
		$sql_str_t .=  " union all ";
		
		$sql_str_t .= " select * from ( ";
		
		$sql_str_t .= " select st.school,st.std_id,st.name,st.grade,odr.class,odr.time1,odr.time2,odr.timee,odr.teacher,odr.count,st.xueguan,odr.cid,odr.dtk_time,odr.dtk_type,odr.reason from oa_dt_record as odr,hw001.student as st where odr.school='" . $school_name . "' and  odr.std_id = st.std_id and dtk_type like '%删除%' " . $xg_w . $w_dk;
		$sql_str_t .=  " group by st.school,odr.cid having count(*)<2 order by st.school,odr.timee DESC,dtk_time DESC,count"; 
		$sql_str_t .= " ) as del2 ";
		
		$sql_str .= $sql_str_t;
		
		$sql_str .= " ) as school_del_all group by school,xueguan ";
		
		
		$dk_count = $model->query($sql_str); //各校区删课统计数据；
		
		$this->dkcount = json_encode($dk_count);
		
		
		//调课统计
		unset($sql_str);
		unset($sql_str_t);
		unset($sql_str_r);
		
		
		
		//调课时统计
		$sql_str = "select school,xueguan,sum(count) as ksnum,count(distinct std_id) as pnum from (";
		
		$sql_str_t = " select * from ( ";
		$sql_str_t .= " select st.school,st.std_id,st.name,st.grade,odr.class,odr.time1,odr.time2,odr.timee,odr.teacher,odr.count,st.xueguan,odr.cid,odr.dtk_time,odr.dtk_type,odr.reason from oa_dt_record as odr,hw001.student as st where odr.school='" . $school_name . "' and odr.std_id = st.std_id and dtk_type like '%调课%' " . $xg_w . $w_dk;
		$sql_str_t .=  " group by st.school,odr.cid having count(*)>=2 order by st.school,odr.timee DESC,dtk_time DESC,count"; //一门课程多次调课，记为一次；
		$sql_str_t .= " ) as del1 ";
		
		$sql_str_t .=  " union all ";
		
		$sql_str_t .= " select * from ( ";
		
		$sql_str_t .= " select st.school,st.std_id,st.name,st.grade,odr.class,odr.time1,odr.time2,odr.timee,odr.teacher,odr.count,st.xueguan,odr.cid,odr.dtk_time,odr.dtk_type,odr.reason from oa_dt_record as odr,hw001.student as st where odr.school='" . $school_name . "' and  odr.std_id = st.std_id and dtk_type like '%调课%' " . $xg_w . $w_dk;
		$sql_str_t .=  " group by st.school,odr.cid having count(*)<2 order by st.school,odr.timee DESC,dtk_time DESC,count"; 
		$sql_str_t .= " ) as del2 ";
		
		$sql_str .= $sql_str_t;
		
		$sql_str .= " ) as school_del_all group by school,xueguan ";
		
		
		$tk_count = $model->query($sql_str); //各校区调课统计数据；
		
		$this->tkcount = json_encode($tk_count);
		
		
		if($cycle == 1){
			$w_xf = "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') = date_format(NOW(), '%Y-%m-%d') ";
		}else if($cycle == 2){
			$w_xf = "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($week[0])) . "' ";
			$w_xf .= "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($week[1])) . "' ";
					
		}elseif($cycle == 3){
			$w_xf = "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($month[0])) . "' ";
			$w_xf .= "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($month[1])) . "' ";
		}
		
		//今日续费人数
		unset($sql_str);
		$sql_str = "SELECT st.school,st.xueguan,sum(cs.value) as money,count(distinct cs.std_id) as pnum FROM  hongwen_oa.oa_consumption as cs,hw001.student as st ";
		$sql_str .= " where st.school='" . $school_name . "' and cs.std_id = st.std_id " . $w_xf . " and st.state=1 AND cs.belong_type = 2  group by st.school,st.xueguan  ";
		
		$xf_count = $model->query($sql_str); //各校区续费情况
		
		$this->xfcount = json_encode($xf_count);
		
		
		//今日停课人数
		unset($sql_str);
		unset($sql_str_yf);
		unset($sql_str_tk);
			
		if($cycle == 1){
			$w_tk_date= date('Y-m-d');
			$w_tk_type = '停课';
			$sql_str_t = "select school,xueguan,count(distinct std_id) as pnum from hongwen_oa.oa_xg_statistic where school='" . $school_name . "' and ocur_date = '" . $w_tk_date . "' and type='" . $w_tk_type . "' and std_id not in ( ";
			$sql_str_t .= "select std_id from hongwen_oa.oa_xg_statistic where school='" . $school_name . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($w_tk_date)))  . "' and type='" . $w_tk_type . "' group by school,xueguan,std_id ) group by school,xueguan ";
			$yf_tk_count = $model->query($sql_str_t);
			
		}elseif($cycle == 2){
			$w_tk_date= date('Y-m-d');
			$w_tk_type = '停课';
			$sql_str_t = "select school,xueguan,count(distinct std_id) as pnum from hongwen_oa.oa_xg_statistic where school='" . $school_name . "' and ocur_date = '" . $w_tk_date . "' and type='" . $w_tk_type . "' and std_id not in ( ";
			$sql_str_t .= "select std_id from hongwen_oa.oa_xg_statistic where school='" . $school_name . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($week[0])))  . "' and type='" . $w_tk_type . "' group by school,xueguan,std_id ) group by school,xueguan ";
			$yf_tk_count = $model->query($sql_str_t);
			
		}elseif($cycle == 3){
			$w_tk_date= date('Y-m-d');
			$w_tk_type = '停课';
			$sql_str_t = "select school,xueguan,count(distinct std_id) as pnum from hongwen_oa.oa_xg_statistic where school='" . $school_name . "' and ocur_date = '" . $w_tk_date . "' and type='" . $w_tk_type . "' and std_id not in ( ";
			$sql_str_t .= "select std_id from hongwen_oa.oa_xg_statistic where school='" . $school_name . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($month[0])))  . "' and type='" . $w_tk_type . "' group by school,xueguan,std_id ) group by school,xueguan ";
			$yf_tk_count = $model->query($sql_str_t);
		}
		
		$this->yftkcount = json_encode($yf_tk_count);
				      	
		//今日结课人数
		unset($sql_str);
		unset($sql_str_wf);
		unset($sql_str_tk);
		
		
		
		if($cycle == 1){
			$w_jk_date= date('Y-m-d');
			$w_jk_type = '结课';
			$sql_str_j = "select school,xueguan,count(distinct std_id) as pnum from hongwen_oa.oa_xg_statistic where school='" . $school_name . "' and ocur_date = '" . $w_jk_date . "' and type='" . $w_jk_type . "' and std_id not in ( ";
			$sql_str_j .= "select std_id from hongwen_oa.oa_xg_statistic where school='" . $school_name . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($w_jk_date)))  . "' and type='" . $w_jk_type . "' group by school,xueguan,std_id ) group by school,xueguan ";
			
			$wf_jk_count = $model->query($sql_str_j);
			
		}elseif($cycle == 2){
			$w_jk_type = '结课';
			$w_jk_date= date('Y-m-d');
			$sql_str_j = "select school,xueguan,count(distinct std_id) as pnum from hongwen_oa.oa_xg_statistic where school='" . $school_name . "' and ocur_date = '" . $w_jk_date . "' and type='" . $w_jk_type . "' and std_id not in ( ";
			$sql_str_j .= "select std_id from hongwen_oa.oa_xg_statistic where school='" . $school_name . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($week[0])))  . "' and type='" . $w_jk_type . "' group by school,xueguan,std_id ) group by school,xueguan ";
			
			$wf_jk_count = $model->query($sql_str_j);
			
		}elseif($cycle == 3){
			$w_jk_date= date('Y-m-d');
			$w_jk_type = '结课';
			$sql_str_j = "select school,xueguan,count(distinct std_id) as pnum from hongwen_oa.oa_xg_statistic where school='" . $school_name . "' and ocur_date = '" . $w_jk_date . "' and type='" . $w_jk_type . "' and std_id not in ( ";
			$sql_str_j .= "select std_id from hongwen_oa.oa_xg_statistic where school='" . $school_name . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($month[0])))  . "' and type='" . $w_jk_type . "' group by school,xueguan,std_id ) group by school,xueguan ";
			
			$wf_jk_count = $model->query($sql_str_j);
		}
		
		$this->wftkcount = json_encode($wf_jk_count);
		
		
		if($cycle == 1){
			$w_tf = "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') = DATE_FORMAT(now(), '%Y-%m-%d')) ";
		}else if($cycle == 2){
			$w_tf = "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($week[0])) . "' ";
			$w_tf .= "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($week[1])) . "' ) ";
					
		}elseif($cycle == 3){
			$w_tf = "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($month[0])) . "' ";
			$w_tf .= "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($month[1])) . "' ) ";
		}
		
		//今日退费学员
		unset($sql_str);
		
		$sql_str = "select st.school,st.xueguan,count(distinct st.std_id) as pnum from hw001.student as st";
		$sql_str .= " where school='" . $school_name . "' and st.std_id in (select distinct std_id from hongwen_oa.oa_consumption where type = 200 " . $w_tf ;

		$sql_str .= " group by school,xueguan order by school,xueguan ";
		
		$tf_count = $model->query($sql_str);
		
		
		$this->tfcount = json_encode($tf_count);
		
		
		if($cycle == 1){
			$w_wh = "   and DATE_FORMAT(weihu_timee, '%Y-%m-%d') =  DATE_FORMAT(NOW(), '%Y-%m-%d') ";
		}else if($cycle == 2){
			$w_wh = "  and DATE_FORMAT(weihu_timee, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($week[0])) . "' ";
			$w_wh .= "  and DATE_FORMAT(weihu_timee, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($week[1])) . "' ";
					
		}elseif($cycle == 3){
			$w_wh = "  and DATE_FORMAT(weihu_timee, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($month[0])) . "' ";
			$w_wh .= "  and DATE_FORMAT(weihu_timee, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($month[1])) . "' ";
		}
		
		//今日维护人数
		unset($sql_str);
		
		$sql_str = "select st.school as school,st.xueguan,count(distinct wh.std_id) as pnum from hongwen_oa.oa_weihu as wh,hw001.student as st where  st.school='" . $school_name . "' and wh.std_id = st.std_id " . $w_wh . " group by st.school,st.xueguan ";
		
		$wh_count = $model->query($sql_str);
		
		
		$this->whcount = json_encode($wh_count);
		
		unset($sql_str);
		
		//截至本月有效学员数
		
		unset($sql_str);
		/*所有有费学员，但不包含只有特色课堂的学员*/
		$sql_str = "select std_id from hongwen_oa.oa_consumption where  state != 50 and is_del != 1 and std_id not in ( ";
		/*只有特色课堂的学员*/
		$sql_str .= " select std_id from oa_course where oa_course.std_id in ( ";
		
		$sql_str .= " select std_id from oa_course where oa_course.state in (200,250) and oa_course.unit_plan in ( ";
		
		$sql_str .= " select our.id ";
		
		$sql_str .= " from oa_unitprice_role as our,oa_foo_info as F1,oa_foo_info as F2,oa_foo_info as F3,oa_foo_info as F4 ";
		
		$sql_str .= " where our.school = F1.id and our.grade = F2.id and our.level = F3.id and our.course = F4.id and F4.group = 4)) ";
		
		$sql_str .= " group by oa_course.std_id having sum(oa_course.hour+oa_course.ext_hour-oa_course.used_hour)>0 and count(oa_course.id)=1) ";
		
		$sql_str .= " group by std_id having sum(value)>0 ";
		
		$yf_all = $model->query($sql_str);

		unset($sql_str);
		/*特色课堂有订单学员*/
		$sql_str = "select oa_course.id,std_id,unit_plan from oa_course where oa_course.state in (200,250) and oa_course.school=" . $school_id . " and oa_course.std_id in ( ";
		
		$sql_str .= " select std_id from oa_course where oa_course.unit_plan in ( ";
		
		$sql_str .= " select our.id from oa_unitprice_role as our,oa_foo_info as F1,oa_foo_info as F2,oa_foo_info as F3,oa_foo_info as F4 ";
		
		$sql_str .= " where our.school=" . $school_id . " and our.school = F1.id and our.grade = F2.id and our.level = F3.id and our.course = F4.id and F4.group = 4 ))";
		
		$sql_str .= " group by oa_course.std_id having sum(oa_course.hour+oa_course.ext_hour-oa_course.used_hour)>0 and count(oa_course.id)>1 ";
		
		$ydd1 = $model->query($sql_str);
		
		unset($sql_str);
		
		/*有订单学员，但不包含有特色课堂的学员*/
		$sql_str = "select std_id from oa_course where oa_course.state in (200,250) and oa_course.school=" . $school_id . " and oa_course.unit_plan not in ( ";
		
		$sql_str .= " select our.id from oa_unitprice_role as our,oa_foo_info as F1,oa_foo_info as F2,oa_foo_info as F3,oa_foo_info as F4 ";
		
		$sql_str .= " where our.school = F1.id and our.grade = F2.id and our.level = F3.id and our.course = F4.id and F4.group = 4 ) ";
		
		$sql_str .= " group by oa_course.std_id having sum(oa_course.hour+oa_course.ext_hour-oa_course.used_hour)>0 ";
		
		$ydd2 = $model->query($sql_str);
		
		$yx_lst = array_merge(array_column($yf_all,'std_id'),array_column($ydd1,'std_id'),array_column($ydd2,'std_id'));
		
		
		$w_st['st.std_id'] = array('in',$yx_lst); //有效学员std_id编号列表，有效学员包括在读学员和停课学员；
		
		
		$std_lst_str = implode("','", $yx_lst);		
		
		
		unset($sql_str);
		$sql_str = "select school,xueguan,count(distinct std_id) as pnum from hw001.student where school='" . $school_name . "' and std_id in ('" . $std_lst_str . "')  group by school,xueguan";
		$yxrscount = $model->query($sql_str);
		$this->yxrscount = json_encode($yxrscount); //各校区有效人数统计；  
				  
		$sql_str_rm = "select school,xueguan,count(distinct std_id) as pnum from (";
		$sql_str_rm .= " select st.*,(cs.hour+cs.ext_hour-cs.used_hour) as remain_hour from hw001.student as st,hongwen_oa.oa_course as cs where st.school='" . $school_name . "' and st.std_id = cs.std_id ";
		$sql_str_rm .= " and st.std_id in ('" . $std_lst_str . "')";
		$sql_str_rm .= " and st.state in (1,2) and cs.state in ('200','250') group by st.school,cs.std_id ";
		$sql_str_rm .= " ) as course where remain_hour<=10 group by school,xueguan ";
		
		$yx_count = $model->query($sql_str_rm);
		
				
		$this->yxcount = json_encode($yx_count);	
		
		unset($sql_str_t);
		unset($yf_tk_lst);
		if($cycle == 1){
			$w_tk_date= date('Y-m-d');
			$sql_str_t = "select std_id from hongwen_oa.oa_xg_statistic where school='" . $school_name . "' and ocur_date = '" . $w_tk_date . "' and type='停课' and std_id not in ( ";
			$sql_str_t .= "select std_id from hongwen_oa.oa_xg_statistic where school='" . $school_name . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($w_tk_date))) . "' and type='停课' group by std_id )  group by std_id ";
			$yf_tk_lst = $model->query($sql_str_t);
			
		}elseif($cycle == 2){
			$sql_str_t = "select std_id from hongwen_oa.oa_xg_statistic where school='" . $school_name . "' and ocur_date = '" . $w_tk_date . "' and type='停课' and std_id not in ( ";
			$sql_str_t .= "select std_id from hongwen_oa.oa_xg_statistic where school='" . $school_name . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($week[0]))) . "' and type='停课' group by std_id )  group by std_id ";
			$yf_tk_lst = $model->query($sql_str_t);
		}elseif($cycle == 3){
			$sql_str_t = "select std_id from hongwen_oa.oa_xg_statistic where school='" . $school_name . "' and ocur_date = '" . $w_tk_date . "' and type='停课' and std_id not in ( ";
			$sql_str_t .= "select std_id from hongwen_oa.oa_xg_statistic where school='" . $school_name . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($month[0]))) . "' and type='停课' group by std_id )  group by std_id ";
			$yf_tk_lst = $model->query($sql_str_t);
		}
		

		//从所有有效学员中获取在读学员
		foreach($yx_lst as $k=>$v){
			if(array_search($v['std_id'], $yf_tk_lst) !== FALSE){
				unset($yx_lst[$k]);
			}
		}

		$std_lst = array_values($yx_lst);
		
		$zd_std_lst = implode("','", $std_lst);
		//半月未维护人数；
		unset($sql_str);
		
		$sql_str = " SELECT school,xueguan,count(distinct std_id) as pnum from ( ";
		$sql_str .= " select st.std_id,st.school,st.xueguan,wh.weihu_timee,st.state,datediff(DATE_FORMAT(now(), '%Y-%m-%d'),DATE_FORMAT(max(weihu_timee), '%Y-%m-%d')) dif_date FROM hongwen_oa.oa_weihu AS wh, hw001.student AS st";
		$sql_str .= " WHERE st.school='" . $school_name . "' and st.state = 1 AND wh.std_id = st.std_id and st.std_id in ('" . $zd_std_lst . "') ";
		$sql_str .= " group by st.school,st.std_id  order by weihu_timee DESC  ) as weihu ";
		$sql_str .= " where  (dif_date>=15 or dif_date is null or dif_date = '')  group by school,xueguan ";		
		
		$hwh_count = $model->query($sql_str);
		
		$this->hwhcount = json_encode($hwh_count);
		$yf_std_lst = array_column($yf_tk_lst,'std_id');
		$tk_std_lst = implode("','", $yf_std_lst); //停课学员的学员编号；	
		//半月未激活人数；
		unset($sql_str);
		
		$sql_str = " SELECT school,xueguan,count(distinct std_id) as pnum from ( ";
		$sql_str .= " select st.std_id,st.school,st.xueguan,wh.weihu_timee,st.state,datediff(DATE_FORMAT(now(), '%Y-%m-%d'),DATE_FORMAT(max(weihu_timee), '%Y-%m-%d')) dif_date FROM hongwen_oa.oa_weihu AS wh, hw001.student AS st";
		$sql_str .= " WHERE st.school='" . $school_name . "' and st.state in(1,2) AND wh.std_id = st.std_id and st.std_id in ('" . $tk_std_lst . "') ";
		$sql_str .= " group by st.school,st.std_id  order by weihu_timee DESC  ) as weihu ";
		$sql_str .= " where  (dif_date>=15 or dif_date is null or dif_date = '')  group by school,xueguan ";	
		
		$hjh_count = $model->query($sql_str);
		
		$this->hjhcount = json_encode($hjh_count);
		
		$grade_lst = C('SCHOOL_GRADE');
		$gradelst = array_column($grade_lst,'name','id');
		$this->gradelst = $gradelst;
		
		$this->display();
				
	}
	
	
	//集团各校区统计数据日、周、月
	public function xq_statistic_detail(){
		
		
		$this->statis_count = 10;
		$this->dk_count = 10;
		$this->dtk_tk_count = 10;
		$this->xf_count = 10;
		$this->tk_count = 10;
		$this->jk_count = 10;
		$this->tf_count = 10;
		$this->wh_count = 10;
		$this->yx_count = 10;

		$type = I('post.item');
		
		$xueguan = I('post.school');
		
		$school_id = session('school_id');
		
		$xg = M('user');
		$stu = M('hw001.student',NULL);
		$consump = M('consumption');
		$course = M('course');
		$class = M('hw001.class',NULL);
		
		//校区账户进来, 首先获取所有学管师的名字；
		$wx['position_id'] = 18;
		$wx['is_del'] = 0;
		$wx['school'] = get_school_id($school);
		$xg_lst = $xg->where($wx)->getField('name',TRUE);
		
		if(count($xg_lst)){
			$xglst = implode("','",$xg_lst);
			$xg_w = " and st.xueguan in ('" . $xglst . "') ";	
		}
			   
		
		//获取统计的周期；
		$cycle = I('post.cycle');
		if(empty($cycle)){
			$cycle = 1;
		}
		//前端页面周期回显；
		$this->cycle= $cycle;
		$nowDate = date('Y-m-d');
		
		//获取统计周的时间段
		$week = get_date($nowDate,'w'); //本周
		//获取统计月的时间段
		$month = get_date($nowDate,'m'); //本月

		/*============================================================以下开始统计各种数据==================================================================*/
		$model = new \Think\Model();
		
		$pageNumber = I('get.pageNumber');
		$pageCount = I('get.pageCount');

		$pageNum = empty($pageNumber)?$this->pageNumber:$pageNumber;
		$pageCount = empty($pageCount)?$this->pageCount:$pageCount;
		
		if($type == 'ks'){
			if($cycle == 1){
				$w_ks = " and date_format(cl.timee, '%Y-%m-%d')='".$nowDate."' ";
			}else if($cycle == 2){
				$w_ks = " and date_format(cl.timee, '%Y-%m-%d')>='". date('Y-m-d',strtotime($week[0])) . "' ";
				$w_ks .= " and date_format(cl.timee, '%Y-%m-%d')<='".date('Y-m-d',strtotime($week[1])) . "' ";		
			}elseif($cycle == 3){
				$w_ks = " and date_format(cl.timee, '%Y-%m-%d')>='". date('Y-m-d',strtotime($month[0])) . "' ";
				$w_ks .= " and date_format(cl.timee, '%Y-%m-%d')<='".date('Y-m-d',strtotime($month[1])) . "' ";
			}
				
			//获取上课人的详情
			$sql_dt_str =  "select count(*) as count from hw001.class as cl, ";
			$sql_dt_str .=" (select st.school,st.std_id,st.name,st.grade,st.xueguan from hw001.student as st where st.state=1 and st.school='" . get_school_name($school_id) . "' and st.xueguan='" . $xueguan . "' ) as stus ";
			$sql_dt_str .=" where cl.std_id=stus.std_id " . $w_ks ;
			$ks_count = $model->query($sql_dt_str);
			
			unset($sql_dt_str);
			//获取上课人的详情
			$sql_dt_str =  "select stus.school,cl.id,stus.std_id,stus.name,stus.grade,cl.class,cl.time1,cl.time2,cl.timee,cl.teacher,cl.count,stus.xueguan from hw001.class as cl, ";
			$sql_dt_str .=" (select st.school,st.std_id,st.name,st.grade,st.xueguan from hw001.student as st where st.state=1 and  st.school='" . get_school_name($school_id) . "' and st.xueguan='" . $xueguan . "' ) as stus ";
			$sql_dt_str .=" where cl.std_id=stus.std_id " . $w_ks . "  order by stus.school,stus.xueguan,stus.std_id,cl.count limit " . $pageNum . "," . $pageCount;
	
			$ks_data = $model->query($sql_dt_str);
			
			$maxCount = $ks_count?$ks_count[0]['count']:0;
			
			if(IS_AJAX && I('get.pageCount')){
				// 发送给页面的数据
				$this->ajaxReturn([

					'state'=>'ok',//查询结果
					'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
					'data'=>$ks_data
				  ]);
			}
			
		}		
		
		
		
		if($type == 'ks_rs'){
			if($cycle == 1){
				$w_ks = " and date_format(cl.timee, '%Y-%m-%d')='".$nowDate."' ";
			}else if($cycle == 2){
				$w_ks = " and date_format(cl.timee, '%Y-%m-%d')>='". date('Y-m-d',strtotime($week[0])) . "' ";
				$w_ks .= " and date_format(cl.timee, '%Y-%m-%d')<='".date('Y-m-d',strtotime($week[1])) . "' ";		
			}elseif($cycle == 3){
				$w_ks = " and date_format(cl.timee, '%Y-%m-%d')>='". date('Y-m-d',strtotime($month[0])) . "' ";
				$w_ks .= " and date_format(cl.timee, '%Y-%m-%d')<='".date('Y-m-d',strtotime($month[1])) . "' ";
			}
				
			//获取上课人的详情
			$sql_dt_str =  "select count(distinct cl.std_id) as count from hw001.class as cl, ";
			$sql_dt_str .=" (select st.school,st.std_id,st.name,st.grade,st.xueguan from hw001.student as st where st.state=1 and st.school='" . get_school_name($school_id) . "' and st.xueguan='" . $xueguan . "' ) as stus ";
			$sql_dt_str .=" where cl.std_id=stus.std_id " . $w_ks ;
			$ks_count = $model->query($sql_dt_str);
			
			unset($sql_dt_str);
			//获取上课人的详情
			$sql_dt_str =  "select stus.school,cl.id,stus.std_id,stus.name,stus.grade,cl.class,cl.time1,cl.time2,cl.timee,cl.teacher,cl.count,stus.xueguan from hw001.class as cl, ";
			$sql_dt_str .=" (select st.school,st.std_id,st.name,st.grade,st.xueguan from hw001.student as st where st.state=1 and st.school='" . get_school_name($school_id) . "' and st.xueguan='" . $xueguan . "' ) as stus ";
			$sql_dt_str .=" where cl.std_id=stus.std_id " . $w_ks . " group by cl.std_id order by stus.school,stus.xueguan,stus.std_id,cl.count limit " . $pageNum . "," . $pageCount;
	
			$ks_data = $model->query($sql_dt_str);
			
			$maxCount = $ks_count?$ks_count[0]['count']:0;
			
			if(IS_AJAX && I('get.pageCount')){
				// 发送给页面的数据
				$this->ajaxReturn([

					'state'=>'ok',//查询结果
					'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
					'data'=>$ks_data
				  ]);
			}
		}
		
		
		
		if($type == 'dk_ks'){
			if($cycle == 1){
				$w_dk = " and DATE_FORMAT(dtk_time,'%Y-%m-%d')='" . date('Y-m-d'). "' ";
			}else if($cycle == 2){
				$w_dk = " and DATE_FORMAT(dtk_time,'%Y-%m-%d') >='" . date('Y-m-d',strtotime($week[0])) . "' ";
				$w_dk .= " and DATE_FORMAT(dtk_time,'%Y-%m-%d') <='" . date('Y-m-d',strtotime($week[1])) . "' ";
						
			}elseif($cycle == 3){
				$w_dk = " and DATE_FORMAT(dtk_time,'%Y-%m-%d') >='" . date('Y-m-d',strtotime($month[0])) . "' ";
				$w_dk .= " and DATE_FORMAT(dtk_time,'%Y-%m-%d') <='" . date('Y-m-d',strtotime($month[1])) . "' ";
			}
			
			
			//删课时统计
			$sql_str_t = " select * from ( ";
			$sql_str_t .= " select st.school,st.std_id,st.name,st.grade,odr.class,odr.time1,odr.time2,odr.timee,odr.teacher,odr.count,st.xueguan,odr.cid,odr.dtk_time,odr.dtk_type,odr.reason from oa_dt_record as odr,hw001.student as st where st.school='" . get_school_name($school_id) . "' and  st.xueguan='" . $xueguan . "' and odr.std_id = st.std_id and dtk_type like '%删除%' " . $w_dk;
			$sql_str_t .=  " group by st.school,odr.cid having count(*)>=2 order by st.school,odr.timee DESC,dtk_time DESC,count"; //一门课程多次删除，记为一次；
			$sql_str_t .= " ) as del1 ";
			
			$sql_str_t .=  " union all ";
			
			$sql_str_t .= " select * from ( ";
			
			$sql_str_t .= " select st.school,st.std_id,st.name,st.grade,odr.class,odr.time1,odr.time2,odr.timee,odr.teacher,odr.count,st.xueguan,odr.cid,odr.dtk_time,odr.dtk_type,odr.reason from oa_dt_record as odr,hw001.student as st where st.school='" . get_school_name($school_id) . "' and  st.xueguan='" . $xueguan . "' and odr.std_id = st.std_id and dtk_type like '%删除%' " . $w_dk;
			$sql_str_t .=  " group by st.school,odr.cid having count(*)<2 order by st.school,odr.timee DESC,dtk_time DESC,count"; 
			$sql_str_t .= " ) as del2 ";
			
			
			//删课详情数量
			$sql_str_r = "select count(*) as count from (";
			$sql_str_r .=  $sql_str_t;
			$sql_str_r .= " ) as school_del_detail  order by school,cid,timee DESC,dtk_time DESC,count ";
			$dk_count = $model->query($sql_str_r); //各校区删课详情数据列表
			
			$maxCount = $dk_count?$dk_count[0]['count']:0;
			
			//删课详情
			$sql_str_r = "select * from (";
			$sql_str_r .=  $sql_str_t;
			$sql_str_r .= " ) as school_del_detail  order by school,cid,timee DESC,dtk_time DESC,count limit " . $pageNum . "," . $pageCount;
			
			
			$dk_lst = $model->query($sql_str_r); //各校区删课详情数据列表
			
			if(IS_AJAX && I('get.pageCount')){
				// 发送给页面的数据
				$this->ajaxReturn([
					'state'=>'ok',//查询结果
					'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
					'data'=>$dk_lst
				  ]);
			}
		}
		
		//调课统计
		unset($sql_str);
		unset($sql_str_t);
		unset($sql_str_r);
		
		
		if($type == 'tk_rs'){
			//调课时详情
			$sql_str_t = " select * from ( ";
			$sql_str_t .= " select st.school,st.std_id,st.name,st.grade,odr.class,odr.time1,odr.time2,odr.timee,odr.teacher,odr.count,st.xueguan,odr.cid,odr.dtk_time,odr.dtk_type,odr.reason from oa_dt_record as odr,hw001.student as st where st.school='" . get_school_name($school_id) . "' and  st.xueguan='" . $xueguan . "' and  odr.std_id = st.std_id and dtk_type like '%调课%' " . $w_dk;
			$sql_str_t .=  " group by st.school,odr.cid having count(*)>=2 order by st.school,odr.timee DESC,dtk_time DESC,count"; //一门课程多次调课，记为一次；
			$sql_str_t .= " ) as del1 ";
			
			$sql_str_t .=  " union all ";
			
			$sql_str_t .= " select * from ( ";
			
			$sql_str_t .= " select st.school,st.std_id,st.name,st.grade,odr.class,odr.time1,odr.time2,odr.timee,odr.teacher,odr.count,st.xueguan,odr.cid,odr.dtk_time,odr.dtk_type,odr.reason from oa_dt_record as odr,hw001.student as st where st.school='" . get_school_name($school_id) . "' and  st.xueguan='" . $xueguan. "' and odr.std_id = st.std_id and dtk_type like '%调课%' " . $w_dk;
			$sql_str_t .=  " group by st.school,odr.cid having count(*)<2 order by st.school,odr.timee DESC,dtk_time DESC,count"; 
			$sql_str_t .= " ) as del2 ";
			
			//调课详情总数
			$sql_str_r = "select count(*) as count from (";
			$sql_str_r .=  $sql_str_t;
			$sql_str_r .= " ) as school_del_detail  order by school,cid,timee DESC,dtk_time DESC,count ";
			
			$tk_count = $model->query($sql_str_r); //各校区调课详情数量
			
			$maxCount = $tk_count?$tk_count[0]['count']:0;
			
			//调课详情
			$sql_str_r = "select * from (";
			$sql_str_r .=  $sql_str_t;
			$sql_str_r .= " ) as school_del_detail  order by school,cid,timee DESC,dtk_time DESC,count limit " . $pageNum . "," . $pageCount;
			
			$tk_lst = $model->query($sql_str_r); //各校区调课详情数据列表
			
			if(IS_AJAX && I('get.pageCount')){
				// 发送给页面的数据
				$this->ajaxReturn([
					'state'=>'ok',//查询结果
					'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
					'data'=>$tk_lst
				  ]);
			}
		}
		
		if($type == 'xf_rs'){
			if($cycle == 1){
				$w_xf = "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') = date_format(NOW(), '%Y-%m-%d') ";
			}else if($cycle == 2){
				$w_xf = "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($week[0])) . "' ";
				$w_xf .= "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($week[1])) . "' ";
						
			}elseif($cycle == 3){
				$w_xf = "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($month[0])) . "' ";
				$w_xf .= "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($month[1])) . "' ";
			}
			
			//今日续费人数
			unset($sql_str);
			$sql_str = "SELECT count(*) as count FROM  hongwen_oa.oa_consumption as cs,hw001.student as st ";
			$sql_str .= " where st.school='" . get_school_name($school_id) . "' and  st.xueguan='" . $xueguan . "' and cs.std_id = st.std_id " . $w_xf . " and st.state=1 AND cs.belong_type = 2  order by st.school,st.xueguan, cs.std_id ";
			
			$detail_xf_count = $model->query($sql_str); //续费详情
			$maxCount = $detail_xf_count?$detail_xf_count[0]['count']:0;
			
			unset($sql_str);
			$sql_str = "SELECT st.school,st.name,st.grade,st.xueguan,cs.std_id,cs.value as money FROM  hongwen_oa.oa_consumption as cs,hw001.student as st ";
			$sql_str .= " where st.school='" . get_school_name($school_id) . "' and st.xueguan='" . $xueguan . "' and cs.std_id = st.std_id " . $w_xf . " and st.state=1 AND cs.belong_type = 2  order by st.school,st.xueguan, cs.std_id limit " . $pageNum . "," . $pageCount;
			
			$detail_xf_lst = $model->query($sql_str); //续费详情
			
			if(IS_AJAX && I('get.pageCount')){
				// 发送给页面的数据
				$this->ajaxReturn([
					'state'=>'ok',//查询结果
					'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
					'data'=>$detail_xf_lst
				  ]);
			}
		}
		
		
	
		
		//今日停课人数
		unset($sql_str);
		unset($sql_str_yf);
		unset($sql_str_tk);
		
		if($type == 'tks_rs'){
			if($cycle == 1){
				$w_tk_date= date('Y-m-d');
				$w_tk_type = '停课';
				$sql_str = "select count(distinct std_id) as count from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and xueguan='" . $xueguan . "' and ocur_date = '" . $w_tk_date . "' and type='" . $w_tk_type . "' and std_id not in (";
				$sql_str = "select distinct std_id from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and xueguan='" . $xueguan . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($w_tk_date))) . "' and type='" . $w_tk_type . "' ) ";
				$yf_tk_count = $model->query($sql_str);
				
				$sql_str_t = "select std_id,name,grade,xueguan,tel2,school from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and xueguan='" . $xueguan . "' and ocur_date = '" . $w_tk_date . "' and type='" . $w_tk_type . "' and std_id not in ( ";
				$sql_str_t .= "select distinct std_id from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and xueguan='" . $xueguan . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($w_tk_date))) . "' and type='" . $w_tk_type . "' ) ";
				$sql_str_t .= " group by school,std_id order by school,std_id limit " . $pageNum . "," . $pageCount;
				$yf_tk_data = $model->query($sql_str_t);
				
			}elseif($cycle == 2){
				$w_tk_date= date('Y-m-d');
				$w_tk_type = '停课';
				$sql_str = "select count(distinct std_id) as count from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and xueguan='" . $xueguan . "' and ocur_date = '" . $w_tk_date . "' and type='" . $w_tk_type . "' and std_id not in (";
				$sql_str = "select distinct std_id from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and xueguan='" . $xueguan . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($week[0]))) . "' and type='" . $w_tk_type . "' ) ";
				$yf_tk_count = $model->query($sql_str);
				
				$sql_str_t = "select std_id,name,grade,xueguan,tel2,school from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and xueguan='" . $xueguan . "' and ocur_date = '" . $w_tk_date . "' and type='" . $w_tk_type . "' and std_id not in ( ";
				$sql_str_t .= "select distinct std_id from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and xueguan='" . $xueguan . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($week[0]))) . "' and type='" . $w_tk_type . "' ) ";
				$sql_str_t .= " group by school,std_id order by school,std_id limit " . $pageNum . "," . $pageCount;
				$yf_tk_data = $model->query($sql_str_t);
			}elseif($cycle == 3){
				$w_tk_date= date('Y-m-d');
				$w_tk_type = '停课';
				$sql_str = "select count(distinct std_id) as count from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and xueguan='" . $xueguan . "' and ocur_date = '" . $w_tk_date . "' and type='" . $w_tk_type . "' and std_id not in (";
				$sql_str = "select distinct std_id from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and xueguan='" . $xueguan . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($month[0]))) . "' and type='" . $w_tk_type . "' ) ";
				$yf_tk_count = $model->query($sql_str);
				
				$sql_str_t = "select std_id,name,grade,xueguan,tel2,school from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and xueguan='" . $xueguan . "' and ocur_date = '" . $w_tk_date . "' and type='" . $w_tk_type . "' and std_id not in ( ";
				$sql_str_t .= "select distinct std_id from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and xueguan='" . $xueguan . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($month[0]))) . "' and type='" . $w_tk_type . "' ) ";
				$sql_str_t .= " group by school,std_id order by school,std_id limit " . $pageNum . "," . $pageCount;
				$yf_tk_data = $model->query($sql_str_t);
			}
			
			$maxCount = $yf_tk_count?$yf_tk_count[0]['count']:0;
			
			if(IS_AJAX && I('get.pageCount')){
				// 发送给页面的数据
				$this->ajaxReturn([
					'state'=>'ok',//查询结果
					'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
					'data'=>$yf_tk_data
				  ]);
			}
		}	
		
				      	
		//今日结课人数
		unset($sql_str);
		unset($sql_str_wf);
		unset($sql_str_tk);
		
		
		if($type == 'jks_rs'){
			if($cycle == 1){
				$w_jk_date= date('Y-m-d');
				$w_jk_type = '结课';
				$sql_str = "select  count(distinct std_id) as count from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and xueguan='" . $xueguan . "' and ocur_date = '" . $w_jk_date . "' and type='" . $w_jk_type . "' and std_id not in ( ";
				$sql_str .= "select  distinct std_id from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and xueguan='" . $xueguan . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($w_jk_date))) . "' and type='" . $w_jk_type . "' ) ";
				$wf_jk_count = $model->query($sql_str);
				
				$sql_str_j = "select std_id,name,grade,xueguan,tel2,school from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and  xueguan='" . $xueguan . "' and ocur_date = '" . $w_jk_date . "' and type='" . $w_jk_type . "' and std_id not in ( ";
				$sql_str_j .= " select  distinct std_id from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and xueguan='" . $xueguan . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($w_jk_date))) . "' and type='" . $w_jk_type . "' ) ";
				$sql_str_j .= " group by school,std_id order by school,std_id limit " . $pageNum . "," . $pageCount;
				$wf_jk_data = $model->query($sql_str_j);
				
			}elseif($cycle == 2){
				$w_jk_date= date('Y-m-d');
				$w_jk_type = '结课';
				$sql_str = "select  count(distinct std_id) as count from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and xueguan='" . $xueguan . "' and ocur_date = '" . $w_jk_date . "' and type='" . $w_jk_type . "' and std_id not in ( ";
				$sql_str .= "select  distinct std_id from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and xueguan='" . $xueguan . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($week[0]))) . "' and type='" . $w_jk_type . "' ) ";
				$wf_jk_count = $model->query($sql_str);
				
				$sql_str_j = "select std_id,name,grade,xueguan,tel2,school from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and  xueguan='" . $xueguan . "' and ocur_date = '" . $w_jk_date . "' and type='" . $w_jk_type . "' and std_id not in ( ";
				$sql_str_j .= " select  distinct std_id from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and xueguan='" . $xueguan . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($week[0]))) . "' and type='" . $w_jk_type . "' ) ";
				$sql_str_j .= " group by school,std_id order by school,std_id limit " . $pageNum . "," . $pageCount;
				$wf_jk_data = $model->query($sql_str_j);
			}elseif($cycle == 3){
				$w_jk_date= date('Y-m-d');
				$w_jk_type = '结课';
				$sql_str = "select  count(distinct std_id) as count from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and xueguan='" . $xueguan . "' and ocur_date = '" . $w_jk_date . "' and type='" . $w_jk_type . "' and std_id not in ( ";
				$sql_str .= "select  distinct std_id from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and xueguan='" . $xueguan . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($month[0]))) . "' and type='" . $w_jk_type . "' ) ";
				$wf_jk_count = $model->query($sql_str);
				
				$sql_str_j = "select std_id,name,grade,xueguan,tel2,school from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and  xueguan='" . $xueguan . "' and ocur_date = '" . $w_jk_date . "' and type='" . $w_jk_type . "' and std_id not in ( ";
				$sql_str_j .= " select  distinct std_id from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and xueguan='" . $xueguan . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($month[0]))) . "' and type='" . $w_jk_type . "' ) ";
				$sql_str_j .= " group by school,std_id order by school,std_id limit " . $pageNum . "," . $pageCount;
				$wf_jk_data = $model->query($sql_str_j);
			}
			
			$maxCount = $wf_jk_count?$wf_jk_count[0]['count']:0;
			
			if(IS_AJAX && I('get.pageCount')){
				// 发送给页面的数据
				$this->ajaxReturn([
					'state'=>'ok',//查询结果
					'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
					'data'=>$wf_jk_data
				  ]);
			}
		}
		
		
		if($type == 'tf_rs'){
			if($cycle == 1){
				$w_tf = "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') = DATE_FORMAT(now(), '%Y-%m-%d')) ";
			}else if($cycle == 2){
				$w_tf = "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($week[0])) . "' ";
				$w_tf .= "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($week[1])) . "' ) ";
						
			}elseif($cycle == 3){
				$w_tf = "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($month[0])) . "' ";
				$w_tf .= "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($month[1])) . "' ) ";
			}
			
			//今日退费学员
			unset($sql_str);
			
			$sql_str = "select count(*) as count from hw001.student as st";
			$sql_str .= " where std_id in (select distinct std_id from hongwen_oa.oa_consumption where type = 200 " . $w_tf . " and st.school='" . get_school_name($school_id) . "' and st.xueguan='" . $xueguan . "'";
			$tf_count = $model->query($sql_str);
			
			unset($sql_str);
			
			$sql_str = "select std_id,name,grade,school,xueguan,DATE_FORMAT(now(), '%Y-%m-%d') from hw001.student as st";
			$sql_str .= " where std_id in (select distinct std_id from hongwen_oa.oa_consumption where type = 200 " . $w_tf . " and st.school='" . get_school_name($school_id) . "' and st.xueguan='" . $xueguan . "'";
	
			$sql_str .= " order by school,xueguan,std_id  limit " . $pageNum . "," . $pageCount;
			
			$tf_data = $model->query($sql_str);
			
			$maxCount = $tf_count?$tf_count[0]['count']:0;
			
			if(IS_AJAX && I('get.pageCount')){
				// 发送给页面的数据
				$this->ajaxReturn([
					'state'=>'ok',//查询结果
					'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
					'data'=>$tf_data
				  ]);
			}
		}
			
		
		
				
		if($type == 'wh_rs'){
			if($cycle == 1){
				$w_wh = "   and DATE_FORMAT(weihu_timee, '%Y-%m-%d') =  DATE_FORMAT(NOW(), '%Y-%m-%d') ";
			}else if($cycle == 2){
				$w_wh = "  and DATE_FORMAT(weihu_timee, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($week[0])) . "' ";
				$w_wh .= "  and DATE_FORMAT(weihu_timee, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($week[1])) . "' ";
						
			}elseif($cycle == 3){
				$w_wh = "  and DATE_FORMAT(weihu_timee, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($month[0])) . "' ";
				$w_wh .= "  and DATE_FORMAT(weihu_timee, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($month[1])) . "' ";
			}
			
			//今日维护人数
			unset($sql_str);
			$sql_str = "select count(*) as count from hongwen_oa.oa_weihu as wh,hw001.student as st where st.school='" . get_school_name($school_id) . "' and  st.xueguan='" . $xueguan . "' and wh.std_id = st.std_id " . $w_wh  . " order by st.school, wh.std_id";
			$wh_count = $model->query($sql_str);
			
			unset($sql_str);
			$sql_str = "select st.school,st.name,st.grade,st.xueguan,DATE_FORMAT(wh.weihu_timee,'%Y-%m-%d') as weihu_timee,wh.weihu_content,wh.std_id from hongwen_oa.oa_weihu as wh,hw001.student as st where st.school='" . get_school_name($school_id) . "' and  st.xueguan='" . $xueguan . "' and wh.std_id = st.std_id " . $w_wh  . " order by st.school, wh.std_id limit " . $pageNum . "," . $pageCount;
			
			$wh_lst = $model->query($sql_str);
			
			$maxCount = $wh_count?$wh_count[0]['count']:0;
			
			if(IS_AJAX && I('get.pageCount')){
				// 发送给页面的数据
				$this->ajaxReturn([
					'state'=>'ok',//查询结果
					'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
					'data'=>$wh_lst
				  ]);
			}
			
		}
		
		
		if(($type == 'yx_rs') || ($type == 'hwh_rs') || ($type == 'hjh_rs') || ($type == 'yxrs_tj')){
			
			//截至本月有效学员数
			unset($sql_str);
			/*所有有费学员，但不包含只有特色课堂的学员*/
			$sql_str = "select std_id from hongwen_oa.oa_consumption where  state != 50 and is_del != 1 and std_id not in ( ";
			/*只有特色课堂的学员*/
			$sql_str .= " select std_id from oa_course where oa_course.state in (200,250) and oa_course.std_id in ( ";
			
			$sql_str .= " select std_id from oa_course where oa_course.unit_plan in ( ";
			
			$sql_str .= " select our.id ";
			
			$sql_str .= " from oa_unitprice_role as our,oa_foo_info as F1,oa_foo_info as F2,oa_foo_info as F3,oa_foo_info as F4 ";
			
			$sql_str .= " where our.school = F1.id and our.grade = F2.id and our.level = F3.id and our.course = F4.id and F4.group = 4)) ";
			
			$sql_str .= " group by oa_course.std_id having sum(oa_course.hour+oa_course.ext_hour-oa_course.used_hour)>0 and count(oa_course.id)=1) ";
			
			$sql_str .= " group by std_id having sum(value)>0 ";
			
			$yf_all = $model->query($sql_str);
	
			unset($sql_str);
			/*特色课堂有订单学员*/
			$sql_str = "select oa_course.id,std_id,unit_plan from oa_course where oa_course.state in (200,250) and oa_course.std_id in ( ";
			
			$sql_str .= " select std_id from oa_course where oa_course.state in (200,250) and oa_course.unit_plan in ( ";
			
			$sql_str .= " select our.id from oa_unitprice_role as our,oa_foo_info as F1,oa_foo_info as F2,oa_foo_info as F3,oa_foo_info as F4 ";
			
			$sql_str .= " where our.school='" . $school_id . "' and our.school = F1.id and our.grade = F2.id and our.level = F3.id and our.course = F4.id and F4.group = 4 ))";
			
			$sql_str .= " group by oa_course.std_id having sum(oa_course.hour+oa_course.ext_hour-oa_course.used_hour)>0 and count(oa_course.id)>1 ";
			
			$ydd1 = $model->query($sql_str);
			
			unset($sql_str);
			
			/*有订单学员，但不包含有特色课堂的学员*/
			$sql_str = "select std_id from oa_course where oa_course.state in (200,250) and oa_course.unit_plan not in ( ";
			
			$sql_str .= " select our.id from oa_unitprice_role as our,oa_foo_info as F1,oa_foo_info as F2,oa_foo_info as F3,oa_foo_info as F4 ";
			
			$sql_str .= " where our.school = F1.id and our.grade = F2.id and our.level = F3.id and our.course = F4.id and F4.group = 4 ) ";
			
			$sql_str .= " and oa_course.school=" . $school_id .  " group by oa_course.std_id having sum(oa_course.hour+oa_course.ext_hour-oa_course.used_hour)>0 ";
			
			$ydd2 = $model->query($sql_str);
			
			$yx_lst = array_merge(array_column($yf_all,'std_id'),array_column($ydd1,'std_id'),array_column($ydd2,'std_id'));
			
			
			$w_st['st.std_id'] = array('in',$yx_lst); //有效学员std_id编号列表，有效学员包括在读学员和停课学员；
			
			if($type == 'yxrs_tj'){
				unset($sql_str);
				$std_lst_str = implode("','", $yx_lst);	
				$sql_str = "select count(distinct std_id) as count from hw001.student where school='" . get_school_name($school_id) . "' and std_id in ('" . $std_lst_str . "') and xueguan='" . $xueguan . "' ";
				$yxrs_count = $model->query($sql_str);
				
				unset($sql_str);
				$sql_str = "select std_id,name,sex,grade,xueguan,school from hw001.student where school='" . get_school_name($school_id) . "' and std_id in ('" . $std_lst_str . "') and xueguan='" . $xueguan . "'  limit " . $pageNum . "," . $pageCount;
				$yxrs_data = $model->query($sql_str);
				
				$maxCount = $yxrs_count?$yxrs_count[0]['count']:0;
			
				if(IS_AJAX && I('get.pageCount')){
					// 发送给页面的数据
					$this->ajaxReturn([
						'state'=>'ok',//查询结果
						'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
						'data'=>$yxrs_data
					  ]);
				}
			}
				
			if($type == 'yx_rs'){
				$std_lst_str = implode("','", $yx_lst);				  
					
				$sql_str_rm = "select count(*) as count from (";
				$sql_str_rm .= " select st.std_id,st.name,st.grade,st.xueguan,cs.id as courseid,st.school,(cs.hour+cs.ext_hour-cs.used_hour) as remain_hour from hw001.student as st,hongwen_oa.oa_course as cs where st.school='" . get_school_name($school_id) . "' and  st.xueguan='" . $xueguan . "' and st.std_id = cs.std_id ";
				$sql_str_rm .= " and st.std_id in ('" . $std_lst_str . "')";
				$sql_str_rm .= " and st.state in (1,2) and cs.state in ('200','250') group by st.school,cs.std_id ";
				$sql_str_rm .= " ) as course where remain_hour<=10  ";
				
				$yx_count = $model->query($sql_str_rm); 
				
				unset($sql_str_rm);
				$sql_str_rm = "select * from (";
				$sql_str_rm .= " select st.std_id,st.name,st.grade,st.xueguan,cs.id as courseid,st.school,(cs.hour+cs.ext_hour-cs.used_hour) as remain_hour from hw001.student as st,hongwen_oa.oa_course as cs where st.school='" . get_school_name($school_id) . "' and  st.xueguan='" . $xueguan . "' and st.std_id = cs.std_id ";
				$sql_str_rm .= " and st.std_id in ('" . $std_lst_str . "')";
				$sql_str_rm .= " and st.state in (1,2) and cs.state in ('200','250') group by st.school,cs.std_id ";
				$sql_str_rm .= " ) as course where remain_hour<=10  order by school,std_id limit " . $pageNum . "," . $pageCount;
				
				$yx_data = $model->query($sql_str_rm); 
				
				$maxCount = $yx_count?$yx_count[0]['count']:0;
			
				if(IS_AJAX && I('get.pageCount')){
					// 发送给页面的数据
					$this->ajaxReturn([
						'state'=>'ok',//查询结果
						'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
						'data'=>$yx_data
					  ]);
				}
			
			}
			
			if(($type == 'hwh_rs') || ($type == 'hjh_rs')){
				unset($sql_str_t);
				unset($yf_tk_lst);
				if($cycle == 1){
					$w_tk_date= date('Y-m-d');
					$sql_str_t = "select std_id from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and xueguan='" . $xueguan . "' and ocur_date = '" . $w_tk_date . "' and type='停课' and std_id not in ( ";
					$sql_str_t .= " select std_id from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and xueguan='" . $xueguan . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($w_tk_date))) . "' and type='停课' group by std_id ) group by std_id ";
					$yf_tk_lst = $model->query($sql_str_t);
					
				}elseif($cycle == 2){
					$w_tk_date= date('Y-m-d');
					$sql_str_t = "select std_id from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and xueguan='" . $xueguan . "' and ocur_date = '" . $w_tk_date . "' and type='停课' and std_id not in ( ";
					$sql_str_t .= " select std_id from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and xueguan='" . $xueguan . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($week[0]))) . "' and type='停课' group by std_id ) group by std_id ";
					$yf_tk_lst = $model->query($sql_str_t);
				}elseif($cycle == 3){
					$w_tk_date= date('Y-m-d');
					$sql_str_t = "select std_id from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and xueguan='" . $xueguan . "' and ocur_date = '" . $w_tk_date . "' and type='停课' and std_id not in ( ";
					$sql_str_t .= " select std_id from hongwen_oa.oa_xg_statistic where school='" . get_school_name($school_id) . "' and xueguan='" . $xueguan . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($month[0]))) . "' and type='停课' group by std_id ) group by std_id ";
					$yf_tk_lst = $model->query($sql_str_t);
				}
				
				if($type == 'hwh_rs'){
						
					//从所有有效学员中获取在读学员
					foreach($yx_lst as $k=>$v){
						if(array_search($v['std_id'], $yf_tk_lst) !== FALSE){
							unset($yx_lst[$k]);
						}
					}

					$std_lst = array_values($yx_lst);
					
//					$std_lst = array_diff($yx_lst, $yf_tk_lst); //在读学员
//					$std_lst = array_values($std_lst);
					
					$zd_std_lst = implode("','", $std_lst);
					//半月未维护人数；
					unset($sql_str);
					
					$sql_str = " SELECT count(*) as count from ( ";
					$sql_str .= " select st.std_id,st.xueguan,st.grade,st.name,st.school,wh.weihu_timee,st.state,datediff(DATE_FORMAT(now(), '%Y-%m-%d'),DATE_FORMAT(max(weihu_timee), '%Y-%m-%d')) dif_date FROM hongwen_oa.oa_weihu AS wh, hw001.student AS st";
					$sql_str .= " WHERE st.school='" . get_school_name($school_id) . "' and  st.xueguan='" . $xueguan . "' and st.state = 1 AND wh.std_id = st.std_id and st.std_id in ('" . $zd_std_lst . "') ";
					$sql_str .= " group by st.school,st.std_id  order by weihu_timee DESC  ) as weihu ";
					$sql_str .= " where  (dif_date>=15 or dif_date is null or dif_date = '') ";	
					
					$hwh_count = $model->query($sql_str);
					unset($sql_str);
					
					$sql_str = " SELECT std_id,name,xueguan,grade,school,weihu_timee,state,dif_date from ( ";
					$sql_str .= " select st.std_id,st.xueguan,st.grade,st.name,st.school,wh.weihu_timee,st.state,datediff(DATE_FORMAT(now(), '%Y-%m-%d'),DATE_FORMAT(max(weihu_timee), '%Y-%m-%d')) dif_date FROM hongwen_oa.oa_weihu AS wh, hw001.student AS st";
					$sql_str .= " WHERE st.school='" . get_school_name($school_id) . "' and  st.xueguan='" . $xueguan . "' and st.state = 1 AND wh.std_id = st.std_id and st.std_id in ('" . $zd_std_lst . "') ";
					$sql_str .= " group by st.school,st.std_id  order by weihu_timee DESC  ) as weihu ";
					$sql_str .= " where  (dif_date>=15 or dif_date is null or dif_date = '') order by school,std_id,weihu_timee DESC limit " . $pageNum . "," . $pageCount;	
					
					$hwh_lst = $model->query($sql_str);
					
					$maxCount = $hwh_count?$hwh_count[0]['count']:0;
			
					if(IS_AJAX && I('get.pageCount')){
						// 发送给页面的数据
						$this->ajaxReturn([
							'state'=>'ok',//查询结果
							'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
							'data'=>$hwh_lst
						  ]);
					}
									
				}
				
				if($type == 'hjh_rs'){
					$yf_std_lst = array_column($yf_tk_lst,'std_id');
					$tk_std_lst = implode("','", $yf_std_lst); //停课学员的学员编号；	
					//半月未激活人数；
					
					unset($sql_str);
					$sql_str = " SELECT count(*) as count from ( ";
					$sql_str .= " select st.std_id,st.school,wh.weihu_timee,st.state,datediff(DATE_FORMAT(now(), '%Y-%m-%d'),DATE_FORMAT(max(weihu_timee), '%Y-%m-%d')) dif_date FROM hongwen_oa.oa_weihu AS wh, hw001.student AS st";
					$sql_str .= " WHERE st.school='" . get_school_name($school_id) . "' and st.xueguan='" . $xueguan . "' and st.state in(1,2) AND wh.std_id = st.std_id and st.std_id in ('" . $tk_std_lst . "') ";
					$sql_str .= " group by st.school,st.std_id  order by weihu_timee DESC  ) as weihu ";
					$sql_str .= " where  (dif_date>=15 or dif_date is null or dif_date = '') ";	
					$hjh_count = $model->query($sql_str);
					
					unset($sql_str);
					$sql_str = " SELECT std_id,name,xueguan,grade,school,weihu_timee,state,dif_date from ( ";
					$sql_str .= " select st.std_id,st.name,st.xueguan,st.grade,st.school,wh.weihu_timee,st.state,datediff(DATE_FORMAT(now(), '%Y-%m-%d'),DATE_FORMAT(max(weihu_timee), '%Y-%m-%d')) dif_date FROM hongwen_oa.oa_weihu AS wh, hw001.student AS st";
					$sql_str .= " WHERE st.school='" . get_school_name($school_id) . "' and st.xueguan='" . $xueguan . "' and st.state in (1,2) AND wh.std_id = st.std_id and st.std_id in ('" . $tk_std_lst . "') ";
					$sql_str .= " group by st.school,st.std_id  order by weihu_timee DESC  ) as weihu ";
					$sql_str .= " where  (dif_date>=15 or dif_date is null or dif_date = '') order by school,std_id,weihu_timee DESC limit " . $pageNum . "," . $pageCount;	
					
					$hjh_lst = $model->query($sql_str);
					
					$maxCount = $hjh_count?$hjh_count[0]['count']:0;
			
					if(IS_AJAX && I('get.pageCount')){
						// 发送给页面的数据
						$this->ajaxReturn([
							'state'=>'ok',//查询结果
							'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
							'data'=>$hjh_lst
						  ]);
					}
				}
			}
		}
	}
	
	
		//集团各校区统计数据日、周、月
	public function jtxq_all_statistic(){
		
		
		$this->statis_count = 10;
		$this->dk_count = 10;
		$this->dtk_tk_count = 10;
		$this->xf_count = 10;
		$this->tk_count = 10;
		$this->jk_count = 10;
		$this->tf_count = 10;
		$this->wh_count = 10;
		$this->yx_count = 10;


		$school = M('foo_info');
		$xg = M('user');
		$stu = M('hw001.student',NULL);
		$consump = M('consumption');
		$course = M('course');
		$class = M('hw001.class',NULL);
				
		
		$school_id= session('school_id');

		
		//校区账户进来, 首先获取所有学管师的名字；
		$wx['position_id'] = 18;
		$wx['is_del'] = 0;
		$xg_lst = $xg->where($wx)->getField('name',TRUE);
		
		if(count($xg_lst)){
			$xglst = implode("','",$xg_lst);
			$xg_w = " and st.xueguan in ('" . $xglst . "') ";	
		}
			   
		
		//获取统计的周期；
		$cycle = I('get.cycle');
		if(empty($cycle)){
			$cycle = 1;
			$nowDate = date('Y-m-d');
		}
		//前端页面周期回显；
		$this->cycle= $cycle;
//		$nowDate = date('Y-m-d');
		
		if($cycle == 1){
			if(I('get.statistic_day')){
				$nowDate = I('get.statistic_day');	
			}
				
		}else{
			$nowDate = date('Y-m-d');
		}
		
		if($cycle ==4){
			if(I('get.beginDate') && I('get.endDate') ){
				$beginDate = I('get.beginDate');
				$endDate = I('get.endDate');
				
				$this->beginD = $beginDate;//设置前端页面数据回显；
				$this->endD = $endDate;//设置前端页面数据回显；
			}
		}
		 
		
		$this->nowDate = $nowDate; //设置前端页面数据；
		//获取统计周的时间段
		$week = get_date($nowDate,'w'); //本周
		//获取统计月的时间段
		$month = get_date($nowDate,'m'); //本月

		/*============================================================以下开始统计各种数据==================================================================*/
		$model = new \Think\Model();

		//统计课时数及上课人数
		$sql_str = "select stus.school,sum(count) as ksnum,count(distinct stus.std_id) as pnum from hw001.class as cl, ";
		$sql_str .=" (select st.school,st.std_id,st.name,st.xueguan from hw001.student as st,hongwen_oa.oa_user as us where st.state=1 and st.xueguan = us.name and us.position_id=18 and us.is_del=0 ) as stus";
		
		if($cycle == 1){
			$w_ks = " and date_format(cl.timee, '%Y-%m-%d')='".$nowDate."' ";
		}else if($cycle == 2){
			$w_ks = " and date_format(cl.timee, '%Y-%m-%d')>='". date('Y-m-d',strtotime($week[0])) . "' ";
			$w_ks .= " and date_format(cl.timee, '%Y-%m-%d')<='".date('Y-m-d',strtotime($week[1])) . "' ";		
		}elseif($cycle == 3){
			$w_ks = " and date_format(cl.timee, '%Y-%m-%d')>='". date('Y-m-d',strtotime($month[0])) . "' ";
			$w_ks .= " and date_format(cl.timee, '%Y-%m-%d')<='".date('Y-m-d',strtotime($month[1])) . "' ";
		}elseif($cycle == 4){
			$w_ks = " and date_format(cl.timee, '%Y-%m-%d')>='". $beginDate . "' ";
			$w_ks .= " and date_format(cl.timee, '%Y-%m-%d')<='".$endDate . "' ";
		}
			
		
		$sql_str .=" where cl.std_id=stus.std_id " . $w_ks . " group by stus.school";

		$ks_count = $model->query($sql_str);
		$this->kscount = json_encode($ks_count); //页面赋值；

		
		
		if($cycle == 1){
//			$w_dk = " and DATE_FORMAT(dtk_time,'%Y-%m-%d')='" . date('Y-m-d'). "' ";
			$w_dk = " and DATE_FORMAT(dtk_time,'%Y-%m-%d')='" . $nowDate. "' ";
		}else if($cycle == 2){
			$w_dk = " and DATE_FORMAT(dtk_time,'%Y-%m-%d') >='" . date('Y-m-d',strtotime($week[0])) . "' ";
			$w_dk .= " and DATE_FORMAT(dtk_time,'%Y-%m-%d') <='" . date('Y-m-d',strtotime($week[1])) . "' ";
					
		}elseif($cycle == 3){
			$w_dk = " and DATE_FORMAT(dtk_time,'%Y-%m-%d') >='" . date('Y-m-d',strtotime($month[0])) . "' ";
			$w_dk .= " and DATE_FORMAT(dtk_time,'%Y-%m-%d') <='" . date('Y-m-d',strtotime($month[1])) . "' ";
		}elseif($cycle == 4){
			$w_dk = " and DATE_FORMAT(dtk_time,'%Y-%m-%d') >='" . $beginDate . "' ";
			$w_dk .= " and DATE_FORMAT(dtk_time,'%Y-%m-%d') <='" . $endDate . "' ";
		}
		
		
		//删课时统计
		$sql_str = "select school,sum(count) as ksnum,count(distinct std_id) as pnum from (";
		
		$sql_str_t = " select * from ( ";
		$sql_str_t .= " select st.school,st.std_id,st.name,st.grade,odr.class,odr.time1,odr.time2,odr.timee,odr.teacher,odr.count,st.xueguan,odr.cid,odr.dtk_time,odr.dtk_type,odr.reason from oa_dt_record as odr,hw001.student as st where odr.std_id = st.std_id and dtk_type like '%删除%' " . $xg_w . $w_dk;
		$sql_str_t .=  " group by st.school,odr.cid having count(*)>=2 order by st.school,odr.timee DESC,dtk_time DESC,count"; //一门课程多次删除，记为一次；
		$sql_str_t .= " ) as del1 ";
		
		$sql_str_t .=  " union all ";
		
		$sql_str_t .= " select * from ( ";
		
		$sql_str_t .= " select st.school,st.std_id,st.name,st.grade,odr.class,odr.time1,odr.time2,odr.timee,odr.teacher,odr.count,st.xueguan,odr.cid,odr.dtk_time,odr.dtk_type,odr.reason from oa_dt_record as odr,hw001.student as st where odr.std_id = st.std_id and dtk_type like '%删除%' " . $xg_w . $w_dk;
		$sql_str_t .=  " group by st.school,odr.cid having count(*)<2 order by st.school,odr.timee DESC,dtk_time DESC,count"; 
		$sql_str_t .= " ) as del2 ";
		
		$sql_str .= $sql_str_t;
		
		$sql_str .= " ) as school_del_all group by school ";
		
		
		$dk_count = $model->query($sql_str); //各校区删课统计数据；
		
		$this->dkcount = json_encode($dk_count);
		
		
		//调课统计
		unset($sql_str);
		unset($sql_str_t);
		unset($sql_str_r);
		
		
		
		//调课时统计
		$sql_str = "select school,sum(count) as ksnum,count(distinct std_id) as pnum from (";
		
		$sql_str_t = " select * from ( ";
		$sql_str_t .= " select st.school,st.std_id,st.name,st.grade,odr.class,odr.time1,odr.time2,odr.timee,odr.teacher,odr.count,st.xueguan,odr.cid,odr.dtk_time,odr.dtk_type,odr.reason from oa_dt_record as odr,hw001.student as st where odr.std_id = st.std_id and dtk_type like '%调课%' " . $xg_w . $w_dk;
		$sql_str_t .=  " group by st.school,odr.cid having count(*)>=2 order by st.school,odr.timee DESC,dtk_time DESC,count"; //一门课程多次调课，记为一次；
		$sql_str_t .= " ) as del1 ";
		
		$sql_str_t .=  " union all ";
		
		$sql_str_t .= " select * from ( ";
		
		$sql_str_t .= " select st.school,st.std_id,st.name,st.grade,odr.class,odr.time1,odr.time2,odr.timee,odr.teacher,odr.count,st.xueguan,odr.cid,odr.dtk_time,odr.dtk_type,odr.reason from oa_dt_record as odr,hw001.student as st where odr.std_id = st.std_id and dtk_type like '%调课%' " . $xg_w . $w_dk;
		$sql_str_t .=  " group by st.school,odr.cid having count(*)<2 order by st.school,odr.timee DESC,dtk_time DESC,count"; 
		$sql_str_t .= " ) as del2 ";
		
		$sql_str .= $sql_str_t;
		
		$sql_str .= " ) as school_del_all group by school ";
		
		
		$tk_count = $model->query($sql_str); //各校区调课统计数据；
		
		$this->tkcount = json_encode($tk_count);
		
		
		if($cycle == 1){
//			$w_xf = "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') = date_format(NOW(), '%Y-%m-%d') ";
			$w_xf = "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') = '" . $nowDate. "' ";
		}else if($cycle == 2){
			$w_xf = "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($week[0])) . "' ";
			$w_xf .= "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($week[1])) . "' ";
					
		}elseif($cycle == 3){
			$w_xf = "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($month[0])) . "' ";
			$w_xf .= "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($month[1])) . "' ";
		}elseif($cycle == 4){
			$w_xf = "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') >='" . $beginDate . "' ";
			$w_xf .= "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') <='" . $endDate . "' ";
		}
		
		//今日续费人数
		unset($sql_str);
		$sql_str = "SELECT st.school,sum(cs.value) as money,count(distinct cs.std_id) as pnum FROM  hongwen_oa.oa_consumption as cs,hw001.student as st ";
		$sql_str .= " where cs.std_id = st.std_id " . $w_xf . " and st.state=1 AND cs.belong_type = 2  group by st.school  ";
		
		$xf_count = $model->query($sql_str); //各校区续费情况
		
		$this->xfcount = json_encode($xf_count);
		
		
		//今日停课人数
		unset($sql_str);
		unset($sql_str_yf);
		unset($sql_str_tk);
			
		if($cycle == 1){
//			$w_tk_date= date('Y-m-d');
			$w_tk_date= $nowDate;
			$w_tk_type = '停课';
			$sql_str_t = "select school,count(distinct std_id) as pnum from hongwen_oa.oa_xg_statistic where ocur_date = '" . $w_tk_date . "' and type='" . $w_tk_type . "' and std_id not in ( ";
			$sql_str_t .= "select distinct std_id from hongwen_oa.oa_xg_statistic where ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($w_tk_date))) . "' and type='" . $w_tk_type . "' group by std_id ) group by school ";
			$yf_tk_count = $model->query($sql_str_t);
			
		}elseif($cycle == 2){
			$w_tk_date= date('Y-m-d');
			$w_tk_type = '停课';
			$sql_str_t = "select school,count(distinct std_id) as pnum from hongwen_oa.oa_xg_statistic where ocur_date = '" . $w_tk_date . "' and type='" . $w_tk_type . "' and std_id not in ( ";
			$sql_str_t .= "select distinct std_id from hongwen_oa.oa_xg_statistic where ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($week[0]))) . "' and type='" . $w_tk_type . "' group by std_id ) group by school ";
			$yf_tk_count = $model->query($sql_str_t);
		}elseif($cycle == 3){
			$w_tk_date= date('Y-m-d');
			$w_tk_type = '停课';
			$sql_str_t = "select school,count(distinct std_id) as pnum from hongwen_oa.oa_xg_statistic where ocur_date = '" . $w_tk_date . "' and type='" . $w_tk_type . "' and std_id not in ( ";
			$sql_str_t .= "select distinct std_id from hongwen_oa.oa_xg_statistic where ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($month[0]))) . "' and type='" . $w_tk_type . "' group by std_id ) group by school ";
			$yf_tk_count = $model->query($sql_str_t);
		}elseif($cycle == 4){
			
			$w_tk_type = '停课';
			$sql_str_t = "select school,count(distinct std_id) as pnum from hongwen_oa.oa_xg_statistic where ocur_date between '" . $beginDate . "' and '" . $endDate . "'  and type='" . $w_tk_type . "' and std_id not in ( ";
			$sql_str_t .= "select distinct std_id from hongwen_oa.oa_xg_statistic where ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($beginDate))) . "' and type='" . $w_tk_type . "' group by std_id ) group by school ";
			$yf_tk_count = $model->query($sql_str_t);
		}
		
		$this->yftkcount = json_encode($yf_tk_count);
				      	
		//今日结课人数
		unset($sql_str);
		unset($sql_str_wf);
		unset($sql_str_tk);
		
		
		
		if($cycle == 1){
//			$w_jk_date= date('Y-m-d');
			$w_jk_date= $nowDate;
			$w_jk_type = '结课';
			$sql_str_j = "select school,count(distinct std_id) as pnum from hongwen_oa.oa_xg_statistic where ocur_date = '" . $w_jk_date . "' and type='" . $w_jk_type . "' and std_id not in ( ";
			$sql_str_j .= "select distinct std_id from hongwen_oa.oa_xg_statistic where ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($w_jk_date))) . "' and type='" . $w_jk_type . "' group by std_id ) group by school ";
			$wf_jk_count = $model->query($sql_str_j);
			
		}elseif($cycle == 2){
			$w_jk_date= date('Y-m-d');
			$w_jk_type = '结课';
			$sql_str_j = "select school,count(distinct std_id) as pnum from hongwen_oa.oa_xg_statistic where ocur_date = '" . $w_jk_date . "' and type='" . $w_jk_type . "' and std_id not in ( ";
			$sql_str_j .= "select distinct std_id from hongwen_oa.oa_xg_statistic where ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($week[0]))) . "' and type='" . $w_jk_type . "' group by std_id ) group by school ";
			$wf_jk_count = $model->query($sql_str_j);
		}elseif($cycle == 3){
			$w_jk_date= date('Y-m-d');
			$w_jk_type = '结课';
			$sql_str_j = "select school,count(distinct std_id) as pnum from hongwen_oa.oa_xg_statistic where ocur_date = '" . $w_jk_date . "' and type='" . $w_jk_type . "' and std_id not in ( ";
			$sql_str_j .= "select distinct std_id from hongwen_oa.oa_xg_statistic where ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($month[0]))) . "' and type='" . $w_jk_type . "' group by std_id ) group by school ";
			$wf_jk_count = $model->query($sql_str_j);
		}elseif($cycle == 4){
			$w_jk_type = '结课';
			$sql_str_j = "select school,count(distinct std_id) as pnum from hongwen_oa.oa_xg_statistic where ocur_date between '" . $beginDate . "' and '" . $endDate . "' and type='" . $w_jk_type . "' and std_id not in ( ";
			$sql_str_j .= "select distinct std_id from hongwen_oa.oa_xg_statistic where ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($beginDate))) . "' and type='" . $w_jk_type . "' group by std_id ) group by school ";
			$wf_jk_count = $model->query($sql_str_j);
		}
		
		$this->wftkcount = json_encode($wf_jk_count);
		
		
		if($cycle == 1){
//			$w_tf = "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') = DATE_FORMAT(now(), '%Y-%m-%d')) ";
			$w_tf = "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') = '" . $nowDate . "') ";
		}else if($cycle == 2){
			$w_tf = "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($week[0])) . "' ";
			$w_tf .= "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($week[1])) . "' ) ";
					
		}elseif($cycle == 3){
			$w_tf = "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($month[0])) . "' ";
			$w_tf .= "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($month[1])) . "' ) ";
		}elseif($cycle == 4){
			$w_tf = "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') >='" . $beginDate . "' ";
			$w_tf .= "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') <='" . $endDate . "' ) ";
		}
		
		//今日退费学员
		unset($sql_str);
		
		$sql_str = "select school,count(distinct std_id) as pnum from hw001.student as st";
		$sql_str .= " where std_id in (select distinct std_id from hongwen_oa.oa_consumption where type = 200 " . $w_tf ;

		$sql_str .= " group by school order by school ";
		
		$tf_count = $model->query($sql_str);
		
		
		$this->tfcount = json_encode($tf_count);
		
		
		if($cycle == 1){
//			$w_wh = "   and DATE_FORMAT(weihu_timee, '%Y-%m-%d') =  DATE_FORMAT(NOW(), '%Y-%m-%d') ";
			$w_wh = "   and DATE_FORMAT(weihu_timee, '%Y-%m-%d') =  '" . $nowDate . "' ";
		}else if($cycle == 2){
			$w_wh = "  and DATE_FORMAT(weihu_timee, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($week[0])) . "' ";
			$w_wh .= "  and DATE_FORMAT(weihu_timee, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($week[1])) . "' ";
					
		}elseif($cycle == 3){
			$w_wh = "  and DATE_FORMAT(weihu_timee, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($month[0])) . "' ";
			$w_wh .= "  and DATE_FORMAT(weihu_timee, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($month[1])) . "' ";
		}elseif($cycle == 4){
			$w_wh = "  and DATE_FORMAT(weihu_timee, '%Y-%m-%d') >='" . $beginDate . "' ";
			$w_wh .= "  and DATE_FORMAT(weihu_timee, '%Y-%m-%d') <='" . $endDate . "' ";
		}
		
		//今日维护人数
		unset($sql_str);
		
		$sql_str = "select st.school as school,count(distinct wh.std_id) as pnum from hongwen_oa.oa_weihu as wh,hw001.student as st where wh.std_id = st.std_id " . $w_wh . " group by st.school";
		
		$wh_count = $model->query($sql_str);
		
		
		$this->whcount = json_encode($wh_count);
		
		unset($sql_str);
		
		//截至本月有效学员数
		
		unset($sql_str);
		/*所有有费学员，但不包含只有特色课堂的学员*/
		$sql_str = "select std_id from hongwen_oa.oa_consumption where  state != 50 and is_del != 1 and std_id not in ( ";
		/*只有特色课堂的学员*/
		$sql_str .= " select std_id from oa_course where  oa_course.state in (200,250) and oa_course.std_id in ( ";
		
		$sql_str .= " select std_id from oa_course where oa_course.unit_plan in ( ";
		
		$sql_str .= " select our.id ";
		
		$sql_str .= " from oa_unitprice_role as our,oa_foo_info as F1,oa_foo_info as F2,oa_foo_info as F3,oa_foo_info as F4 ";
		
		$sql_str .= " where our.school = F1.id and our.grade = F2.id and our.level = F3.id and our.course = F4.id and F4.group = 4)) ";
		
		$sql_str .= " group by oa_course.std_id having sum(oa_course.hour+oa_course.ext_hour-oa_course.used_hour)>0 and count(oa_course.id)=1) ";
		
		$sql_str .= " group by std_id having sum(value)>0 ";
		
		$yf_all = $model->query($sql_str);

		unset($sql_str);
		/*特色课堂有订单学员*/
		$sql_str = "select oa_course.id,std_id,unit_plan from oa_course where  oa_course.state in (200,250) and oa_course.std_id in ( ";
		
		$sql_str .= " select std_id from oa_course where  oa_course.state in (200,250) and oa_course.unit_plan in ( ";
		
		$sql_str .= " select our.id from oa_unitprice_role as our,oa_foo_info as F1,oa_foo_info as F2,oa_foo_info as F3,oa_foo_info as F4 ";
		
		$sql_str .= " where our.school = F1.id and our.grade = F2.id and our.level = F3.id and our.course = F4.id and F4.group = 4 ))";
		
		$sql_str .= " group by oa_course.std_id having sum(oa_course.hour+oa_course.ext_hour-oa_course.used_hour)>0 and count(oa_course.id)>1 ";
		
		$ydd1 = $model->query($sql_str);
		
		unset($sql_str);
		
		/*有订单学员，但不包含有特色课堂的学员*/
		$sql_str = "select std_id from oa_course where  oa_course.state in (200,250) and oa_course.unit_plan not in ( ";
		
		$sql_str .= " select our.id from oa_unitprice_role as our,oa_foo_info as F1,oa_foo_info as F2,oa_foo_info as F3,oa_foo_info as F4 ";
		
		$sql_str .= " where our.school = F1.id and our.grade = F2.id and our.level = F3.id and our.course = F4.id and F4.group = 4 ) ";
		
		$sql_str .= " group by oa_course.std_id having sum(oa_course.hour+oa_course.ext_hour-oa_course.used_hour)>0 ";
		
		$ydd2 = $model->query($sql_str);
		
		$yx_lst = array_merge(array_column($yf_all,'std_id'),array_column($ydd1,'std_id'),array_column($ydd2,'std_id'));
		
		
		$w_st['st.std_id'] = array('in',$yx_lst); //有效学员std_id编号列表，有效学员包括在读学员和停课学员；
		
		
		$std_lst_str = implode("','", $yx_lst);		
		
		
		unset($sql_str);
		$sql_str = "select school,count(distinct std_id) as pnum from hw001.student where std_id in ('" . $std_lst_str . "')  group by school";
		$yxrscount = $model->query($sql_str);
		$this->yxrscount = json_encode($yxrscount); //各校区有效人数统计；  
				  
		$sql_str_rm = "select school,count(distinct std_id) as pnum from (";
		$sql_str_rm .= " select st.*,(cs.hour+cs.ext_hour-cs.used_hour) as remain_hour from hw001.student as st,hongwen_oa.oa_course as cs where st.std_id = cs.std_id ";
		$sql_str_rm .= " and st.std_id in ('" . $std_lst_str . "')";
		$sql_str_rm .= " and st.state=1 and cs.state in ('200','250') group by st.school,cs.std_id ";
		$sql_str_rm .= " ) as course where remain_hour<=10 group by school order by school,std_id";
		
		$yx_count = $model->query($sql_str_rm);
		
				
		$this->yxcount = json_encode($yx_count);	
		
		unset($sql_str_t);
		unset($yf_tk_lst);
		if($cycle == 1){
//			$w_tk_date= date('Y-m-d');
			$w_tk_date= $nowDate;
			$sql_str_t = "select std_id from hongwen_oa.oa_xg_statistic where ocur_date = '" . $w_tk_date . "' and type='停课' and std_id not in ( ";
			$sql_str_t .= "select std_id from hongwen_oa.oa_xg_statistic where ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($w_tk_date))) . "' and type='停课' group by std_id ) group by std_id ";
			$yf_tk_lst = $model->query($sql_str_t);
			
		}elseif($cycle == 2){
			$w_tk_date= date('Y-m-d');
			$sql_str_t = "select std_id from hongwen_oa.oa_xg_statistic where ocur_date = '" . $w_tk_date . "' and type='停课' and std_id not in ( ";
			$sql_str_t .= "select std_id from hongwen_oa.oa_xg_statistic where ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($week[0]))) . "' and type='停课' group by std_id ) group by std_id ";
			$yf_tk_lst = $model->query($sql_str_t);
		}elseif($cycle == 3){
			$w_tk_date= date('Y-m-d');
			$sql_str_t = "select std_id from hongwen_oa.oa_xg_statistic where ocur_date = '" . $w_tk_date . "' and type='停课' and std_id not in ( ";
			$sql_str_t .= "select std_id from hongwen_oa.oa_xg_statistic where ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($month[0]))) . "' and type='停课' group by std_id ) group by std_id ";
			$yf_tk_lst = $model->query($sql_str_t);
		}elseif($cycle == 4){
			$sql_str_t = "select std_id from hongwen_oa.oa_xg_statistic where ocur_date between '" . $beginDate . "' and '" . $endDate . "'  and type='停课' and std_id not in ( ";
			$sql_str_t .= "select std_id from hongwen_oa.oa_xg_statistic where ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($beginDate))) . "' and type='停课' group by std_id ) group by std_id ";
			$yf_tk_lst = $model->query($sql_str_t);
		}
		
		
//		$std_lst = array_diff($yx_lst, $yf_tk_lst); //在读学员
//		$std_lst = array_values($std_lst);

		//从所有有效学员中获取在读学员
		foreach($yx_lst as $k=>$v){
			if(array_search($v['std_id'], $yf_tk_lst) !== FALSE){
				unset($yx_lst[$k]);
			}
		}

		$std_lst = array_values($yx_lst);
		
		$zd_std_lst = implode("','", $std_lst);
		//半月未维护人数；
		unset($sql_str);
		
		$sql_str = " SELECT school as school,count(distinct std_id) as pnum from ( ";
		$sql_str .= " select st.std_id,st.school,wh.weihu_timee,st.state,datediff(DATE_FORMAT(now(), '%Y-%m-%d'),DATE_FORMAT(max(weihu_timee), '%Y-%m-%d')) dif_date FROM hongwen_oa.oa_weihu AS wh, hw001.student AS st";
		$sql_str .= " WHERE st.state = 1 AND wh.std_id = st.std_id and st.std_id in ('" . $zd_std_lst . "') ";
		$sql_str .= " group by st.school,st.std_id  order by weihu_timee DESC  ) as weihu ";
		$sql_str .= " where  (dif_date>=15 or dif_date is null or dif_date = '')  group by school ";		
		
		$hwh_count = $model->query($sql_str);
		
		$this->hwhcount = json_encode($hwh_count);
		$yf_std_lst = array_column($yf_tk_lst,'std_id');
		$tk_std_lst = implode("','", $yf_std_lst); //停课学员的学员编号；	
		//半月未激活人数；
		unset($sql_str);
		
		$sql_str = " SELECT school as school,count(distinct std_id) as pnum from ( ";
		$sql_str .= " select st.std_id,st.school,wh.weihu_timee,st.state,datediff(DATE_FORMAT(now(), '%Y-%m-%d'),DATE_FORMAT(max(weihu_timee), '%Y-%m-%d')) dif_date FROM hongwen_oa.oa_weihu AS wh, hw001.student AS st";
		$sql_str .= " WHERE st.state = 1 AND wh.std_id = st.std_id and st.std_id in ('" . $tk_std_lst . "') ";
		$sql_str .= " group by st.school,st.std_id  order by weihu_timee DESC  ) as weihu ";
		$sql_str .= " where  (dif_date>=15 or dif_date is null or dif_date = '')  group by school ";	
		
		$hjh_count = $model->query($sql_str);
		
		$this->hjhcount = json_encode($hjh_count);
		
		$grade_lst = C('SCHOOL_GRADE');
		$gradelst = array_column($grade_lst,'name','id');
		$this->gradelst = $gradelst;
		
		$this->display();
				
	}


	//集团各校区统计数据日、周、月
	public function jtxq_statistic_detail(){
		
		
		$this->statis_count = 10;
		$this->dk_count = 10;
		$this->dtk_tk_count = 10;
		$this->xf_count = 10;
		$this->tk_count = 10;
		$this->jk_count = 10;
		$this->tf_count = 10;
		$this->wh_count = 10;
		$this->yx_count = 10;

		$type = I('post.item');
		
		$school = I('post.school');
		
		//$school = M('foo_info');
		$xg = M('user');
		$stu = M('hw001.student',NULL);
		$consump = M('consumption');
		$course = M('course');
		$class = M('hw001.class',NULL);
		
		//校区账户进来, 首先获取所有学管师的名字；
		$wx['position_id'] = 18;
		$wx['is_del'] = 0;
		$wx['school'] = get_school_id($school);
		$xg_lst = $xg->where($wx)->getField('name',TRUE);
		
		if(count($xg_lst)){
			$xglst = implode("','",$xg_lst);
			$xg_w = " and st.xueguan in ('" . $xglst . "') ";	
		}
			   
		
		//获取统计的周期；
		$cycle = I('post.cycle');
		/*if(empty($cycle)){
			$cycle = 1;
		}
		//前端页面周期回显；
		$this->cycle= $cycle;
		$nowDate = date('Y-m-d');*/
		
		if(empty($cycle)){
			$cycle = 1;
			$nowDate = date('Y-m-d');
		}
		//前端页面周期回显；
		$this->cycle= $cycle;
		
		if($cycle == 1){
			if(I('get.statistic_day')){
				$nowDate = I('get.statistic_day');	
			}
				
		}else{
			$nowDate = date('Y-m-d');
		}
		
		
		if($cycle ==4){
			if(I('get.beginDate') && I('get.endDate') ){
				$beginDate = I('get.beginDate');
				$endDate = I('get.endDate');
			}
		}
		
		//获取统计周的时间段
		$week = get_date($nowDate,'w'); //本周
		//获取统计月的时间段
		$month = get_date($nowDate,'m'); //本月

		/*============================================================以下开始统计各种数据==================================================================*/
		$model = new \Think\Model();
		
		$pageNumber = I('get.pageNumber');
		$pageCount = I('get.pageCount');

		$pageNum = empty($pageNumber)?$this->pageNumber:$pageNumber;
		$pageCount = empty($pageCount)?$this->pageCount:$pageCount;
		
		if($type == 'ks'){
			if($cycle == 1){
				$w_ks = " and date_format(cl.timee, '%Y-%m-%d')='".$nowDate."' ";
			}else if($cycle == 2){
				$w_ks = " and date_format(cl.timee, '%Y-%m-%d')>='". date('Y-m-d',strtotime($week[0])) . "' ";
				$w_ks .= " and date_format(cl.timee, '%Y-%m-%d')<='".date('Y-m-d',strtotime($week[1])) . "' ";		
			}elseif($cycle == 3){
				$w_ks = " and date_format(cl.timee, '%Y-%m-%d')>='". date('Y-m-d',strtotime($month[0])) . "' ";
				$w_ks .= " and date_format(cl.timee, '%Y-%m-%d')<='".date('Y-m-d',strtotime($month[1])) . "' ";
			}elseif($cycle == 4){
				$w_ks = " and date_format(cl.timee, '%Y-%m-%d')>='". $beginDate . "' ";
				$w_ks .= " and date_format(cl.timee, '%Y-%m-%d')<='".$endDate . "' ";
			}
				
			//获取上课人的详情
			$sql_dt_str =  "select count(*) as count from hw001.class as cl, ";
			$sql_dt_str .=" (select st.school,st.std_id,st.name,st.grade,st.xueguan from hw001.student as st,hongwen_oa.oa_user as us where st.state=1 and st.xueguan = us.name and us.position_id=18 and st.school='" . $school . "' and us.is_del=0 ) as stus ";
			$sql_dt_str .=" where cl.std_id=stus.std_id " . $w_ks ;
			$ks_count = $model->query($sql_dt_str);
			
			unset($sql_dt_str);
			//获取上课人的详情
			$sql_dt_str =  "select stus.school,cl.id,stus.std_id,stus.name,stus.grade,cl.class,cl.time1,cl.time2,cl.timee,cl.teacher,cl.count,stus.xueguan from hw001.class as cl, ";
			$sql_dt_str .=" (select st.school,st.std_id,st.name,st.grade,st.xueguan from hw001.student as st,hongwen_oa.oa_user as us where st.state=1 and st.xueguan = us.name and us.position_id=18 and st.school='" . $school . "' and us.is_del=0 ) as stus ";
			$sql_dt_str .=" where cl.std_id=stus.std_id " . $w_ks . "  order by stus.school,stus.xueguan,stus.std_id,cl.count limit " . $pageNum . "," . $pageCount;
	
			$ks_data = $model->query($sql_dt_str);
			
			$maxCount = $ks_count?$ks_count[0]['count']:0;
			
			if(IS_AJAX && I('get.pageCount')){
				// 发送给页面的数据
				$this->ajaxReturn([

					'state'=>'ok',//查询结果
					'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
					'data'=>$ks_data
				  ]);
			}
			
		}		
		
		
		
		if($type == 'ks_rs'){
			if($cycle == 1){
				$w_ks = " and date_format(cl.timee, '%Y-%m-%d')='".$nowDate."' ";
			}else if($cycle == 2){
				$w_ks = " and date_format(cl.timee, '%Y-%m-%d')>='". date('Y-m-d',strtotime($week[0])) . "' ";
				$w_ks .= " and date_format(cl.timee, '%Y-%m-%d')<='".date('Y-m-d',strtotime($week[1])) . "' ";		
			}elseif($cycle == 3){
				$w_ks = " and date_format(cl.timee, '%Y-%m-%d')>='". date('Y-m-d',strtotime($month[0])) . "' ";
				$w_ks .= " and date_format(cl.timee, '%Y-%m-%d')<='".date('Y-m-d',strtotime($month[1])) . "' ";
			}elseif($cycle == 4){
				$w_ks = " and date_format(cl.timee, '%Y-%m-%d')>='". $beginDate . "' ";
				$w_ks .= " and date_format(cl.timee, '%Y-%m-%d')<='". $endDate . "' ";
			}
				
			//获取上课人的详情
			$sql_dt_str =  "select count(distinct cl.std_id) as count from hw001.class as cl, ";
			$sql_dt_str .=" (select st.school,st.std_id,st.name,st.grade,st.xueguan from hw001.student as st,hongwen_oa.oa_user as us where st.state=1 and st.xueguan = us.name and us.position_id=18 and st.school='" . $school . "' and us.is_del=0 ) as stus ";
			$sql_dt_str .=" where cl.std_id=stus.std_id " . $w_ks ;
			$ks_count = $model->query($sql_dt_str);
			
			unset($sql_dt_str);
			//获取上课人的详情
			$sql_dt_str =  "select stus.school,cl.id,stus.std_id,stus.name,stus.grade,cl.class,cl.time1,cl.time2,cl.timee,cl.teacher,cl.count,stus.xueguan from hw001.class as cl, ";
			$sql_dt_str .=" (select st.school,st.std_id,st.name,st.grade,st.xueguan from hw001.student as st,hongwen_oa.oa_user as us where st.state=1 and st.xueguan = us.name and us.position_id=18 and st.school='" . $school . "' and us.is_del=0 ) as stus ";
			$sql_dt_str .=" where cl.std_id=stus.std_id " . $w_ks . " group by cl.std_id order by stus.school,stus.xueguan,stus.std_id,cl.count limit " . $pageNum . "," . $pageCount;
	
			$ks_data = $model->query($sql_dt_str);
			
			$maxCount = $ks_count?$ks_count[0]['count']:0;
			
			if(IS_AJAX && I('get.pageCount')){
				// 发送给页面的数据
				$this->ajaxReturn([

					'state'=>'ok',//查询结果
					'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
					'data'=>$ks_data
				  ]);
			}
		}
		
		
		
		if($type == 'dk_ks'){
			if($cycle == 1){
//				$w_dk = " and DATE_FORMAT(dtk_time,'%Y-%m-%d')='" . date('Y-m-d'). "' ";
				$w_dk = " and DATE_FORMAT(dtk_time,'%Y-%m-%d')='" . $nowDate . "' ";
			}else if($cycle == 2){
				$w_dk = " and DATE_FORMAT(dtk_time,'%Y-%m-%d') >='" . date('Y-m-d',strtotime($week[0])) . "' ";
				$w_dk .= " and DATE_FORMAT(dtk_time,'%Y-%m-%d') <='" . date('Y-m-d',strtotime($week[1])) . "' ";
						
			}elseif($cycle == 3){
				$w_dk = " and DATE_FORMAT(dtk_time,'%Y-%m-%d') >='" . date('Y-m-d',strtotime($month[0])) . "' ";
				$w_dk .= " and DATE_FORMAT(dtk_time,'%Y-%m-%d') <='" . date('Y-m-d',strtotime($month[1])) . "' ";
			}elseif($cycle == 4){
				$w_dk = " and DATE_FORMAT(dtk_time,'%Y-%m-%d') >='" . $beginDate . "' ";
				$w_dk .= " and DATE_FORMAT(dtk_time,'%Y-%m-%d') <='" . $endDate . "' ";
			}
			
			
			//删课时统计
			$sql_str_t = " select * from ( ";
			$sql_str_t .= " select st.school,st.std_id,st.name,st.grade,odr.class,odr.time1,odr.time2,odr.timee,odr.teacher,odr.count,st.xueguan,odr.cid,odr.dtk_time,odr.dtk_type,odr.reason from oa_dt_record as odr,hw001.student as st where st.school='" . $school . "' and odr.std_id = st.std_id and dtk_type like '%删除%' " . $xg_w . $w_dk;
			$sql_str_t .=  " group by st.school,odr.cid having count(*)>=2 order by st.school,odr.timee DESC,dtk_time DESC,count"; //一门课程多次删除，记为一次；
			$sql_str_t .= " ) as del1 ";
			
			$sql_str_t .=  " union all ";
			
			$sql_str_t .= " select * from ( ";
			
			$sql_str_t .= " select st.school,st.std_id,st.name,st.grade,odr.class,odr.time1,odr.time2,odr.timee,odr.teacher,odr.count,st.xueguan,odr.cid,odr.dtk_time,odr.dtk_type,odr.reason from oa_dt_record as odr,hw001.student as st where st.school='" . $school . "' and odr.std_id = st.std_id and dtk_type like '%删除%' " . $xg_w . $w_dk;
			$sql_str_t .=  " group by st.school,odr.cid having count(*)<2 order by st.school,odr.timee DESC,dtk_time DESC,count"; 
			$sql_str_t .= " ) as del2 ";
			
			
			//删课详情数量
			$sql_str_r = "select count(*) as count from (";
			$sql_str_r .=  $sql_str_t;
			$sql_str_r .= " ) as school_del_detail  order by school,cid,timee DESC,dtk_time DESC,count ";
			$dk_count = $model->query($sql_str_r); //各校区删课详情数据列表
			
			$maxCount = $dk_count?$dk_count[0]['count']:0;
			
			//删课详情
			$sql_str_r = "select * from (";
			$sql_str_r .=  $sql_str_t;
			$sql_str_r .= " ) as school_del_detail  order by school,cid,timee DESC,dtk_time DESC,count limit " . $pageNum . "," . $pageCount;
			
			
			$dk_lst = $model->query($sql_str_r); //各校区删课详情数据列表
			
			if(IS_AJAX && I('get.pageCount')){
				// 发送给页面的数据
				$this->ajaxReturn([
					'state'=>'ok',//查询结果
					'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
					'data'=>$dk_lst
				  ]);
			}
		}
		
		//调课统计
		unset($sql_str);
		unset($sql_str_t);
		unset($sql_str_r);
		
		
		if($type == 'tk_rs'){
			
			if($cycle == 1){
//				$w_dk = " and DATE_FORMAT(dtk_time,'%Y-%m-%d')='" . date('Y-m-d'). "' ";
			$w_dk = " and DATE_FORMAT(dtk_time,'%Y-%m-%d')='" . $nowDate . "' ";
			}else if($cycle == 2){
				$w_dk = " and DATE_FORMAT(dtk_time,'%Y-%m-%d') >='" . date('Y-m-d',strtotime($week[0])) . "' ";
				$w_dk .= " and DATE_FORMAT(dtk_time,'%Y-%m-%d') <='" . date('Y-m-d',strtotime($week[1])) . "' ";
						
			}elseif($cycle == 3){
				$w_dk = " and DATE_FORMAT(dtk_time,'%Y-%m-%d') >='" . date('Y-m-d',strtotime($month[0])) . "' ";
				$w_dk .= " and DATE_FORMAT(dtk_time,'%Y-%m-%d') <='" . date('Y-m-d',strtotime($month[1])) . "' ";
			}elseif($cycle == 4){
				$w_dk = " and DATE_FORMAT(dtk_time,'%Y-%m-%d') >='" . $beginDate . "' ";
				$w_dk .= " and DATE_FORMAT(dtk_time,'%Y-%m-%d') <='" . $endDate . "' ";
			}
			
			//调课时详情
			$sql_str_t = " select * from ( ";
			$sql_str_t .= " select st.school,st.std_id,st.name,st.grade,odr.class,odr.time1,odr.time2,odr.timee,odr.teacher,odr.count,st.xueguan,odr.cid,odr.dtk_time,odr.dtk_type,odr.reason from oa_dt_record as odr,hw001.student as st where st.school='" . $school . "' and  odr.std_id = st.std_id and dtk_type like '%调课%' " . $xg_w . $w_dk;
			$sql_str_t .=  " group by st.school,odr.cid having count(*)>=2 order by st.school,odr.timee DESC,dtk_time DESC,count"; //一门课程多次调课，记为一次；
			$sql_str_t .= " ) as del1 ";
			
			$sql_str_t .=  " union all ";
			
			$sql_str_t .= " select * from ( ";
			
			$sql_str_t .= " select st.school,st.std_id,st.name,st.grade,odr.class,odr.time1,odr.time2,odr.timee,odr.teacher,odr.count,st.xueguan,odr.cid,odr.dtk_time,odr.dtk_type,odr.reason from oa_dt_record as odr,hw001.student as st where st.school='" . $school . "' and odr.std_id = st.std_id and dtk_type like '%调课%' " . $xg_w . $w_dk;
			$sql_str_t .=  " group by st.school,odr.cid having count(*)<2 order by st.school,odr.timee DESC,dtk_time DESC,count"; 
			$sql_str_t .= " ) as del2 ";
			
			//调课详情总数
			$sql_str_r = "select count(*) as count from (";
			$sql_str_r .=  $sql_str_t;
			$sql_str_r .= " ) as school_del_detail  order by school,cid,timee DESC,dtk_time DESC,count ";
			
			$tk_count = $model->query($sql_str_r); //各校区调课详情数量
			
			$maxCount = $tk_count?$tk_count[0]['count']:0;
			
			//调课详情
			$sql_str_r = "select * from (";
			$sql_str_r .=  $sql_str_t;
			$sql_str_r .= " ) as school_del_detail  order by school,cid,timee DESC,dtk_time DESC,count limit " . $pageNum . "," . $pageCount;
			
			$tk_lst = $model->query($sql_str_r); //各校区调课详情数据列表
			
			if(IS_AJAX && I('get.pageCount')){
				// 发送给页面的数据
				$this->ajaxReturn([
					'state'=>'ok',//查询结果
					'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
					'data'=>$tk_lst
				  ]);
			}
		}
		
		if($type == 'xf_rs'){
			if($cycle == 1){
//				$w_xf = "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') = date_format(NOW(), '%Y-%m-%d') ";
				$w_xf = "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') = '" . $nowDate . "' ";
			}else if($cycle == 2){
				$w_xf = "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($week[0])) . "' ";
				$w_xf .= "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($week[1])) . "' ";
						
			}elseif($cycle == 3){
				$w_xf = "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($month[0])) . "' ";
				$w_xf .= "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($month[1])) . "' ";
			}elseif($cycle == 4){
				$w_xf = "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') >='" . $beginDate . "' ";
				$w_xf .= "  and FROM_UNIXTIME(cs.create_time, '%Y-%m-%d') <='" . $endDate . "' ";
			}
			
			//今日续费人数
			unset($sql_str);
			$sql_str = "SELECT count(*) as count FROM  hongwen_oa.oa_consumption as cs,hw001.student as st ";
			$sql_str .= " where st.school='" . $school . "' and cs.std_id = st.std_id " . $w_xf . " and st.state=1 AND cs.belong_type = 2  order by st.school,st.xueguan, cs.std_id ";
			
			$detail_xf_count = $model->query($sql_str); //续费详情
			$maxCount = $detail_xf_count?$detail_xf_count[0]['count']:0;
			
			unset($sql_str);
			$sql_str = "SELECT st.school,st.name,st.grade,st.xueguan,cs.std_id,cs.value as money FROM  hongwen_oa.oa_consumption as cs,hw001.student as st ";
			$sql_str .= " where st.school='" . $school . "' and cs.std_id = st.std_id " . $w_xf . " and st.state=1 AND cs.belong_type = 2  order by st.school,st.xueguan, cs.std_id limit " . $pageNum . "," . $pageCount;
			
			$detail_xf_lst = $model->query($sql_str); //续费详情
			
			if(IS_AJAX && I('get.pageCount')){
				// 发送给页面的数据
				$this->ajaxReturn([
					'state'=>'ok',//查询结果
					'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
					'data'=>$detail_xf_lst
				  ]);
			}
		}
		
		
	
		
		//今日停课人数
		unset($sql_str);
		unset($sql_str_yf);
		unset($sql_str_tk);
		
		if($type == 'tks_rs'){
			if($cycle == 1){
//				$w_tk_date= date('Y-m-d');
				$w_tk_date= $nowDate;
				$w_tk_type = '停课';
				$sql_str = "select count(distinct std_id) as count from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date = '" . $w_tk_date . "' and type='" . $w_tk_type . "' and std_id not in ( ";
				$sql_str .= "select distinct std_id from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($w_tk_date))) . "' and type='" . $w_tk_type . "' ) ";
				$yf_tk_count = $model->query($sql_str);
				
				$sql_str_t = "select std_id,name,grade,xueguan,tel2,school from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date = '" . $w_tk_date . "' and type='" . $w_tk_type . "' and std_id not in ( ";
				$sql_str_t .= " select distinct std_id from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($w_tk_date))) . "' and type='" . $w_tk_type . "' ) group by school,std_id order by school,std_id limit " . $pageNum . "," . $pageCount;
				$yf_tk_data = $model->query($sql_str_t);
				
			}elseif($cycle == 2){
				$w_tk_date= date('Y-m-d');
				$w_tk_type = '停课';
				$sql_str = "select count(distinct std_id) as count from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date = '" . $w_tk_date . "' and type='" . $w_tk_type . "' and std_id not in ( ";
				$sql_str .= "select distinct std_id from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($week[0]))) . "' and type='" . $w_tk_type . "' ) ";
				$yf_tk_count = $model->query($sql_str);
				
				$sql_str_t = "select std_id,name,grade,xueguan,tel2,school from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date = '" . $w_tk_date . "' and type='" . $w_tk_type . "' and std_id not in ( ";
				$sql_str_t .= " select distinct std_id from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($week[0]))) . "' and type='" . $w_tk_type . "' ) group by school,std_id order by school,std_id limit " . $pageNum . "," . $pageCount;
				$yf_tk_data = $model->query($sql_str_t);
			}elseif($cycle == 3){
				$w_tk_date= date('Y-m-d');
				$w_tk_type = '停课';
				$sql_str = "select count(distinct std_id) as count from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date = '" . $w_tk_date . "' and type='" . $w_tk_type . "' and std_id not in ( ";
				$sql_str .= "select distinct std_id from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($month[0]))) . "' and type='" . $w_tk_type . "' ) ";
				$yf_tk_count = $model->query($sql_str);
				
				$sql_str_t = "select std_id,name,grade,xueguan,tel2,school from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date = '" . $w_tk_date . "' and type='" . $w_tk_type . "' and std_id not in ( ";
				$sql_str_t .= " select distinct std_id from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($month[0]))) . "' and type='" . $w_tk_type . "' ) group by school,std_id order by school,std_id limit " . $pageNum . "," . $pageCount;
				$yf_tk_data = $model->query($sql_str_t);
			}elseif($cycle == 4){
				$w_tk_type = '停课';
				$sql_str = "select count(distinct std_id) as count from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date between '" . $beginDate . "' and '" . $endDate . "' and type='" . $w_tk_type . "' and std_id not in ( ";
				$sql_str .= "select distinct std_id from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($beginDate))) . "' and type='" . $w_tk_type . "' ) ";
				$yf_tk_count = $model->query($sql_str);
				
				$sql_str_t = "select std_id,name,grade,xueguan,tel2,school from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date between '" . $beginDate . "' and '" . $endDate . "' and type='" . $w_tk_type . "' and std_id not in ( ";
				$sql_str_t .= " select distinct std_id from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($beginDate))) . "' and type='" . $w_tk_type . "' ) group by school,std_id order by school,std_id limit " . $pageNum . "," . $pageCount;
				$yf_tk_data = $model->query($sql_str_t);
			}
			
			$maxCount = $yf_tk_count?$yf_tk_count[0]['count']:0;
			
			if(IS_AJAX && I('get.pageCount')){
				// 发送给页面的数据
				$this->ajaxReturn([
					'state'=>'ok',//查询结果
					'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
					'data'=>$yf_tk_data
				  ]);
			}
		}	
		
				      	
		//今日结课人数
		unset($sql_str);
		unset($sql_str_wf);
		unset($sql_str_tk);
		
		
		if($type == 'jks_rs'){
			if($cycle == 1){
//				$w_jk_date= date('Y-m-d');
				$w_jk_date= $nowDate;
				$w_jk_type = '结课';
				$sql_str = "select  count(distinct std_id) as count from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date = '" . $w_jk_date . "' and type='" . $w_jk_type . "' and std_id not in ( ";
				$sql_str .= "select  distinct std_id from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($w_jk_date))) . "' and type='" . $w_jk_type . "' ) ";
				$wf_jk_count = $model->query($sql_str);
				
				$sql_str_j = "select std_id,name,grade,xueguan,tel2,school from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date = '" . $w_jk_date . "' and type='" . $w_jk_type . "' and std_id not in ( " ;
				$sql_str_j = " select distinct std_id from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($w_jk_date))) . "' and type='" . $w_jk_type . "' ) group by school,std_id order by school,std_id limit " . $pageNum . "," . $pageCount;
				$wf_jk_data = $model->query($sql_str_j);
				
			}elseif($cycle == 2){
				$w_jk_date= date('Y-m-d');
				$w_jk_type = '结课';
				$sql_str = "select  count(distinct std_id) as count from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date = '" . $w_jk_date . "' and type='" . $w_jk_type . "' and std_id not in ( ";
				$sql_str .= "select  distinct std_id from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($week[0]))) . "' and type='" . $w_jk_type . "' ) ";
				$wf_jk_count = $model->query($sql_str);
				
				$sql_str_j = "select std_id,name,grade,xueguan,tel2,school from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date = '" . $w_jk_date . "' and type='" . $w_jk_type . "' and std_id not in ( " ;
				$sql_str_j = " select distinct std_id from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($week[0]))) . "' and type='" . $w_jk_type . "' ) group by school,std_id order by school,std_id limit " . $pageNum . "," . $pageCount;
				$wf_jk_data = $model->query($sql_str_j);
			}elseif($cycle == 3){
				$w_jk_date= date('Y-m-d');
				$w_jk_type = '结课';
				$sql_str = "select  count(distinct std_id) as count from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date = '" . $w_jk_date . "' and type='" . $w_jk_type . "' and std_id not in ( ";
				$sql_str .= "select  distinct std_id from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($month[0]))) . "' and type='" . $w_jk_type . "' ) ";
				$wf_jk_count = $model->query($sql_str);
				
				$sql_str_j = "select std_id,name,grade,xueguan,tel2,school from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date = '" . $w_jk_date . "' and type='" . $w_jk_type . "' and std_id not in ( " ;
				$sql_str_j = " select distinct std_id from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($month[0]))) . "' and type='" . $w_jk_type . "' ) group by school,std_id order by school,std_id limit " . $pageNum . "," . $pageCount;
				$wf_jk_data = $model->query($sql_str_j);
			}elseif($cycle == 4){
				$w_jk_type = '结课';
				$sql_str = "select  count(distinct std_id) as count from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date between '" . $beginDate . "' and '" . $endDate . "' and type='" . $w_jk_type . "' and std_id not in ( ";
				$sql_str .= "select  distinct std_id from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($beginDate))) . "' and type='" . $w_jk_type . "' ) ";
				$wf_jk_count = $model->query($sql_str);
				
				$sql_str_j = "select std_id,name,grade,xueguan,tel2,school from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date between '" . $beginDate . "' and '" . $endDate . "' and type='" . $w_jk_type . "' and std_id not in ( " ;
				$sql_str_j = " select distinct std_id from hongwen_oa.oa_xg_statistic where school='" . $school . "' and ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($beginDate))) . "' and type='" . $w_jk_type . "' ) group by school,std_id order by school,std_id limit " . $pageNum . "," . $pageCount;
				$wf_jk_data = $model->query($sql_str_j);
			}
			
			$maxCount = $wf_jk_count?$wf_jk_count[0]['count']:0;
			
			if(IS_AJAX && I('get.pageCount')){
				// 发送给页面的数据
				$this->ajaxReturn([
					'state'=>'ok',//查询结果
					'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
					'data'=>$wf_jk_data
				  ]);
			}
		}
		
		
		if($type == 'tf_rs'){
			if($cycle == 1){
//				$w_tf = "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') = DATE_FORMAT(now(), '%Y-%m-%d')) ";
				$w_tf = "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') = '" . $nowDate . "') ";
			}else if($cycle == 2){
				$w_tf = "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($week[0])) . "' ";
				$w_tf .= "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($week[1])) . "' ) ";
						
			}elseif($cycle == 3){
				$w_tf = "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($month[0])) . "' ";
				$w_tf .= "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($month[1])) . "' ) ";
			}elseif($cycle == 4){
				$w_tf = "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') >='" . $beginDate . "' ";
				$w_tf .= "  and FROM_UNIXTIME(create_time, '%Y-%m-%d') <='" . $endDate . "' ) ";
			}
			
			//今日退费学员
			unset($sql_str);
			
			$sql_str = "select count(*) as count from hw001.student as st";
			$sql_str .= " where std_id in (select distinct std_id from hongwen_oa.oa_consumption where school='" . $school . "' and type = 200 " . $w_tf . $xg_w;
			$tf_count = $model->query($sql_str);
			
			unset($sql_str);
			
			$sql_str = "select std_id,name,grade,school,xueguan,DATE_FORMAT(now(), '%Y-%m-%d') from hw001.student as st";
			$sql_str .= " where std_id in (select distinct std_id from hongwen_oa.oa_consumption where school='" . $school . "' and type = 200 " . $w_tf . $xg_w;
	
			$sql_str .= " order by school,xueguan,std_id  limit " . $pageNum . "," . $pageCount;
			
			$tf_data = $model->query($sql_str);
			
			$maxCount = $tf_count?$tf_count[0]['count']:0;
			
			if(IS_AJAX && I('get.pageCount')){
				// 发送给页面的数据
				$this->ajaxReturn([
					'state'=>'ok',//查询结果
					'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
					'data'=>$tf_data
				  ]);
			}
		}
			
		
		
				
		if($type == 'wh_rs'){
			if($cycle == 1){
//				$w_wh = "   and DATE_FORMAT(weihu_timee, '%Y-%m-%d') =  DATE_FORMAT(NOW(), '%Y-%m-%d') ";
				$w_wh = "   and DATE_FORMAT(weihu_timee, '%Y-%m-%d') =  '" . $nowDate . "' ";
			}else if($cycle == 2){
				$w_wh = "  and DATE_FORMAT(weihu_timee, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($week[0])) . "' ";
				$w_wh .= "  and DATE_FORMAT(weihu_timee, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($week[1])) . "' ";
						
			}elseif($cycle == 3){
				$w_wh = "  and DATE_FORMAT(weihu_timee, '%Y-%m-%d') >='" . date('Y-m-d',strtotime($month[0])) . "' ";
				$w_wh .= "  and DATE_FORMAT(weihu_timee, '%Y-%m-%d') <='" . date('Y-m-d',strtotime($month[1])) . "' ";
			}elseif($cycle == 4){
				$w_wh = "  and DATE_FORMAT(weihu_timee, '%Y-%m-%d') >='" . $beginDate . "' ";
				$w_wh .= "  and DATE_FORMAT(weihu_timee, '%Y-%m-%d') <='" . $endDate . "' ";
			}
			
			if(isset($_POST['xueguan'])){
				$w_wh .= " and st.xueguan='" . $_POST['xueguan'] . "' ";
			}
			
			$w2['school']= get_school_id(I('post.school'));
			$w2['position_id']=array('in','12,13,18');
			$w2['is_del'] = 0;
			$xg_front_lst = M('user')->where($w2)->getField('name',true);
			
			//今日维护人数
			unset($sql_str);
			$sql_str = "select count(*) as count from hongwen_oa.oa_weihu as wh,hw001.student as st where st.school='" . $school . "' and wh.std_id = st.std_id " . $w_wh  . " order by st.school, wh.std_id";
			$wh_count = $model->query($sql_str);
			
			unset($sql_str);
			$sql_str = "select st.school,st.name,st.grade,st.xueguan,DATE_FORMAT(wh.weihu_timee,'%Y-%m-%d') as weihu_timee,wh.weihu_content,wh.std_id from hongwen_oa.oa_weihu as wh,hw001.student as st where st.school='" . $school . "' and wh.std_id = st.std_id " . $w_wh  . " order by st.school, wh.std_id limit " . $pageNum . "," . $pageCount;
			
			$wh_lst = $model->query($sql_str);
			
			$maxCount = $wh_count?$wh_count[0]['count']:0;
			
			if(IS_AJAX && I('get.pageCount')){
				// 发送给页面的数据
				$this->ajaxReturn([
					'state'=>'ok',//查询结果
					'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
					'data'=>$wh_lst,
					'xueguan'=>$xg_front_lst
				  ]);
			}
			
		}
		
		
		if(($type == 'yx_rs') || ($type == 'hwh_rs') || ($type == 'hjh_rs') || ($type == 'yxrs_tj')){
			
			//截至本月有效学员数
			unset($sql_str);
			/*所有有费学员，但不包含只有特色课堂的学员*/
			$sql_str = "select std_id from hongwen_oa.oa_consumption where  state != 50 and is_del != 1 and std_id not in ( ";
			/*只有特色课堂的学员*/
			$sql_str .= " select std_id from oa_course where  oa_course.state in (200,250) and oa_course.std_id in ( ";
			
			$sql_str .= " select std_id from oa_course where oa_course.unit_plan in ( ";
			
			$sql_str .= " select our.id ";
			
			$sql_str .= " from oa_unitprice_role as our,oa_foo_info as F1,oa_foo_info as F2,oa_foo_info as F3,oa_foo_info as F4 ";
			
			$sql_str .= " where our.school = F1.id and our.grade = F2.id and our.level = F3.id and our.course = F4.id and F4.group = 4)) ";
			
			$sql_str .= " group by oa_course.std_id having sum(oa_course.hour+oa_course.ext_hour-oa_course.used_hour)>0 and count(oa_course.id)=1) ";
			
			$sql_str .= " group by std_id having sum(value)>0 ";
			
			$yf_all = $model->query($sql_str);
	
			unset($sql_str);
			/*特色课堂有订单学员*/
			$sql_str = "select oa_course.id,std_id,unit_plan from oa_course where  oa_course.state in (200,250) and oa_course.std_id in ( ";
			
			$sql_str .= " select std_id from oa_course where  oa_course.state in (200,250) and oa_course.unit_plan in ( ";
			
			$sql_str .= " select our.id from oa_unitprice_role as our,oa_foo_info as F1,oa_foo_info as F2,oa_foo_info as F3,oa_foo_info as F4 ";
			
			$sql_str .= " where our.school='" . get_school_id($school) . "' and our.school = F1.id and our.grade = F2.id and our.level = F3.id and our.course = F4.id and F4.group = 4 ))";
			
			$sql_str .= " group by oa_course.std_id having sum(oa_course.hour+oa_course.ext_hour-oa_course.used_hour)>0 and count(oa_course.id)>1 ";
			
			$ydd1 = $model->query($sql_str);
			
			unset($sql_str);
			
			/*有订单学员，但不包含有特色课堂的学员*/
			$sql_str = "select std_id from oa_course where  oa_course.state in (200,250) and oa_course.unit_plan not in ( ";
			
			$sql_str .= " select our.id from oa_unitprice_role as our,oa_foo_info as F1,oa_foo_info as F2,oa_foo_info as F3,oa_foo_info as F4 ";
			
			$sql_str .= " where our.school = F1.id and our.grade = F2.id and our.level = F3.id and our.course = F4.id and F4.group = 4 ) ";
			
			$sql_str .= " and oa_course.school=" . get_school_id($school) .  " group by oa_course.std_id having sum(oa_course.hour+oa_course.ext_hour-oa_course.used_hour)>0 ";
			
			$ydd2 = $model->query($sql_str);
			
			$yx_lst = array_merge(array_column($yf_all,'std_id'),array_column($ydd1,'std_id'),array_column($ydd2,'std_id'));
			
			
			$w_st['st.std_id'] = array('in',$yx_lst); //有效学员std_id编号列表，有效学员包括在读学员和停课学员；
			
			if($type == 'yxrs_tj'){
				unset($sql_str);
				$std_lst_str = implode("','", $yx_lst);	
				$sql_str = "select count(distinct std_id) as count from hw001.student where std_id in ('" . $std_lst_str . "') and school='" . $school . "' ";
				$yxrs_count = $model->query($sql_str);
				
				unset($sql_str);
				$sql_str = "select std_id,name,sex,grade,xueguan,school from hw001.student where std_id in ('" . $std_lst_str . "') and school='" . $school . "'  limit " . $pageNum . "," . $pageCount;
				$yxrs_data = $model->query($sql_str);
				
				$maxCount = $yxrs_count?$yxrs_count[0]['count']:0;
			
				if(IS_AJAX && I('get.pageCount')){
					// 发送给页面的数据
					$this->ajaxReturn([
						'state'=>'ok',//查询结果
						'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
						'data'=>$yxrs_data
					  ]);
				}
			}
				
			if($type == 'yx_rs'){
				$std_lst_str = implode("','", $yx_lst);				  
					
				$sql_str_rm = "select count(*) as count from (";
				$sql_str_rm .= " select st.std_id,st.name,st.grade,st.xueguan,cs.id as courseid,st.school,(cs.hour+cs.ext_hour-cs.used_hour) as remain_hour from hw001.student as st,hongwen_oa.oa_course as cs where st.school='" . $school . "' and st.std_id = cs.std_id ";
				$sql_str_rm .= " and st.std_id in ('" . $std_lst_str . "')";
				$sql_str_rm .= " and st.state=1 and cs.state in ('200','250') group by st.school,cs.std_id ";
				$sql_str_rm .= " ) as course where remain_hour<=10  ";
				
				$yx_count = $model->query($sql_str_rm); 
				
				unset($sql_str_rm);
				$sql_str_rm = "select * from (";
				$sql_str_rm .= " select st.std_id,st.name,st.grade,st.xueguan,cs.id as courseid,st.school,(cs.hour+cs.ext_hour-cs.used_hour) as remain_hour from hw001.student as st,hongwen_oa.oa_course as cs where st.school='" . $school . "' and st.std_id = cs.std_id ";
				$sql_str_rm .= " and st.std_id in ('" . $std_lst_str . "')";
				$sql_str_rm .= " and st.state=1 and cs.state in ('200','250') group by st.school,cs.std_id ";
				$sql_str_rm .= " ) as course where remain_hour<=10  order by school,std_id limit " . $pageNum . "," . $pageCount;
				
				$yx_data = $model->query($sql_str_rm); 
				
				$maxCount = $yx_count?$yx_count[0]['count']:0;
			
				if(IS_AJAX && I('get.pageCount')){
					// 发送给页面的数据
					$this->ajaxReturn([
						'state'=>'ok',//查询结果
						'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
						'data'=>$yx_data
					  ]);
				}
			
			}
			
			if(($type == 'hwh_rs') || ($type == 'hjh_rs')){
				unset($sql_str_t);
				unset($yf_tk_lst);
				if($cycle == 1){
//					$w_tk_date= date('Y-m-d');
					$w_tk_date= $nowDate;
					$sql_str_t = "select std_id from hongwen_oa.oa_xg_statistic where ocur_date = '" . $w_tk_date . "' and type='停课' and std_id not in ( ";
					$sql_str_t .= "select std_id from hongwen_oa.oa_xg_statistic where ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($w_tk_date))) . "' and type='停课' group by std_id ) group by std_id ";
					$yf_tk_lst = $model->query($sql_str_t);
					
				}elseif($cycle == 2){
					$w_tk_date= date('Y-m-d');
					$sql_str_t = "select std_id from hongwen_oa.oa_xg_statistic where ocur_date = '" . $w_tk_date . "' and type='停课' and std_id not in ( ";
					$sql_str_t .= "select std_id from hongwen_oa.oa_xg_statistic where ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($week[0]))) . "' and type='停课' group by std_id ) group by std_id ";
					$yf_tk_lst = $model->query($sql_str_t);
				}elseif($cycle == 3){
					$w_tk_date= date('Y-m-d');
					$sql_str_t = "select std_id from hongwen_oa.oa_xg_statistic where ocur_date = '" . $w_tk_date . "' and type='停课' and std_id not in ( ";
					$sql_str_t .= "select std_id from hongwen_oa.oa_xg_statistic where ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($month[0]))) . "' and type='停课' group by std_id ) group by std_id ";
					$yf_tk_lst = $model->query($sql_str_t);
				}elseif($cycle == 4){
					$sql_str_t = "select std_id from hongwen_oa.oa_xg_statistic where ocur_date between '" . $beginDate . "' and '" . $endDate . "' and type='停课' and std_id not in ( ";
					$sql_str_t .= "select std_id from hongwen_oa.oa_xg_statistic where ocur_date = '" . date('Y-m-d',strtotime('-1 day',strtotime($beginDate))) . "' and type='停课' group by std_id ) group by std_id ";
					$yf_tk_lst = $model->query($sql_str_t);
				}
				
				if($type == 'hwh_rs'){
						
					//从所有有效学员中获取在读学员
					foreach($yx_lst as $k=>$v){
						if(array_search($v['std_id'], $yf_tk_lst) !== FALSE){
							unset($yx_lst[$k]);
						}
					}

					$std_lst = array_values($yx_lst);
					
//					$std_lst = array_diff($yx_lst, $yf_tk_lst); //在读学员
//					$std_lst = array_values($std_lst);
					
					$zd_std_lst = implode("','", $std_lst);
					//半月未维护人数；
					unset($sql_str);
					
					$sql_str = " SELECT count(*) as count from ( ";
					$sql_str .= " select st.std_id,st.xueguan,st.grade,st.name,st.school,wh.weihu_timee,st.state,datediff(DATE_FORMAT(now(), '%Y-%m-%d'),DATE_FORMAT(max(weihu_timee), '%Y-%m-%d')) dif_date FROM hongwen_oa.oa_weihu AS wh, hw001.student AS st";
					$sql_str .= " WHERE st.school='" . $school . "' and st.state = 1 AND wh.std_id = st.std_id and st.std_id in ('" . $zd_std_lst . "') ";
					$sql_str .= " group by st.school,st.std_id  order by weihu_timee DESC  ) as weihu ";
					$sql_str .= " where  (dif_date>=15 or dif_date is null or dif_date = '') ";	
					
					$hwh_count = $model->query($sql_str);
					unset($sql_str);
					
					$sql_str = " SELECT std_id,name,xueguan,grade,school,weihu_timee,state,dif_date from ( ";
					$sql_str .= " select st.std_id,st.xueguan,st.grade,st.name,st.school,wh.weihu_timee,st.state,datediff(DATE_FORMAT(now(), '%Y-%m-%d'),DATE_FORMAT(max(weihu_timee), '%Y-%m-%d')) dif_date FROM hongwen_oa.oa_weihu AS wh, hw001.student AS st";
					$sql_str .= " WHERE st.school='" . $school . "' and st.state = 1 AND wh.std_id = st.std_id and st.std_id in ('" . $zd_std_lst . "') ";
					$sql_str .= " group by st.school,st.std_id  order by weihu_timee DESC  ) as weihu ";
					$sql_str .= " where  (dif_date>=15 or dif_date is null or dif_date = '') order by school,std_id,weihu_timee DESC limit " . $pageNum . "," . $pageCount;	
					
					$hwh_lst = $model->query($sql_str);
					
					$maxCount = $hwh_count?$hwh_count[0]['count']:0;
			
					if(IS_AJAX && I('get.pageCount')){
						// 发送给页面的数据
						$this->ajaxReturn([
							'state'=>'ok',//查询结果
							'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
							'data'=>$hwh_lst
						  ]);
					}
									
				}
				
				if($type == 'hjh_rs'){
					$yf_std_lst = array_column($yf_tk_lst,'std_id');
					$tk_std_lst = implode("','", $yf_std_lst); //停课学员的学员编号；	
					//半月未激活人数；
					
					unset($sql_str);
					$sql_str = " SELECT count(*) as count from ( ";
					$sql_str .= " select st.std_id,st.school,wh.weihu_timee,st.state,datediff(DATE_FORMAT(now(), '%Y-%m-%d'),DATE_FORMAT(max(weihu_timee), '%Y-%m-%d')) dif_date FROM hongwen_oa.oa_weihu AS wh, hw001.student AS st";
					$sql_str .= " WHERE st.school='" . $school . "' and st.state = 1 AND wh.std_id = st.std_id and st.std_id in ('" . $tk_std_lst . "') ";
					$sql_str .= " group by st.school,st.std_id  order by weihu_timee DESC  ) as weihu ";
					$sql_str .= " where  (dif_date>=15 or dif_date is null or dif_date = '') ";	
					$hjh_count = $model->query($sql_str);
					
					unset($sql_str);
					$sql_str = " SELECT std_id,name,xueguan,grade,school,weihu_timee,state,dif_date from ( ";
					$sql_str .= " select st.std_id,st.name,st.xueguan,st.grade,st.school,wh.weihu_timee,st.state,datediff(DATE_FORMAT(now(), '%Y-%m-%d'),DATE_FORMAT(max(weihu_timee), '%Y-%m-%d')) dif_date FROM hongwen_oa.oa_weihu AS wh, hw001.student AS st";
					$sql_str .= " WHERE st.school='" . $school . "' and st.state = 1 AND wh.std_id = st.std_id and st.std_id in ('" . $tk_std_lst . "') ";
					$sql_str .= " group by st.school,st.std_id  order by weihu_timee DESC  ) as weihu ";
					$sql_str .= " where  (dif_date>=15 or dif_date is null or dif_date = '') order by school,std_id,weihu_timee DESC limit " . $pageNum . "," . $pageCount;	
					
					$hjh_lst = $model->query($sql_str);
					
					$maxCount = $hjh_count?$hjh_count[0]['count']:0;
			
					if(IS_AJAX && I('get.pageCount')){
						// 发送给页面的数据
						$this->ajaxReturn([
							'state'=>'ok',//查询结果
							'maxCount'=>$maxCount,//查询到数据库有多少条满足条件记录
							'data'=>$hjh_lst
						  ]);
					}
				}
			}
		}
	}

}
