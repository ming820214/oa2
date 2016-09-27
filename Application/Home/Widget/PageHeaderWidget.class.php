<?php
namespace Home\Widget;
use Think\Controller;
class PageHeaderWidget extends Controller {
	protected $config = array('app_type' => 'public');

	public function simple($name) {
		$this -> assign('name', $name);
		$this -> display('Widget:PageHeader/simple');
	}

	public function finance($name) {
		$this -> assign('name', $name);
		$this -> display('Widget:PageHeader/finance');
	}

	public function search($name) {
		$this -> assign('name', $name);
		$this -> display('Widget:PageHeader/search');
	}
	
	public function search_definition($name) {
		$this -> assign('name', $name);
		$this -> display('Widget:PageHeader/search_definition');
	}

	public function search_ajax($name) {
		$this -> assign('name', $name);
		$this -> display('Widget:PageHeader/search_ajax');
	}
	
	public function search_select($name,$option) {
		$this -> assign('name', $name);
		$this -> assign('option', $option);
		$this -> display('Widget:PageHeader/search_select');
	}

	public function adv_search($name) {
		$this -> assign('name', $name);
		$this -> display('Widget:PageHeader/adv_search');
	}

	public function local_search($name) {
		$this -> assign('name', $name);
		$this -> display('Widget:PageHeader/local_search');
	}

	public function popup($name, $showClear = false) {
		$this -> assign('name', $name);
		$this -> assign('showClear', $showClear);
		$this -> display('Widget:PageHeader/popup');
	}
}
?>
