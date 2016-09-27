<?php
namespace Home\Controller;
class ConsumptionsController extends HomeController {
    private $_month = null;
    function _initialize() {
        parent::_initialize();
        $this->_month = session('date');
        $this->assign('from', strtolower(ACTION_NAME));
        $this->assign('controller', CONTROLLER_NAME);
        $this->assign('pageCount', 20);

        $this->assign('allowCheck', false);
        $this->assign('allowExport', false);
    }

    public function export(){
        $this->assign('title', '数据导出-'.get_school_name(session('school_id')).
            '-'.session('user_name'));
        $this->assign('allowExport', true);
        $this->display('consumption');
    }

    public function getList(){
        $month = session('date');
        $start = (int)$_GET['start'];
        $count = (int)$_GET['count'];
        $from  = $_GET['from'];
        $search = $_GET['search'];

        $conditionCom = array(
            'is_del' => array('eq', 0),
            'month' => $month,
            );

        $conditionCom = $this->_mergeCondition($conditionCom,
                                                    json_decode($search, true));
        if($conditionCom['student'])$conditionCom['student']=['like',$conditionCom['student'].'%'];//姓名首字母查询

        $sumCheckedPriceCondition  = $conditionCom;
        $sumCheckingPriceCondition = $conditionCom;
        $listCondition             = $conditionCom;
        $order = '`state`, `id` desc';

        switch($from){
            case 'export':
                $sumCheckingPriceCondition['state'] = array('lt',
                                            C('CONSUME_STATES')['SUCCESS']['id']);
                $sumCheckedPriceCondition['state'] = array('eq',
                                            C('CONSUME_STATES')['SUCCESS']['id']);
                $order = '`state` desc , `id` desc';
                break;

            case 'checka':
                $listCondition['emp_school'] = array('eq', session('school_id'));
                $listCondition['state'] = array('egt',
                                            C('CONSUME_STATES')['CHECK1']['id']);
                $sumCheckingPriceCondition['emp_school'] = array('eq',
                                            session('school_id'));
                $sumCheckingPriceCondition['state'] = array('eq',
                                            C('CONSUME_STATES')['CHECK1']['id']);
                $sumCheckedPriceCondition['emp_school'] = array('eq',
                                            session('school_id'));
                $sumCheckedPriceCondition['state'] = array('gt',
                                            C('CONSUME_STATES')['CHECK1']['id']);
                break;
            case 'checkb':
                $listCondition['emp_school'] = array('eq', session('school_id'));
                $listCondition['state'] = array('egt',
                                            C('CONSUME_STATES')['CHECK2']['id']);
                $sumCheckingPriceCondition['emp_school'] = array('eq',
                                            session('school_id'));
                $sumCheckingPriceCondition['state'] = array('eq',
                                            C('CONSUME_STATES')['CHECK2']['id']);
                $sumCheckedPriceCondition['emp_school'] = array('eq',
                                            session('school_id'));
                $sumCheckedPriceCondition['state'] = array('gt',
                                            C('CONSUME_STATES')['CHECK2']['id']);
                break;
            case 'checkc':
                $listCondition['state'] = array('egt',
                                            C('CONSUME_STATES')['CHECK3']['id']);
                $sumCheckingPriceCondition['state'] = array('eq',
                                            C('CONSUME_STATES')['CHECK3']['id']);
                $sumCheckedPriceCondition['state'] = array('gt',
                                            C('CONSUME_STATES')['CHECK3']['id']);
                break;
            case 'checkd':
                $listCondition['state'] = array('egt',
                                            C('CONSUME_STATES')['CHECK4']['id']);
                $sumCheckingPriceCondition['state'] = array('eq',
                                            C('CONSUME_STATES')['CHECK4']['id']);
                $sumCheckedPriceCondition['state'] = array('gt',
                                            C('CONSUME_STATES')['CHECK4']['id']);
                break;
            default:
                return false;
                break;
        }

        $Consumption = D('Consumption');
        $data = array(
            'state'    => 'ok',
            'checking' => $Consumption->sumCheckPrice($sumCheckingPriceCondition),
            'checked'  => $Consumption->sumCheckPrice($sumCheckedPriceCondition),
            'checkup'  => 0,
            'data'     => $Consumption->getList($listCondition, $start, $count,
                            $order),
            'start'    => $start,
            'count'    => $count,
            'total'    => $Consumption->where($listCondition)->count(),
        );

        $data['checkup']  = formatPrice($data['checking'] + $data['checked']);
        $data['checked']  = formatPrice($data['checked']);
        $data['checking'] = formatPrice($data['checking']);

        $this->ajaxReturn($data);
    }

