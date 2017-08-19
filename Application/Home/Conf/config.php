<?php
if (!defined('THINK_PATH')) exit();
$array=array(
    // 'SHOW_PAGE_TRACE' =>true,
    'LOAD_EXT_CONFIG'	=>'xueguan',
	// 模板相关配置
	'TMPL_PARSE_STRING' => array(
		'__INS__'    => __ROOT__ . '/Public/Ins',
		'__STATIC__' => __ROOT__ . '/Public/Static',
		'__ADDONS__' => __ROOT__ . '/Public/' . MODULE_NAME . '/Addons',
		'__IMG__'    => __ROOT__ . '/Public/' . MODULE_NAME . '/images',
		'__CSS__'    => __ROOT__ . '/Public/' . MODULE_NAME . '/css',
		'__JS__'     => __ROOT__ . '/Public/' . MODULE_NAME . '/js',
		'__FINANCE__' =>__ROOT__ . 'Home/View/financeSys/js'
	),
	'TMPL_NO_HAVE_AUTH' =>APP_PATH.MODULE_NAME.'/View/Public/no_have_auth.html',
	'EMP_PIC_PATH'      =>'Uploads/emp_pic/',
	'TEMPLETE_PATH'     =>'./Uploads/Templete',

    // 文件上传相关配置
    'DOWNLOAD_UPLOAD' => array(
        'mimes'    => '', //允许上传的文件MiMe类型
        'maxSize'  => 20*1024*1024, //上传的文件大小限制 (0-不做限制)
        'autoSub'  => true, //自动子目录保存文件
        'subName'  =>  array('date','Y-m'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
        'rootPath' => './Uploads/Download/', //保存根路径
        'savePath' => '', //保存路径
        'saveName' => array('uniqid', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
        'saveExt'  => '', //文件保存后缀，空则使用原后缀
        'replace'  => false, //存在同名是否覆盖
        'hash'     => true, //是否生成hash编码
        'callback' => false, //检测文件是否存在回调函数，如果存在返回文件信息数组
    ),

    //下载模型上传配置（文件上传类配置）
    'UPLOAD_FILE_EXT'=>'ppt,pptx,xls,xlsx,jpg,gif,png,jpeg,zip,rar,tar,gz,7z,doc,docx,txt,xml,pdf', //允许上传的文件后缀

    // 系统配置
    'SYSTEM_NAME'      => 'OA管理系统',
    'UPLOAD_FILE_TYPE' => 'doc,docx,xls,xlsx,ppt,pptx,pdf,gif,png,tif,zip,rar,jpg,jpeg,txt',
    'IS_VERIFY_CODE'   => '0',
    'DEFAULT_ROLE'     => 2, // 新增加用户默认权限

    // 数据库
    'DB_PREFIX'=>'oa_',

    /* 认证相关 */
    'USER_AUTH_KEY'     =>'auth_id',    // 用户认证SESSION标记
    'ADMIN_AUTH_KEY'    =>'administrator',
    'USER_AUTH_GATEWAY' =>'public/login',// 默认认证网关
    'DB_LIKE_FIELDS'    =>'content|remark',
    'AUTH' => array(
        'read'=>'index,read,down,folder,export',
        'write'=>'add,edit,upload,del_file,save,del',
        'admin'=>'restore,destory,import,export'
        ),
	'TMPL_ACTION_ERROR'     =>  APP_PATH.'Home/View/dispatch_jump.tpl', // 默认错误跳转对应的模板文件
	'TMPL_ACTION_SUCCESS'   =>  APP_PATH.'Home/View/dispatch_jump.tpl', // 默认成功跳转对应的模板文件
    'WWW' => 'http://i.ihongwen.com'//程序地址，微信通知用

);

return $array;
