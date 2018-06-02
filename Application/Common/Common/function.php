<?php
/*---------------------------------------------------------------------------
 鸿文OA系统 - 让工作更轻松快乐
 -------------------------------------------------------------------------*/

/**
 * 系统加密方法
 * @param string $data 要加密的字符串
 * @param string $key  加密密钥
 * @param int $expire  过期时间 单位 秒
 * @return string
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */

function think_encrypt($data, $key = '', $expire = 0) {
	$key = md5(empty($key) ? C('DATA_AUTH_KEY') : $key);
	$data = base64_encode($data);
	$x = 0;
	$len = strlen($data);
	$l = strlen($key);
	$char = '';

	for ($i = 0; $i < $len; $i++) {
		if ($x == $l)
			$x = 0;
		$char .= substr($key, $x, 1);
		$x++;
	}

	$str = sprintf('%010d', $expire ? $expire + time() : 0);

	for ($i = 0; $i < $len; $i++) {
		$str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1))) % 256);
	}
	return str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($str));
}

/**
 * 系统解密方法
 * @param  string $data 要解密的字符串 （必须是think_encrypt方法加密的字符串）
 * @param  string $key  加密密钥
 * @return string
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */

function think_decrypt($data, $key = '') {
	$key = md5(empty($key) ? C('DATA_AUTH_KEY') : $key);
	$data = str_replace(array('-', '_'), array('+', '/'), $data);
	$mod4 = strlen($data) % 4;
	if ($mod4) {
		$data .= substr('====', $mod4);
	}
	$data = base64_decode($data);
	$expire = substr($data, 0, 10);
	$data = substr($data, 10);

	if ($expire > 0 && $expire < time()) {
		return '';
	}
	$x = 0;
	$len = strlen($data);
	$l = strlen($key);
	$char = $str = '';

	for ($i = 0; $i < $len; $i++) {
		if ($x == $l)
			$x = 0;
		$char .= substr($key, $x, 1);
		$x++;
	}

	for ($i = 0; $i < $len; $i++) {
		if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
			$str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
		} else {
			$str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
		}
	}
	return base64_decode($str);
}

function is_weixin() {
	if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
		return true;
	}
	return false;
}

function is_mobile($mobile) {
	return preg_match("/^(?:13\d|14\d|15\d|18[0123456789])-?\d{5}(\d{3}|\*{3})$/", $mobile);
}

function is_email($email) {
	return strlen($email) > 6 && preg_match("/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/", $email);
}

/**
 * 发送HTTP请求方法，目前只支持CURL发送请求
 * @param  string $url    请求URL
 * @param  array  $params 请求参数
 * @param  string $method 请求方法GET/POST
 * @return array  $data   响应数据
 */
function http($url, $params, $method = 'GET', $header = array(), $multi = false) {
	$opts = array(CURLOPT_TIMEOUT => 30, CURLOPT_RETURNTRANSFER => 1, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_HTTPHEADER => $header);

	/* 根据请求类型设置特定参数 */
	switch(strtoupper($method)) {
		case 'GET' :
			$opts[CURLOPT_URL] = $url . '?' . str_replace("&amp;", "&", http_build_query($params));
			break;
		case 'POST' :
			//判断是否传输文件
			//$params = $multi ? $params : http_build_query($params);
			$opts[CURLOPT_URL] = $url;
			$opts[CURLOPT_POST] = 1;
			$opts[CURLOPT_POSTFIELDS] = $params;
			break;
		default :
			throw new Exception('不支持的请求方式！');
	}

	/* 初始化并执行curl请求 */
	$ch = curl_init();
	curl_setopt_array($ch, $opts);
	$data = curl_exec($ch);
	$error = curl_error($ch);
	curl_close($ch);
	if ($error)
		throw new Exception('请求发生错误：' . $error);
	return $data;
}

function upload_filter($val) {
	$allow_ext = explode(",", C('UPLOAD_FILE_EXT'));
	if (in_array($val, $allow_ext)) {
		return true;
	} else {
		return false;
	}
}