    public function exportConsumptions(){
        $month = $this->_month;
        $search = $_POST;

        $condition = array(
            'is_del' => array('eq', 0),
            'month' => $month,
            );

        $condition = $this->_mergeCondition($condition, $search);

        $Consumption = D('Consumption');
        $list = $Consumption->getList($condition, 0, 100000000, '`id` desc',1);

        $excel = new \Home\Hongwen\ExportExcel();
        $head = array(
            array('id'              , '序号'),
            array('month'           , '期次'),
            array('state_str'       , '状态'),
            array('school'          , '校区'),
            array('type_str'        , '消费类型'),
            array('std_id'          , '学号'),
            array('student'         , '学员'),
            array('emp'             , '操作人'),
            array('value_format'    , '金额（￥）'),
            array('pay_no'          , '收据编号'),
            array('create_time_str' , '创建时间'),
            array('remark'          , '备注'),
            array('reason'          , '原因'),
            array('order_id'        , '课程序号'),
            array('order_plan'      , '价格方案'),
            array('order_unitprice' , '单价'),
            array('order_hour'       , '订购'),
            array('order_ext_hour'          , '赠送'),
            array('order_factor'          , '折扣'),
            array('order_used_hour'          , '已上课时'),
            array('order_std_type'          , '学员类型(1新2老)'),
            array('order_price'          , '课程费用'),
            array('order_director'          , '教学主任'),
            array('order_manager'          , '学管师'),
        );
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition:attachment; filename=学员费用数据'.date(' Y-m-d H-i-s').'.xls');
        header('Cache-Control: max-age=0');
        die($excel->export($list, $head));
    }

    public function cancel(){
        $returnData = array(
            'state' => 'error',
            'info'  => '操作失败',
        );
        if(D('Consumption')->checkBack($_POST['id'], $_POST['from'], $_POST['reason'])){
            $returnData = array(
                'state' => 'ok',
                'info'  => '操作成功',
            );
        }

        $this->ajaxReturn($returnData);

    }

    public function checka() {
        $this->assign('title', C('CONSUME_STATES')['CHECK1']['name'].'-'.get_school_name(session('school_id')).
            '-'.session('user_name'));
        $this->assign('allowCheck', true);
        $this->display('consumption');
    }

    public function checkb() {
        $this->assign('title', C('CONSUME_STATES')['CHECK2']['name'].'-'.get_school_name(session('school_id')).
            '-'.session('user_name'));
        $this->assign('allowCheck', true);
        $this->display('consumption');
    }

    public function checkc() {
        $this->assign('title', C('CONSUME_STATES')['CHECK3']['name'].'-'.get_school_name(session('school_id')).
            '-'.session('user_name'));
        $this->assign('allowCheck', true);
        $this->display('consumption');
    }

    public function checkd() {
        $this->assign('title', C('CONSUME_STATES')['CHECK4']['name'].'-'.get_school_name(session('school_id')).
            '-'.session('user_name'));
        $this->assign('allowCheck', true);
        $this->display('consumption');
    }

    public function check(){
        $returnData = array(
            'state' => 'error',
            'info'  => '审核失败',
        );

        if(D('Consumption')->check($_POST['id'], $_POST['from'])){
            $returnData = array(
                'state' => 'ok',
                'info'  => '审核成功',
            );
        }

        $this->ajaxReturn($returnData);
    }

    private function _mergeCondition($conditionCom, $condition){
        foreach($condition as $key => $value){
            if($value === ''){
                unset($condition[$key]);
            }
        }

        if(empty($condition)){
            return $conditionCom;
        }

        $condition = array_merge($conditionCom, $condition);
        foreach($condition as $key => $value){
            if($value == '_all_'){
                unset($condition[$key]);
            }
        }

        return $condition;
    }

/**
修改页面数据
*/
    public function change_consumptions($id){
        if(IS_AJSX){
            if(I('get.month')){//修改期次
                if(M('consumption')->where(['id'=>$id])->setField('month',I('get.month')))
                    $this->ajaxReturn('ok');
            }
        }
    }

// 修改某条消费信息
    public function change(){

        if(I('info')){
            if(I('info')){
                $info=M('consumption')->where(['type'=>['egt','10000'],'id'=>I('info')])->find();
                if($info){
                    $info['school']=get_school_name($info['emp_school']);
                    $info['belong']=M('user')->where(['school'=>$info['emp_school'],'is_del'=>0,'position'=>['in','10,11,12,13,18,19']])->getField('id,name');
                }
                $this->ajaxReturn($info);
            }
        }elseif(IS_POST) {
            $mod=M('consumption');
            $mod->create();
            $mod->belong_user_name=get_user_name(I('post.belong_user'));
            // var_dump($mod);
            // die;
            if($mod->save())$this->success('修改成功……');
        }else{
            $this->display();
        }
    }

}
