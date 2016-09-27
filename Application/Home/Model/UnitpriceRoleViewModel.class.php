<?php
namespace Home\Model;
use Think\Model\ViewModel;

class  UnitpriceRoleViewModel extends ViewModel {
    public $viewFields = array(

        'UnitpriceRole'   => ['id', 'name','label','count','rule','school','grade', 'course', 'level', 'factor','price', 'is_join', 'is_del'],
        'FooInfo'         => ['name' => 'school_name', '_as' => 'F1','_on' => 'F1.id=UnitpriceRole.school'],
        'FooInfo '        => ['name' => 'grade_name', '_as' => 'F2','_on'  => 'F2.id=UnitpriceRole.grade','_type' => 'left'],
        'FooInfo  '       => ['name' => 'level_name', '_as' => 'F3','_on'  => 'F3.id=UnitpriceRole.level'],
        'FooInfo   '      => ['group'=>'course_group','name' => 'course_name','_as' => 'F4','_on' => 'F4.id=UnitpriceRole.course'],

    );

}
