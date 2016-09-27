<?php
namespace Home\Controller;

class UnitpriceRoleController extends HomeController {
    protected $config = array('app_type' => 'master','admin'=>'UnitpriceRole');

    public function index() {
        $w['school']=0;
        if(I('school'))$w['school']=I('school');
        $node = M("UnitpriceRole");
        $this -> assign('eq_pid', 0);

        $menu = array();
        $menu = $node -> where($w) -> field('id,name,is_del') -> order('school asc, course asc, grade asc, level asc, subject asc') -> select();
        $tree = $this->list_to_tree($menu, 0);

        $model = M("UnitpriceRole");
        $list = $model -> where($w) -> order('school asc, course asc, grade asc, level asc, subject asc') -> getField('id,name');
        $this -> assign('node_list', $list);
        $this -> assign('menu', $this->popup_tree_menu($tree));

        $this->assign('schoolList', C('SCHOOL'));
        $this->assign('gradeList', C('SCHOOL_GRADE'));
        $this->assign('courseList', C('SCHOOL_COURSE'));
        $this->assign('subjectList', C('SCHOOL_SUBJECT'));
        $this->assign('teacherLevelList', C('SCHOOL_TEACHER_LEVEL'));

        $this -> display();
    }

    public function add(){
        $node = M("UnitpriceRole");
        $list = $node -> order('sort asc') -> getField('id,name');
        $this -> assign('groupList', $list);
        $this->assign('schoolList', C('SCHOOL'));
        $this->assign('gradeList', C('SCHOOL_GRADE'));
        $this->assign('courseList', C('SCHOOL_COURSE'));
        $this->assign('subjectList', C('SCHOOL_SUBJECT'));
        $this->assign('teacherLevelList', C('SCHOOL_TEACHER_LEVEL'));

        $this->display();
    }

    public function read($id){
        $model = M('UnitpriceRole');
        $vo = $model -> find($id);
        if($vo['dept_id']){
            $vo['dept_name'] = get_dept_name($vo['dept_id']);
        }

        if($vo['dept_id2']){
            $vo['dept_name2'] = get_dept_name($vo['dept_id2']);
        }

        if (IS_AJAX) {
            if ($vo !== false) {// 读取成功
                $return['data'] = $vo;
                $return['status'] = 1;
                $return['info'] = "读取成功";
                $this -> ajaxReturn($return);
            } else {
                $return['status'] = 0;
                $return['info'] = "读取错误";
                $this -> ajaxReturn($return);
            }
        }
        $this -> assign('vo', $vo);
        $this -> display();
        return $vo;
    }

    protected function _insert($name='UnitpriceRole') {
        $model = D('UnitpriceRole');
        if (false === $model -> create()) {
            $this -> error($model -> getError());
        }
        if (strpos($model -> url, '##') !== false) {
            $model -> sub_folder = ucfirst(get_controller(str_replace("##", "", $model -> url))) . "Folder";
        } else {
            $model -> sub_folder = '';
        }
        //保存当前数据对象
        $list = $model -> add();
        if ($list !== false) {//保存成功
            $this -> assign('jumpUrl', get_return_url());
            $this -> success('新增成功!');
        } else {
            //失败提示
            $this -> error('新增失败!');
        }
    }

    protected function _update($name='UnitpriceRole'){
        $id = $_POST['id'];
        $model = D("UnitpriceRole");
        if (false === $model -> create()) {
            $this -> error($model -> getError());
        }
        if (strpos($model -> url, '##') !== false) {
            $model -> sub_folder = ucfirst(get_controller(str_replace("##", "", $model -> url))) . "Folder";
        } else {
            $model -> sub_folder = '';
        }
        // 更新数据
        $list = $model -> save();
        if (false !== $list) {
            //成功提示
            $this -> assign('jumpUrl', get_return_url());
            $this -> success('编辑成功!');
        } else {
            //错误提示
            $this -> error('编辑失败!');
        }
    }


    function del($id){
        $this -> _del($id);
    }

    function list_to_tree($list, $root = 0, $pk = 'id', $pid = 'pid', $child = '_child') {
        // 创建Tree
        $tree = array();
        if (is_array($list)) {
            // 创建基于主键的数组引用
            $refer = array();
            foreach ($list as $key => $data) {
                $refer[$data[$pk]] = &$list[$key];
            }
            foreach ($list as $key => $data) {
                // 判断是否存在parent
                $parentId = 0;
                if (isset($data[$pid])) {
                    $parentId = $data[$pid];
                }
                if ((string)$root == $parentId) {
                    $tree[] = &$list[$key];
                } else {
                    if (isset($refer[$parentId])) {
                        $parent = &$refer[$parentId];
                        $parent[$child][] = &$list[$key];
                    }
                }
            }
        }
        return $tree;
    }

    public function popup_tree_menu($tree, $level = 0) {
        $level++;
        $html = "";
        if (is_array($tree)) {
            $html = "<ul class=\"tree_menu\">\r\n";
            foreach ($tree as $val) {
                if (isset($val["name"])) {
                    $title = $val["name"];
                    $id = $val["id"];
                    if (empty($val["id"])) {
                        $id = $val["name"];
                    }
                    if (!empty($val["is_del"])) {
                        $del_class = "is_del";
                    } else {
                        $del_class = "";
                    }
                    $del = '';
                    $del2 = '';
                    if($val['is_del']){
                        $del = ' style="display:none"';
                    }
                    if (isset($val['_child'])) {
                        $html = $html . "<li{$del}>\r\n<a data-level=\"{$level}\" class=\"$del_class\" node=\"$id\" >#{$id}<i class=\"fa fa-angle-right level$level\"></i><span>$title</span></a>\r\n";
                        $html = $html . $this->popup_tree_menu($val['_child'], $level);
                        $html = $html . "</li>\r\n";
                    } else {
                        $html = $html . "<li{$del}>\r\n<a data-level=\"{$level}\" class=\"$del_class\" node=\"$id\" ><i class=\"fa fa-angle-right level$level\"></i><span>$title</span></a>\r\n</li>\r\n";
                    }
                }
            }
            $html = $html . "</ul>\r\n";
        }
        return $html;
    }
}
