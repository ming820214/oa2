<?php
namespace Home\Model;
use Think\Model\RelationModel;

class FankuiModel extends RelationModel{
	
	protected $dbName = 'hw001';
	protected $tableName = 'fankui';
	protected $tablePrefix = '';
	
	protected $_link=array(
		'class_set'=> array(
				'mapping_type'=>self::BELONGS_TO,
				'class_name'=>'class',
				'foreign_key'=>'cid',
				'mapping_name'=>'class_set',
		),
		'student_set'=>array(
			'mapping_type' =>self::BELONGS_TO,
			'class_name' => 'students',
			'foreign_key' => 'stuid',
			'mapping_name' => 'student_set'
		)
	);
}
