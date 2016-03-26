<?php
/**
 * Functions
 * @author dotcoo zhao <dotcoo@163.com>
 * @link https://www.dotcoo.com/functions
 */

/**
 * 是否post提交
 */
function is_post() {
	return $_SERVER["REQUEST_METHOD"] == "POST";
}

/**
 * 获取提交的数据
 * @param string $names
 * @param array $form
 * @param string $return_errors
 * @return data
 */
function form_data($names, array &$form = null, $return_errors = false) {
	$names = array_map("trim", explode(",", $names));
	$form = $form === null ? $_POST : $form;
	$data = array();
	$errors = array();
	foreach ($names as $name) {
		// 解析规则
		list($name, $type, $defval, $pattern) = array_map("trim", explode(":", $name)) + array("", "s", "", "");
		$required = ctype_lower($type);
		// 默认值
		$value = array_key_exists($name, $form) ? $form[$name] : $defval;
		// 必填 但是没有填写
		if ($required && $value === "") {
			$errors[] = array("field" => $name, "message" => "%s不能为空!");
			continue;
		}
		// 填写了 格式检查
		if ($value === "" && $pattern !== "" && !preg_match("/^".strtr("\0\1", ",:", $pattern)."$/", $value)) {
			$errors[] = array("field" => $name, "message" => "%s格式不正确!");
			continue;
		}
		// 类型检查
		$type = strtolower($type);
		if ($type == "i" && !is_numeric($value)) {
			$errors[] = array("field" => $name, "message" => "%s类型不正确!");
			continue;
		} elseif ($type == "f" && !is_numeric($value)) {
			$errors[] = array("field" => $name, "message" => "%s类型不正确!");
			continue;
		} elseif ($type == "s" && !is_string($value)) {
			$errors[] = array("field" => $name, "message" => "%s类型不正确!");
			continue;
		} elseif ($type == "a" && !is_array($value)) {
			$errors[] = array("field" => $name, "message" => "%s类型不正确!");
			continue;
		}
		// 检查通过
		$data[$name] = $value;
	}
	// 检查是否有错误
	if ($return_errors) {
		return array($data, $return_errors);
	} else {
		if (!empty($errors)) {
			error_message($_SERVER["REQUEST_METHOD"] == "POST" ? "表单填写有误!" : "参数错误!", $errors);
		}
		return $data;
	}
}

/**
 * 跳转到指定url
 * @param string $url
 */
function redirect($url) {
	header("Location: $url");
	exit("Redirect <a href=\"$url\">$url</a>");
}

/**
 * 获得ip地址
 * @return number
 */
function ip() {
	return ip2long($_SERVER["REMOTE_ADDR"]);
}

/**
 * 检测是否上传上传文件
 * @param string $name file标签的name属性
 * @param number $i 多文件上传时的索引
 * @return bool
 */
