<?php
/**
 * Table class
 * @author dotcoo zhao <dotcoo@163.com>
 * @link http://www.dotcoo.com/table
 */
class Table {
	/**
	 * @var PDO
	 */
	public static $__pdo = null;			// 默认PDO对象
	public static $__host = "127.0.0.1";	// 默认主机
	public static $__user = "root";			// 默认账户
	public static $__password = "123456";	// 默认密码
	public static $__dbname = "test";		// 默认数据库名称
	public static $__charset = "utf8";		// 默认字符集

	/**
	 * @var PDO
	 */
	public $_pdo = null;					// PDO对象
	public $_table = null;					// table
	public $_pk = "id";						// paramry
	public $_where = array();				// where
	public $_where_params = array();		// where params
	public $_count_where = array();			// count where
	public $_count_where_params = array();	// count where params
	public $_group = "";					// group
	public $_having = array();				// having
	public $_having_params = array();		// having params
	public $_order = null;					// order
	public $_limit = null;					// limit
	public $_offset = null;					// offset
	public $_for_update = "";				// read lock
	public $_lock_in_share_model = "";		// write lock

	/**
	 * Table Construct
	 * @param string $table_name
	 * @param string $pk
	 * @param string $prefix
	 * @param PDO $pdo
	 */
	function __construct($table=null, $pk=null, PDO $pdo=null) {
		$this->_table = isset($table) ? $table : $this->_table;
		$this->_pk = isset($pk) ? $pk : $this->_pk;
		$this->_pdo = $pdo;
	}

	/**
	 * 获取PDO对象
	 * @return PDO
	 */
	public function getPDO() {
		if (isset($this->_pdo)) {
			return $this->_pdo;
		}

		if (isset(self::$__pdo)) {
			return self::$__pdo;
		}

		$dsn = sprintf("mysql:host=%s;dbname=%s;charset=%s;", self::$__host, self::$__dbname, self::$__charset);
		$options = array(
				PDO::ATTR_PERSISTENT => true,
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
				// PDO::ATTR_EMULATE_PREPARES => false,
		);
		return self::$__pdo = new PDO($dsn, self::$__user, self::$__password, $options);
	}
	
	/**
	 * 获取主键列名
	 * @return string
	 */
	public function getPK() {
		return $this->_pk;
	}

	/**
	 * 执行语句
	 * @param string $sql
	 * @return PDOStatement
	 */
	public function query($sql) {
		$params = func_get_args();
		array_shift($params);
		return $this->vquery($sql, $params);
	}

	/**
	 * 执行语句
	 * @param string $sql
	 * @return PDOStatement
	 */
	public function vquery($sql, array $params = array()) {
		$sqls = explode("?", $sql);
		$sql_new = array_shift($sqls);
		$params_new = array();
		foreach ($sqls as $i => $sql_item) {
			if (is_array($params[$i])) {
				$sql_new .= str_repeat("?,", count($params[$i])-1)."?".$sql_item;
				$params_new = array_merge($params_new, $params[$i]);
			} else {
				$sql_new .= "?".$sql_item;
				$params_new[] = $params[$i];
			}
		}
		$stmt = $this->getPDO()->prepare($sql_new);
		foreach ($params_new as $i => $param) {
			switch (gettype($param)) {
				case "integer":
					$stmt->bindValue($i+1, $param, PDO::PARAM_INT);
					break;
				case "NULL":
					$stmt->bindValue($i+1, $param, PDO::PARAM_NULL);
					break;
				default :
					$stmt->bindValue($i+1, $param);
			}
		}
// 		echo $sql_new, "\n"; var_dump($params_new); // exit();
		$stmt->executeResult = $stmt->execute();
		$this->reset();
		return $stmt;
	}

	/**
	 * 查询数据
	 * @param string $field
	 * @return PDOStatement
	 */
	public function select($columns="*") {
		$params = array_merge($this->_where_params, $this->_having_params);
		$sql = "SELECT $columns FROM `{$this->_table}`";
		$sql .= empty($this->_where) ? "" : " WHERE ". implode(" AND ", $this->_where);
		$sql .= empty($this->_group) ? "" : " GROUP BY ". $this->_group;
		$sql .= empty($this->_having) ? "" : " HAVING ". implode(" AND ", $this->_having);
		$sql .= empty($this->_order) ? "" : " ORDER BY ". $this->_order;
		if (isset($this->_limit)) {
			$sql .= " LIMIT ?";
			$params[] = $this->_limit;
			if (isset($this->_offset)) {
				$sql .= " OFFSET ?";
				$params[] = $this->_offset;
			}
		}
		$sql .= $this->_for_update;
		$sql .= $this->_lock_in_share_model;

		$this->_count_where = $this->_where;
		$this->_count_where_params = $this->_where_params;
		return $this->vquery($sql, $params);
	}

