<?php
namespace Home\Controller;

class ProcessRuleController extends HomeController {
    protected $config = array('app_type' => 'master','admin'=>'FinanceType');

    public function index() {
        $node = M("Department");
        $menu = array();
        $menu = $node->where(["is_del"=>0]) -> field('id,pid,name,is_del,dept_grade') -> order('sort asc') -> select();
        $tree = list_to_tree($menu);
        $this -> assign('menu', popup_tree_menu($tree));
        
        $model = M("Department");
        $list = $model -> order('sort asc') -> getField('id,name');
        $this -> assign('dept_list', $list);
        
        $this -> display();
    }

    public function read($id){
        $model = M('ProcessRule');
        $vo = $model->where(['dept_id'=>$id])->select();
        
        $result="";
        foreach($vo as $k=>$v){
            $result->dept_id = $v['dept_id'];
            $result->rank_id[$k]=$v['rank_id'];
            $result->remark = $v['remark'];
            $result->is_del = $v['is_del'];
        }
        if (IS_AJAX) {
            if ($vo !== false) {// 读取成功
                $return['data'] = $result;
                $return['status'] = 1;
                $return['info'] = "读取成功";
                $this -> ajaxReturn($return);
            } else {
                $return['status'] = 0;
                $return['info'] = "读取错误";
                $this -> ajaxReturn($return);
            }
        }
        $this -> assign('vo', $result);
        $this -> display();
        return $vo;
    }

    protected function _insert($name='ProcessRule') {
        $model = D('ProcessRule');
        if (false === $model -> create()) {
            $this -> error($model -> getError());
        }
        if (strpos($model -> url, '##') !== false) {
            $model -> sub_folder = ucfirst(get_controller(str_replace("##", "", $model -> url))) . "Folder";
        } else {
            $model -> sub_folder = '';
        }
        
        $deptId = $model->dept_id;
        $remark = $model->remark;
        $ranks = $_POST['ranks'];
        $list = false;
        
        $model->where(["dept_id"=>$deptId])->delete();
        
        //保存当前数据对象
        foreach($ranks as $k=>$v){
            $model->rank_id = $v;
            $model->rank_name = get_config('ORG_RANK')[$v];
            $model->phase = $k+1;
            $model->dept_id = $deptId;
            $model->remark = $remark;
            
            $list = $model -> add();
        }
       
        if ($list !== false) {//保存成功
            
            $this -> assign('jumpUrl', get_return_url());
            $this -> success('新增成功!');
        } else {
            //失败提示
            $this -> error('新增失败!');
        }
    }

    protected function _update($name='ProcessRule'){
        //$id = $_POST['id'];
        $model = D("ProcessRule");
        if (false === $model -> create()) {
            $this -> error($model -> getError());
        }
        if (strpos($model -> url, '##') !== false) {
            $model -> sub_folder = ucfirst(get_controller(str_replace("##", "", $model -> url))) . "Folder";
        } else {
            $model -> sub_folder = '';
        }
        
        $deptId = $model->dept_id;
        $remark = $model->remark;
        $ranks = $_POST['ranks'];
        $list = false;
        //保存当前数据对象
        foreach($ranks as $k=>$v){
            $model->rank_id = $v;
            $model->rank_name = get_config('ORG_RANK')[$v];
            $model->phase = $k+1;
            $model->dept_id = $deptId;
            $model->remark = $remark;
            
            $list = $model -> save();
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
                        $html = $html . "<li>\r\n<a data-level=\"{$level}\" class=\"$del_class\" node=\"$id\" grade=\"$level\"><i class=\"fa fa-angle-right level$level\"></i><span>$title</span></a>\r\n";
                        $html = $html . $this->popup_tree_menu($val['_child'], $level);
                        $html = $html . "</li>\r\n";
                    } else {
                        $html = $html . "<li>\r\n<a data-level=\"{$level}\" class=\"$del_class\" node=\"$id\" grade=\"$level\"><i class=\"fa fa-angle-right level$level\"></i><span>$title</span></a>\r\n</li>\r\n";
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
