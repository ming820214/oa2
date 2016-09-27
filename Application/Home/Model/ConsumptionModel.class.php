<?php
namespace Home\Model;

class ConsumptionModel extends CommonModel{
    /**
     * 充值
     * @param  array      $data  相关数据
     * @return boolen|int        失败返回 false ，成功返回记录 id
     */
    public function charge($data){
        $data['state'] = C('CONSUME_STATES')['CHECK1']['id'];

        return $this->addOne($data);
    }

    /**
     * 退费
     * @param  array      $data  相关数据
     * @return boolen|int        失败返回 false ，成功返回记录 id
     */
    public function yreturn($data){
        $data['state'] = C('CONSUME_STATES')['CHECK1']['id'];
        $data['type']  = getConsumeType('RETURN')['id'];

        return $this->addOne($data);
    }

    /**
     * 缴费
     * @param  array      $data  相关数据
     * @return boolen|int        失败返回 false ，成功返回记录 id
     */
    public function pay($data){
        $data['state'] = C('CONSUME_STATES')['CHECK1']['id'];
        $data['type']  = getConsumeType('PAY')['id'];

        // return $this->addOne($data);//关闭了页面缴费功能了
        return true;
    }

    /**
     * 订购
     * @param  array      $data  相关数据
     * @return boolen|int        失败返回 false ，成功返回记录 id
     */
    public function book($data){
        $data['state'] = C('CONSUME_STATES')['CHECK1']['id'];
        $data['type']  = getConsumeType('BOOK')['id'];

        return $this->addOne($data);
    }

    /**
     * 退课
     * @param  array      $data  相关数据
     * @return boolen|int        失败返回 false ，成功返回记录 id
     */
    public function drop($data){
        $data['state'] = C('CONSUME_STATES')['CHECK1']['id'];
        $data['type']  = getConsumeType('DROP')['id'];

        return $this->addOne($data);
    }

    /**
     * 调课
     * @param  array      $data  相关数据
     * @return boolen|int        失败返回 false ，成功返回记录 id
     */
    public function renewal($data){
        $data['state'] = C('CONSUME_STATES')['CHECK1']['id'];
        $data['type']  = getConsumeType('RENEWAL')['id'];

        return $this->addOne($data);
    }

    /**
     * 转课
     * @param  array      $data  相关数据
     * @return boolen|int        失败返回 false ，成功返回记录 id
     */
    public function change($data){
        $data['state'] = C('CONSUME_STATES')['CHECK1']['id'];
        $data['type']  = getConsumeType('CHANGE')['id'];

        return $this->addOne($data);
    }

    public function addOne($data){
        $data['month']      = session('date');
        $data['emp']        = session('user_name');
        $data['emp_no']     = session('emp_no');
        $data['emp_school'] = session('school_id');

        if($this->create($data)){
            return $this->add();
        }

        return false;
    }

    //项目列表,list2数据导出时关联订单信息
    public function getList($condition, $start, $count, $order,$list2=false){
        $list = $this->where($condition)->limit($start, $count)->order($order)
                    ->select();
        foreach($list as &$item){
            $item['type_str']        = getConsumeTypeById($item['type'])['name'];
            $item['state_str']       = getConsumeStateById($item['state'])['name'];
            $item['value_format']    = formatPrice($item['value']);
            $item['create_time_str'] = formatTime('Y-m-d H:i:s', $item['create_time']);
            $item['school'] = get_school_name($item['emp_school']);
            if($list2 && $item['type']<10000){
                $l2=$this->getlist2($item['id']);
                $item['order_id']=$l2['order_id'];
                $item['order_plan']=$l2['plan_name'];
                $item['order_unitprice']=$l2['unitprice'];
                $item['order_hour']=$l2['hour'];
                $item['order_ext_hour']=$l2['ext_hour'];
                $item['order_factor']=$l2['factor'];
                $item['order_used_hour']=$l2['used_hour'];
                $item['order_std_type']=$l2['std_type'];
                $item['order_director']=$l2['director'];
                $item['order_manager']=$l2['manager'];
                $item['order_price']=$l2['price'];
				$item['order_course_type'] = $l2['course']; //课程类型
            }
        }

        return $list;
    }

