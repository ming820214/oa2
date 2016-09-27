<?php
namespace Home\Controller;

class BudgetController extends HomeController {

    //微信推送审核通知，1校区，2部门，3总裁,4财务
    public function tongzhi($type,$dept_id=null){
        switch ($type) {
            case '1'://通知校长
                $name=M('user')->where(array('school'=>session('school_id'),'position_id'=>C('CHECK_POSITION')['SCHOOL']))->getfield('name');
                if($name)$this->news(7,$name,'OA提醒','系统中有预算待审核。',wx_oauth(C('WWW').U('Public/log_wx?urll=budget/check?cc=1')));
                break;
            case '2'://通知部门
                $name=M('user')->where(array('school'=>0,'dept_id'=>$dept_id,'position_id'=>C('CHECK_POSITION')['DEPT']))->getfield('name');
                if($name)$this->news(7,$name,'OA提醒','系统中有预算待审核。',wx_oauth(C('WWW').U('Public/log_wx?urll=budget/check?cc=2')));
                break;
            case '3'://通知总裁
                $name=M('user')->where(array('school'=>0,'dept_id'=>$dept_id,'position_id'=>C('CHECK_POSITION')['DEPT2']))->getfield('name');
                if($name)$this->news(7,$name,'OA提醒','系统中有预算待审核。',wx_oauth(C('WWW').U('Public/log_wx?urll=budget/check?cc=3')));
                break;
            case '5'://通知大总裁
                $this->news(7,'李文龙','OA提醒','系统中有预算待审核。',wx_oauth(C('WWW').U('Public/log_wx?urll=budget/check?cc=3')));
                break;
            case '4'://通知财务
                $this->news(7,'张毅','OA提醒','系统中有预算待审核。',wx_oauth(C('WWW').U('Public/log_wx?urll=budget/check?cc=4')));
                break;
            default:
                # code...
                break;
        }
    }

    //预算申请
    public function index(){
        die;
        if($_POST['add'])$this->add();
        if($_POST['change'])$this->change();
        if($_POST['delt'])$this->delt();
        $w['date']=session('date');

        if(get_school_name()=='集团'){
            $w['state']=array('egt',0);
            $w['name']=session('user_name');
        }else{
            $w['state']=array('egt',0);
            $w['school']=get_school_name();
        }
        $mod=D('budget');
        $count   = $mod->where($w)->count();// 查询满足要求的总记录数
        $Page    = new \Home\Hongwen\Page($count,25);//
        $show    = $Page->show();// 分页显示输出
        $this->page=$show;// 赋值分页输出
        $list = $mod->where($w)->order('state asc ,id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach ($list as $k=>$v) {
            if($v['state']<=1&&get_school_name()!='集团')$list[$k]['idd']=$list[$k]['id'];
            if($v['state']<=2&&get_school_name()=='集团')$list[$k]['idd']=$list[$k]['id'];
        }
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        //计算金额
        $con=$mod->where($w)->select();
        foreach ($con as $v) {
            if($v['state']<=1&&get_school_name()!='集团'){
                $cont['a']+=$v['d']*$v['e'];
            }elseif($v['state']<=2&&get_school_name()=='集团'){
                $cont['a']+=$v['d']*$v['e'];
            }else{
                $cont['b']+=$v['d']*$v['e'];
            }
        }
        $this->cont=$cont;
        $this->display('index'); // 输出模板
    }

    //添加
    private function add(){
            $m=D('budget');
            $m->create();
            $h=$_POST['d'];
            $m->d=round($h,2);
            $m->date=session('date');
            $m->time=date('y-m-d h:i:s');
            $m->school=get_school_name();
            $m->name=session('user_name');
            $pid=M('finance_type')->where(['name'=>$_POST['aa'],'pid'=>1])->getField('id',false);
            $dept=M('finance_type')->where(['pid'=>$pid,'name'=>$_POST['b']])->find();
            if(!$dept)die;
            $m->dept_id=$dept['dept_id'];
            $m->dept_id2=$dept['dept_id2'];
            if(round($h,2)*$_POST['e']>3000){
                $m->dept_id3=1;
                $m->bm=get_dept_name($dept['dept_id']).'+'.get_dept_name($dept['dept_id2']).'+总裁';
            }else{
                $m->bm=get_dept_name($dept['dept_id']).'+'.get_dept_name($dept['dept_id2']);
            }
            if(get_school_name()=='集团'){
                if($dept['dept_id']){
                    $m->state=2;//通知二级部门
                    $this->tongzhi(2,$dept['dept_id']);
                }elseif($dept['dept_id2']){
                    $m->state=3;//没有二级通知一级
                    $this->tongzhi(3,$dept['dept_id2']);
                }
            }else{
                $this->tongzhi(1);
            }
            if($m->add()){
                session('jsr',$_POST['jsr']);
                session('card',$_POST['card']);
                session('why',$_POST['why']);
                session('week',$_POST['week']);
                return true;
            }else{
                $this->success('有错误,录入失败');
            }
    }

