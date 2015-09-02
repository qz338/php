<?php
// 数据库表结构
/*
CREATE TABLE `user_test` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`username` varchar(45) NOT NULL,
`password` varchar(45) NOT NULL,
`nickname` varchar(45) NOT NULL,
`r` tinyint(4) NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `blog_test` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
*/

// 初始化
header("Content-Type: text/html; charset=utf-8");

include "Table.php";

Table::$__host = "127.0.0.1";
Table::$__user = "root";
Table::$__password = "123456";
Table::$__dbname = "test";
Table::$__charset = "utf8";

// 创建实体对象
$userTable = new Table("user_test");
$blogTable = new Table("blog_test");

// 插入数据
for ($i=1; $i<=100; $i++) {
	$user = array(
			"username" => "admin$i",
			"password" => "admin$i",
			"nickname" => "管理员$i",
			"r" => mt_rand(0, 4),
	);
	echo $userTable->insert($user)->rowCount(), "\n";
	echo $userTable->lastInsertId(), "\n";
}
// 获取数据
var_dump($userTable->where("id > ?", 0)->select()->fetchAll());

// 批量插入数据
$fields = array("username","password","nickname","r");
for ($i=101; $i<=200; $i++) {
	$rows[] = array("admin$i","admin$i","管理员$i",mt_rand(0, 4));
}
$userTable->batchInsert($fields, $rows);
// 获取数据
var_dump($userTable->select()->fetchAll());

// 修改数据
$user = array(
		"username" => "admin4-1",
		"password" => "admin4-1",
		"nickname" => "管理员4-1",
		"r" => mt_rand(0, 4),
);
echo $userTable->where("id = ?", 4)->update($user)->rowCount(), "\n";

// 根据主键查询数据
var_dump($userTable->find(4));

// replace数据
$user = array(
		"id" => 4,
		"username" => "admin4",
		"password" => "admin4",
		"nickname" => "管理员4",
		"r" => mt_rand(0, 4),
);
echo $userTable->replace($user)->rowCount(), "\n";

// 根据主键查询数据
var_dump($userTable->find(4));

// 删除数据
echo $userTable->where("id = ?", 4)->delete()->rowCount(), "\n";

// 根据主键查询数据
var_dump($userTable->find(4));

// 多where条件
var_dump($userTable->where("id > ?", 4)->where("id in (?)", array(5,7,9))->select()->fetchAll());

// 分组 过滤
var_dump($userTable->group("r")->having("c between ? and ?", 10, 20)->having("c > ?", 1)
	->select("*, r, count(*) as c")->fetchAll());

// 排序
var_dump($userTable->order("username, id desc")->select()->fetchAll());

// 限制行数
var_dump($userTable->limitOffset(3, 3)->select()->fetchAll());

// 分页
var_dump($userTable->page(3, 3)->select()->fetchAll());

// 条件 分页 总行数
var_dump($userTable->calcFoundRows()->where("r=?", 3)->order("id desc")->page(2, 3)->select()->fetchAll());
echo $userTable->count(), "\n";

// 复杂查询
var_dump($userTable->where("id > ?", 0)->where("id < ?", 100)
	->group("r")->having("c between ? and ?", 1, 100)->having("c > ?", 1)
	->order("c desc")->page(2, 3)->select("*, count(*) as c")->fetchAll());

// 列加减
$id = 2;
var_dump($userTable->find($id));
// 加一
var_dump($userTable->where("id = ?", $id)->plus("r")->find($id));
// 减一
var_dump($userTable->where("id = ?", $id)->plus("r", -1)->find($id));
// 多列
var_dump($userTable->where("id = ?", $id)->plus("r", 1, "r", -1)->find($id));

// 列加减 并获得修改后的值
$id = 2;
var_dump($userTable->find($id));
// 加一
echo $userTable->where("id = ?", $id)->incr("r"), "\n";
var_dump($userTable->find($id));
// 减一
echo $userTable->where("id = ?", $id)->incr("r", -1), "\n";
var_dump($userTable->find($id));

// 保存 修改
$user = array(
	"id" => 3,
	"nickname" => "管理员3-3",
);
echo $userTable->save($user)->rowCount(), "\n";
var_dump($userTable->find(3));
// 保存 添加
$user = array(
		"username" => "admin11",
		"password" => "admin11",
		"nickname" => "管理员11",
		"r" => mt_rand(0, 4),
);
echo $userTable->save($user)->rowCount(), "\n";
$id = $userTable->lastInsertId();
echo $id, "\n";
var_dump($userTable->find($id));

// 生成外键测试数据
$users = $userTable->select("id")->fetchAll();
$id = 0;
foreach ($users as $user) {
	for ($i=0; $i<10; $i++) {
		$id++;
		$blog = array(
				"user_id" => $user["id"],
				"title" => "blog$id",
		);
		$blogTable->insert($blog);
	}
}

// 外键 测试
$blogs = $blogTable->where("id in (?)", array(1,12,23,34,56,67,78,89,90,101))->select()->fetchAll();
var_dump($userTable->foreignKey($blogs, "user_id", "*,id")->fetchAll(PDO::FETCH_UNIQUE)); // 获取数据
var_dump($userTable->foreignKey($blogs, "user_id", "id,username")->fetchAll(PDO::FETCH_KEY_PAIR)); // 获取数据

// PDO fetch 示例
var_dump($userTable->select("*, id")->fetchAll(PDO::FETCH_UNIQUE)); // 获取映射数据
var_dump($userTable->select("nickname")->fetchAll(PDO::FETCH_COLUMN)); // 获取数组
var_dump($userTable->select("id, nickname")->fetchAll(PDO::FETCH_KEY_PAIR)); // 获取键值对
var_dump($userTable->select("r, id, nickname")->fetchAll(PDO::FETCH_GROUP)); // 获取数据分组
var_dump($userTable->select("r, id")->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_COLUMN)); // 获取数据分组
var_dump($userTable->select("r, nickname")->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_KEY_PAIR)); // 获取数据分组
var_dump($userTable->select()->fetchAll(PDO::FETCH_OBJ)); // 获取对象 指定获取方式，将结果集中的每一行作为一个属性名对应列名的对象返回。
var_dump($userTable->select()->fetchAll(PDO::FETCH_CLASS)); // 获取对象 指定获取方式，返回一个所请求类的新实例，映射列到类中对应的属性名。 Note: 如果所请求的类中不存在该属性，则调用 __set() 魔术方法
var_dump($userTable->select()->fetchAll(PDO::FETCH_INTO)); // 获取对象 指定获取方式，更新一个请求类的现有实例，映射列到类中对应的属性名。
var_dump($userTable->select()->fetchAll(PDO::FETCH_FUNC, function($id, $username, $password, $r){ // 获取自定义行
	return array("id"=>$id, "name"=>"$username - $password - $r");
}));
var_dump($userTable->select()->fetchAll(PDO::FETCH_FUNC, function($id, $username, $password, $r){ //  获取单一值
	return "$id - $username - $password - $r";
}));
