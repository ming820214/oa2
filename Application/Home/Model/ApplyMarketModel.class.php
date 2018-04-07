<?php
namespace Home\Model;
/**
 *申请审核的过程管理
 */
class ApplyMarketModel extends CommonModel{

    /**
    *审核
    *@param array  审核的额数据列表(申请的id)
    *@return bool  状态
    *-10=>审核失败,0=>待提交,5=>计划退回,10=>校长审核,20=>部门审核,30=>中心审核,40=>总裁审核,50=>计划通过,55=>申请退回,60=>资金申请,70=>资金审批,80=>审批通过,90=>报销申请,95=>退回报销,100=>校长确认,110=>部门确认,120=>中心确认,130=>总裁确认,140=>费用确认,150=>入账确认,160=>审核完成
    */
    public function check($type,$ids,$why=''){

        $list=M('applyMarket')->where(['id'=>['in',$ids]])->field('record',true)->select();
        if($type==-1){//删除数据
            $this->check_del($list);
            $this->save_check($list,4);
        }elseif ($type==0){//退回数据
            $this->check_back($list,$why);
            $this->save_check($list,3);
        }elseif ($type==1){//审核数据
            $this->check_access($list);
            $this->save_check($list,1);
        }
        $this->check_notice($type,$list);//微信通知

        return true;

    }

    /**
    *执行审核数据
    */
    public function check_access(&$list){
        foreach ($list as &$v) {
            $v['back'] =null; 
            
            if($v['state'] <80){
                if($v['state'] ==10){
                    $v['state']=$v['state']+60;
                }else{
                    $v['state']=$v['state']+10;
                }
            }
        }
    }

    /**
    *执行退回数据
    */
    public function check_back(&$list,$why){
        foreach ($list as &$v) {
            $v['state']=0;
            $v['back'] = 1;
            $v['why_add'] = $why;
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
    public function check_notice($type,$list){
        $flag_type = 0;
        foreach ($list as $v) {
            $w['name'] = $v['apply_user'];
				
            if($w){
              $user[]=M('user')->where(['is_del'=>0])->where($w)->getField('wechat_userid');//wechat_userid
            }
            
            if($v['state'] == '70'){
                //佟彤
             array_push($user, "niexin");
            }
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
        $content = '';
        if($type == -1){
          $content = "您有新的营销企划类设计申请被删除！";
        }else if($type == 0){
         $content = "您有新的营销企划类设计申请被退回！";
        }else{
          $content = "您有新的营销企划类设计申请待审核！";
        }
        
        $wx= getWechatObj();
        
        $wx->sendNewsMsg(
            [$wx->buildNewsItem($content,$info,wx_oauth(C('WWW').U('Public/log_wx?urll=apply_market/examine')),'')],
            ['touser'=>$user],
            C('WECHAT_APP')['XZMS']
            );
        
    }

    /**
    *记录审核过程,1通过，2修改,3退回,4删除
    */
    function save_check($data,$type=1){
        $mod=M('applyMarket');
        $info=session('user_name').($type==1?',通过,':($type==2?',修改,':($type==3?',退回,':',删除,'))).date('Y-m-d H:i:s');
        foreach ($data as $v) {
            $inf=$info.','.$v['state'];
            $v['record']=['exp', "CONCAT_WS('|', `record`, '{$inf}')"];
            
            if($type==3 && $v['why']){
             $v['why'] .= get_user_name() . '：' . $v['why_add'];
            }
            
            $v['update_time'] = date('Y-m-d H:i:s');
            
            $mod->where(['id'=>$v['id']])->save($v);
        }
    }

}
