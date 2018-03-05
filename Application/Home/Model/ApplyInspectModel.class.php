<?php
namespace Home\Model;
/**
 *申请审核的过程管理
 */
class ApplyInspectModel extends CommonModel{

    /**
    *审核
    *@param array  审核的额数据列表(申请的id)
    *@return bool  状态
    *0=>待提交,10=>校长确认,20=>区域总监审查,30=>督导确认,40=>审批通过
    */
    public function check($type,$ids,$why=''){

        $list=M('applyInspect')->where(['id'=>['in',$ids]])->field('record',true)->select();
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
                
                if($v['state'] <40){
                     $v['state']=$v['state']+10;
                }
        }
    }

    /**
    *执行退回数据
    */
    public function check_back(&$list,$why){
        foreach ($list as &$v) {
            $v['state'] -= 10;
            $v['back'] = 1;
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
    public function check_notice($type,$list){
        $flag_type = 0;
        foreach ($list as $v) {
            $w['name'] = $v['apply_user'];
				
            if($w){
              $user[]=M('user')->where(['is_del'=>0])->where($w)->getField('wechat_userid');//wechat_userid
            }
            
            if($v['state'] == '20' && $v['area'] == 10){
                //张鹏
                array_push($user, "XZsmqh29");
            }else if($v['state'] == '20' && $v['area'] == 20){
                //张玉珠
                array_push($user, "XZdl01");
            }else if($v['state'] == '20' && $v['area'] == 40){
                //何亮
                array_push($user, "XZsy01");
            }else if($v['state'] == '20' && $v['area'] == 30){
                //王大鹏
                array_push($user, "XZfx01");
            }else if($v['state'] == '20' && $v['area'] == 50){
                //李明帅
                array_push($user, "JZsyjn03");
            }else if($v['state'] == '30'){
                //潘小龙
                array_push($user, "XZfx03");
                
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
        
        $content = "整改申请有变动，请注意查看！";
        
        $wx= getWechatObj();
        
        $wx->sendNewsMsg(
            [$wx->buildNewsItem($content,$info,wx_oauth(C('WWW').U('Public/log_wx?urll=apply_inspect/examine')),'')],
            ['touser'=>$user],
            C('WECHAT_APP')['XZMS']
            );
        
    }

    /**
    *记录审核过程,1通过，2修改,3退回,4删除
    */
    function save_check($data,$type=1){
        $mod=M('applyInspect');
        $info=session('user_name').($type==1?',通过,':($type==2?',修改,':($type==3?',退回,':',删除,'))).date('Y-m-d H:i:s');
        foreach ($data as $v) {
            $inf=$info.','.$v['state'];
            $v['record']=['exp', "CONCAT_WS('|', `record`, '{$inf}')"];
            
            if($v['why']){
             $v['why']=get_user_name().'：'.$v['why'];
            }
             
            $mod->where(['id'=>$v['id']])->save($v);
        }
    }

}
