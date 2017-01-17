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
    		'组织部',
        '财政中心',
        '运营中心',
        '战略发展',
        '人事中心',
        '教学中心',
    		'师训部',
    		'初中部',
    		'教研部',
        '总裁'
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