function get_img_info($img) {
	$img_info = getimagesize($img);
	if ($img_info !== false) {
		$img_type = strtolower(substr(image_type_to_extension($img_info[2]), 1));
		$info = array("width" => $img_info[0], "height" => $img_info[1], "type" => $img_type, "mime" => $img_info['mime'], );
		return $info;
	} else {
		return false;
	}
}

function get_return_url($level = null) {
	if (empty($level)) {
		$return_url = cookie('return_url');
	} else {
		$return_url = cookie('return_url_' . $level);
	}
	return $return_url;
}

function get_system_config($code) {
	return C($code);
}

//获取到数据库里保存的记录
function get_config($code){
	$cc=M("SystemConfig")->where(['code'=>$code])->getField('val');
	$cc=explode(',',$cc);
	foreach ($cc as &$v) {
		$v=explode('=>',$v);
		$da[$v[0]]=$v[1];
	}
	return $da;
}

function get_user_id() {
	$user_id = session(C('USER_AUTH_KEY'));
	return isset($user_id) ? $user_id : 0;
}

function get_school_name($school_id=null){
	if($school_id === null){
		$school_id = session("school_id");
	}

	foreach(C('SCHOOL') as $val) {
		if($val['id'] == $school_id){
			return $val['name'];
			break;
		}
	}

	return null;
}

function get_school_region($school_id){
    if($school_id === null){
        return null;
    }
    
    foreach(C('SCHOOL') as $val) {
        if($val['id'] == $school_id){
            return $val['region'];
            break;
        }
    }
    
    return null;
}

function get_school_id($school_name){
	if($school_name === null){
		$school_id = session("school_id");
	}

	foreach(C('SCHOOL') as $val) {
		if($val['name'] == $school_name){
			return $val['id'];
			break;
		}
	}

	return $school_id;
}

function get_user_name($user_id = null) {
	if ($user_id==null) {
		$user_name = session('user_name');
		return isset($user_name) ? $user_name : 0;
	} else {
		$where['id'] = array('eq', $user_id);
		return M("User") -> where($where) -> getField('name');
	}
}

function get_dept_id($dept_name = null) {
	if(empty($dept_name)){
		return session('dept_id');
	}else{
		return M("Dept") -> where(['name'=>$dept_name]) -> getField('id');
	}
}

function get_dept_name($dept_id = null) {
	if ($dept_id===null) {
		$result = M("Dept") -> find(session("dept_id"));
		return $result['name'];
	} else {
		$where['id'] = array('eq', $dept_id);
		return M("Dept") -> where($where) -> getField('name');
	}
}

function get_department_name($data,$dept_id = null) {
    if ($dept_id===null) {
        $dept_id = session("new_dept_id");
    } 
    
    foreach($data as $ko=>$vv){
        if($vv['id'] == $dept_id){
            return $vv['name'];
            //break;
        }
    }
}

function get_position_id($position_name = null) {
	if(empty($position_name)){
		return session('position_id');
	}else{
		return M("Position") -> where(['name'=>$position_name]) -> getField('id');
	}
}

function get_position_name($position_id = null) {
	if (empty($position_id)) {
		$result = M("Position") -> find(session("position_id"));
		return $result['name'];
	} else {
		$where['id'] = array('eq', $position_id);
		return M("Position") -> where($where) -> getField('name');
	}
}

function get_controller($str) {
	$arr_str = explode("/", $str);
	return $arr_str[0];
}

function date_to_int($date) {
	$date = explode("-", $date);
	$time = explode(":", "00:00");
	$time = mktime($time[0], $time[1], 0, $date[1], $date[2], $date[0]);
	return $time;
}

function filter_search_field($v1) {
	if ($v1 == "keyword")
		return true;
	$prefix = substr($v1, 0, 3);
	$arr_key = array("be_", "en_", "eq_", "li_", "lt_", "gt_", "bt_");
	if (in_array($prefix, $arr_key)) {
		return true;
	} else {
		return false;
	}
}

