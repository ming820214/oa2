<?php
return array(
    'PURCHASE_TYPE'         => array(), // 采购类型，将被覆盖
    'PURCHASE_TYPE_ROOT_ID' => 17, //采购预算类型根泪在数据表的 ID
    'PURCHASE_STATES'       => array( // 采购审核状态
        'RETURN'    => array('id' => 50,  'name' => '退回修改'),
        'IN_SCHOOL' => array('id' => 100, 'name' => '校区审核'),
        'IN_DEPT'   => array('id' => 200, 'name' => '部门审核'),
        'IN_DEPT2'  => array('id' => 250, 'name' => '中心审核'),
        'IN_GROUP'  => array('id' => 300, 'name' => '确认审核'),
        'SUCCESS'   => array('id' => 400, 'name' => '审核成功'),
    ),

    'COSY_TYPE'             => array(), // 成本类型，将被覆盖
    'COST_TYPE_ROOT_ID'     => 1, // 成本类型根类在数据表的 ID

    'CHECK_POSITION' => array( // 审核相关职位，估计会废弃，直接去权限表获取相关人员
        'SCHOOL' => 10, // 校长职位ID
        'DEPT'   => 8, // 部门主管职位ID
        'DEPT2'  => 7, // 部门总裁职位ID
        // 'GROUP'  => 0, // 确认审核职位ID
    ),

    //////////// 课款管理相关 /////////////////////////////////////////////
    'CHARGE_DISCOUNT_TYPE_ID' => array( // 优惠类型
        'CLASS_NUM' => 1, // 课时优惠类ID
        'DISCOUNT'  => 2, // 折扣优惠类ID
    ),

    'CONSUME_TYPE' => [ // 学员消费类型
        'CHARGE'  => ['id' => 10000,  'name' => '缴费'], // 大于 10000 是增加
        // 'PAY'     => ['id' => 10010,  'name' => '缴费'],//后期可以取消该状态
        'YHQ'     => ['id' => 10020,  'name' => '优惠券'],
        'XZQ'     => ['id' => 10030,  'name' => '校长券(抵值+)'],
        'XZQQ'     => ['id' => 10040,  'name' => '校长券(归还-)'],
        'CWDX'     => ['id' => 10050,  'name' => '错误抵消'],
        'VIPC'     => ['id' => 10060,  'name' => '贵宾卡'],
        'BOOK'    => ['id' => 10,     'name' => '订购'],
        'RENEWAL' => ['id' => 50,     'name' => '调课'],
        'CHANGE'  => ['id' => 55,      'name' => '转班'],
        'DROP'    => ['id' => 100,    'name' => '退课'],
        'RETURN'  => ['id' => 200,    'name' => '学员退费'],
    ],

    'CONSUME_STATES'       => [ // 消费审核状态
        'CANCEL'  => ['id' => 50,  'name' => '作废'],
        'CHECK1'  => ['id' => 100, 'name' => '财务审核'],
        'CHECK2'  => ['id' => 200, 'name' => '校区审核'],
        'CHECK3'  => ['id' => 250, 'name' => '部门审核'],
        'CHECK4'  => ['id' => 300, 'name' => '集团确认'],
        'SUCCESS' => ['id' => 400, 'name' => '审核成功'],
    ],

    'COURSE_STATES'       => [ // 课程状态
        'NORMAL'    => ['id' => 200, 'name' => '正常'],
        'PAUSE'     => ['id' => 250, 'name' => '暂停排课'],
        'FINISH'    => ['id' => 300, 'name' => '已结课'],
        'DROPED'    => ['id' => 500, 'name' => '已退课'],
        'CHECK'     => ['id' => 100, 'name' => '审核中'],


        // 'CANCEL'    => ['id' => 50,  'name' => '已作废'],
        // 'RENEWINGCHECK'     => ['id' => 150, 'name' => '调课中(后)'],
        // 'DROPING'   => ['id' => 400, 'name' => '退课中'],
        // 'CHANGEING' => ['id' => 600, 'name' => '转班中'],
        // 'CHANGED'   => ['id' => 700, 'name' => '已转班'],
        // 'RENEWING'  => ['id' => 800, 'name' => '调课中(前)'],
        // 'RENEWED'   => ['id' => 900, 'name' => '已调课'],
    ],

    'POSITION_ID' => [ // 用到的职位ID
        'SCHOOL_DIRECTOR' => 19, // 教学主任
        'SCHOOL_DIRECTOR_XZ' => 13, // 业务副校长
        'SCHOOL_CONSULTS_XZ' =>11, //咨询副校长
        'SCHOOL_MANAGER'  => 18, // 学管师
        'SCHOOL_MASTER'   => 10, // 校长
        'CONTROLLER'      => 8,  // 主管
        'PRESIDENT'       => 7,  // 总裁
    ],

    'DISCOUNT_ID' => [ // 优惠ID
        'HOUR'   => 1, // 课时优惠
        'FACTOR' => 2, // 折扣优惠
    ],

);