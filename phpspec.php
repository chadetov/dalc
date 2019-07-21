<?
	class PHPSpec{

		private static $db_user = 'dbuser';
		private static $db_password = 'dbpass';
		private static $db_host = 'adbhost';
		private static $db_name = 'dbname';

		public function get_db_spec(){

			$result = new stdClass();
			$result->db_user = self::$db_user;
			$result->db_password = self::$db_password;
			$result->db_host = self::$db_host;
			$result->db_name = self::$db_name;

			return $result;
		}

	}

?>
