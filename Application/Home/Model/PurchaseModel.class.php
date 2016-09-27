<?php
namespace Home\Model;

class PurchaseModel extends CommonModel{
    public function addOne($data){
        $data['month']         = session('date');
        $data['apply_user']    = session('user_name');
        $data['emp_no']        = session('emp_no');
        $data['apply_school']  = session('school_id');
        $data['apply_time']    = time();
        $data['purchase_time'] = strtotime($data['purchase_time']);
        $data['price_time']    = strtotime($data['price_time']);
        $data['state']         = getPurchaseState('IN_SCHOOL')['id'];

        if($this->create($data)){
            $insId = $this->add();
            if($insId){
                if(session('school_id') == C('SCHOOL_JTID')){ // 集团的人申请，直接到部门审核
                    $this->check(array($insId), 'schoolcheck');
                }else{
                    $this->_inform($insId);
                }

                return true;
            }
        }

        return false;
    }

    public function saveOne($data){
        $data['purchase_time'] = strtotime($data['purchase_time']);
        $data['price_time']    = strtotime($data['price_time']);

        if($_POST['from'] == 'apply'){
            // $_POST['state'] = getPurchaseState('IN_SCHOOL')['id'];
            $data['state'] = getPurchaseState('IN_SCHOOL')['id'];
        }


        if($this->save($data) !== false){
            if($_POST['from'] == 'apply' && session('school_id') == C('SCHOOL_JTID')){ // 集团的人申请，直接到部门审核
                $this->check(array($data['id']), 'schoolcheck');
            }
            return true;
        }

        return false;
    }

    public function del($idList){
        $flag = 0;
        foreach($idList as $id){
            $track = session('user_name').'['.session('emp_no').']'.' 于 '
                        .date('Y-m-d H:i').' 删除';
            $flag += (int)$this->save(array(
                            'id' => (int)$id,
                            'is_del' => 1,
                            'log' => array('exp', "CONCAT_WS('|', `log`, '{$track}')"),
                      ));
        }

        return $flag;
    }

    public function getOne($id){
        $one                  = $this->where(array('id' => $id))->find();
        $one['price_time']    = $this->_fixDate($one['price_time']);
        $one['purchase_time'] = $this->_fixDate($one['purchase_time']);
        $one['price']         = formatPrice($one['price']);
        // $one['unit_price']    = formatPrice($one['unit_price']);

        return $one;
    }

    public function getList($condition, $start, $count, $order){
        $list = $this->where($condition)->limit($start, $count)->order($order)
                    ->select();
        foreach($list as &$item){
            $item['state_str']          = $this->_getStateStr($item['state']);
            $item['belong_str']         = get_school_name($item['belong']);
            $item['receive_school_str'] = get_school_name($item['receive_school']);
            $item['apply_school_str']   = get_school_name($item['apply_school']);
            $item['dept_name']          = $item['dept_id'] > 0 ? get_dept_name($item['dept_id']) : '';
            $item['dept_name2']         = $item['dept_id2'] > 0 ? get_dept_name($item['dept_id2']) : '';
            $item['apply_time_str']     = $this->_fixDate($item['apply_time']);
            $item['apply_time_full']    = date('Y-m-d H:i:s', $item['apply_time']);
            $item['price_time_str']     = $this->_fixDate($item['price_time']);
            $item['purchase_time_str']  = $this->_fixDate($item['purchase_time']);
            $item['price']              = formatPrice($item['price']);
            $item['unit_price']         = formatPrice($item['unit_price']);

            $item['take_state_str'] = '未确认';
            if ($item['take_state']) {
                $item['take_state_str'] = '已确认';
            }
        }

        return $list;
    }

    public function sumPrice($condition){
        return ($this->where($condition)->sum('price')+$this->where($condition)->sum('take_cost1'));
    }

