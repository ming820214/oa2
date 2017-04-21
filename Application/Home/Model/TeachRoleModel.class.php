<?php
namespace Home\Model;

class TeachRoleModel extends CommonModel{
    public function addOne($data){
        if($this->create($data)){
            $insId = $this->add();
            if($insId){
                return true;
            }
        }

        return false;
    }

    public function getList($condition, $order){
        //$list = $this->where($condition)->order($order)->select(); 隐藏掉方案设置为隐藏的数据项；
        
        $condition['oa_unitprice_role.displays'] = '0';
    	$list = $this->join("oa_unitprice_role on oa_teach_role.plan_id = oa_unitprice_role.id ")
    				 ->where($condition)
    				 ->field('oa_teach_role.id, oa_teach_role.uid, oa_teach_role.plan_id, oa_teach_role.name, oa_teach_role.course, oa_teach_role.grade, oa_teach_role.subject, oa_teach_role.level, oa_teach_role.school, oa_teach_role.create_time, oa_teach_role.is_del')
    				 ->order($order)->select();
        return $list;
    }

    public function saveOne($data){
        $data['update_time'] = time();
        if($this->save($data) !== false){
            return true;
        }

        return false;
    }

    public function delOne($id){
        return $this->save(array(
                    'id' => (int)$id,
                    'is_del' => 1,
              ));
    }

    public function getOne($id){
        $one = $this->where(array('id' => $id))->find();
        return $one;
    }

    public function hasOne($uid, $planId, $subjectId){
        return $this->where([
            'is_del'  => array('eq', 0),
            'uid'     => array('eq', $uid),
            'plan_id' => array('eq', $planId),
            'subject' => array('eq', $subjectId),
        ])->find();
    }

}
