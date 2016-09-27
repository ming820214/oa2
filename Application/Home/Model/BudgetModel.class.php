<?php
namespace Home\Model;

class BudgetModel extends CommonModel{
    protected $dbName = 'hw003';
    protected $tablePrefix = 'money_';

    public function writePurchase2Budget($id){
        $purchase = D('Purchase');
        $one = $purchase->getOne($id);
        $one['apply_school_str'] = get_school_name($one['apply_school']);
        $one['receive_school_str'] = get_school_name($one['receive_school']);
        $one['belong_str'] = get_school_name($one['belong']);
        $one['class'] = 1;
        // $one['count'] = 1;
        $one['apply_time_str'] = ($one['apply_time'] > 10000) ? date('Y-m-d', $one['apply_time']) : '';
        $one['purchase_time_str'] = ($one['purchase_time'] > 10000) ? date('Y-m-d', $one['purchase_time']) : '';
        $one['week'] = '';
        $one['tel'] = '';
        $one['bm'] = get_dept_name($one['dept_id']).'+'.get_dept_name($one['dept_id2']);
        $one['detail'] = $one['cost_project'].'|'.$one['name'];
        $one['state'] = 4; // 直接进入财务确认
        $one['time5'] = '';
        // $one['timestamp'] = '';
        // $one['priced'] = $one['price'];
        // $one['remarks'] .= '>>>包含物流费用:'.$one['take_cost1'];
        $one['dept_id3'] = 0;

        if($one['priced'] >= 3000){
            $one['bm'] .= '+总裁';
            $one['dept_id3'] = 1;
            $one['state'] = 3.5; // 需要总裁审核
        }

        $map = array(
            'purchase_id'   => 'id',
            'school'    => 'apply_school_str',
            'class'     => 'class',
            'time'      => 'apply_time_str',
            'date'      => 'month',
            'week'      => 'week',
            'name'      => 'apply_user',
            'tel'       => 'tel',
            'jsr'       => 'receiver',
            'jsxq'      => 'receive_school_str',
            'card'      => 'card_num',
            'why'       => 'reason',
            'bm'        => 'bm',
            'gs'        => 'belong_str',
            'aa'        => 'cost_type',
            'b'         => 'cost_type2',
            'c'         => 'detail',
            'd'         => 'unit_price',
            'e'         => 'count',
            'g'         => 'purchase_time_str',
            'state'     => 'state',
            'other'     => 'remarks',
            'time5'     => 'time5',
            'dept_id'   => 'dept_id',
            'dept_id2'  => 'dept_id2',
            'dept_id3'  => 'dept_id3',
            // 'timestamp' => 'timestamp',
            'record'    => 'log',
        );

        $data = array();
        foreach($map as $key => $value) {
            $data[$key] = $one[$value];
        }
        // print_r($data);die;

        if($this->create($data)){
            $addId = $this->add();

            if($addId){
                $this->_inform($addId);
                return true;
            }
        }

        return false;
    }

    /**
     * 通知对应的人, 现在只是通知部门审核
     * @param  int $id 记录 id
     */
    private function _inform($id) {
        $user = D('User');
        $budget = $this->find($id);

        if($budget['dept_id3']==1){
            $userlist = ['AAA'];
            $uri = 'Public/log_wx?urll=budget/check&cc=3';
        }else{
            $userlist = ['CW001'];
            $uri = 'Public/log_wx?urll=budget/check&cc=4';
        }

		$wx = getWechatObj();
        $wx->sendNewsMsg(array(
            $wx->buildNewsItem("OA提醒", '系统中有预算项目待审核。', wx_oauth(C('WW').U($uri)), ''),
            ), array('touser' => [$userlist]), C('WECHAT_APP')['XZMS']);

    }
}
