<?php
namespace Home\Model;
use Think\Model\RelationModel;

class WeihuAdviceModel extends RelationModel{
	
	protected $tableName="weihu_advice";
	protected $tablePrefix="oa_";
	protected $dbName="hongwen_oa"; 
	
	//relation_deep 参数是对深层关联的一个参数，当该参数为TRUE的时候，或者非空的情况下，该关联会进行无限关联，直到查不到相应关联数据为止。当relation_deep为FALSE或者默认状态的情况下，该关联只查询一层关联
	protected $_link = array(
			'WeihuAdvice' => array(
						    'mapping_type'=>self::HAS_ONE,
							'mapping_name'=>'WeihuAdvice',
							'class_name' => 'WeihuAdvice',
							'parent_key' => 'pid',
							'relation_deep' => TRUE 
							)
							
							
	);
}