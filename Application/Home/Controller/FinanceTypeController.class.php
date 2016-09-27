<?php
namespace Home\Controller;

class FinanceTypeController extends HomeController {
    protected $config = array('app_type' => 'master','admin'=>'FinanceType');

    public function index() {
        $node = M("FinanceType");
        if (!empty($_POST['eq_pid'])) {
            $eq_pid = $_POST['eq_pid'];
        } elseif (!empty($_GET['eq_pid'])) {
            $eq_pid = $_GET['eq_pid'];
        } else {
            $eq_pid = $node -> where('pid=0') -> order('sort asc') -> getField('id');
        }

        $this -> assign('eq_pid', $eq_pid);

        $list = $node -> where('pid=0') -> order('sort asc') -> getField('id,name');
        $this -> assign('groupList', $list);

        $menu = array();
        $menu = $node -> field('id,pid,name') -> order('sort asc') -> select();
        $tree = $this->list_to_tree($menu, $eq_pid);

        $model = M("FinanceType");
        $list = $model -> order('sort asc') -> getField('id,name');
        $this -> assign('node_list', $list);
        $this -> assign('menu', $this->popup_tree_menu($tree));
        $this -> display();
    }

    public function read($id){
        $model = M('FinanceType');
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

    protected function _insert($name='FinanceType') {
        $model = D('FinanceType');
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

    protected function _update($name='FinanceType'){
        $id = $_POST['id'];
        $model = D("FinanceType");
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

    function winpop() {
        $menu = D("FinanceType") -> order('sort asc') -> select();
        $tree = $this->list_to_tree($menu);
        $this -> assign('menu', $this->popup_tree_menu($tree));
        $this -> display();
    }

    function del($id){
        $where['pid']=array('eq',$id);
        $list=M("FinanceType")->where($where)->select();

        if($list){
            $this->error('有子节点不能删除');
        }
        $model = M("RoleNode");
        $where['node_id'] = $id;
        $model -> where($where) -> delete();
        $this -> _destory($id);
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
                    if (isset($val['_child'])) {
                        $html = $html . "<li>\r\n<a data-level=\"{$level}\" class=\"$del_class\" node=\"$id\" ><i class=\"fa fa-angle-right level$level\"></i><span>$title</span></a>\r\n";
                        $html = $html . $this->popup_tree_menu($val['_child'], $level);
                        $html = $html . "</li>\r\n";
                    } else {
                        $html = $html . "<li>\r\n<a data-level=\"{$level}\" class=\"$del_class\" node=\"$id\" ><i class=\"fa fa-angle-right level$level\"></i><span>$title</span></a>\r\n</li>\r\n";
                    }
                }
            }
            $html = $html . "</ul>\r\n";
        }
        return $html;
    }

    function depts() {
        $plugin['jquery-ui'] = true;
        $this -> assign("plugin", $plugin);
        $model = M("Dept");
        $list = array();
        $list = $model -> where('is_del=0') -> field('id,pid,name') -> order('sort asc') -> select();
        $list = list_to_tree($list);
        $this -> assign('list_dept', popup_tree_menu($list));
        $this -> assign('xid', $_GET['xid']);
        $this -> assign('xname', $_GET['xname']);
        $this -> display();
        return;
    }
}