	/**
	 * 添加数据
	 * @param array $data
	 * @return PDOStatement
	 */
	public function insert(array $data) {
		$sql = "INSERT `{$this->_table}` SET";
		$params = array();
		foreach ($data as $col=>$val) {
			$sql .= " `$col` = ?,";
			$params[] = $val;
		}
		$sql{strlen($sql)-1} = " ";
		return $this->vquery($sql, $params);
	}
	
	/**
	 * 批量插入数据
	 * @param array $names
	 * @param array $rows
	 * @param number $batch
	 * @return Table
	 */
	public function batchInsert(array $fields, array $rows, $batch=1000) {
		$i = 0;
		$sql = "INSERT `{$this->_table}` (`".implode("`, `", $fields)."`) VALUES ";
		foreach ($rows as $row) {
			$i++;
			$sql .= "('".implode("','", array_map("addslashes", $row))."'),";
			if ($i >= $batch) {
				$sql{strlen($sql)-1} = " ";
				$this->query($sql);
				$i = 0;
				$sql = "INSERT `{$this->_table}` (`".implode("`, `", $fields)."`) VALUES ";
			}
		}
		if ($i > 0) {
			$sql{strlen($sql)-1} = " ";
			$this->query($sql);
		}
		return $this;
	}

	/**
	 * 更新数据
	 * @param array $data
	 * @return PDOStatement
	 */
	public function update(array $data) {
		$sql = "UPDATE `{$this->_table}` SET";
		$params = array();
		foreach ($data as $col=>$val) {
			$sql .= " `$col` = ?,";
			$params[] = $val;
		}
		$sql{strlen($sql)-1} = " ";
		$sql .= empty($this->_where) ? "" : "WHERE ". implode(" AND ", $this->_where);
		$params = array_merge($params, $this->_where_params);
		return $this->vquery($sql, $params);
	}

	/**
	 * 替换数据
	 * @param array $data
	 * @return PDOStatement
	 */
	public function replace(array $data) {
		$sql = "REPLACE `{$this->_table}` SET";
		$params = array();
		foreach ($data as $col=>$val) {
			$sql .= " `$col` = ?,";
			$params[] = $val;
		}
		$sql{strlen($sql)-1} = " ";
		$sql .= empty($this->_where) ? "" : "WHERE ". implode(" AND ", $this->_where);
		$params = array_merge($params, $this->_where_params);
		return $this->vquery($sql, $params);
	}

	/**
	 * 删除数据
	 * @return PDOStatement
	 */
	public function delete() {
		$sql = "DELETE FROM `{$this->_table}`";
		$sql .= empty($this->_where) ? "" : " WHERE ". implode(" AND ", $this->_where);
		return $this->vquery($sql, $this->_where_params);
	}

	/**
	 * 重置所有
	 * @return Table
	 */
	public function reset() {
		$this->_where = array();
		$this->_where_params = array();
		$this->_group = null;
		$this->_having = array();
		$this->_having_params = array();
		$this->_order = null;
		$this->_limit = null;
		$this->_offset = null;
		$this->_for_update = "";
		$this->_lock_in_share_model = "";
		return $this;
	}

	/**
	 * where查询条件
	 * @param string $format
	 * @return Table
	 */
	public function where($format) {
		$args = func_get_args();
		array_shift($args);
		$this->_where[] = $format;
		$this->_where_params = array_merge($this->_where_params, $args);
		return $this;
	}

	/**
	 * group分组
	 * @param string $columns
	 * @return Table
	 */
	public function group($columns) {
		$this->_group = $columns;
		return $this;
	}

	/**
	 * having过滤条件
	 * @param string $format
	 * @return Table
	 */
	public function having($format) {
		$args = func_get_args();
		array_shift($args);
		$this->_having[] = $format;
		$this->_having_params = array_merge($this->_having_params, $args);
		return $this;
	}

