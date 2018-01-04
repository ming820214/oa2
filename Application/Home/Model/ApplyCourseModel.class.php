<?php
namespace Home\Model;
/**
 *申请审核的过程管理
 */
class ApplyCourseModel extends CommonModel{

    /**
    *审核
    *@param array  审核的额数据列表(申请的id)
    *@return bool  状态
    *0=>待提交,10=>区域总监审核,20=>运营中心审批,30=>课程添加,40=>财务申请确认,50=>销售课程,60=>区域结束审核,70=>事业部总裁结束审核,80=>课程删除,90=>财务结束确认,100=>结束通过
    */
    public function check($type,$ids,$why=''){

        $list=M('applyCourse')->where(['id'=>['in',$ids]])->field('record',true)->select();
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
        $this->check_notice($type,$list);//微信通知

        return true;

    }

    /**
    *执行审核数据
    */
    public function check_access(&$list){
        foreach ($list as &$v) {
                $v['back'] =null;   
                if($v['state']<100){
                    $v['state']=$v['state']+10;
                }
                
        }
    }

    /**
    *执行退回数据
    */
    public function check_back(&$list,$why){
        foreach ($list as &$v) {
            if($v['state']>50){
                $v['state']=50;
            }else{
                $v['state']=0;
            }
            
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
        foreach ($list as $v) {
            $w['name'] = strstr($v['apply_user'],'#',true);
				
            if($w){
              $user[]=M('user')->where(['is_del'=>0])->where($w)->getField('wechat_userid');//wechat_userid
            }
            
            if(($v['state'] == '10' || $v['state'] == '60') && $v['area'] == '辽宁'){
               //姜博文
               array_push($user, "XZsmqh28");
            }else if(($v['state'] == '10' || $v['state'] == '60') && $v['area'] == '辽东'){
                //张鹏
                array_push($user, "XZsmqh29");
            }else if(($v['state'] == '10' || $v['state'] == '60') && $v['area'] == '辽西'){
                //张玉珠
                array_push($user, "XZdl01");
            }else if(($v['state'] == '10' || $v['state'] == '60') && $v['area'] == '黑龙江'){
                //何亮
                array_push($user, "XZsy01");
            }else if(($v['state'] == '10' || $v['state'] == '60')  && $v['area'] == '吉林'){
                //王大鹏
                array_push($user, "XZfx01");
            }else if(($v['state'] == '20' || $v['state'] == '70') ){
                if($v['area'] != '多种经营事业部'){
                    //王胜鑫
                    array_push($user, "YY001");
                }else{
                    array_push($user, "lvxueru");
                }

            }else if(($v['state'] == '40' || $v['state'] == '90') ){
                 //齐静
                 array_push($user, "A02");
            }else if(($v['state'] == '30' || $v['state'] == '80') ){
                //邹德涛
                array_push($user, "ZXhld001");
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
          $content = "新的课程申请被删除！";
        }else if($type == 0){
         $content = "新的课程申请被退回！";
        }else{
          $content = "您有新的课程申请待审核！";
        }
        
        $wx= getWechatObj();
        $wx->sendNewsMsg(
            [$wx->buildNewsItem($content,$info,wx_oauth(C('WWW').U('Public/log_wx?urll=apply_course/examine')),'')],
            ['touser'=>$user],
            C('WECHAT_APP')['XZMS']
        );
    }

    /**
    *记录审核过程,1通过，2修改,3退回,4删除
    */
    function save_check($data,$type=1){
        $mod=M('applyCourse');
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
