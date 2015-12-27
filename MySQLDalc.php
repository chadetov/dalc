<?
    class MySQLDalc implements Dalc{

		private $is_debug = false;
		private $table_name;
		private $insert_columns; // column1, column2, column3
		private $insert_values; // value1, value2, value3
		private $order_by;
		private $group_by;
	    private $having;
		private $where_clauses;
		private $set_clause;

		function __construct(){}

		function __destruct(){}

		public function activate_test()
		{
			$this->is_debug = true;
		}

		public function connect()
		{
			$dbp = json_decode(file_get_contents('mysql.json');

		    $link = mysql_connect($dbp->server, $dbp->user, $dbp->password);
		    if (!$link) {
			    die(mysql_error());
		    }
		    mysql_set_charset('utf8', $link); 
		    $db_selected = mysql_select_db($dbp->dbname, $link);
	            
		    if (!$db_selected) {
			    die (mysql_error());
		    } 
		}

		public function clear(){
		    unset($this->table_name);
		    unset($this->insert_columns);
		    unset($this->insert_values);
		    unset($this->order_by);
		    unset($this->group_by);
            unset($this->having);
		    unset($this->where_clauses);
		    unset($this->set_clause);
		    unset($this->affected_rows);
		    //$this->debug = false;
		}

		public function table($table_name){
		    $this->table_name = $this->secure($table_name);
		}

		public function add($column, $value){
		    $column = $this->secure($column);
		    $value = $this->secure($value);

		    if(isset($this->insert_columns)){$this->insert_columns .= ', '.$column;}else{$this->insert_columns = $column;}
		    if(isset($this->insert_values)){$this->insert_values .= ', \''.$value.'\'';}else{$this->insert_values = '\''.$value.'\'';}
		}

		public function insert(){
		    $sql = 'INSERT INTO '.$this->table_name.' ('.$this->insert_columns.') VALUES ('.$this->insert_values.')';
		    return $this->run($sql);
		}

		public function get($columns, $limit){

		    $columns = $this->secure($columns);
		    $limit = $this->secure($limit);

		    if($limit){$ss_limit = ' LIMIT '.$limit;} else {$ss_limit = '';}

		    $where = $this->create_where_clause();

		    if(!isset($this->order_by)){
				$this->order_by = '';
		    }

		    if(!isset($this->group_by)){
				$this->group_by = '';
		    }
	            
            if(!isset($this->having)){
				$this->having = '';
		    }

		    $sql = 'SELECT '.$columns.' FROM '.$this->table_name.$where.$this->group_by.$this->having.$this->order_by.$ss_limit;
		    $result = $this->run($sql);


		    $list = array();

		    if($result->status){
				$numfields = mysql_num_fields($result->set);

				for($i=0; $i<$numfields; $i++){
				    $fieldsname[$i] = mysql_field_name($result->set, $i);
				}

				$i=0;
				while ($row = mysql_fetch_object($result->set)){
					$list[$i] = new stdClass();
				    foreach($fieldsname as $fieldname){
					    $list[$i]->$fieldname = $row->$fieldname;
				    }
				    $i++;
				}

		    }

		    $result->set = $list;
		    return $result;
		}

		private function create_where_clause(){

		    $final_where = '';

		    $noc = 0;
		    
		    if(isset($this->where_clauses)){
				$noc = count($this->where_clauses);
		    }

		    if($noc>0){
				for($i=0; $i<$noc; $i++){
					if($this->where_clauses[$i]['cluster'] != ''){
						$clauses[$this->where_clauses[$i]['cluster']][] = $this->where_clauses[$i]['statement'];
					}
				}

				for($i=0; $i<$noc; $i++){
					if($this->where_clauses[$i]['cluster'] == ''){
						$clauses[][] = $this->where_clauses[$i]['statement'];
					}
				}

				$final_where = ' WHERE';

				foreach($clauses as $clause){
					$nos = count($clause);
					if($nos == 1){
						$final_where .=	' '.$clause[0];
					}
					else{
						$final_where .=	' (';
						for($j=0; $j<$nos; $j++){
							$final_where .=	' '.$clause[$j].' OR';
						}
						$final_where = substr($final_where, 0, -3);
						$final_where .=	')';
					}
					$final_where .=	' AND';
				}

				$final_where = substr($final_where, 0, -4);
		    }

		    return $final_where;

		}

		public function where($column_name, $operator, $value, $cluster=''){

		    if($operator == 'IN'){
		    	// in this case, $value is an array:
		    	$cnt = count($value);
		    	$str = '';
		    	for($i=0; $i<$cnt; $i++){
		    		$str .= '"'.$this->secure($value[$i]).'",';
		    	}
		    	$str = '('.substr($str, 0, -1).')';

				$this->where_clauses[] = array('statement' => $this->secure($column_name).' '.$this->secure($operator).' '.$this->secure($str), 'cluster' => $cluster);
		    }
	        elseif($operator == '<' || $operator == '>'){
	            $this->where_clauses[] = array('statement' => $this->secure($column_name).' '.$operator.' \''.$this->secure($value).'\'', 'cluster' => $cluster);
	        }
		    elseif($operator == 'IS'){
				$this->where_clauses[] = array('statement' => $this->secure($column_name).' '.$this->secure($operator).' '.$this->secure($value), 'cluster' => $cluster);
		    }
		    elseif($column_name && $operator){
				$this->where_clauses[] = array('statement' => $this->secure($column_name).' '.$this->secure($operator).' \''.$this->secure($value).'\'', 'cluster' => $cluster);
		    }

		}

		public function update(){
		    $where = $this->create_where_clause();
		    $sql = 'UPDATE '.$this->table_name.' SET '.$this->set_clause.$where;
		    return $this->run($sql);
		}

		public function delete(){
		    $where = $this->create_where_clause();
		    if($where){
				$sql = 'DELETE FROM '.$this->table_name.$where;
				return $this->run($sql);
		    }
		}

		private function run($sql){

			$or = new stdClass();

		    if($this->is_debug){
				$or->sql = $sql;
		    }
		    else{
				$or->sql = 'Debug mode is off';
		    }

		    $resultset = mysql_query($sql);

		    if($resultset === false){
				$or->status = false;
				$or->error = mysql_error();
		    }
		    else if($resultset === true){
				// INSERT, UPDATE, DELETE, DROP performed succesfully
				$or->status  = true;
				$or->id = mysql_insert_id();
		    }
		    else{
				// SELECT, SHOW, DESCRIBE, EXPLAIN performed succesfully
				$or->status  = true;
				$or->set = $resultset;
				$or->nor = mysql_num_rows($resultset);
		    }

		    //$this->save($sql, $or->status, $or->error);

		    return $or;
		}

		private function save($sql, $qr, $err=''){
			
		    if(!empty($this->is_debug)) {
				$now = date('Y-m-d H:i:s');
				$sql = $this->secure($sql);
				$sql = 'INSERT INTO queries (query, date_created, session, qr, err) VALUES (\''.$sql.'\',\''.$now.'\',\''.serialize($_SESSION).'\', '.$qr.', \''.$err.'\')';
				mysql_query($sql);
		    }
		}

		private function secure($var){
			$var = str_replace('\'', '', $var);
			$var = str_replace('<script', '<', $var);


		    return $var;
		}

		public function error(){
		    return mysql_error();
		}

		public function pick_random(){
			$this->order_by = ' ORDER BY RAND()';
		}

		public function orderby($column_name, $order_param){
		    if($column_name){
				$column_name = $this->secure($column_name);
				$order_param = $this->secure($order_param);
			
				if($order_param == 'ASC' || $order_param == 'DESC' || $order_param == ''){
			    	if(!isset($this->order_by)){
						$this->order_by = ' ORDER BY '.$column_name.' '.$order_param;
			    	}
			    	else{
						$this->order_by .= ', '.$column_name.' '.$order_param;
			    	}
				}
		    }
		}

		public function groupby($column_name){
		    if($column_name){
				$column_name = $this->secure($column_name);

				if(!isset($this->group_by)){
				    $this->group_by = ' GROUP BY '.$column_name;
				}
				else{
				    $this->group_by .= ', '.$column_name;
				}
		    }
		}
	        
	    public function having($condition){
	            
	        if($condition){
				if(!isset($this->having)){
				    $this->having = ' HAVING '.$condition;
				}
		    }
	    }

		public function set($column_name, $value){

		    if(isset($this->set_clause)){
			    $this->set_clause .= ', ';
		    }

		    $column_name = $this->secure($column_name);
		    $value = $this->secure($value);

		    $this->set_clause .= ' '.$column_name.'=\''.$value.'\'';
		}

		public function begin(){
		    mysql_query('SET AUTOCOMMIT = 0');
		    mysql_query('START TRANSACTION');
		}

		public function rollback(){
		    mysql_query('ROLLBACK');
		    mysql_query('SET AUTOCOMMIT = 1'); 
		}

		public function commit(){
		    mysql_query('COMMIT');
		    mysql_query('SET AUTOCOMMIT = 1');
		}

		public function optimize($table_name){
			$this->run('OPTIMIZE TABLE '.$table);
		}

    }
?>