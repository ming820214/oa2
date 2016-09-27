<?php
/*--------------------------------------------------------------------
 鸿文OA系统 - 让工作更轻松快乐
--------------------------------------------------------------*/

namespace Home\Controller;

class PositionController extends HomeController {
	protected $config = array('app_type' => 'master');

	function _search_filter(&$map) {
		$keyword = I('keyword');
		if (!empty($keyword)) {
			$map['position_no|name'] = array('like', "%" . $keyword . "%");
		}
	}

	function del() {
		$id = $_POST['id'];
		$this -> _destory($id);
	}

}
