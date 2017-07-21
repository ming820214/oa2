<?php
namespace Home\Model;
/**
 *申请审核的过程管理
 */
class ApplyModel extends CommonModel{

    /**
    *审核
    *@param array  审核的额数据列表(申请的id)
    *@return bool  状态
    *-10=>审核失败,0=>待提交,5=>计划退回,10=>校长审核,20=>部门审核,30=>中心审核,40=>总裁审核,50=>计划通过,55=>申请退回,60=>资金申请,70=>资金审批,80=>审批通过,90=>报销申请,95=>退回报销,100=>校长确认,110=>部门确认,120=>中心确认,130=>总裁确认,140=>费用确认,150=>入账确认,160=>审核完成
    */
    public function check($type,$ids,$why=''){

        $list=M('apply')->where(['id'=>['in',$ids]])->field('id,type,unit_price,count,money,school,state,dept1,dept2')->select();
        if($type==-1){//删除数据
            $this->check_del($list);
            $this->save_check($list,4);
        }elseif ($type==0){//退回数据
            $this->check_back($list,$why);
            $this->save_check($list,3);
        }elseif ($type==1) {//审核数据
            $this->check_access($list);
            $this->save_check($list,1);
        }
        $this->check_notice($list);//微信通知

        return true;

    }

    /**
    *执行审核数据
    */
    public function check_access(&$list){
        foreach ($list as &$v) {
            if($v['state']==160)continue;
            if($v['state']==150){
                $v['state']=160;
                continue;
            }
            //计划内、计划外的项目
            if($v['type']==10 || $v['type']==20 || $v['type']==40){
                //遇到被退回的数据处理
                if(in_array($v['state'],[5,55,95]))$v['state']-=5;

//                 $v['state']+=10; 该语句针对type为10、20的类型，现在增加一个type为40的类型简易预算，改为下面的操作方式
				if($v['type']==40 && ($v['state'] === '0' || $v['state'] === 0)){
					$v['state']=60;
				}else{
					$v['state']+=10;
				}
                if($v['state']==110&&$v['money']==0)$v['state']-=10;
                //集团账号申请跳过校区状态
                if($v['school']==0){
                    if($v['state']==10)$v['state']=20;//跳过校长审核
                    if($v['state']==100)$v['state']=110;//跳过校长确认
                    // 如果二级主管申请的项目第一次审核人就是自己的也跳过
                    if($v['dept1']&&$v['state']==20&&session('dept_id')==$v['dept1']&&session('position_id')==8)$v['state']+=10;
                }
                //审核部门为空的跳到中心，中心为空的跳总裁办
                if(($v['state']==20&&$v['dept1']==0))$v['state']=30;
                if(($v['state']==30&&$v['dept2']==0))$v['state']=40;
                if(($v['state']==110&&$v['dept1']==0))$v['state']=120;
                if(($v['state']==120&&$v['dept2']==0))$v['state']=130;

                //if($v['state']==70)$v['state']+=10; 齐静将资金申请审核的环节交给王丽丽处理；
                
                if($v['state']==120&&$v['dept2']==32)$v['state']+=10;
                //计划申请金额等于0的项目跳过总裁审核
                if($v['state']==130&&round($v['unit_price']*$v['count'],2)==0)$v['state']=140;
                //（实际支出<=申请金额）也跳过总裁审核
                if($v['state']==130&&(round($v['money'],2)<=round($v['unit_price']*$v['count'],2)))$v['state']=140;
                if($v['state']==140)$v['state']+=10;

            }elseif ($v['type']==30){
                //直接跳到入账确认
                $v['state']=150;
            }
        }
    }

    /**
    *执行退回数据
    */
    public function check_back(&$list,$why){
        foreach ($list as &$v) {
        	//针对简易预算退回进行特别处理，因为简易预算是计划提交之后直接进入到资金申请阶段
//             $v['state']=($v['state']>90)?95:(($v['state']>50)?55:5);//退回修改 
			if($v['type'] == 40){
				$v['state']=($v['state']>90)?95:5;//退回修改
			}else{
				$v['state']=($v['state']>90)?95:(($v['state']>50)?55:5);//退回修改
			}
            $v['why']=$why;
        }
    }