	/**
	 * order排序
	 * @param string $columns
	 * @return Table
	 */
	public function order($order) {
		$this->_order = $order;
		return $this;
	}

	/**
	 * limit数据偏移
	 * @param number $offset
	 * @param number $limit
	 * @return Table
	 */
	public function limitOffset($limit, $offset=null) {
		$this->_limit = $limit;
		$this->_offset = $offset;
		return $this;
	}

	/**
	 * 独占锁，不可读不可写
	 * @return Table
	 */
	public function forUpdate() {
		$this->forUpdate = " FOR UPDATE";
		return $this;
	}

	/**
	 * 共享锁，可读不可写
	 * @return Table
	 */
	public function lockInShareMode() {
		$this->_lock_in_share_model = " LOCK IN SHARE MODE";
		return $this;
	}

	/**
	 * 事务开始
	 * @return bool
	 */
	public function begin() {
		return $this->getPDO()->beginTransaction();
	}

	/**
	 * 事务提交
	 * @return bool
	 */
	public function commit() {
		return $this->getPDO()->commit();
	}

	/**
	 * 事务回滚
	 * @return bool
	 */
	public function rollBack() {
		return $this->getPDO()->rollBack();
	}

	/**
	 * page分页
	 * @param number $page
	 * @param number $pagesize
	 * @return Table
	 */
	public function page($page, $pagesize = 15) {
		$this->_limit = $pagesize;
		$this->_offset = ($page - 1) * $pagesize;
		return $this;
	}

	/**
	 * 获取自增ID
	 * @return int
	 */
	public function lastInsertId() {
		return $this->getPDO()->lastInsertId();
	}

	/**
	 * 获取符合条件的行数
	 * @return int
	 */
	public function count() {
		$sql = "SELECT count(*) FROM `{$this->_table}`";
		$sql .= empty($this->_count_where) ? "" : " WHERE ". implode(" AND ", $this->_count_where);
		return $this->vquery($sql, $this->_count_where_params)->fetchColumn();
	}

	/**
	 * 将选中行的指定字段加一
	 * @param string $col
	 * @param number $val
	 * @return Table
	 */
	public function plus($col, $val = 1) {
		$sets = array("`$col` = `$col` + ?");
		$vals = array($val);
		$args = array_slice(func_get_args(), 2);
		while (count($args) > 1) {
			$col = array_shift($args);
			$val = array_shift($args);
			$sets[] = "`$col` = `$col` + ?";
			$vals[] = $val;
		}
		$sql = "UPDATE `{$this->_table}` SET ".implode(", ", $sets);
		$sql .= empty($this->_where) ? "" : " WHERE ". implode(" AND ", $this->_where);
		$params = array_merge($vals, $this->_where_params);
		$this->vquery($sql, $params);
		return $this;
	}

	/**
	 * 将选中行的指定字段加一
	 * @param string $col
	 * @param number $val
	 * @return int
	 */
	public function incr($col, $val = 1) {
		$sql = "UPDATE `{$this->_table}` SET `$col` =  last_insert_id(`$col` + ?)";
		$sql .= empty($this->_where) ? "" : " WHERE ". implode(" AND ", $this->_where);
		$params = array_merge(array($val), $this->_where_params);
		$this->vquery($sql, $params);
		return $this->getPDO()->lastInsertId();
	}

	/**
	 * 根据主键查找行
	 * @param number $id
	 * @return array
	 */
	public function find($id) {
		return $this->where("`{$this->_pk}` = ?", $id)->select()->fetch();
	}

	/**
	 * 保存数据,自动判断是新增还是更新
	 * @param array $data
	 * @return PDOStatement
	 */
	public function save(array $data) {
		if (array_key_exists($this->_pk, $data)) {
			$pk_val = $data[$this->_pk];
			unset($data[$this->_pk]);
			return $this->where("`{$this->_pk}` = ?", $pk_val)->update($data);
		} else {
			return $this->insert($data);
		}
	}

	/**
	 * 获取外键数据
	 * @param array $rows
	 * @param string $foreign_key
	 * @param string $field
	 * @return PDOStatement
	 */
	public function foreignKey(array $rows, $foreign_key, $field="*") {
		$ids = array_column($rows, $foreign_key);
		if (empty($ids)) {
			return new PDOStatement();
		}
		$ids = array_unique($ids);
		return $this->where("`{$this->_pk}` in (?)", $ids)->select($field);
	}
}