function get_model_fields($model) {
	$arr_field = array();
	if (isset($model -> viewFields)) {
		foreach ($model->viewFields as $key => $val) {
			unset($val['_on']);
			unset($val['_type']);
			if (!empty($val[0]) && ($val[0] == "*")) {
				$model = M($key);
				$fields = $model -> getDbFields();
				$arr_field = array_merge($arr_field, array_values($fields));
			} else {
				$arr_field = array_merge($arr_field, array_values($val));
			}
		}
	} else {
		$arr_field = $model -> getDbFields();
	}
	return $arr_field;
}

function IP($ip = '', $file = 'UTFWry.dat') {
	$_ip = array();
	if (isset($_ip[$ip])) {
		return $_ip[$ip];
	} else {
		import("ORG.Net.IpLocation");
		$iplocation = new IpLocation($file);
		$location = $iplocation -> getlocation($ip);
		$_ip[$ip] = $location['country'] . $location['area'];
	}
	return $_ip[$ip];
}

function sort_by($array, $keyname = null, $sortby = 'asc') {
	//dump($array);
	$myarray = $inarray = array();
	# First store the keyvalues in a seperate array
	foreach ($array as $i => $befree) {
		$myarray[$i] = $array[$i][$keyname];
	}
	//dump($array);
	# Sort the new array by
	switch ($sortby) {
		case 'asc' :
			# Sort an array and maintain index association...
			asort($myarray, SORT_STRING);
			break;
		case 'desc' :
		case 'arsort' :
			# Sort an array in reverse order and maintain index association
			arsort($myarray, SORT_STRING);
			break;
		case 'natcasesor' :
			# Sort an array using a case insensitive "natural order" algorithm
			natcasesort($myarray);
			break;
	}
	# Rebuild the old array
	foreach ($myarray as $key => $befree) {
		$inarray[] = $array[$key];
	}
	return $inarray;
}

function fill_option($list, $data = null) {
	$html = "";
	if (is_array($list)) {
		foreach ($list as $key => $val) {

			if (is_array($val)) {
				$id = $val['id'];
				$name = $val['name'];
				if (empty($data)) {
					$selected = "";
				} else {
					$selected = "selected";
				}
				$html = $html . "<option value='{$id}' $selected>{$name}</option>";
			} else {
				if ($key == $data) {
					$selected = "selected";
				} else {
					$selected = "";
				}
				$html = $html . "<option value='{$key}' $selected>{$val}</option>";
			}
		}
	}

	echo $html;
}

function fill_option_ex($list, $select_id = null) {
	$html = "";
	if (is_array($list)) {
		foreach ($list as $key => $val) {

			if (is_array($val)) {
				$id = $val['id'];
				$name = $val['name'];
				if ($id !== $select_id) {
					$selected = "";
				} else {
					$selected = "selected";
				}
				$html .= '<option value="'.$id.'" '.$selected.'>'.$name.'</option>';
			} else {
				if ($key !== $select_id) {
					$selected = "";
				} else {
					$selected = "selected";
				}
				$html .= '<option value="'.$key.'" '.$selected.'>'.$val.'</option>';
			}
		}
	}

	echo $html;
}

function fill_option_extend($list, $arr = null,$sel) {
    $html = "";
    if (is_array($list)) {
        foreach ($list as $key => $val) {
            
            if (is_array($val)) {
                
                if(in_array($val['id'],$arr)){
                    $id = $val['id'];
                    $name = $val['name'];
                    
                    if ($id !== $sel) {
                        $selected = "";
                    } else {
                        $selected = "selected";
                    }
                    $html .= '<option value="'.$id.'" '.$selected.'>'.$name.'</option>';
                }
            } else {
                if(in_array($key,$arr)){
                    if ($key !== $sel) {
                        $selected = "";
                    } else {
                        $selected = "selected";
                    }
                    $html .= '<option value="'.$key.'" '.$selected.'>'.$val.'</option>';
                }
            }
        }
    }
    
    echo $html;
}
//输出不带valve的
function fill_option_val($list, $select_id = null) {
	$html = "";
	if (is_array($list)) {
		foreach ($list as $key => $val) {

			if (is_array($val)) {
				$id = $val['id'];
				$name = $val['name'];
				if ($id !== $select_id) {
					$selected = "";
				} else {
					$selected = "selected";
				}
				$html .= '<option '.$selected.'>'.$name.'</option>';
			} else {
				if ($key !== $select_id) {
					$selected = "";
				} else {
					$selected = "selected";
				}
				$html .= '<option '.$selected.'>'.$val.'</option>';
			}
		}
	}

	echo $html;
}

