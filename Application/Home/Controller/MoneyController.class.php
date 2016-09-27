<?php
namespace Home\Controller;
class MoneyController extends HomeController {
    //微信推送审核通知，1校区，2部门，3总裁,4张毅
    public function tongzhi($type,$dept_id=null){
        switch ($type) {
            case '1'://通知校长
                $name=M('user')->where(array('school'=>session('school_id'),'position_id'=>10))->getfield('name');
                if($name)$this->news(6,$name,'OA提醒','系统中有花销待审核。',wx_oauth(C('WWW').U('Public/log_wx?urll=money/check1')));
                break;
            // case '2'://通知部门
            //     $name=M('user')->where(array('school'=>0,'dept_id'=>$dept_id,'position_id'=>8))->getfield('name');
            //     if($name)$this->news(6,$name,'OA提醒','系统中有花销待审核。',wx_oauth(C('WWW').U('Public/log_wx?urll=money/check?cc=2')));
            //     break;
            // case '3'://通知总裁
            //     $name=M('user')->where(array('school'=>0,'dept_id'=>$dept_id,'position_id'=>7))->getfield('name');
            //     if($name)$this->news(6,$name,'OA提醒','系统中有花销待审核。',wx_oauth(C('WWW').U('Public/log_wx?urll=money/check?cc=3')));
            //     break;
            case '4'://通知财务
                $this->news(6,'张毅','OA提醒','系统中有花销待审核。',wx_oauth(C('WWW').U('Public/log_wx?urll=money/check2')));
                break;
            default:
                # code...
                break;
        }
    }

    //新增
    public function add(){
        if(IS_POST){
            $m=M('hw003.money_add',null);
            $m->kk=round($_POST['d'],2);
            $m->create();
            $m->r=session('user_name');
            $m->b=get_school_name();
            if($_POST['mm']=='')$m->mm=1;
            if(get_school_name()=='集团'){
                $m->state=2;
                // $this->tongzhi(4);
            }else{
                $this->tongzhi(1);
            }
            if($m->add()){
                $this->redirect('index');
            }else{
                $this->success('有错误,录入失败');
            }
        }
    }

    //修改
    public function change(){
        if($_POST['id']){
            $m=M('hw003.money_add',null);
            $m->kk=round($_POST['d'],2);
            $m->create();
            if($_POST['mm']=='')$m->mm=1;
            if($_POST['mm']=='' && get_school_name()=='集团')$m->mm=2;
            $m->r=session('user_name');
            if($_POST['x'])$m->state=1;//退回修改的处理
            if($_POST['x'] && get_school_name()=='集团')$m->state=2;//退回修改的处理
            $m->save();
            $this->record($v,'退回或修改');
        }
    }
    //删除
    public function delt(){
        if($_POST['id'])
            foreach ($_POST['id'] as  $v) {
                $r=M('hw003.money_add',null)->where(['id'=>$v])->setField('state',-2);
                $this->record($v,'删除');
            }
            if($r){
                $this->success('删除成功……');
            }else{
                $this->error('删除失败……');
            }
    }

    public function index(){
        // die;

        if($_POST['change'])$this->change();//修改按钮
        $w['b']=get_school_name();
        $w['date']=session('date');
        $w['state']=['neq',-2];
        if(get_school_name()=='集团')$w['r']=session('user_name');
        $mod=M('hw003.money_add',null);
        $count   = $mod->where($w)->count();// 查询满足要求的总记录数
        $Page    = new \Home\Hongwen\Page($count,25);//
        $show    = $Page->show();// 分页显示输出
        $list = $mod->where($w)->order('state asc,id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach ($list as $k=>$v) {
            if(($v['state']<=1)&&get_school_name()!='集团')$list[$k]['idd']=$list[$k]['id'];
            if(($v['state']<=2)&&get_school_name()=='集团')$list[$k]['idd']=$list[$k]['id'];
        }
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        //计算金额
        $con=$mod->where($w)->select();
        foreach ($con as $v) {
            if($v['state']==1||$v['state']==2){
                $cont['a']+=$v['kk']*$v['l'];
            }elseif($v['state']==3){
                $cont['b']+=$v['kk']*$v['l'];
            }
        }
        $this->cont=$cont;
        $this->display('index'); // 输出模板
    }

      //修改数据回调使用
    public function api_c($id){
      if(isset($_POST['id'])&&$_POST['id']!=''){
        $where['id']=$_POST['id'];
        $shuchu=M('hw003.money_add',null)->where($where)->find();
        print(json_encode($shuchu));//将信息发送给浏览器
      }
    }

