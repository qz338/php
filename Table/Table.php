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
	public static $__pdo 		= null;			// 默认PDO对象
	public static $__host 		= "127.0.0.1";	// 默认主机
	public static $__user 		= "root";		// 默认账户
	public static $__password 	= "123456";		// 默认密码
	public static $__dbname 	= "test";		// 默认数据库名称
	public static $__charset 	= "utf8";		// 默认字符集

	/**
	 * @var PDO
	 */
	public $pdo 	= null;		// PDO对象
	public $prefix 	= "";		// 表前缀
	public $table 	= "table";	// 表名
	public $tab 	= "t";		// 表别名
	public $pk 		= "id";		// 主键
	public $debug 	= false;	// 调试模式

	public $_keywords 		= array();	// keywords
	public $_columns 		= array();	// columns
	public $_table 			= "";		// table
	public $_joins 			= array();	// joins
	public $_wheres 		= array();	// where
	public $_wheres_params 	= array();	// where params
	public $_groups 		= array();	// group
	public $_havings 		= array();	// having
	public $_havings_params = array();	// having params
	public $_orders 		= array();	// order
	public $_limit 			= null;		// limit
	public $_offset 		= null;		// offset
	public $_for_update 	= "";		// read lock
	public $_lock_in_share_mode = "";	// write lock
	// public $_count_wheres 			= array();	// count where
	// public $_count_wheres_params 	= array();	// count where params

	public static $param_types = array(			// 参数类型
		"boolean" 	=> PDO::PARAM_BOOL,
		"NULL" 		=> PDO::PARAM_NULL,
		"double" 	=> PDO::PARAM_INT,
		"integer" 	=> PDO::PARAM_INT,
		"string" 	=> PDO::PARAM_STR,
	);

	/**
	 * Table Construct
	 * @param string $table 表名
	 * @param string $tab 表别名
	 * @param string $pk 表主键
	 * @param PDO $pdo PDO
	 */
	function __construct($table = null, $tab = null, $pk = null, PDO $pdo = null) {
		$this->table = isset($table) ? $table : $this->table;
		$this->tab = isset($tab) ? $tab : $this->tab;
		$this->pk = isset($pk) ? $pk : $this->pk;
		$this->pdo = isset($pdo) ? $pdo : $this->pdo;
		$this->_table = $this->prefix . $this->table;
	}

	/**
	 * 获取PDO对象
	 * @return PDO
	 */
	public function getPDO() {
		if (isset($this->pdo)) {
			return $this->pdo;
		}

		if (isset(self::$__pdo)) {
			return self::$__pdo;
		}

		$dsn = sprintf("mysql:host=%s;dbname=%s;charset=%s;", self::$__host, self::$__dbname, self::$__charset);
		$options = array(
				PDO::ATTR_PERSISTENT => true,
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
				PDO::ATTR_STRINGIFY_FETCHES => false,
				// PDO::ATTR_EMULATE_PREPARES => false,
		);
		return self::$__pdo = new PDO($dsn, self::$__user, self::$__password, $options);
	}
	
	/**
	 * 获取主键列名
	 * @return string
	 */
	public function getPK() {
		return $this->pk;
	}

	/**
	 * 设置表前缀
	 * @param string $prefix
	 * @return Table
	 */
	public function setPrefix($prefix) {
		$this->prefix = $prefix;
		$this->_table = $this->prefix . $this->table;
		return $this;
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
	 * @param array $params
	 * @return PDOStatement
	 */
	public function vquery($sql, array $params = array()) {
		if (strpos($sql, "'") !== false) {
			throw new Exception("a ha ha ha ha ha ha!");
		}
		if ($this->debug) {
			var_dump($sql, $params);
		}
		$stmt = $this->getPDO()->prepare($sql);
		foreach ($params as $i => $param) {
			$stmt->bindValue($i + 1, $param, $this->getParamType($param));
		}
		$this->reset();
		$stmt->executeResult = $stmt->execute();
		return $stmt;
	}

	/**
	 * 获取参数类型
	 * @param mixed $param
	 * @return integer
	 */
	public function getParamType($param) {
		$type = gettype($param);
		return array_key_exists($type, self::$param_types) ? self::$param_types[$type] : PDO::PARAM_STR;
	}

	/**
	 * 查询数据
	 * @param string $columns
	 * @return PDOStatement
	 */
	public function select($columns = null) {
		if (!empty($columns)) {
			$this->_columns[] = $columns;
		}

		$keywords 	= empty($this->_keywords) 	? ""  : " " . implode(" ", $this->_keywords);
		$columns 	= empty($this->_columns) 	? "*" : implode(", ", $this->_columns);
		$table 		= $this->_table . (empty($this->_joins) ? "" : "` AS `" . $this->tab);
		$joins 		= empty($this->_joins) 		? ""  : " LEFT JOIN " . implode(" LEFT JOIN ", $this->_joins);
		$wheres 	= empty($this->_wheres) 	? ""  : " WHERE " . implode(" AND ", $this->_wheres);
		$groups 	= empty($this->_groups) 	? ""  : " GROUP BY " . implode(", ", $this->_groups);
		$havings 	= empty($this->_havings) 	? ""  : " HAVING " . implode(" AND ", $this->_havings);
		$orders 	= empty($this->_orders) 	? ""  : " ORDER BY " . implode(", ", $this->_orders);
		$limit 		= !isset($this->_limit) 	? ""  : " LIMIT ?";
		$offset 	= !isset($this->_offset) 	? ""  : " OFFSET ?";
		$forUpdate 	= $this->_for_update;
		$lockInShareMode = $this->_lock_in_share_mode;
		$sql = sprintf("SELECT%s %s FROM `%s`%s%s%s%s%s%s%s%s%s", $keywords, $columns, $table, $joins, $wheres, $groups, $havings, $orders, $limit, $offset, $forUpdate, $lockInShareMode);

		$params = array_merge($this->_wheres_params, $this->_havings_params);
		if (isset($this->_limit)) {
			$params[] = $this->_limit;
		}
		if (isset($this->_offset)) {
			$params[] = $this->_offset;
		}

		// $this->_count_wheres = $this->_wheres;
		// $this->_count_wheres_params = $this->_wheres_params;

		return $this->vquery($sql, $params);
	}

	/**
	 * 添加数据
	 * @param array $data
	 * @return PDOStatement
	 */
	public function insert(array $data) {
		$sets = array();
		$params = array();
		foreach ($data as $col => $val) {
			$sets[] = sprintf("`%s` = ?", $col);
			$params[] = $val;
		}
		$sql = sprintf("INSERT INTO `%s` SET %s", $this->_table, implode(", ", $sets));
		return $this->vquery($sql, $params);
	}

	/**
	 * 批量插入数据
	 * @param array $columns
	 * @param array $rows
	 * @param number $batch
	 * @return Table
	 */
	public function batchInsert(array $columns, array &$rows, $batch = 1000) {
		$column = implode("`,`", $columns);
		$value = ",(?" . str_repeat(",?", count($columns) - 1) . ")";
		$params = array();
		$len = count($rows);
		for ($i = 0; $i < $len; $i++) {
			$params = array_merge($params, $rows[$i]);
			if (($i + 1) % $batch == 0) {
				$sql = sprintf("INSERT INTO `%s` (`%s`) VALUES %s%s", $this->_table, $column, substr($value, 1), str_repeat($value, $batch - 1));
				$this->vquery($sql, $params);
				$params = array();
			}
		}
		if ($len % $batch > 0) {
			$sql = sprintf("INSERT INTO `%s` (`%s`) VALUES %s%s", $this->_table, $column, substr($value, 1), str_repeat($value, $len % $batch - 1));
			$this->vquery($sql, $params);
		}
		return $this;
	}

	/**
	 * 更新数据
	 * @param array $data
	 * @return PDOStatement
	 */
	public function update(array $data) {
		if (empty($this->_wheres)) {
			throw new Exception("WHERE is empty!");
		}
		$sets = array();
		$params = array();
		foreach ($data as $col => $val) {
			$sets[] = sprintf("`%s` = ?", $col);
			$params[] = $val;
		}
		$wheres = " WHERE " . implode(" AND ", $this->_wheres);
		$sql = sprintf("UPDATE `%s` SET %s%s", $this->_table, implode(", ", $sets), $wheres);
		$params = array_merge($params, $this->_wheres_params);
		return $this->vquery($sql, $params);
	}

	/**
	 * 替换数据
	 * @param array $data
	 * @return PDOStatement
	 */
	public function replace(array $data) {
		$sets = array();
		$params = array();
		foreach ($data as $col => $val) {
			$sets[] = sprintf("`%s` = ?", $col);
			$params[] = $val;
		}
		$sql = sprintf("REPLACE INTO `%s` SET %s", $this->_table, implode(", ", $sets));
		return $this->vquery($sql, $params);
	}

	/**
	 * 删除数据
	 * @return PDOStatement
	 */
	public function delete($id = 0) {
		if (!empty($id)) {
			$this->where(sprintf("%s = ?", $this->pk), $id);
		}
		if (empty($this->_wheres)) {
			throw new Exception("WHERE is empty!");
		}
		$wheres = " WHERE " . implode(" AND ", $this->_wheres);
		$sql = sprintf("DELETE FROM `%s`%s", $this->_table, $wheres);
		return $this->vquery($sql, $this->_wheres_params);
	}

	/**
	 * 重置所有
	 * @return Table
	 */
	public function reset() {
		$this->_keywords 		= array();
		$this->_columns 		= array();
		$this->_joins 			= array();
		$this->_wheres 			= array();
		$this->_wheres_params 	= array();
		$this->_groups 			= array();
		$this->_havings 		= array();
		$this->_havings_params 	= array();
		$this->_orders 			= array();
		$this->_limit 			= null;
		$this->_offset 			= null;
		$this->_for_update 		= "";
		$this->_lock_in_share_mode = "";
		return $this;
	}

	/**
	 * 设置MySQL关键字
	 * @param string $keyword
	 * @return Table
	 */
	public function keyword($keyword) {
		$this->_keywords[] = $keyword;
		return $this;
	}

	/**
	 * 设置SQL_CALC_FOUND_ROWS关键字
	 * @return Table
	 */
	public function calcFoundRows() {
		return $this->keyword("SQL_CALC_FOUND_ROWS");
	}

	/**
	 * column返回的列
	 * @param string $column
	 * @return Table
	 */
	public function column($column) {
		$this->_columns[] = $column;
		return $this;
	}
	
	/**
	 * join连表查询
	 * @param string $join
	 * @param string $cond
	 * @return Table
	 */
	public function join($join, $cond) {
		$this->_joins[] = sprintf("%s ON %s", $join, $cond);
		return $this;
	}

	/**
	 * where查询条件
	 * @param string $where
	 * @return Table
	 */
	public function where($where) {
		$args = func_get_args();
		array_shift($args);

		$ws = explode("?", $where);
		$where = array_shift($ws);
		$params = array();
		foreach ($ws as $i => $w) {
			if (is_array($args[$i])) {
				$where .= "?" . str_repeat(",?", count($args[$i]) - 1) . $w;
				$params = array_merge($params, $args[$i]);
			} else {
				$where .= "?" . $w;
				$params[] = $args[$i];
			}
		}

		$this->_wheres[] = $where;
		$this->_wheres_params = array_merge($this->_wheres_params, $params);
		return $this;
	}

	/**
	 * group分组
	 * @param string $group
	 * @return Table
	 */
	public function group($group) {
		$this->_groups[] = $group;
		return $this;
	}

	/**
	 * having过滤条件
	 * @param string $having
	 * @return Table
	 */
	public function having($having) {
		$args = func_get_args();
		array_shift($args);

		$ws = explode("?", $having);
		$having = array_shift($ws);
		$params = array();
		foreach ($ws as $i => $w) {
			if (is_array($args[$i])) {
				$having .= "?" . str_repeat(",?", count($args[$i]) - 1) . $w;
				$params = array_merge($params, $args[$i]);
			} else {
				$having .= "?" . $w;
				$params[] = $args[$i];
			}
		}

		$this->_havings[] = $having;
		$this->_havings_params = array_merge($this->_havings_params, $params);
		return $this;
	}

	/**
	 * order排序
	 * @param string $order
	 * @return Table
	 */
	public function order($order) {
		$this->_orders[] = $order;
		return $this;
	}

	/**
	 * limit数据
	 * @param number $limit
	 * @return Table
	 */
	public function limit($limit) {
		$this->_limit = intval($limit);
		return $this;
	}

	/**
	 * offset偏移
	 * @param number $offset
	 * @return Table
	 */
	public function offset($offset) {
		$this->_offset = intval($offset);
		return $this;
	}

	/**
	 * 独占锁，不可读不可写
	 * @return Table
	 */
	public function forUpdate() {
		$this->_for_update = " FOR UPDATE";
		return $this;
	}

	/**
	 * 共享锁，可读不可写
	 * @return Table
	 */
	public function lockInShareMode() {
		$this->_lock_in_share_mode = " LOCK IN SHARE MODE";
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
		$page = intval($page);
		$pagesize = intval($pagesize);
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
		return $this->vquery("SELECT FOUND_ROWS()")->fetchColumn();
		// $wheres = empty($this->_count_wheres) ? "" : " WHERE " . implode(" AND ", $this->_count_wheres);
		// $sql = sprintf("SELECT count(*) FROM `%s`%s", $this->_table, $wheres);
		// return $this->vquery($sql, $this->_count_wheres_params)->fetchColumn();
	}

	/**
	 * 将选中行的指定字段加一
	 * @param string $col
	 * @param number $val
	 * @return Table
	 */
	public function plus($col, $val = 1) {
		$sets = array(sprintf("`%s` = `%s` + ?", $col, $col));
		$vals = array($val);
		$args = array_slice(func_get_args(), 2);
		while (count($args) > 1) {
			$col = array_shift($args);
			$val = array_shift($args);
			$sets[] = sprintf("`%s` = `%s` + ?", $col, $col);
			$vals[] = $val;
		}
		if (empty($this->_wheres)) {
			throw new Exception("WHERE is empty!");
		}
		$wheres = " WHERE " . implode(" AND ", $this->_wheres);
		$sql = sprintf("UPDATE `%s` SET %s%s", $this->_table, implode(", ", $sets), $wheres);
		$params = array_merge($vals, $this->_wheres_params);
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
		if (empty($this->_wheres)) {
			throw new Exception("WHERE is empty!");
		}
		$wheres = " WHERE " . implode(" AND ", $this->_wheres);
		$sql = sprintf("UPDATE `%s` SET `%s` = last_insert_id(`%s` + ?)%s", $this->_table, $col, $col, $wheres);
		$params = array_merge(array($val), $this->_wheres_params);
		$this->vquery($sql, $params);
		return $this->getPDO()->lastInsertId();
	}

	/**
	 * 根据主键查找行
	 * @param number $id
	 * @return array
	 */
	public function find($id) {
		return $this->where(sprintf("`%s` = ?", $this->pk), $id)->select()->fetch();
	}

	/**
	 * 保存数据,自动判断是新增还是更新
	 * @param array $data
	 * @return PDOStatement
	 */
	public function save(array $data) {
		if (array_key_exists($this->pk, $data)) {
			$pk_val = $data[$this->pk];
			unset($data[$this->pk]);
			return $this->where(sprintf("`%s` = ?", $this->pk), $pk_val)->update($data);
		} else {
			return $this->insert($data);
		}
	}

	/**
	 * 获取外键数据
	 * @param array $rows
	 * @param string $foreign_key
	 * @param string $columns
	 * @return PDOStatement
	 */
	public function foreignKey(array $rows, $foreign_key, $columns = "*") {
		$ids = array_column($rows, $foreign_key);
		if (empty($ids)) {
			return new PDOStatement();
		}
		$ids = array_unique($ids);
		return $this->where(sprintf("`%s` in (?)", $this->pk), $ids)->select($columns);
	}
}