/**
 +----------------------------------------------------------
 * 产生随机字串，可用来自动生成密码
 * 默认长度6位 字母和数字混合 支持中文
 +----------------------------------------------------------
 * @param string $len 长度
 * @param string $type 字串类型
 * 0 字母 1 数字 其它 混合
 * @param string $addChars 额外字符
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function rand_string($len = 6, $type = '', $addChars = '') {
	$str = '';
	switch ($type) {
		case 0 :
			$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . $addChars;
			break;
		case 1 :
			$chars = str_repeat('0123456789', 3);
			break;
		case 2 :
			$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
			break;
		case 3 :
			$chars = 'abcdefghijklmnopqrstuvwxyz' . $addChars;
			break;
		default :
			// 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
			$chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789' . $addChars;
			break;
	}
	if ($len > 10) {//位数过长重复字符串一定次数
		$chars = $type == 1 ? str_repeat($chars, $len) : str_repeat($chars, 5);
	}
	if ($type != 4) {
		$chars = str_shuffle($chars);
		$str = substr($chars, 0, $len);
	} else {
		// 中文随机字
		for ($i = 0; $i < $len; $i++) {
			$str .= msubstr($chars, floor(mt_rand(0, mb_strlen($chars, 'utf-8') - 1)), 1);
		}
	}
	return $str;
}

function list_to_tree($list, $root = 0, $pk = 'id', $pid = 'pid', $child = '_child') {
	// 创建Tree
	$tree = array();
	if (is_array($list)) {
		// 创建基于主键的数组引用
		$refer = array();
		foreach ($list as $key => $data) {
			$refer[$data[$pk]] = &$list[$key];
		}
		foreach ($list as $key => $data) {
			// 判断是否存在parent
			$parentId = 0;
			if (isset($data[$pid])) {
				$parentId = $data[$pid];
			}
			if ((string)$root == $parentId) {
				$tree[] = &$list[$key];
			} else {
				if (isset($refer[$parentId])) {
					$parent = &$refer[$parentId];
					$parent[$child][] = &$list[$key];
				}
			}
		}
	}
	return $tree;
}

function tree_to_list($tree, $level = 0, $pk = 'id', $pid = 'pid', $child = '_child') {
	$list = array();
	if (is_array($tree)) {
		foreach ($tree as $val) {
			$val['level'] = $level;
			if (isset($val['_child'])) {
				$child = $val['_child'];
				if (is_array($child)) {
					unset($val['_child']);
					$list[] = $val;
					$list = array_merge($list, tree_to_list($child, $level + 1));
				}
			} else {
				$list[] = $val;
			}
		}
		return $list;
	}
}

function popup_tree_menu($tree, $level = 0) {
	$level++;
	$html = "";
	if (is_array($tree)) {
		$html = "<ul class=\"tree_menu\">\r\n";
		foreach ($tree as $val) {
			if (isset($val["name"])) {
				$title = $val["name"];
				$id = $val["id"];
				if (empty($val["id"])) {
					$id = $val["name"];
				}
				if (!empty($val["is_del"])) {
					$del_class = "is_del";
				} else {
					$del_class = "";
				}
				if (isset($val['_child'])) {
					$html = $html . "<li>\r\n<a class=\"$del_class\" node=\"$id\" grade=\"$level\"><i class=\"fa fa-angle-right level$level\"></i><span>$title</span></a>\r\n";
					$html = $html . popup_tree_menu($val['_child'], $level);
					$html = $html . "</li>\r\n";
				} else {
					$html = $html . "<li>\r\n<a class=\"$del_class\" node=\"$id\" grade=\"$level\"><i class=\"fa fa-angle-right level$level\"></i><span>$title</span></a>\r\n</li>\r\n";
				}
			}
		}
		$html = $html . "</ul>\r\n";
	}
	return $html;
}

function reunit($size) {
	$unit = " B";
	if ($size > 1024) {
		$size = $size / 1024;
		$unit = " KB";
	}
	if ($size > 1024) {
		$size = $size / 1024;
		$unit = " MB";
	}
	if ($size > 1024) {
		$size = $size / 1024;
		$unit = " GB";
	}
	return round($size, 2) . $unit;
}

function rotate($a) {
	$b = array();
	if (is_array($a)) {
		foreach ($a as $val) {
			foreach ($val as $k => $v) {
				$b[$k][] = $v;
			}
		}
	}
	return $b;
}

function utf_strlen($string) {
	return count(mb_str_split($string));
}

function utf_str_sub($string, $cnt) {
	$charlist = mb_str_split($string);
	$new = array_chunk($charlist, $cnt);
	return implode($new[0]);
}

function get_letter($string) {
	$charlist = mb_str_split($string);
	return implode(array_map("get_first_char", $charlist));
}

function mb_str_split($string) {
	// Split at all position not after the start: ^
	// and not before the end: $
	return preg_split('/(?<!^)(?!$)/u', $string);
}

function get_first_char($s0) {
	$fchar = ord(substr($s0, 0, 1));
	if (($fchar >= ord("a") and $fchar <= ord("z")) or ($fchar >= ord("A") and $fchar <= ord("Z")))
		return strtoupper(chr($fchar));
	$s = iconv("UTF-8", "GBK", $s0);
	$asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
	if ($asc >= -20319 and $asc <= -20284)
		return "A";
	if ($asc >= -20283 and $asc <= -19776)
		return "B";
	if ($asc >= -19775 and $asc <= -19219)
		return "C";
	if ($asc >= -19218 and $asc <= -18711)
		return "D";
	if ($asc >= -18710 and $asc <= -18527)
		return "E";
	if ($asc >= -18526 and $asc <= -18240)
		return "F";
	if ($asc >= -18239 and $asc <= -17923)
		return "G";
	if ($asc >= -17922 and $asc <= -17418)
		return "H";
	if ($asc >= -17417 and $asc <= -16475)
		return "J";
	if ($asc >= -16474 and $asc <= -16213)
		return "K";
	if ($asc >= -16212 and $asc <= -15641)
		return "L";
	if ($asc >= -15640 and $asc <= -15166)
		return "M";
	if ($asc >= -15165 and $asc <= -14923)
		return "N";
	if ($asc >= -14922 and $asc <= -14915)
		return "O";
	if ($asc >= -14914 and $asc <= -14631)
		return "P";
	if ($asc >= -14630 and $asc <= -14150)
		return "Q";
	if ($asc >= -14149 and $asc <= -14091)
		return "R";
	if ($asc >= -14090 and $asc <= -13319)
		return "S";
	if ($asc >= -13318 and $asc <= -12839)
		return "T";
	if ($asc >= -12838 and $asc <= -12557)
		return "W";
	if ($asc >= -12556 and $asc <= -11848)
		return "X";
	if ($asc >= -11847 and $asc <= -11056)
		return "Y";
	if ($asc >= -11055 and $asc <= -10247)
		return "Z";
	return null;
}

function get_emp_pic($id) {

	$where['id'] = array('eq', $id);
	$data = M("User") -> where($where) -> getField("pic");
	if (empty($data)) {
		$data = "./Uploads/emp_pic/no_avatar.jpg";
	} else {

	}
	return $data;
}

function status($status) {
	if ($status == 0) {
		return "启用";
	}
	if ($status == 1) {
		return "禁用";
	}
}

function sub_tree_menu($tree, $level = 0) {
	$level++;
	$html = "";
	if (is_array($tree)) {
		$html = "<ul class=\"tree_menu\">\r\n";
		foreach ($tree as $val) {
			if (isset($val["name"])) {
				$title = $val["name"];
				$id = $val["id"];
				if (empty($val["id"])) {
					$id = $val["name"];
				}
				if (isset($val['_child'])) {
					$html = $html . "<li>\r\n<a node=\"$id\"><i class=\"fa fa-angle-right level$level\"></i><span>$title</span></a>\r\n";
					$html = $html . sub_tree_menu($val['_child'], $level);
					$html = $html . "</li>\r\n";
				} else {
					$html = $html . "<li>\r\n<a  node=\"$id\" ><i class=\"fa fa-angle-right level$level\"></i><span>$title</span></a>\r\n</li>\r\n";
				}
			}
		}
		$html = $html . "</ul>\r\n";
	}
	return $html;
}

function get_emp_no($user_id = null) {
	if (empty($user_id)) {
		$emp_no = session("emp_no");
		return isset($emp_no) ? $emp_no : 0;
	} else {
		$where['id'] = array('eq', $user_id);
		return M("User") -> where($where) -> getField('emp_no');
	}
}

function dropdown_menu($tree, $level = 0) {
	$level++;
	$html = "";
	if (is_array($tree)) {
		foreach ($tree as $val) {
			if (isset($val["name"])) {
				$title = $val["name"];
				$id = $val["id"];
				if (empty($val["id"])) {
					$id = $val["name"];
				}
				if (isset($val['_child'])) {
					$html = $html . "<li id=\"$id\" class=\"level$level\"><a>$title</a>\r\n";
					$html = $html . dropdown_menu($val['_child'], $level);
					$html = $html . "</li>\r\n";
				} else {
					$html = $html . "<li  id=\"$id\"  class=\"level$level\">\r\n<a>$title</a>\r\n</li>\r\n";
				}
			}
		}
	}
	return $html;
}

function to_date($time, $format = 'Y-m-d H:i:s') {
	if (empty($time)) {
		return '';
	}
	$format = str_replace('#', ':', $format);
	return date($format, $time);
}

function formatPrice($price){
	return number_format(round($price, 2), 2);
}

function formatTime($format, $timestamp){
	return $timestamp > 10000 ? date($format, $timestamp) : '';
}

function getPurchaseStateById($id){
	foreach(C('PURCHASE_STATES') as $key => $value){
		if($value['id'] == $id){
			return array(
				'id'    => $id,
				'name'  => $value['name'],
				'alias' => $key,
			);
			break;
		}
	}

	return null;
}

function getPurchaseState($key){
    return C('PURCHASE_STATES')[$key];
}

function getWechatObj(){
	return new \Wechat\Hongwen\Wechat(array(
        'corpid' => C('WECHAT_CORPID'),
        'secret' => C('WECHAT_SECRET'),
    ));
}

function getConsumeType($key = null){
	if(empty($key)){
		return C('CONSUME_TYPE');
	}

	return C('CONSUME_TYPE')[$key];
}

function getConsumeTypeById($id){
	foreach(C('CONSUME_TYPE') as $key => $value){
		if($value['id'] == $id){
			return array(
				'id'    => $id,
				'name'  => $value['name'],
				'alias' => $key,
			);
			break;
		}
	}

	return null;
}

function getConsumeStateById($id){
	foreach(C('CONSUME_STATES') as $key => $value){
		if($value['id'] == $id){
			return array(
				'id'    => $id,
				'name'  => $value['name'],
				'alias' => $key,
			);
			break;
		}
	}

	return null;
}

function getSchoolCourseById($id){
	foreach(C('SCHOOL_COURSE') as $key => $value){
		if($value['id'] == $id){
			return array(
				'id'    => $id,
				'name'  => $value['name'],
				'group' => $value['group'],
			);
			break;
		}
	}

	return null;
}

function getCourseStateById($id){
	foreach(C('COURSE_STATES') as $key => $value){
		if($value['id'] == $id){
			return array(
				'id'    => $id,
				'name'  => $value['name'],
				'alias' => $key,
			);
			break;
		}
	}

	return null;
}

function getStudentTypeById($id){
	foreach(C('SCHOOL_STUDENT_TYPE') as $key => $value){
		if($value['id'] == $id){
			return array(
				'id'    => $id,
				'name'  => $value['name'],
				'alias' => $key,
			);
			break;
		}
	}

	return null;
}

function getSubjectNameById($id) {
	foreach(C('SCHOOL_SUBJECT') as $key => $value){
		if($value['id'] == $id){
			return array(
				'id'    => $id,
				'name'  => $value['name'],
				'alias' => $key,
			);
			break;
		}
	}

	return null;
}

/**
微信公众号调用的相关方法
*/

