<?php
/*--------------------------------------------------------------------
 鸿文OA系统 - 让工作更轻松快乐
--------------------------------------------------------------*/

namespace Home\Controller;

class IndexController extends HomeController {
	protected $config = array('app_type' => 'public');
	//过滤查询字段

	public function index() {
		$plugin['jquery-ui'] = true;
		$this -> assign("plugin", $plugin);

		cookie("current_node", null);
		cookie("top_menu", null);

		$this -> assign("home_sort", $config['home_sort']);

		$this -> display();
	}
}
?>
