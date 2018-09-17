<?php
	
	class TestClass{

		private $db;

		public function __construct(Dalc db){
			$this->db = $db;
		}

		public function testMethod(){

			$db->clear();
			$db->table('user');
			return $db->get('*', '1');
		}

	}
?>