// 把姓名转化成微信id
function userid($name){
	if($name)
    $data=M('hw003.person_all',null)->where(array('name'=>$name))->getfield('userid');
    return $data;
}

//发送微信所有形式数据
function send($msg){
    $data=json_encode($msg,JSON_UNESCAPED_UNICODE);//处理要发送的数据
    $tk=M('hw003.access',null)->where('id=1')->find();//获取access_tokon
    if((time()-$tk['timestamp'])>7000)$tk['tokon']=accesstokon();// 遇到tokon过期的情况重新获取
    $url='https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token='.$tk['tokon'];
    $out=url_post($url,$data);
    if($out['errmsg']=='ok'){
        return true;
    }else{
        // var_dump($out);echo("截图该页面并向系统管理员反馈相关情况……");die;
    }
}

//post发送数据
function url_post($url,$data){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_SSLVERSION,CURLOPT_SSLVERSION_TLSv1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    $output = curl_exec($ch);
    curl_close($ch);
    $out =json_decode(stripslashes($output), true);//转成数组
    return $out;
}

//get方式获取数据
function url_get($url){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_SSLVERSION,CURLOPT_SSLVERSION_TLSv1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    $output = curl_exec($ch);
    curl_close($ch);
    $data=json_decode(stripslashes($output), true);
    return $data;
}

