<?php
namespace Home\Model;
/**
 *申请审核的过程管理
 */
class ProcessModel extends CommonModel{

    /**
    *审核
    *@param array  审核的额数据列表(申请的id)
    *@return bool  状态
    *-10=>审核失败,0=>待提交,5=>计划退回,10=>校长审核,20=>部门审核,30=>中心审核,40=>总裁审核,50=>计划通过,55=>申请退回,60=>资金申请,70=>资金审批,80=>审批通过,90=>报销申请,95=>退回报销,100=>校长确认,110=>部门确认,120=>中心确认,130=>总裁确认,140=>费用确认,150=>入账确认,160=>审核完成
    */
    public function check($type,$ids,$why=''){

        $list=M('process')->where(['id'=>['in',$ids]])->field('id,unit_price,count,money,dept,state')->select();
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
    // 1 专员级  2  主管级 3 经理级  4 总监级 5 中心副总裁级 6 中心总裁级 7 集团总裁级 8 财务资金主管 9 财务中心总裁 10 计划通过 11 财务中心总裁级(资金) 12 税金专员 13 资金计划通过
    public function check_access(&$list){
        foreach ($list as &$v) {
            
            if($v['state']==310){
                continue;
            }
            if($v['state']==300){
                $v['state'] = 310;
                $v['phase'] += 1;
                continue;
            }
            
            $rank_rule = M('ProcessRule')->where(['dept_id'=>$v['dept'],'phase'=>array('elt',$v['all_phase']),'is_del'=>0])->order('phase')->select();
            if($v['state'] == 125 || $v['state'] == ($rank_rule[0]['rank_id']-5) || $v['state'] == (200 + $rank_rule[0]['rank_id']-5) ){
                $v['state'] += 5;
                $v['phase'] += 1;
            }else if($v['state']<120){
                if($v['phase']<$v['all_phase']){
                    $v['phase'] += 1;
                    $v['state'] = $rank_rule[$v['phase']]['rank_id'];
                }else if($v['phase']==$v['all_phase']){
                    if($v['unit_price'] * $v['count']<5000){
                        
                        $subjects = M('PostSubject')->where(['subject_id'=>$v['subject']])->select();
                        if(count($subjects)>0){
                            $v['phase'] = 8; //资金主管
                            $v['state'] = 100;
                        }else{
                            $v['phase'] = 9; //财务中心总裁
                            $v['state'] = 110;
                        }
                        
                    }else{
                        $lwl = false;
                        foreach($rank_rule as $km=>$ko){
                            if($ko['rank_id'] == 90){
                                $lwl = true;break;
                            }
                        }
                        if(lwl){
                            $subjects = M('PostSubject')->where(['subject_id'=>$v['subject']])->select();
                            if(count($subjects)>0){
                                $v['phase'] = 8; //资金主管
                                $v['state'] = 100;
                            }else{
                                $v['phase'] = 9; //财务中心总裁
                                $v['state'] = 110;
                            }
                        }else{
                           $v['phase'] = 7;
                           $v['state'] = 90;
                        }
                    }
                }else if($v['phase'] == 7 || $v['phase'] == 8 || $v['phase'] == 9){
                    $v['phase'] += 1;
                    $v['state'] += 10;
                }/* else if($v['phase'] == 7){
                    $v['phase'] = 8;
                    $v['state'] = 100;
                }else if($v['phase'] == 8){
                    $v['phase'] = 9; //财务中心总裁
                    $v['state'] = 110;
                }else if($v['phase'] == 9){
                    $v['phase'] = 10; //计划审核通过
                    $v['state'] = 120;
                } */
            }else if($v['state']>=120 && $v['state']<150){
                $v['phase'] += 1;
                $v['state'] += 10;
            }else if($v['state']>=150 && $v['state']<=290){
                if($v['money'] == ($v['unit_price'] * $v['count'])){
                    if($v['state'] == 150){
                        $v['phase'] = 9; //财务中心总裁
                        $v['state'] = 300;
                    }/* else{
                        $v['phase'] += 1;
                        $v['state'] += 10;
                    } */
                    
                }else if($v['money'] != ($v['unit_price'] * $v['count'])){
                    if($v['state'] == 150){
                        $v['phase'] = $rank_rule[0]['phase'];
                        $v['state'] = 200 + $rank_rule[0]['rank_id'];
                    }else{
//                         $v['phase'] += 1;
//                         $v['state'] += 200 + $rank_rule[$v['phase']]['rank_id'];
                        
                        if($v['phase']<$v['all_phase']){
                            $v['phase'] += 1;
                            $v['state'] = 200 + $rank_rule[$v['phase']]['rank_id'];
                        }else if($v['phase']=$v['all_phase']){
                            if($v['unit_price'] * $v['count']<5000){
                                $v['phase'] = 9; //财务中心总裁
                                $v['state'] = 300;
                            }else{
                                $lwl = false;
                                foreach($rank_rule as $km=>$ko){
                                    if($ko['rank_id'] == 90){
                                        $lwl = true;break;
                                    }
                                }
                                if(lwl){
                                    $v['phase'] = 9; //财务中心总裁
                                    $v['state'] = 300;
                                }else{
                                    $v['phase'] = 7; //5000+ 李文龙报销审核
                                    $v['state'] = 290;
                                }
                            }
                        }else if($v['phase'] == 7){
                            $v['phase'] += 2;
                            $v['state'] += 10;
                        }/* else if($v['phase'] == 9){
                            $v['phase'] += 1;
                            $v['state'] += 10;
                        } */
                    }
                    
                }
            }
        }
    }

    /**
    *执行退回数据
    */
    public function check_back(&$list,$why){
        foreach ($list as &$v) {
            $rank_rule = M('ProcessRule')->where(['dept_id'=>$v['dept'],'phase'=>array('elt',$v['all_phase']),'is_del'=>0])->order('phase')->select();
            if($v['state']<120){
                $v['state']=$rank_rule[0]['rank_id']-5;//退回修改
                $v['phase']=$rank_rule[0]['phase']-1;
            }else if($v['state']>=120 && $v['state']<150){
                $v['state']=125;//退回修改
                $v['phase']=10;
            }else if($v['state']>=150 && $v['state']<=290){
                $v['state']= 200 + $rank_rule[0]['rank_id']-5;//退回修改
                $v['phase']=$rank_rule[0]['phase']-1;
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
        $owner[]=[];
        foreach ($list as $v) {
            $owner[] = $v['add_user_name'];
            //审核过程通知上级部门
            //1 专员级  2  主管级 3 经理级  4 总监级 5 中心副总裁级 6 中心总裁级 7 集团总裁级 8 财务资金主管 9 财务中心总裁 10 计划通过 11 财务中心总裁级(资金) 12 税金专员 13 资金计划通过
            //310=>流程结束,300=>财务中心总裁级(报销),290=>集团总裁级(报销),260=>中心总裁级(报销),250=>中心副总裁级(报销),240=>总监级(报销),230=>经理级(报销),220=>主管级(报销),210=>专员级(报销),
            //150=>资金计划通过,140=>税金专员,130=>财务中心总裁级(资金),
            //120=>计划通过,110=>财务中心总裁级,100=>资金部主管,90=>集团总裁级,60=>中心总裁级,50=>中心副总裁级,40=>总监级,30=>经理级,20=>主管级,10=>专员级,0=>待提交
             
            //通知财务中心总裁
            if($v['state']==300||$v['state']==130||$v['state']==110||($v['state']==60 && $v['dept']==1)){ //财务中心部门ID暂待定
                //$user[]=M('user')->where(['new_dept_id'=>'','new_position_id'=>48,'new_rank_id'=>60,'is_del'=>0])->where($w)->getField('wechat_userid');//wechat_userid
                $w['name']='齐静';
            }else if($v['state']==100){
                //通知财务部主管
                $subjects = M('PostSubject')->where(['subject_id'=>$v['subject']])->getField('post_id');
                
                if(count($subjects)>0){
                    $w['name'] = M('user')->where(['new_position_id'=>$subjects[0],'is_del'=>0])->getField('name');
                }else{
                    $w['name']='齐静';
                }
                
            }else if($v['state']==140){
                //通知资金主管王丽丽
                $w['name']='王丽丽';
                //$w['name']=M('user')->where(['new_position_id'=>51,'is_del'=>0])->getField('name');
            }else if($v['state']==90||$v['state']==290){
                //通知集团总裁级
                $w['name']='李文龙';
                //$w['name']=M('user')->where(['new_position_id'=>1,'is_del'=>0])->getField('name');
                $f=F('ntz')+1;
                F('ntz',$f);
            }else{
                $rank_level = $v['state']>200?($v['state']-200):$v['state'];
                $w['name']=M('user')->where(['new_rank_id'=>$rank_level,'new_dept_id'=>$v['dept'],'is_del'=>0])->getField('name');
            }
				
            if($w){
                $user[]=M('user')->where(['is_del'=>0])->where($w)->getField('wechat_userid');//wechat_userid
                unset($w);
            }
            
            
        }
        $user=array_unique($user);

        $info='点击可直接进入审核……';

        //微信通知
        if(empty($user))return;
        //存储一下被通知过的人
        $ff=(array)F('weixin_process');
        $f2=array_merge($ff,$user);
        F('weixin_process',$f2);

        $wx= getWechatObj();
        $wx->sendNewsMsg(
            [$wx->buildNewsItem("有财务待审核",$info,wx_oauth(C('WWW').U('Public/log_wx?urll=process/examine')),'')],
            ['touser'=>$user],
            C('WECHAT_APP')['XZMS']
        );
    }

    /**
    *记录审核过程,1通过，2修改,3退回,4删除
    */
    function save_check($data,$type=1){
        $mod=M('process');
        $info=session('user_name').($type==1?',通过,':($type==2?',修改,':($type==3?',退回,':',删除,'))).date('Y-m-d H:i:s');
        foreach ($data as $v) {
            $inf=$info.','.$v['state'];
            $v['record']=['exp', "CONCAT_WS('|', `record`, '{$inf}')"];
            if($v['state']==150)$v['money_time']=date('Y-m-d H:i:s');
            if($v['why'])$v['why']=get_user_name().'：'.$v['why'];
            
            $v['update_time'] = date('Y-m-d H:i:s');
            $mod->where(['id'=>$v['id']])->save($v);
        }
    }

}
