<?php
namespace Home\Model;
use Think\Model\ViewModel;

class YewuModel extends ViewModel{

    protected $autoCheckFields  =   false;
    public $viewFields = [
            'YewuStudents' => ['id','school','grade','schoolx','name','parents','parent_type','tel1','tel2','yixiang_qiang','address','state','_type'=>'LEFT'],
            'YewuTrack' => ['id'=>'ids','track_time','interest','track_user','direct','flag','info','track_effect','track_next','way','_on'=>'YewuTrack.stuid=YewuStudents.id']
        ];
        
    
    public function get_list($condition,$count)
    {
    	//$data=M('yewu_students')->where($condition)->order('id desc')->limit($count)->select();
    	$data=$this->where($condition)->order('ids desc')->limit($count)->select();

        foreach ($data as &$v) {
            $v['track_user']=M('user')->where(['id'=>$v['track_user']])->getField('name');
            $record=M('YewuTrack')->where(['stuid'=>$v['id']])->order('track_next desc')->find();
            if($record){
                $v['track_time']=substr($record['track_time'],0,10);
                $v['track_next']=substr($record['track_next'],0,10);
            }
        }

    	return	 $data;
    }
    
    public function listCount($condition)
    {
    	return M('yewu_students')->where($condition)->count();
    }
    
    //获取今日需要跟进的任务
    public function need_track($condition){
        $stu=M('yewu_students')->where($condition)->order('assign_time DESC,get_way,yixiang_qiang')->select();
        foreach ($stu as $v) {
            $record=M('YewuTrack')->where(['stuid'=>$v['id']])->order('timestamp desc')->find();
			
            if($record){
                $v['track_time']=substr($record['track_time'],0,10);
                $v['track_next']=substr($record['track_next'],0,10);
                if($record['flag'] && $record['track_next'] <= date('Y-m-d 00:00:00'))$data[]=$v;
            }else{
                $data[]=$v;
            }
        }
		
        $data=array_sort($data,'assign_time','track_next');
        return $data;
    }

    //学员信息转存到档案，并设置信息为已缴费
    public function save_to($id){
        $info=M('yewu_students')->find($id);
        if(empty($info['name']))die;
        
        //转存到档案
        $mod=M('hw001.student',null);
        $_string="(oa_stuid = $id)";
        if($info['name'])$_string.=" OR (name ='".$info['name']."')";
        if($info['tel'])$_string.=" OR (tel = $info[tel1])";
        $if=$mod->where(['school'=>get_school_name($info['school'])])->where(['_string'=>$_string])->find();
        if(empty($if['oa_stuid'])){
            
            //设置信息为已转正缴费
            $dat['state']=20;
            $dat['save_time']=date('Y-m-d H:i:s');
            M('yewu_students')->where(['id'=>$id])->save($dat);
            
            $mod->where($if)->save(['oa_stuid'=>$id]);
        }else{
            return null;
        }
        if($if)return $if['id'];//如果已转存过则直接返回id

        $info['std_id'] = get_std_id($info['school']);
        $info['school']=get_school_name($info['school']);
        $info['tel']=$info['tel1'];
        $info['hometel']=$info['tel2'];
        $info['state']=1;
        $info['jiaoxue']=get_user_name($info['track_user']);
        unset($info['create_time'],$info['update_time'],$info['id']);
        $p=Firstname()->getInitials(substr($info['name'],0,3));
        $info['p']=$p?$p:'※';
        $info['oa_stuid']=$id;
        unset($info['id']);
        $stuid=$mod->add($info);
        return $stuid;
    }
}