//重新获取accesstokon值并保存
function accesstokon(){
    $CorpID=C('WECHAT_CORPID');
    $Secret=C('WECHAT_SECRET');
    $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$CorpID&corpsecret=$Secret";
    $data=url_get($url);
    $dat['tokon']=$data['access_token'];//获取到值
    $dat['timestamp']=time();
    M('hw003.access',null)->where('id=1')->save($dat);
    return $data['access_token'];
}

//身份认证并跳转进入页面
function wx_oauth($url){
	$url=urlencode($url);
	$data='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.C('WECHAT_CORPID').'&redirect_uri='.$url.'&response_type=code&scope=snsapi_base&state=1#wechat_redirect';
	return $data;
}
function sex($t=0){
	return $t?'男':'女';
}
//多维数组取交集，关联查询过滤，不考虑键值
function array_jj($a,$b){
    foreach ($a as $v1) {
        foreach ($b as $v2) {
            if($v1==$v2)$data[]=$v1;
        }
    }
    return $data;
}
//把二维数组里的某值升级为键
function fix_array_key($list, $key) {
	$arr = null;
	foreach ($list as $val) {
		$arr[$val[$key]] = $val;
	}
	return $arr;
}

//计算一年多少个星期和每周的开始和结束日期
function get_week($year) {
    $year_start = $year . "-01-01";
    $year_end = $year . "-12-31";
    $startday = strtotime($year_start);
    if (intval(date('N', $startday)) != '1') {
        $startday = strtotime("next monday", strtotime($year_start)); //获取年第一周的日期
    }
    $year_mondy = date("Y-m-d", $startday); //获取年第一周的日期

    $endday = strtotime($year_end);
    if (intval(date('W', $endday)) == '7') {
        $endday = strtotime("last sunday", strtotime($year_end));
    }

    $num = intval(date('W', $endday));
    for ($i = 0; $i <= $num; $i++) {
        $j = $i -1;
        $start_date = date("Y-m-d", strtotime("$year_mondy $j week "));

        $end_day = date("m-d", strtotime("$start_date +6 day"));
        $start_date = date("m-d", strtotime("$year_mondy $j week "));

        $week_array[$i+1] = array ($start_date,$end_day);
        // $week_array[$i+1] = array (
        //     str_replace("-",
        //     ".",
        //     $start_date
        // ), str_replace("-", ".", $end_day));
    }
    return $week_array;
}