    public function check1(){
            $w['date']=session('date');
            $w['b']=get_school_name();
            if(get_school_name()=='集团')$w['r']=session('user_name');
        if($_POST['change'])$this->change();//修改按钮

        if($_POST['aax']){
            foreach ($_POST['id'] as $key => $value) {
                $w['id']=$value;
                $d['state']=2;
                $rr=M('hw003.add','money_')->where($w)->save($d);
                $this->record($v,'审核');
            }
            if($rr){
                $this->success('审核完成！');
            }else{
                $this->success('选择要审核的条目！');
            }
        }elseif($_POST['bt']){
            foreach ($_POST['id'] as $key => $value) {
                $w['id']=$value;
                $d['state']=0;
                $rr=M('hw003.add','money_')->where($w)->save($d);
                $this->record($v,'退回');
            }
            if($rr){
                $this->success('数据已退回！');
            }else{
                $this->error('选择要退回的数据！');
            }
        }elseif ($_POST['dl']){
                $this->delt();
        }else{

            $w['state']=1;//
            $list=M('hw003.money_add',null)->where($w)->order('id desc')->select();
            $this->list1=$list;

            $w['state']=array('gt','1');//
            $list=M('hw003.money_add',null)->where($w)->order('id desc')->select();
            $this->list2=$list;

            $this -> display('check');

        }
    }

    //后期添加的一个审核环节，给丹丹
    public function checkdd(){
        $w['date']=session('date');
        if ($_POST['dl']) $this->delt();
        if($_POST['change'])$this->change();//修改按钮
        if($_POST['aax']){
            foreach ($_POST['id'] as $key => $value) {
                $w['id']=$value;
                $d['state']=2.5;
                $rr=M('hw003.add','money_')->where($w)->save($d);
                $this->record($v,'审核');
            }
            if($rr){
                $this->success('审核完成！');
            }else{
                $this->success('选择要审核的条目！');
            }
        }elseif($_POST['bt']){
            foreach ($_POST['id'] as $key => $value) {
                $w['id']=$value;
                $d['state']=0;
                $rr=M('hw003.add','money_')->where($w)->save($d);
                $this->record($v,'退回');
            }
            if($rr){
                $this->success('数据已退回！');
            }else{
                $this->error('选择要退回的数据！');
            }
        }else{
            $w['state']=2;//
            $list=M('hw003.money_add',null)->where($w)->order('id desc')->select();
            $this->list1=$list;

            $w['state']=array('gt',2);//
            $list=M('hw003.money_add',null)->where($w)->order('id desc')->select();
            $this->list2=$list;
            $this -> display('check');

        }

    }




    public function check2(){
            $w['date']=session('date');
        if($_POST['change'])$this->change();//修改按钮
        if($_POST['aax']){
            foreach ($_POST['id'] as $key => $value) {
                $w['id']=$value;
                $d['state']=3;
                $rr=M('hw003.add','money_')->where($w)->save($d);
                $this->record($v,'审核');
            }
            if($rr){
                $this->success('审核完成！');
            }else{
                $this->success('选择要审核的条目！');
            }
        }elseif($_POST['bt']){
            foreach ($_POST['id'] as $key => $value) {
                $w['id']=$value;
                $d['state']=0;
                $rr=M('hw003.add','money_')->where($w)->save($d);
                $this->record($v,'退回');
            }
            if($rr){
                $this->success('数据已退回！');
            }else{
                $this->error('选择要退回的数据！');
            }
        }elseif ($_POST['dl']) {
            $this->delt();
        }else{
            $w['state']=2.5;//
            $list=M('hw003.money_add',null)->where($w)->order('id desc')->select();
            $this->list1=$list;

            $w['state']=array('gt',2.5);//
            $list=M('hw003.money_add',null)->where($w)->order('id desc')->select();
            $this->list2=$list;

            $this -> display('check');

        }
    }

