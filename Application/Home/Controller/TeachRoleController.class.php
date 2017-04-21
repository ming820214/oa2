<?php
namespace Home\Controller;
class TeachRoleController extends HomeController {
    private $_month = null;
    function _initialize() {
        parent::_initialize();
        $this->assign('controller', CONTROLLER_NAME);
    }

    public function index($uid){
        $this->assign('teacherName', M('User')->where("id={$uid}")->getField('name'));
        $this->assign('planList', M('UnitpriceRole')->where("is_del=0 AND displays='0'")->field('id,school,name')->select());
        $this->assign('subjectList', C('SCHOOL_SUBJECT'));
        $this->assign('uid', $uid);
        $this->display();
    }


    public function add(){
        $returnData = array(
            'state' => 'error',
            'info'  => '添加失败',
        );

        $TeachRole = D('TeachRole');
        if($TeachRole->hasOne($_POST['uid'], $_POST['plan_id'], $_POST['subject'])){
            $returnData = array(
                'state' => 'error',
                'info'  => '此方案已经添加',
            );
        }elseif($TeachRole->addOne($_POST)){
            $returnData = array(
                'state' => 'ok',
                'info'  => '添加成功',
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

        if(D('TeachRole')->saveOne($_POST)){
            $returnData = array(
                'state' => 'ok',
                'info'  => '保存成功',
            );
        }

        $this->ajaxReturn($returnData);
    }

    public function getList(){
        $order = '`id` desc';
        $listCondition = array(
            'oa_teach_role.is_del' => array('eq', 0),
            'oa_teach_role.uid' => array('eq', (int)$_GET['uid']),
            );

        $TeachRole = D('TeachRole');
        $list = $TeachRole->getList($listCondition, $order);
        foreach ($list as &$value) {
            $value['subject_name'] = getSubjectNameById($value['subject'])['name'];
        }
        $data = array(
            'state'    => 'ok',
            'data'     => $list,
        );

        $this->ajaxReturn($data);
    }

    public function getone(){
        $returnData = array(
            'state' => 'error',
            'info'  => '获取内容失败',
        );

        $TeachRole = D('TeachRole');
        $one = $TeachRole->getOne((int)$_GET['id']);
        if($one){
            $returnData = array(
                'state' => 'ok',
                'data'  => $one,
            );
        }
        $this->ajaxReturn($returnData);
    }

    public function delOne(){
        $returnData = array(
            'state' => 'error',
            'info'  => '删除失败',
        );
        $TeachRole = D('TeachRole');
        if($TeachRole->delOne($_POST['id'])){
            $returnData = array(
                'state' => 'ok',
                'info'  => '删除成功',
            );
        }

        $this->ajaxReturn($returnData);
    }


}
