<?php
	// PONGSOCKET TWEET ARCHIVE
	// DB class
	// By Andy Graulund
	// <electricnet@gmail.com>
	
	class DB {
		public  $res    = null;
		public  $type   = "";
		public  $on     = false;
		public  $conn   = false;
		public  $mysqli = false;
		private $config = null;
		
		public function __construct($type, $config){
			if(in_array(strtolower(trim($type)), array("mysql" /* more to be added later */))){
				$this->type = strtolower(trim($type));
				if(!empty($config['hostname']) && !empty($config['username']) && !empty($config['password']) && !empty($config['database'])){
					$this->config = $config;
					$this->connect();
				} else {
					throw new Exception("Not enough config information.");
				}
			} else {
				throw new Exception("Unsupported database type.");
			}
		}
		
		protected function connect(){
			switch($this->type){
				case "mysql":
					// Check for MySQLi
					$this->mysqli = extension_loaded("mysqli");
					try {
						$this->on   = true;
						$this->res  = $this->mysqli ?
							new mysqli($this->config['hostname'], $this->config['username'], $this->config['password'], $this->config['database']) :
							mysql_connect($this->config['hostname'], $this->config['username'], $this->config['password']);
						if(!$this->mysqli){ mysql_select_db($this->config['database'], $this->res); }
						$this->conn = true;
						if($this->mysqli && $this->res->connect_error){
							$this->conn = false;
							throw new Exception("Could not connect to the DB: " . $this->res->connect_error);
						} else {
							if(!$this->res){
								$this->conn = false;
								throw new Exception("Could not connect to the DB: " . mysql_error($this->res));
							}
						}
					} catch(Exception $e){
						throw new Exception("Could not connect to the DB: " . $e->getMessage());
					}
					break;
			}
		}
		
		public function query($query, $result = MYSQLI_STORE_RESULT){
			return $this->mysqli ? $this->res->query($query, $result) : mysql_query($query, $this->res);
		}
		
		public function fetch($result = NULL){
			if($this->mysqli){
				if(!$result || !is_object($result)){ return false; }
				return $result->fetch_assoc();
			} else {
				return mysql_fetch_assoc($result);
			}
		}
		
		public function numRows($result = NULL){
			if($this->mysqli){
				if(!$result || !is_object($result)){ return false; }
				return $result->num_rows;
			} else {
				return mysql_num_rows($result);
			}
		}
		
		public function insertID(){
			return $this->mysqli ? $this->res->insert_id : mysql_insert_id($this->res);
		}
		
		public function error(){
			return $this->mysqli ? $this->res->error : mysql_error($this->res);
		}
		
		public function s($str){
			return $this->mysqli ? $this->res->real_escape_string($str) : mysql_real_escape_string($str, $this->res);
		}
		
		public function clientVersion(){
			return $this->_getMySQLVersion($this->mysqli ? $this->res->get_client_info() : mysql_get_client_info());
		}
		
		public function serverVersion(){
			return $this->_getMySQLVersion($this->mysqli ? $this->res->server_info : mysql_get_server_info());
		}
		
		public function reconnect(){
			if($this->mysqli){
				$this->res->kill($this->res->thread_id);
				$this->res->close();
			} else {
				mysql_close();
			}
			$this->res  = null;
			$this->conn = false;
			$this->connect();
		}
		
		private function _getMySQLVersion($versionstring){
			if(preg_match("/^mysqlnd ([0-9\.]+)/", $cv, $matches)){
				return $matches[1];
			}
			return $versionstring;
		}
	}