    //项目补充课程id,用于在数据导出时同时获取到订购课程的详情
    public function getlist2($id){
        return D('CourseView')->where(['order_id'=>$id])->find();
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
        return $one;
    }

    public function getBalance($std_id) {
        return $this->where([
            'std_id' => $std_id,
            'state'  => ['neq', C('CONSUME_STATES')['CANCEL']['id']],
            'is_del'  => ['neq',1],
        ])->sum('value');
    }

    public function check($idList, $from){
        $successCount = 0;
        $successState = C('CONSUME_STATES')['SUCCESS']['id'];
        $courseDropedState  = C('COURSE_STATES')['DROPED']['id'];
        $courseNormalState  = C('COURSE_STATES')['NORMAL']['id'];
        $courseChangedState = C('COURSE_STATES')['CHANGED']['id'];
        $courseRenewedState = C('COURSE_STATES')['RENEWED']['id'];

        foreach($idList as $id){
            $state = $this->_getCheckStates($id)[$from];
            if ($this->checkOne($id, $state, $reason)) {
                ++$successCount;
            }
        }

        return $successCount;
    }

    public function checkBack($idList, $from, $reason) {
        $successCount = 0;
        $state             = C('CONSUME_STATES')['CANCEL']['id'];
        $courseCancelState = C('COURSE_STATES')['CANCEL']['id'];
        $courseNormalState = C('COURSE_STATES')['NORMAL']['id'];

        foreach($idList as $id){
            if ($this->checkOne($id, $state, $reason)) {
                $consume = $this->find($id);
                if ($consume['type'] == getConsumeType('BOOK')['id']) {//订购课程的情况
                    // 作废订单
                    // D('Course')->checkOrder($id, $courseCancelState);
                } elseif ($consume['type'] == getConsumeType('RENEWAL')['id']){//调整课时的情况
                    // 作废新订单
                    D('Course')->checkOrder($id, $courseCancelState);
                    // 恢复老课程
                    D('Course')->checkDrop($consume['ext_id'], $courseNormalState);
                } elseif ($consume['type'] == getConsumeType('DROP')['id']) {
                    // 恢复课程
                    D('Course')->checkDrop($consume['ext_id'], $courseNormalState);
                }

                ++$successCount;
            }

        }

        return $successCount;
    }

    //记录审核过程
    public function checkOne($id, $state, $reason = '') {
        $track = session('user_name').'['.session('emp_no').']'.' 于 '
                .date('Y-m-d H:i').' 审核至 '.getConsumeStateById($state)['name'];
        $info=$this->find($id);
        if($info['type']<1000 && $state==50)$state=$info['state'];
        $data = [
            'id'    => (int)$id,
            'state' => $state,
            'log'   => ['exp', "CONCAT_WS('>>>', `log`, '{$track}')"],
        ];

        if (!empty($reason)) {
            $data['reason'] = ['exp', "CONCAT_WS('>>>', `reason`, '{$reason}')"];
        }

        return $this->save($data);
    }

    public function sumCheckPrice($condition) {
        return $this->where($condition)->where('value>0')->sum('value');//不考虑订购
    }

    private function _getCheckStates($id){
        $states = array(
            // 'checka' => C('CONSUME_STATES')['CHECK2']['id'],
            'checka' => C('CONSUME_STATES')['CHECK3']['id'],
            // 'checkb' => C('CONSUME_STATES')['CHECK3']['id'],
            'checkc' => C('CONSUME_STATES')['CHECK4']['id'],
            'checkd' => C('CONSUME_STATES')['SUCCESS']['id'],
        );

        return $states;
    }
}
