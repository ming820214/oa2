<?php
namespace Home\Controller;
class PurchaseController extends HomeController {
    function _initialize() {
        parent::_initialize();
        $this->assign('from', strtolower(ACTION_NAME));
        $this->assign('pageCount', 50);
        $this->assign('showApplyFrom', false);
        $this->assign('allowAdmin', false);
        $this->assign('allowCheck', false);
        $this->assign('allowExport', false);
        $this->assign('allowTrace', false);
    }

    public function index() {
        // $this->display();
    }

    public function apply(){
        die;
        $this->assign('title', '采购申请-'.get_school_name(session('school_id')).
            '-'.session('user_name'));
        $this->assign('showApplyFrom', true);
        $this->assign('allowAdmin', true);
        $this->display('purchase');
    }

    public function schoolcheck(){
        $this->assign('title', '校区审核-'.get_school_name(session('school_id')).
            '-'.session('user_name'));
        $this->assign('allowAdmin', true);
        $this->assign('allowCheck', true);
        $this->display('purchase');
    }

    public function deptcheck(){
        $this->assign('title', '部门审核-'.get_school_name(session('school_id')).
            '-'.session('user_name'));
        $this->assign('allowAdmin', true);
        $this->assign('allowCheck', true);
        $this->display('purchase');
    }

    public function deptcheck2(){
        $this->assign('title', '总裁审核-'.get_school_name(session('school_id')).
            '-'.session('user_name'));
        $this->assign('allowAdmin', true);
        $this->assign('allowCheck', true);
        $this->display('purchase');
    }

    public function groupcheck(){
        $this->assign('title', '审核确认-'.get_school_name(session('school_id')).
            '-'.session('user_name'));
        $this->assign('allowAdmin', true);
        $this->assign('allowCheck', true);
        $this->display('purchase');
    }

    public function trace(){
        $this->assign('title', '采购跟踪-'.get_school_name(session('school_id')).
            '-'.session('user_name'));
        $this->assign('allowTrace', true);

        $this->display();
    }

    public function confirm() {
        $this->assign('title', '确认收货-'.get_school_name(session('school_id')).
            '-'.session('user_name'));
        $this->display();
    }

    public function export(){
        $this->assign('title', '数据导出-'.get_school_name(session('school_id')).
            '-'.session('user_name'));
        $this->assign('allowAdmin', true);
        $this->assign('allowExport', true);
        $this->display('purchase');
    }