//把给定的时间按周次转换成时间戳,(周，星期，时间H:i),返回时间戳
function timee($week,$i,$info){
	if(!$info)return 0;
	$year=strtotime(date('Y',strtotime(session('date'))).'-01-01');
	if($i)$time=date('Y-m-d',$year-24*3600*date('w',$year)+7*24*3600*($week-1)+24*3600*$i).' '.$info.':00';
	if(!$i)$time=date('Y-m-d',$year-24*3600*date('w',$year)+7*24*3600*$week+24*3600*$i).' '.$info.':00';
	return strtotime($time);
}

//二维数组排序
function array_sort($arr,$keys,$type='asc'){ //保持键值不变
	$keysvalue = $new_array = array();
	foreach ($arr as $k=>$v){
    		$keysvalue[$k] = $v[$keys];
	}
	if($type == 'asc'){
    		asort($keysvalue);
	}else{
    		arsort($keysvalue);
	}
	reset($keysvalue);
	foreach ($keysvalue as $k=>$v){
    		$new_array[$k] = $arr[$k];
	}
	return $new_array;
}

//删除数组中的空值
function array_empty_delt(&$arr){
	foreach ($arr as $k=>$v) {
		if($v==='')unset($arr[$k]);
	}
}


function record($part,$info){
	$md=M('hw003.record',null);
	$m=$md->where(array('part'=>$part))->find();
	$record=$info.','.session('user_name').','.date('Y/m/d H:i:s').'|'.$m['record'];
	if($m){
		$md->where(array('id'=>$m['id']))->save(array('record'=>$record));
	}else{
		$md->add(array('part'=>$part,'record'=>$record));
	}
}

