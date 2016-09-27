<?php
/*---------------------------------------------------------------------------
  鸿文OA系统 - 让工作更轻松快乐

  Copyright (c) 2013 http://i.ihongwen.com All rights reserved.


  Author:

  Support: https://git.oschina.net/smeoa/xiaowei
 -------------------------------------------------------------------------*/


// 节点模型
namespace Home\Model;
use Think\Model;

class  PositionModel extends CommonModel {
	protected $_validate	=	array(
		array('name','checkNode','节点已经存在',0,'callback'),
		);

	public function checkNode() {
		$map['name']	 =	 $_POST['name'];
		$map['pid']	=	isset($_POST['pid'])?$_POST['pid']:0;

        if(!empty($_POST['id'])) {
			$map['id']	=	array('neq',$_POST['id']);
        }
		$result	=	$this->where($map)->field('id')->find();
        if($result) {
        	return false;
        }else{
			return true;
		}
	}
}
