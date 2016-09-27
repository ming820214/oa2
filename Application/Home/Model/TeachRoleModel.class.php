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
        $list = $this->where($condition)->order($order)->select();
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
