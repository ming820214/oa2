<?php

return array(
    'SCHOOL_JTID' => 0, // 集团的ID
    'SCHOOL_ROOT_ID' => 15, // 学校列表根类在数据表的ID
    'SCHOOL_DEFAULT' => array( // 预置入的校区列表
        array('id' => 0,  'name' => '集团'),
    ),

    'SCHOOL' => [], // 校区列表,会与数据库的合并

    'SCHOOL_GRADE_ROOT_ID'          => 16,
    'SCHOOL_GRADE'                  => array(),

    'SCHOOL_COURSE_ROOT_ID'        => 17,
    'SCHOOL_COURSE_ID'             => array(),

    'SCHOOL_SUBJECT_ROOT_ID'       => 18,
    'SCHOOL_SUBJECT'               => array(),

    'SCHOOL_TEACHER_LEVEL_ROOT_ID' => 19,
    'SCHOOL_TEACHER_LEVEL'         => array(),

    'SCHOOL_STUDENT_TYPE' => [
        'OLD' => ['id' => 10, 'name' => '老学员'],
        'NEW' => ['id' => 1,  'name' => '新学员'],
    ],
    'SCHOOL_PART' => [
            '总裁办',
            '会员管理中心',
            '综合办公室',
            '投资关系部',
            '企划部',
            '平面设计部',
            '营运部',
            '校建部',
            '互联网运营部',
            '监察中心',
            '数据监管中心',
            'K研发中心',
            'I研发中心',
            'E研发中心',
            '组织部',
            '课程开发部',
            'GMT教师学院',
            '工商管理学院',
            '学院办公室',
            '鸿道河北分院',
            '人事行政部',
            '招聘部',
            '会计部',
            '资金部',
            '税务部',
        '财政中心',
        '运营中心',
        '战略发展',
        '人事中心',
        '教学中心',
    		'师训部',
    		'初中部',
    		'教研部',
    	'品牌发展中心',
        '总裁',
        '鸿文优途',
        '优途阜新校区',
        '优途锦州校区',
        '优途盘锦校区',
        '优途葫芦岛校区',
        '战略发展中心'
    ],
    'SCHOOL_POSITION' =>[
        '校区财务',
        '教研员',
        '保洁',
        '教学主任',
        '学习管理师',
        '讲师',
        '教务',
        '教管副校长',
        '教学副校长',
        '业务副校长',
        '维护副校长',
        '咨询副校长',
    	'教学督导',
        '校长',
        '助理',
        '主管',
        '总裁'
    ]
);
