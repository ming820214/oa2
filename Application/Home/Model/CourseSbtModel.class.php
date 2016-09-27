<?php
namespace Home\Model;

class CourseSbtModel extends CommonModel{
    /**
     * 写入一个 Course 的科目
     * @param  int    $courseId 选课的 Id
     * @param  array  $data     课程数据['teacher_id' => 1, 'subject_id' => 1]
     * @return boolen
     */
    public function write($courseId, $data){
        $this->where(['course_id' => $courseId])->delete();

        $flag = false;
        if (empty($data) || !is_array($data)) {
            return true;
        }

        foreach ($data as $value) {
            $value['course_id'] = $courseId;
            if ($this->create($value)) {
                if ($this->add()) {
                    $flag = true;
                }
            }
        }

        return $flag;
    }
}