function is_upload($name, $index = null) {
	if (!(isset($_SERVER["HTTP_CONTENT_TYPE"]) && strpos($_SERVER["HTTP_CONTENT_TYPE"], "multipart/form-data") === 0)) {
		exit('Upload: form error!<br />enctype="multipart/form-data"');
	}
	if (empty($_FILES[$name])) {
		return false;
	}
	if ($index === null) {
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
function extname($file) {
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
	for ($i = 0; $i < $len; $i++) {
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
function rows2tree($rows, $pid = 0, $key = "id", $pkey = "pid") {
	$items = array();
	foreach ($rows as $row) {
		if($pid == $row[$pkey]) {
			$row["tree"] = rows2tree($rows, $row[$key]);
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
function rows2floor($rows, $pid = 0, $floor = 0, $key = "id", $pkey = "pid") {
	$items = array();
	foreach ($rows as $row) {
		if($pid == $row[$pkey]) {
			$row["floor"] = $floor;
			$items[] = $row;
			$items = array_merge($items, rows2floor($rows, $row[$key], $floor+1));
		}
	}
	return $items;
}

/**
 * select option 辅助函数
 * @param array $options
 * @param string $val
 * @return array
 */
function select_options($options, $val = -1) {
	$html = "";
	foreach ($options as $key => $value) {
		$selected = $val == $key ? ' selected="selected"' : '';
		$html .= sprintf('<option value="%s"%s>%s</option>%s', $key, $selected, $value, "\n");
	}
	return $html;
}

/**
 * 获取的默认参数
 * @param string $key
 * @param string $default
 * @return array
 */
function get($key, $default = "") {
	return isset($_GET[$key]) ? $_GET[$key] : $default;
}

/**
 * 获取的默认参数
 * @param string $key
 * @param string $default
 * @return array
 */
function post($key, $default = "") {
	return isset($_POST[$key]) ? $_POST[$key] : $default;
}

/**
 * 成功消息
 *
 * success_message 函数会根据是否Ajax请求响应不同的数据内容,除了第
 * 一个参数为提示消息,之后的其他参数可以不按照顺序呢传参,函数会根据参
 * 数的类型调整参数是跳转到的url,还是消息状态码或消息的数据
 *
 * @param string $message 消息内容
 * @param string $url 跳转到的url
 * @param integer $status 状态码
 * @param array $data 消息的数据
 * @param boolean $is_alert 是否显示信息
 */
function success_message($message) {
	$url = "";
	$status = 0;
	$data = array();
	$is_alert = defined("SHOW_MESSAGE_IS_ALERT") ? SHOW_MESSAGE_IS_ALERT : false;

	foreach (array_slice(func_get_args(), 1) as $arg) {
		switch (gettype($arg)) {
			case "string":
				$url = $arg;
				break;
			case "integer":
				$status = $arg;
				break;
			case "array":
				$data = $arg;
				break;
			case "boolean":
				$is_alert = $arg;
				break;
		}
	}

	if (!empty($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest") {
		header("Content-Type: application/json; charset=utf-8");
		$resp = compact("status", "message");
		if ($is_alert) {
			$resp["is_alert"] = $is_alert;
		}
		if (!empty($url)) {
			$resp["url"] = $url;
		}
		if (!empty($data)) {
			$resp["data"] = $data;
		}
		if (defined("DEVEL") && DEVEL) {
			$d = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1)[0];
			$resp["file"] = $d["file"];
			$resp["line"] = $d["line"];
		}
		exit(json_encode($resp, JSON_UNESCAPED_UNICODE));
	}else {
		header("Content-Type: text/html; charset=utf-8");
		if (defined("DEVEL") && DEVEL) {
			$d = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1)[0];
			header("file: {$d["file"]}");
			header("line: {$d["line"]}");
		}
		$alert = $is_alert ? "alert('$message');" : "";
		if (!empty($url)) {
			exit("<script>{$alert}location.href='$url';</script>");
		}
		if (!empty($_SERVER["HTTP_REFERER"])) {
			exit("<script>{$alert}location.href='{$_SERVER["HTTP_REFERER"]}';</script>");
		}
		exit("<script>{$alert}history.back();</script>");
	}
}

/**
 * 错误消息
 *
 * error_message 函数会根据是否Ajax请求响应不同的数据内容,除了第
 * 一个参数为提示消息,之后的其他参数可以不按照顺序呢传参,函数会根据参
 * 数的类型调整参数是跳转到的url,还是消息状态码或消息的数据
 *
 * @param string $message 消息内容
 * @param string $url 跳转到的url
 * @param integer $status 状态码
 * @param array $data 消息的数据
 * @param boolean $is_alert 是否显示信息
 */
function error_message($message) {
	$url = "";
	$status = 1;
	$data = array();
	$is_alert = defined("SHOW_MESSAGE_IS_ALERT") ? SHOW_MESSAGE_IS_ALERT : true;

	foreach (array_slice(func_get_args(), 1) as $arg) {
		switch (gettype($arg)) {
			case "string":
				$url = $arg;
				break;
			case "integer":
				$status = $arg;
				break;
			case "array":
				$data = $arg;
				break;
			case "boolean":
				$is_alert = $arg;
				break;
		}
	}

	if (!empty($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest") {
		header("Content-Type: application/json; charset=utf-8");
		$resp = compact("status", "message");
		if (!empty($data)) {
			$resp["data"] = $data;
		}
		if (defined("DEVEL") && DEVEL) {
			$d = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1)[0];
			$resp["file"] = $d["file"];
			$resp["line"] = $d["line"];
		}
		exit(json_encode($resp, JSON_UNESCAPED_UNICODE));
	}else {
		header("Content-Type: text/html; charset=utf-8");
		if (defined("DEVEL") && DEVEL) {
			$d = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1)[0];
			header("file: {$d["file"]}");
			header("line: {$d["line"]}");
		}
		$alert = $is_alert ? "alert('$message');" : "";
		if (!empty($url)) {
			exit("<script>{$alert}location.href='$url';</script>");
		}
		exit("<script>{$alert}history.back();</script>");
	}
}

/**
 * 获取的默认参数
 * @param string $status
 * @param string $message
 * @param array $data
 * @return array
 */
function json_message($status, $message, $data = array()) {
	header("Content-Type: application/json; charset=utf-8");
	$resp = compact("status", "message");
	if (!empty($data)) {
		$resp["data"] = $data;
	}
	if (defined("DEVEL") && DEVEL) {
		$d = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1)[0];
		$resp["file"] = $d["file"];
		$resp["line"] = $d["line"];
	}
	exit(json_encode($resp, JSON_UNESCAPED_UNICODE));
}

/**
 * 过滤表单html元素
 * @param array $data
 * @return array
 */
function filter_html(&$data) {
	foreach ($data as $k => &$v) {
		if (is_array($v)) {
			filter_html($v);
		} else {
			$v = strip_tags($v);
		}
	}
}
