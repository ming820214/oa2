<?php
namespace Home\Controller;

class CardController extends HomeController {

	private $pageNumber=0;
	private $pageCount=10;
	
	public function index(){
		
		$mod = M('vipcard');
		$condition['del'] = 0;
		
        $list=$mod->where($condition)->select();
		
        foreach ($list as &$v) {//跟踪人
            $v['creator']=M('user')->where(['id'=>$v['creator']])->getField('name');
        }
        $this->list=$list;
        $this->maxCount=$list?count($list):1;//显示全部数据
		
		$this->display();
	}
	
	
	//获取单条或多条信息
    public function pager($id=0){
            $mod=M('vipcard');
        if($id){
            $data[0]=$mod->find($id);//获取到数据
            $count=1;
        }
    
        if(IS_AJAX && I('get.pageCount')){
            $page=I('get.pageNumber');//请求第几条开始
            $page_count=I('get.pageCount');//一页多少条记录

            $condition=I('post.');//获取json查询条件转换成php数组
            $condition['card_school']=$condition['card_school']?:session('school_id');
            if($condition['keyword'])$condition['card_owner|owner_tel'] = array('like', "%" . $condition['keyword'] . "%");
			
			if($condition['card_no']){
				$condition['card_no'] = array('like','%'.$condition['card_no'].'%');
			}
			
			if($condition['card_owner']){
				$condition['card_owner'] = array('like','%'.$condition['card_owner'].'%');
			}
			
			if($condition['owner_tel']){
				$condition['owner_tel'] = array('like','%'.$condition['owner_tel'].'%');
			}
			
            array_empty_delt($condition);
    		$count=$mod->where($condition)->count();//满足条件的记录总数
    		$data=$mod->where($condition)->limit($page,$page_count)->order('id desc')->select();//获取到数据
        }

        foreach ($data as &$v) {//跟踪人
            $v['creator']=M('user')->where(['id'=>$v['creator']])->getField('name');
			$v['card_school'] = $v['card_school']?get_school_name($v['card_school']):'集团';
           
        }

		// 发送给页面的数据
		$this->ajaxReturn([

			'state'=>'ok',//查询结果
			'maxCount'=>$count,//查询到数据库有多少条满足条件记录
			'data'=>$data

		  ]);
    
    }

	public function addCard(){
		$mod=M('vipcard');
        $mod->create();
        if(I('post.id')){
        	$mod->updator=session('auth_id');
            if($mod->save()){
            	$this->ajaxReturn('ok');
			}
        }else{
            $mod->card_school=session('school_id');
            $mod->creator=session('auth_id');
	    	
            
            $condition['card_no']=I('post.card_no');
            $repeat=$mod->where($condition)->find();
            if($repeat){
            	switch($repeat['card_type']){
					case '01':$cardType = '黑卡';break;
					case '02':$cardType = '金卡';break;
					case '03':$cardType = '银卡';break;
            	}
            	$this->ajaxReturn('录入有重复……'. $cardType . ' 卡号：' . $repeat['card_no'].','.get_school_name($repeat['card_school']));
			}
	    	if($mod->add()) {
	    		$this->ajaxReturn('ok');
			}
        }
	}
	
	
	
/**
数据导入
*/
    public function import(){
        if(IS_POST){
            if($_FILES['file']['type']!='application/vnd.ms-excel'||!strstr($_FILES['file']['name'],'.csv')){
                $this->success('文件格式不正确，<br/>请使用提供的模版导入！'); die;
            }
            // var_dump($_FILES['file']);die;
            $file = fopen($_FILES['file']['tmp_name'], "r");
            while ($data = fgetcsv($file)) {
                $list[] = $data;
             }
            if($list[0][0]!='卡片编号')$zz=true;
            if(count($list[0])!=5)die('数据格式出错');
            fclose($file);
             foreach ($list as $k => $v) {
                if($k<1)continue;
                if($zz)
                foreach ($v as &$val) {
                    $val=$this->characett($val);
                }

				switch(trim($v[1])){
					case "黑卡": $v[1] = '01';break;
					case "金卡": $v[1] = '02';break;
					case "银卡": $v[1] = '03';break;
				}
				
                $dat[]=[
                    'card_no'=>$v[0],
                    'card_state'=>'02',
                    'card_type'=> $v[1],
                    'card_value'=> $v[2],
                    'card_owner'=> $v[3],
                    'owner_tel'=>$v[4],
                    'card_school' =>session('school_id'),
                    'del'=>0,
                    'creator'=>session('auth_id')
                   ];
             }
             // 数据去内部重复
             foreach ($dat as $k=>$v) {
                if($v['card_no'])
                if(in_array($v['card_no'],$tmp)){
                    unset($dat[$k]);
                }else{
                    $tmp[]=$v['card_no'];
                }
             }
            // 数据库里查找是否有重复
            $m=M('vipcard')->field('card_no')->distinct(true)->select();
            $m=array_column($m,'card_no');
           
            foreach ($dat as $k => $v) {
                if(in_array($v['card_no'],$m))unset($dat[$k]);
            }
            // var_dump($dat);die;
            M('vipcard')->addAll($dat);
            $this->success('导入成功了'.count($dat).'条记录');
        }
    }

    //输出导入使用的模版文件
    public function import_template(){

        $output='卡片编号,卡片类型,卡片价值,持卡人,持卡人电话';
        header("Content-type:application/vnd.ms-excel");
        header("Content-disposition: attachment;filename=VIP卡数据导入模版文件.csv");
        header("Cache-control: private");
        header("Pragma: private");
        print($output);

    }

    //自动识别文件编码并转换为utf-8,（有问题判断）
    function characett($data){
      if( !empty($data) ){    
        $fileType = mb_detect_encoding($data , ['GBK','UTF-8','ISO-8859-1','UTF-32']) ;   
        if( $fileType != 'UTF-8'){   
          $data = mb_convert_encoding($data ,'utf-8' , $fileType);   
        }
      } 
      return $data;    
    }
	
}