    public function exportExcel(){
        $month = session('date');
        $search = $_POST;

        $condition = array(
            'is_del' => array('eq', 0),
            'month' => $month,
            );

        $condition = $this->_mergeCondition($condition, $search);

        $purchase = D('Purchase');
        $list = $purchase->getList($condition, 0, 1000000, '`id` desc');

        $excel = new \Home\Hongwen\ExportExcel();
        $head = array(
            array('id'                 , '序号'),
            array('state_str'          , '状态'),
            array('month'              , '期次'),
            array('belong_str'         , '归属校区'),
            array('receive_school_str' , '接收校区'),
            array('receiver'           , '接收人'),
            array('apply_time_str'     , '申请日期'),
            array('purchase_time_str'  , '拟定采购日期'),
            array('type'               , '采购类型'),
            array('cost_type'          , '成本类型'),
            array('cost_type2'         , '二级科目'),
            array('cost_project'       , '支出项目'),
            array('name'               , '物品名称'),
            array('brand'              , '品牌'),
            array('model'              , '型号/规格'),
            array('unit_price'         , '单价(￥)'),
            array('count'              , '数量'),
            array('unit'               , '单位'),
            array('price'              , '合计(￥)'),
            array('researcher'         , '调研人'),
            array('research'           , '价格调研情况'),
            array('price_time_str'     , '价格更新日期'),
            array('research_place'     , '调研地点'),
            array('apply_user'         , '申请人员'),
            array('apply_school_str'   , '申请校区'),
            array('track'              , '物流信息'),
            array('back_reason'        , '回退原因'),
            array('remarks'            , '备注'),
        );
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition:attachment; filename='
            .CONTROLLER_NAME.date(' Y-m-d H-i-s').'.xls');
        header('Cache-Control: max-age=0');
        die($excel->export($list, $head));
    }


    public function check(){
        $returnData = array(
            'state' => 'error',
            'info'  => '审核失败',
        );
        $purchase = D('Purchase');
        if($purchase->check($_POST['id'], $_POST['from'])){
            $returnData = array(
                'state' => 'ok',
                'info'  => '审核成功',
            );
        }

        $this->ajaxReturn($returnData);
    }

    public function checkBack(){
        $returnData = array(
            'state' => 'error',
            'info'  => '回退失败',
        );
        $purchase = D('Purchase');
        if($purchase->check($_POST['id'], $_POST['from'], true)){
            $returnData = array(
                'state' => 'ok',
                'info'  => '回退成功',
            );
        }

        $this->ajaxReturn($returnData);

    }

    public function add(){
        $returnData = array(
            'state' => 'error',
            'info'  => '保存失败',
        );

        $purchase = D('Purchase');
        if($purchase->addOne($_POST)){
            $returnData = array(
                'state' => 'ok',
                'info'  => '保存成功',
            );
        }

        $this->ajaxReturn($returnData);
    }

    public function save(){
        if(!$_POST['id']){
            return $this->add();
        }

        $returnData = array(
            'state' => 'error',
            'info'  => '保存失败',
        );

        $purchase = D('Purchase');
        if($purchase->saveOne($_POST)){
            $returnData = array(
                'state' => 'ok',
                'info'  => '保存成功',
            );
        }

        $this->ajaxReturn($returnData);
    }

    public function del(){
        $returnData = array(
            'state' => 'error',
            'info'  => '删除失败',
        );
        $purchase = D('Purchase');
        if($purchase->del($_POST['id'])){
            $returnData = array(
                'state' => 'ok',
                'info'  => '删除成功',
            );
        }

        $this->ajaxReturn($returnData);
    }

    public function getOne(){
        $returnData = array(
            'state' => 'error',
            'info'  => '获取内容失败',
        );

        $purchase = D('Purchase');
        $one = $purchase->getOne((int)$_GET['id']);
        if($one){
            $returnData = array(
                'state' => 'ok',
                'data'  => $one,
            );
        }
        $this->ajaxReturn($returnData);
    }

    public function getList(){
        $month = session('date');
        $start = (int)$_GET['start'];
        $count = (int)$_GET['count'];
        $from  = $_GET['from'];
        $search = $_GET['search'];

        $order = '`id` desc';
        $conditionCom = array(
            'is_del' => array('eq', 0),
            'month' => $month,
            );

        $conditionCom = $this->_mergeCondition($conditionCom,
                                                    json_decode($search, true));

        $sumCheckedPriceCondition  = $conditionCom;
        $sumCheckingPriceCondition = $conditionCom;
        $listCondition             = $conditionCom;
        $order = '`state`, `id` desc';

        switch($from){
            case 'apply':
                $listCondition['apply_school'] = array('eq', session('school_id'));
                if(get_school_name()=='集团')$listCondition['emp_no'] = session('emp_no');
                $sumCheckingPriceCondition['emp_no'] = session('emp_no');
                $sumCheckedPriceCondition['emp_no'] = session('emp_no');
                $sumCheckingPriceCondition['state'] = array('lt',
                                            getPurchaseState('SUCCESS')['id']);
                $sumCheckedPriceCondition['state'] = array('eq',
                                            getPurchaseState('SUCCESS')['id']);
                break;
            case 'schoolcheck':
                $listCondition['apply_school'] = array('eq', session('school_id'));
                $listCondition['state'] = array('egt',
                                            getPurchaseState('IN_SCHOOL')['id']);
                $sumCheckingPriceCondition['apply_school'] = array('eq',
                                            session('school_id'));
                $sumCheckingPriceCondition['state'] = array('eq',
                                            getPurchaseState('IN_SCHOOL')['id']);
                $sumCheckedPriceCondition['apply_school'] = array('eq',
                                            session('school_id'));
                $sumCheckedPriceCondition['state'] = array('gt',
                                            getPurchaseState('IN_SCHOOL')['id']);
                break;
            case 'deptcheck':
                $listCondition['dept_id'] = array('eq', session('dept_id'));
                $listCondition['state'] = array('egt',
                                            getPurchaseState('IN_DEPT')['id']);
                $sumCheckingPriceCondition['dept_id'] = array('eq', session('dept_id'));
                $sumCheckingPriceCondition['state'] = array('eq',
                                            getPurchaseState('IN_DEPT')['id']);
                $sumCheckedPriceCondition['dept_id'] = array('eq', session('dept_id'));
                $sumCheckedPriceCondition['state'] = array('gt',
                                            getPurchaseState('IN_DEPT')['id']);
                break;
            case 'deptcheck2':
                $listCondition['dept_id2'] = array('eq', session('dept_id'));
                $listCondition['state'] = array('egt',
                                            getPurchaseState('IN_DEPT2')['id']);
                $sumCheckingPriceCondition['dept_id2'] = array('eq', session('dept_id'));
                $sumCheckingPriceCondition['state'] = array('eq',
                                            getPurchaseState('IN_DEPT2')['id']);
                $sumCheckedPriceCondition['dept_id2'] = array('eq', session('dept_id'));
                $sumCheckedPriceCondition['state'] = array('gt',
                                            getPurchaseState('IN_DEPT2')['id']);
                break;
            case 'groupcheck':
                $listCondition['state'] = array('egt',
                                            getPurchaseState('IN_GROUP')['id']);
                $sumCheckingPriceCondition['state'] = array('eq',
                                            getPurchaseState('IN_GROUP')['id']);
                $sumCheckedPriceCondition['state'] = array('gt',
                                            getPurchaseState('IN_GROUP')['id']);
                break;
            case 'trace':
                $listCondition['state'] = array('egt',
                                            getPurchaseState('IN_SCHOOL')['id']);
                $sumCheckingPriceCondition['state'] = array('lt',
                                            getPurchaseState('SUCCESS')['id']);
                $sumCheckedPriceCondition['state'] = array('eq',
                                            getPurchaseState('SUCCESS')['id']);
                break;
            case 'confirm':
                $order = '`take_state`, `state`, `id` desc';
                $listCondition['emp_no'] = session('emp_no');
                $sumCheckingPriceCondition['emp_no'] = session('emp_no');
                $sumCheckedPriceCondition['emp_no'] = session('emp_no');
                $listCondition['state'] = array('egt',
                                            getPurchaseState('IN_SCHOOL')['id']);
                $sumCheckingPriceCondition['state'] = array('lt',
                                            getPurchaseState('SUCCESS')['id']);
                $sumCheckedPriceCondition['state'] = array('eq',
                                            getPurchaseState('SUCCESS')['id']);
                break;
            case 'export':
                $sumCheckingPriceCondition['state'] = array('lt',
                                            getPurchaseState('SUCCESS')['id']);
                $sumCheckedPriceCondition['state'] = array('eq',
                                            getPurchaseState('SUCCESS')['id']);
                break;

            default:
                return false;
                break;
        }

        $purchase = D('Purchase');
        $data = array(
            'state'    => 'ok',
            'checking' => $purchase->sumPrice($sumCheckingPriceCondition),
            'checked'  => $purchase->sumPrice($sumCheckedPriceCondition),
            'checkup'  => 0,
            'data'     => $purchase->getList($listCondition, $start, $count,
                            $order),
            'start'    => $start,
            'count'    => $count,
            'total'    => $purchase->where($listCondition)->count(),
        );
        $data['checkup']  = formatPrice($data['checking'] + $data['checked']);
        $data['checked']  = formatPrice($data['checked']);
        $data['checking'] = formatPrice($data['checking']);

        $this->ajaxReturn($data);
    }

    private function _getState($key){
        $purchaseStates = C('PURCHASE_STATES');

        return $purchaseStates[$key];
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
}
