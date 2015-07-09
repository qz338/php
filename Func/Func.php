<?php
/**
 * Functions
 * @author dotcoo zhao <dotcoo@163.com>
 * @link https://www.dotcoo.com/functions
 */

/**
 * 是否post提交
 */
function isPost() {
	return $_SERVER["REQUEST_METHOD"] == "POST";
}

/**
 * 获取提交的数据
 * @param string $namelist
 */
function formData($namelist) {
	$names = explode(",", $namelist);
	$data = array();
	foreach ($names as $name) {
		$data[$name] = $_REQUEST[$name];
	}
	return $data;
}

/**
 * 跳转到指定url
 * @param string $url
 */
function redirect($url) {
	header("Location: $url");
	exit();
}

/**
 * 获得ip地址
 * @return number
 */
function ip(){
	return ip2long($_SERVER["REMOTE_ADDR"]);
}

/**
 * 检测是否上传上传文件
 * @param string $name file标签的name属性
 * @param number $i 多文件上传时的索引
 * @return bool
 */
function isUpload($name, $index = null){
	if (!(isset($_SERVER["HTTP_CONTENT_TYPE"]) && strpos($_SERVER["HTTP_CONTENT_TYPE"], "multipart/form-data")===0)) {
		exit('Upload: form error!<br />enctype="multipart/form-data"');
	}
	if (empty($_FILES[$name])) {
		return false;
	}
	if ($index===null) {
		if ($_FILES[$name]["error"] !== 0) {
			return false;
		}
	} else {
		if (!isset($_FILES[$name][$index])) {
			return false;
		}
		if ($_FILES[$name]["error"][$index] !== 0) {
			return false;
		}
	}
	return true;
}

/**
 * 获取扩展名
 * @param string $file 文件名
 * @return string
 */
function extname($file){
	return strtolower(pathinfo($file, PATHINFO_EXTENSION));
}

/**
 * 随机字符串
 * @param number $len
 * @return string
 */
function random($len) {
	$char = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$str = "";
	for ($i=0; $i<$len; $i++) {
		$str .= $char{mt_rand(0, 61)};
	}
	return $str;
}

/**
 * 计算当前天数
 * @return number
 */
function today($offset = 28800) {
	return (int) (($_SERVER["REQUEST_TIME"] + $offset) / 86400);
}

/**
 * 对树结构递归，禅城层次
 * @param array $rows
 * @param number $pid
 * @param string $key
 * @param string $pkey
 * @return array
*/
function rowsToTree($rows, $pid = 0, $key = "id", $pkey = "pid") {
	$items = array();
	foreach ($rows as $row) {
		if($pid == $row[$pkey]) {	
			$row["tree"] = rowsToTree($rows, $row[$key]);
			$items[] = $row;
		}
	}
	return $items;
}

/**
 * 对树结构排序，添加floor层次
 * @param array $rows
 * @param number $pid
 * @param number $floor
 * @param string $key
 * @param string $pkey
 * @return array
 */
function rowsToFloor($rows, $pid = 0, $floor = 0, $key = "id", $pkey = "pid") {
	$items = array();
	foreach ($rows as $row) {
		if($pid == $row[$pkey]) {
			$row["floor"] = $floor;
			$items[] = $row;
			$items = array_merge($items, rowsToFloor($rows, $row[$key], $floor+1));
		}
	}
	return $items;
}
