<?php
namespace Home\Model;
/**
 * 已排课表的数据
 */
class ArrangedCourseModel extends CommonModel{
    protected $dbName = 'hw001';
    protected $trueTableName = 'class';
    private $_states = [
        'UNCONFIRMED' => ['id' => 0, 'name' => '未确认'], // 未确认
        'CONFIRMED'   => ['id' => 1, 'name' => '已确认'], // 已确认
        'ABSENT'      => ['id' => 2, 'name' => '旷课'], // 旷课
    ];

    /**
     * 根据课程订单里的 ID 获取列表
     * @param  int $courseId 订单课表的 ID
     * @return array
     */
    public function getListByCourseId($courseId) {
        $list = $this->where([
            'course_id' => $courseId,
            ])->order('timee desc')->select();

        foreach ($list as &$item) {
            $item['state_str'] = $item['cwqr'] ? '已确认':'未确认';
            $item['tqr_str'] = $item['tqr'] ? '已确认' : '未确认';
        }

        return $list;
    }

   /**
    * 获取未确认课程课时
    * @param  int   $courseId 订单中课程的id
    * @return float           未被确认的课时数
    */
    public function getUnconfirmedHour($courseId) {
        return (float)($this->where([
                'course_id' => $courseId,
                // 'state'  => $this->_states['UNCONFIRMED']['id'], // 待确认的课
                'cwqr' => ''//财务没有确认即没有确认
                ])->sum('count'));
    }

    /**
    * 获取旷课时
    * @param  int   $courseId 订单中课程的id
    * @return float           旷课的课时数
    */
    public function getAbsentHour($courseId) {
        return (float)($this->where([
                'course_id' => $courseId,
                'state'  => $this->_states['ABSENT']['id'], // 旷课课
                ])->sum('count'));
    }

    /**
     * 获取已确认课时数
     * @param  int   $courseId 订单中课程的id
     * @return float           已确认的课时数
     */
    public function getConfirmedHour($courseId) {
        return (float)($this->where([
                'course_id' => $courseId,
                'state'  => $this->_states['CONFIRMED']['id'], // 已经确认的课
                'cwqr' => ['neq','']//财务确认即确认
                ])->sum('count'));
    }

    public function getStateById($id) {
        foreach ($this->_states as $key => $value) {
            if($value['id'] == $id){
                return [
                    'id'   => $value['id'],
                    'name' => $value['name'],
                    'key'  => $key,
                ];
                break;
            }
        }
    }

    /**
     * 删除未却确认的排课
     * @param  string $courseId 课程ID
     */
    public function delUnconfirmed($courseId) {
        return $this->where([
            'course_id' => $courseId,
            'timee' => ['gt',date('Y-m-d')],
            'state' => ['neq',1],
            'cwqr' => ''
            ])->delete();
    }
}