    /**
    *执行删除数据
    */
    public function check_del(&$list){
        foreach ($list as &$v) {
            $v['is_del']=1;//删除数据
        }
    }

    /**
    *根据状态确定微信通知
    *@param array  数据
    *@return bool  状态
    *    'POSITION_ID' => [ // 用到的职位ID
    *    'SCHOOL_DIRECTOR' => 19, // 教学主任
    *    'SCHOOL_DIRECTOR_XZ' => 13, // 业务副校长
    *    'SCHOOL_MANAGER'  => 18, // 学管师
    *    'SCHOOL_MASTER'   => 10, // 校长
    *    'CONTROLLER'      => 8,  // 二级主管
    *    'PRESIDENT'       => 7,  // 一级总裁
    *]
    */
    public function check_notice($list){
        foreach ($list as $v) {
            //审核过程通知上级部门
                //通知校长
                if($v['state']==10||$v['state']==100){
                    $w['position_id']=C('POSITION_ID')['SCHOOL_MASTER'];
                    $w['school']=session('school_id');
                }
                //通知二级部门主管
                if($v['state']==20||$v['state']==110){
                    $w['position_id']=C('POSITION_ID')['CONTROLLER'];
                    $w['dept_id']=$v['dept1'];
                }
                //通知一级部门主管
                if($v['state']==30||$v['state']==120){
                    $w['position_id']=C('POSITION_ID')['PRESIDENT'];
                    $w['dept_id']=$v['dept2'];
                }
                //通知总裁龙哥
                if(($v['state']==40&&$v['type']==20)||$v['state']==130){
                    $w['name']='李文龙';
                    $f=F('tz')+1;
                    F('tz',$f);
                }
                //通知财务齐姐
                if(($v['state']==40&&$v['type']==10)||($v['state']==30&&$v['dept2']==29)||$v['state']==60||$v['state']==140||$v['state']==150)$w['name']='齐静';
                
                //if(in_array($v['state'],[60,140]))$w['name']='齐静';
                
                if($v['state'] == 70){
                  $w['name']='王丽丽';
                }
                 
				
				
				//通知财务毅哥 dept2 29 总裁办 54 人事中心
				//if(($v['state']==30&&$v['dept2']==54)||($v['state']==120&&$v['dept2']==54))$w['name']='张毅';
                if(($v['state']==30&&$v['dept2']==54)||($v['state']==120&&$v['dept2']==54))$w['name']='侯海洋';
               
                if(($v['state']==30&&$v['dept2']==29)||($v['state']==120&&$v['dept2']==29))$w['name']='张毅';
               
				
            if($w)
            $user[]=M('user')->where(['is_del'=>0])->where($w)->getField('wechat_userid');//wechat_userid
            unset($w);
        }
        $user=array_unique($user);

        $info='点击可直接进入审核……';

        //微信通知
        if(empty($user))return;
        //存储一下被通知过的人
        $ff=(array)F('weixin');
        $f2=array_merge($ff,$user);
        F('weixin',$f2);

        $wx= getWechatObj();
        $wx->sendNewsMsg(
            [$wx->buildNewsItem("有财务待审核",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=apply/examine')),'')],
            ['touser'=>$user],
            C('WECHAT_APP')['XZMS']
        );
    }

    /**
    *记录审核过程,1通过，2修改,3退回,4删除
    */
    function save_check($data,$type=1){
        $mod=M('apply');
        $info=session('user_name').($type==1?',通过,':($type==2?',修改,':($type==3?',退回,':',删除,'))).date('Y-m-d H:i:s');
        foreach ($data as $v) {
            $inf=$info.','.$v['state'];
            $v['record']=['exp', "CONCAT_WS('|', `record`, '{$inf}')"];
            if($v['state']==80)$v['money_time']=date('Y-m-d H:i:s');
            if($v['why'])$v['why']=get_user_name().'：'.$v['why'];
            $mod->where(['id'=>$v['id']])->save($v);
        }
    }

}
