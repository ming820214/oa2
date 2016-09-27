<?php
namespace Home\Model;

class StudentsModel extends CommonModel{
    protected $dbName = 'hw001';
    protected $trueTableName = 'student';
    // protected $_map = [
    //     'name' =>'username',
    //     'mail' =>'email',
    // ]

    public function addOne($data){
        $data['month']           = session('date');
        $data['apply_user']      = session('user_name');
        $data['apply_user_id']   = session('emp_no');
        $data['apply_school_id'] = session('school_id');
        $data['std_id'] = date('Ymd').sprintf("%03d", session('school_id')).sprintf("%03d",
            $this->where("FROM_UNIXTIME(`create_time`,'%Y-%m-%d')=CURRENT_DATE() and school_id='".session('school_id')."'")
            ->count() + 1);

        if($this->create($data)){
            $insId = $this->add();
            if($insId){
                return true;
            }
        }

        return false;
    }

    public function getList($condition, $start, $count, $order){
        $list = $this->where($condition)->limit($start, $count)->order($order)
                    ->select();
        $list = $this->parseFieldsMap($list);
        foreach($list as &$item){
            $item['apply_school_str'] = get_school_name($item['apply_school']);
            $item['create_time_str']  = $this->_fixDate($item['create_time']);
            $item['update_time_str']  = $this->_fixDate($item['update_time']);
        }
        return $list;
    }

    public function saveOne($data){
        $data['update_time'] = time();
        if($this->save($data) !== false){
            return true;
        }

        return false;
    }

    public function del($idList){
        $flag = 0;
        foreach($idList as $id){
            $flag += (int)$this->save(array(
                            'id' => (int)$id,
                            'is_del' => 1,
                      ));
        }

        return $flag;
    }

    public function getOne($id){
        $one = $this->where(array('id' => $id))->find();
        $one = $this->parseFieldsMap($one);
        return $one;
    }

    private function _fixDate($stamp){
        return $stamp > 10000 ? date('Y-m-d H:i:s', $stamp) : '';
    }
	
}