    //删除,审核
    private function delt($s=-2){
        $state=$s;//用于保留原始审核
        if($_POST['id'])
            foreach ($_POST['id'] as $v) {
                $dept=D('budget')->find($v);
                if($state==2 && !$dept['dept_id'])$state=3;
                if($state==4 && session('user_name')!='李文龙' && $dept['dept_id3']==1)$state=3.5;
                if(session('user_name')=='李文龙')$state=4;
                $m=D('budget')->where(['id'=>$v])->setField('state',$state);
                if($state==-2){
                    R('Budget/record',array($v,'删除'));
                }elseif($state==0){
                    R('Budget/record',array($v,'退回'));
                }else{
                    R('Budget/record',array($v,'审核'));
                    if($state==2)$this->tongzhi(2,$dept['dept_id']);//通知部门
                    if($state==3)$this->tongzhi(3,$dept['dept_id2']);//通知总裁
                    if($state==3.5){
                        $this->tongzhi(5);//通知大总裁
                    }
                    if($state==4)$this->tongzhi(4);//通知财务
                }
                $state=$s;
            }
        if($m)return true;
    }

    //修改,type给毅哥做的修改状态不变
    private function change($type=0){
        if($_POST['id']){
            $m=D('budget');
            $m->create();
            $pid=M('finance_type')->where(['name'=>$_POST['aa'],'pid'=>1])->getField('id');
            $dept=M('finance_type')->where(['pid'=>$pid,'name'=>$_POST['b']])->find();
            if(!$dept)die($dept);
            $m->dept_id=$dept['dept_id'];
            $m->dept_id2=$dept['dept_id2'];
            if(round($_POST['d'],2)*$_POST['e']>3000){
                $m->dept_id3=1;
                $m->bm=get_dept_name($dept['dept_id']).'+'.get_dept_name($dept['dept_id2']).'+总裁';
            }else{
                $m->bm=get_dept_name($dept['dept_id']).'+'.get_dept_name($dept['dept_id2']);
                $m->dept_id3=0;
            }
            $x=$m->where(['id'=>$_POST['id']])->getField('state');
            if($x==0 && !$type)$m->state=1;
            if($x==0 && get_school_name()=='集团' && !$type)$m->state=2;
            if($m->save()){
                //记录
                R('Budget/record',array($_POST['id'],'修改'));
                session('jsr',$_POST['jsr']);
                session('card',$_POST['card']);
                session('why',$_POST['why']);
                session('week',$_POST['week']);
                return true;
            }
        }
    }

    //修改数据回调使用
    public function api_c(){
      if(I('get.id')){
        $where['id']=I('get.id');
        $shuchu=D('budget')->where($where)->find();
        print(json_encode($shuchu));//将信息发送给浏览器
      }
    }

    //1校区，2部门，3总裁，4财务
    public function check($cc){
        if($cc==4&&session('user_name')!='张毅')die;
        if($cc==2&&get_position_name()!='主管')die;
        if($cc==3&&get_position_name()!='总裁')die;
        if($cc>1&&get_school_name()!='集团')die;
        $state=$cc+1;
        if($_POST['yes'])$this->delt($state);
        if($_POST['no'])$this->delt(0);
        if($_POST['change'])$this->change();
        if($_POST['delt'])$this->delt();
        $w['cc']=$cc;
        $w['date']=session('date');
        $w['state']=array('BETWEEN',[$cc,4]);
        if(get_school_name()=='集团'){
            if($cc==1)die;//集团没有校区审核
            if($cc==2)$w['dept_id']=session('dept_id');
            if($cc==3&&session('user_name')!='李文龙')$w['dept_id2']=session('dept_id');
            if(session('user_name')=='李文龙')$w['_string']='(dept_id3=1 AND state=3.5) OR dept_id2=29';//大总裁审核
        }else{
            $w['school']=get_school_name();
        } 
        $mod=D('budget');
        $count   = $mod->where($w)->count();// 查询满足要求的总记录数
        $Page    = new \Home\Hongwen\Page($count,40);//
        $show    = $Page->show();// 分页显示输出
        $this->page=$show;// 赋值分页输出
        $list = $mod->where($w)->order('state asc,id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach ($list as $k=>$v) {
        // if($_POST['no'])$this->delt(0);
            if($v['state']==$cc || ($v['state']==3.5 && session('user_name')=='李文龙'))$list[$k]['idd']=$list[$k]['id'];
        }
        $this->assign('list',$list);// 赋值数据集
        //计算金额
        $con=$mod->where($w)->select();
        foreach ($con as $v) {
            if($v['state']==$cc){
                $cont['a']+=$v['d']*$v['e'];
            }else{
                $cont['b']+=$v['d']*$v['e'];
            }
        }
        $this->cont=$cont;
        $this->display('index'); // 输出模板

    }