    public function all(){
        $mod=M('hw003.money_add',null);
        if($_POST['import'])$this->import();//数据导出
        if($_POST['change'])$this->change();
        if($_POST['delt'])$this->delt();//删除数据
        $w['date']=session('date');
        $w['state']=['neq',-2];
        if(I('param.state'))$w['state']=I('param.state');//状态
        if(I('param.state')=='全部')$w['state']=['neq',-2];//
        if(I('param.date'))$w['date']=I('param.date');//花销
        if(I('param.b'))$w['b']=I('param.b');//花销
        if(I('param.g'))$w['g']=I('param.g');//成本类型
        if(I('param.r'))$w['r']=I('param.r');//校区财务
        if(I('param.id'))$w['id']=I('param.id');//校区财务
        $count   = $mod->where($w)->count();// 查询满足要求的总记录数
        $Page    = new \Home\Hongwen\Page($count,25);//
        $show    = $Page->show();// 分页显示输出
        $list=$mod->where($w)->order('state asc,id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('page',$show);// 赋值分页输出
        $this->list=$list;
        //统计金额
        $con=$mod->where($w)->select();
        foreach ($con as $v) {
            $cont+=$v['kk']*$v['l'];
        }
        $this->cont=$cont;
        $this->display();
    }

    public function import(){
        $w['state']=['neq',-2];
        if($_POST['date']!='')$w['date']=$_POST['date'];
        if($_POST['b']!='')$w['b']=$_POST['b'];
        if($_POST['gs']!='')$w['gs']=$_POST['gs'];
        if($_POST['g'])$w['g']=$_POST['g'];
        if($_POST['o']!='')$w['o']=$_POST['o'];
        if($_POST['state']!='全部')$w['state']=(int)$_POST['state'];
        if(I('param.r'))$w['r']=I('param.r');//校区财务
// $w['date']=['between','2014-02,2015-11'];
        $mm=M('hw003.add','money_')->where($w)->order('id desc')->select();

        $output = "<HTML>";
        $output .= "<HEAD>";
        $output .= "<META http-equiv=Content-Type content=\"text/html; charset=utf-8\">";
        $output .= "</HEAD>";
        $output .= "<BODY>";
        $output .= "<TABLE BORDER=1>";
        $output .= "<tr><td>序号</td><td>状态</td><td>凭证号</td><td>期次</td><td>花销</td><td>归属</td><td>报销日期</td><td>发生日期</td><td>成本类型</td><td>二级科目</td><td>支出项目</td><td>明细</td><td>单价</td><td>数量</td><td>合计</td><td>经手人</td><td>报销人</td><td>审批人</td><td>所属部门</td><td>校区财务</td><td>发票数量</td><td>摊销起点</td><td>摊销期次</td><td>备注</td><td>数据创建时间</td></tr>";
            foreach ($mm as $m) {
                if($m['mm']>1){
                    $f=$m['f'];
                    $l=$m['mm'];
                }else{
                    $f='';
                    $l=1;
                }
                switch ($m['state']) {
                    case '-1':
                        $state='审核失败';
                        break;
                    case '0':
                        $state='退回修改';
                        break;
                    case '1':
                        $state='校区审核';
                        break;
                    case '2':
                        $state='部门审核';
                        break;
                    case '2.5':
                        $state='集团审核';
                        break;
                    case '3':
                        $state='审核通过';
                        break;
                    default:
                        # code...
                        break;
                }
                $output .= "<tr><td>".$m['id']."</td><td>".$state."</td><td>".$m['aa']."</td><td>".$m['date']."</td><td>".$m['b']."</td><td>".$m['gs']."</td><td>".$m['d']."</td><td>".$m['e']."</td><td>".$m['g']."</td><td>".$m['h']."</td><td>".$m['i']."</td><td>".$m['j']."</td><td>".$m['kk']."</td><td>".$m['l']."</td><td>".$m['kk']*$m['l']."</td><td>".$m['n']."</td><td>".$m['o']."</td><td>".$m['p']."</td><td>".$m['q']."</td><td>".$m['r']."</td><td>".$m['t']."</td><td>".$f."</td><td>".$l."</td><td>".$m['other']."</td><td>".$m['timestamp']."</td></tr>";
            }
        $output .= "</TABLE>";
        $output .= "</BODY>";
        $output .= "</HTML>";

        $filename='财务系统花销明细导出表'.date('Y-m-d');
        header("Content-type:application/msexcel");
        header("Content-disposition: attachment; filename=$filename.xls");
        header("Cache-control: private");
        header("Pragma: private");
        print($output);
    }


    //审核记录
    public function record($id,$info){
        $mod=M('hw003.money_add',null);
        $inf=$mod->find($id);
        $d['record']=$inf['record'].'<'.$info.date('Y-m-d H:i:s').session('user_name').'>';
        $mod->where(['id'=>$id])->save($d);
    }


    function cc(){

    }
}
?>