    public function check($idList, $from, $back = false){
        $successCount = 0;
        $backReason = '';
        foreach($idList as $id){
            if($back){
                $stateRetuenId = getPurchaseState('RETURN')['id'];
                $state = array(
                    'apply'       => $stateRetuenId,
                    'schoolcheck' => $stateRetuenId,
                    'deptcheck'   => $stateRetuenId,
                    'deptcheck2'   => $stateRetuenId,
                    'groupcheck'  => $stateRetuenId,
                )[$from];
                $backReason = $_POST['back_reason'];
            }else{
                $state = $this->_getCheckStates($id)[$from];
                if($from=='deptcheck' && $this->where(['id'=>$id])->getField('dept_id2')==29)$state = 300;
            }
            $track = session('user_name').'['.session('emp_no').']'.' 于 '
                        .date('Y-m-d H:i').' 审核至 '.$this->_getStateStr($state);
            $flag = $this->save(array(
                            'id' => (int)$id,
                            'state' => $state,
                            'log' => array('exp', "CONCAT_WS('|', `log`, '{$track}')"),
                            'back_reason' => $backReason,
                      ));

            if($flag && $state == getPurchaseState('SUCCESS')['id']){
                if(!(D('Budget')->writePurchase2Budget($id))){
                    \Think\Log::write("记录【{$id}】插入BUDGET表失败！！！{$state}", 'ERR');
                }
            }

            if ($flag) {
                $this->_inform($id);
            }

            $successCount += (int)$flag;
        }

        return $successCount;
    }

    private function _getStateStr($id){
        return getPurchaseStateById($id)['name'];
    }

    private function _fixDate($stamp){
        return $stamp > 10000 ? date('Y-m-d', $stamp) : '';
    }

    private function _getCheckStates($id){
        $states = array(
            'apply'       => getPurchaseState('IN_SCHOOL')['id'],
            'schoolcheck' => getPurchaseState('IN_GROUP')['id'],
            'deptcheck'   => getPurchaseState('IN_GROUP')['id'],
            'deptcheck2'  => getPurchaseState('IN_GROUP')['id'],
            'groupcheck'  => getPurchaseState('SUCCESS')['id'],
        );

        $info = $this->where("`id`={$id}")->find();
        if($info['dept_id2'] > 0){
            $states['schoolcheck'] = getPurchaseState('IN_DEPT2')['id'];
            $states['deptcheck'] = getPurchaseState('IN_DEPT2')['id'];
        }

        if($info['dept_id'] > 0){
            $states['schoolcheck'] = getPurchaseState('IN_DEPT')['id'];
        }

        return $states;
    }

    /**
     * 通知对应的人
     * @param  int $id 记录 id
     */
    private function _inform($id) {
        $user = D('User');
        $wx = getWechatObj();
        $purchase = $this->find($id);
        if ($purchase['state'] == getPurchaseState('IN_SCHOOL')['id']) { // 通知给校长
            $userlist = $user->where([
                'position_id' => C('POSITION_ID')['SCHOOL_MASTER'],
                'school'      => $purchase['apply_school'],
                ])->select();
            $uri = 'Public/log_wx?urll=purchase/schoolcheck';
        } elseif ($purchase['state'] == getPurchaseState('IN_DEPT')['id']) { // 通知主管
            $userlist = $user->where([
                'position_id' => C('POSITION_ID')['CONTROLLER'],
                'dept_id'     => $purchase['dept_id'],
                ])->select();
                $uri = 'Public/log_wx?urll=purchase/deptcheck';
        } elseif ($purchase['state'] == getPurchaseState('IN_DEPT2')['id']) { // 通知总裁
            $userlist = $user->where([
                'position_id' => C('POSITION_ID')['PRESIDENT'],
                'dept_id'     => $purchase['dept_id2'],
                ])->select();
                $uri = 'Public/log_wx?urll=purchase/deptcheck2';
        } elseif ($purchase['state'] == getPurchaseState('IN_GROUP')['id']) { // 通知财务
            $userlist = [
                ['wechat_userid' => 'CW001'], // 张毅
            ];
            $uri = 'Public/log_wx?urll=purchase/groupcheck';
        }

        $wxuser = [];
        foreach ($userlist as $value) {
            $wxuser[] = $value['wechat_userid'];
        }

        if(empty($wxuser)){
            return false;
        }

        $wx->sendNewsMsg(array(
            $wx->buildNewsItem("OA提醒", '系统中有采购项目待审核。', wx_oauth(C('WWW').U($uri)), ''),
            ), array('touser' => $wxuser), C('WECHAT_APP')['XZMS']);
    }
}