    //数据导出
    public function all(){
        if(I('param.delt'))$this->delt();
        if(I('param.import'))$this->import();
        if($_POST['change'])$this->change(1);
        if($_POST['delt'])$this->delt();
        $w['state']=['BETWEEN','-1,5'];
        $w['date']=session('date');
        if(I('param.state'))$w['state']=I('param.state');
        if(I('param.state')=='全部')$w['state']=['BETWEEN','-1,5'];
        if(I('param.date'))$w['date']=I('param.date');
        if(I('param.school'))$w['school']=I('param.school');
        if(I('param.jsxq'))$w['jsxq']=I('param.jsxq');
        if(I('param.aa'))$w['aa']=I('param.aa');
        if(I('param.jsr'))$w['jsr']=I('param.jsr');
        $mod=D('budget');
        $count   = $mod->where($w)->count();// 查询满足要求的总记录数
        $Page    = new \Home\Hongwen\Page($count,40);//
        $show    = $Page->show();// 分页显示输出
        $this->page=$show;// 赋值分页输出
        $list = $mod->where($w)->order('state asc,id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach ($list as $k=>$v) {
            $list[$k]['idd']=$list[$k]['id'];
        }
        $this->assign('list',$list);// 赋值数据集
        //计算金额
        $con=$mod->where($w)->select();
        foreach ($con as $v) {
            if($v['state']==5){
                $cont['a']+=$v['d']*$v['e'];
            }else{
                $cont['b']+=$v['d']*$v['e'];
            }
        }
        $this->cont=$cont;
        $this->display('index'); // 输出模板
    }


    public function import(){

            if($_POST['state'])$w['state']=I('post.state');
            if($_POST['state']=='全部')$w['state']=['BETWEEN','-1,5'];
            if($_POST['date'])$w['date']=I('post.date');
            if($_POST['school'])$w['school']=I('post.school');
            if($_POST['jsxq'])$w['jsxq']=I('post.jsxq');
            if($_POST['aa'])$w['aa']=I('post.aa');
            if($_POST['jsr'])$w['jsr']=I('post.jsr');
        $mm=D('budget')->where($w)->order('id desc')->select();

        $output = "<HTML>";
        $output .= "<HEAD>";
        $output .= "<META http-equiv=Content-Type content=\"text/html; charset=utf-8\">";
        $output .= "</HEAD>";
        $output .= "<BODY>";
        $output .= "<TABLE BORDER=1>";
        $output .= "<tr><td>期次</td><td>状态</td><td>序号</td><td>申请校区</td><td>归属校区</td><td>审核部门</td><td>成本类型</td><td>二级科目</td><td>明细</td><td>单价（元）</td><td>数量</td><td>金额</td><td>期望审批日前</td><td>类型</td><td>预算周期</td><td>接收校区</td><td>接收人</td><td>卡号</td><td>财务</td><td>申请原因</td><td>备注</td><td>数据创建时间</td><td>数据通过时间</td></tr>";
            foreach ($mm as $m) {
                if($m['class']==1){
                    $class='常规预算';
                }else{
                    $class='临时预算';
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
                    case '3':
                        $state='中心审核';
                        break;
                    case '3.5':
                        $state='总裁审核';
                        break;
                    case '4':
                        $state='审批确认';
                        break;
                    case '5':
                        $state='审核通过';
                        break;
                }
                $output .= "<tr><td>".$m['date']."</td><td>".$state."</td><td>".$m['id']."</td><td>".$m['school']."</td><td>".$m['gs']."</td><td>".$m['bm']."</td><td>".$m['aa']."</td><td>".$m['b']."</td><td>".$m['c']."</td><td>".$m['d']."</td><td>".$m['e']."</td><td>".$m['d']*$m['e']."</td><td>".$m['g']."</td><td>".$class."</td><td>".$m['week']."</td><td>".$m['jsxq']."</td><td>".$m['jsr']."</td><td>".$m['card']."</td><td>".$m['name']."</td><td>".$m['why']."</td><td>".$m['other']."</td><td>".$m['timestamp']."</td><td>".$m['time5']."</td></tr>";
            }
        $output .= "</TABLE>";
        $output .= "</BODY>";
        $output .= "</HTML>";
        $filename='财务系统预算明细导出表'.date('Y-m-d');
        header("Content-type:application/msexcel");
        header("Content-disposition: attachment; filename=$filename.xls");
        header("Cache-control: private");
        header("Pragma: private");
        print($output);
    }




    //审核记录
    public function record($id,$info){
        $w['id']=$id;
        $inf=D('budget')->where($w)->find();
        $d['record']=$inf['record'].'<'.$info.date('Y-m-d H:i:s').session('user_name').'>';
        D('budget')->where($w)->save($d);
    }

    public function cc(){
        // D('budget')->where(['state'=>3.5])->setField('state',4);
    }
}
