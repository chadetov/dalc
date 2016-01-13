<?php 
	
	namespace Dalc;

	interface Dalc{
		public function connect();
		public function clear();
		public function table($table_name);
		public function add($column, $value);
		public function insert();
		public function get($columns, $limit);
		public function where($column_name, $operator, $value, $cluster='');
		public function update();
		public function delete();
		public function error();
		public function pick_random();
		public function groupby($column_name);
		public function having($condition);
		public function set($column_name, $value);
		public function begin();
		public function rollback();
		public function commit();
		public function optimize($table_name);
	}

?>