//通知管理员意外情况
function admin($info){
	$info.=session('user_name').session('emp_no');
	$wx = getWechatObj();
    $wx->sendTextMsg($info, array('touser' => ['WWW']), C('WECHAT_APP')['WGMS']);
    die('权限不足,禁止访问！');
}

//实例化获取汉字首字母
function Firstname(){
	return new \Home\Hongwen\Firstname();
}

//生成一个学号,日期+校区id+第几
function get_std_id($school_id){

	$d=date('Ymd').sprintf("%03d", session('school_id')).'000';
	$w['school']=get_school_name($school_id);
	$w['std_id']=['egt',$d];
	$cc=M('hw001.student',null)->where($w)->max('std_id');

	return $cc?($cc+1):$d;

}
//获取当天 本月本周或者下月下周的开始时间及结束时间
function get_date($date,$t='d',$n=0)   
{   
    if($t=='d'){   
       $firstday = date('Y-m-d 00:00:00',strtotime("$n day"));   
       $lastday = date("Y-m-d 23:59:59",strtotime("$n day"));   
    }elseif($t=='w'){   
       if($n != 0)
       {
       	$date = date('Y-m-d',strtotime("$n week"));
       }   
       $lastday = date("Y-m-d 00:00:00",strtotime("$date Sunday"));   
       $firstday = date("Y-m-d 23:59:59",strtotime("$lastday -6 days")); 
     
    }elseif($t=='m'){   
       if($n!=0)
       {
       	$date = date('Y-m-d',strtotime("$n months"));
       }   
       $firstday = date("Y-m-01 00:00:00",strtotime($date));   
       $lastday = date("Y-m-d 23:59:59",strtotime("$firstday +1 month -1 day"));    
    } 
    return array($firstday,$lastday);   
} 

/*
 *该函数调用实例
$now_time = time();   
$date=date("Y-m-d",$now_time);   
$day1   = get_date($date,'d');   
$day2   = get_date($date,'d',-1);   
$week1 = get_date($date,'w');   
$week2 = get_date($date,'w',-1);   
$month1 = get_date($date,'m');   
$month2 = get_date($date,'m',-1);   
echo '<pre>';   
print_r($day1);//今天   
print_r($day2);//昨天   
print_r($week1);//这周   
print_r($week2);//上周   
print_r($month1);//这月   
print_r($month2);//上月   
echo '</pre>';*/

