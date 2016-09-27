<?php
namespace Home\Model;
use Think\Model\ViewModel;

class  TeachPriceViewModel extends ViewModel {
    public $viewFields = array(
        'TeachRole' => ['uid', 'plan_id', 'subject','level'],
        'User' => ['name' => 'teacher', '_on' => 'User.id=TeachRole.uid'],
        'UnitpriceRole' => ['name' => 'plan_name', 'school', 'grade', 'course', 'price', 'level',
                                '_on' => 'UnitpriceRole.id=TeachRole.plan_id']
        );
}
