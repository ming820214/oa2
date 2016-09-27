<?php
namespace Home\Model;
use Think\Model\ViewModel;

class  CourseSbtViewModel extends ViewModel {
    public $viewFields = array(
        'CourseSbt'        => ['id', 'course_id', 'teacher_id', 'subject_id', '_type' => 'left'],
        // 这里键值加空格原因http://www.thinkphp.cn/topic/31628.html
        'User'          => ['name' => 'teacher_name', '_as' => 'u1',
                                            '_on' => 'u1.id=CourseSbt.teacher_id', '_type' => 'left'],
        'FooInfo'       => ['name' => 'subject_name',
                                            '_on' => 'FooInfo.id=CourseSbt.subject_id', '_type' => 'left'],
        );

    /**
     * 获取某个 Course 的科目和老师信息
     * @param  int $courseId
     * @return array
     */
    public function getSbt($courseId) {
        return $this->where(['CourseSbt.course_id' => $courseId])->select();
    }
}
