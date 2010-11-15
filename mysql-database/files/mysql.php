<?php
/**
* MySQL Connection File
*
* Easy to use MySQL Connection and ORM
*
* Licensed under the MIT license.
*
* @category   Orchestra
* @copyright  Copyright (c) 2010, Christopher Hein
* @license    http://orchestramvc.chrishe.in/license
* @version    Release: 0.0.1:beta
* @link       http://orchestramvc.chrishe.in/docs/lib/database/mysql/
*
*/
require(ROOT.'/config/config.php');
require(ROOT.'/config/database.php');

class Database {
  protected $_handler;
  protected $_server;
  protected $_user;
  protected $_password;
  protected $_database;
  protected $_status;
	protected $_result;
  
  protected $_table;
  protected $_order;
  protected $_limit;
  
  public function __construct() {
    global $app;
    global $db;
    
    $this->_server    = $db[$app['status']]['server'];
    $this->_user      = $db[$app['status']]['user'];
    $this->_password  = $db[$app['status']]['password'];
    $this->_database  = $db[$app['status']]['name'];
    $this->status     = 0;
  }
  
  /* Connection
      Gaining access to the database to run querys
  */
  private function connect($persistant = false) {
    if($persistant == true) {
      $this->_handle = mysql_pconnect($this->_server, $this->_user, $this->_password);
    } else {
      $this->_handle = mysql_connect($this->_server, $this->_user, $this->_password);
    }
    if(!$this->_handle) {
      $hooks->generate_error("MySQL Connection Error, using: ".$this->server." :: ".$this->_user." :: ".$this->_password, "500.php", E_USER_ERROR);
    } else {
      $this->_status = 1;
      return $this->_handle;
    }
  }
  
  /* Disconnection
      Kill the connection to the database
  */
  private function disconnect() {
    $this->_status = 0;
    mysql_close($this->_handle);
    return true;
  }

	/* Create Table
      Run the is with an array of options to create tables
  */
	public function up($db, $items) {
		$this->connect();
		$sql = "CREATE TABLE ".$this->_database.".".$db." (";
		$sql .= "id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY, ";
		foreach($items as $i) {
			$rm = split(" => ", $i);
			switch($rm[0]) {
				case 'string':
					$sql .= "".$rm[1]." VARCHAR(255) NOT NULL, ";
				break;
				case 'text':
					$sql .= "".$rm[1]." TEXT NOT NULL, ";
				break;
				case 'integer':
				break;
			}
		}
		$sql .= "created_at TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00', updated_at TIMESTAMP NOT NULL DEFAULT now() on update now()";
		$sql .= ");";
		$run = mysql_query($sql);
		$this->disconnect();
	}
	
	/* Drop Table
      Run the is with a table name to drop the table
  */	
	public function down($table) {
		$this->connect();
		$sql = "DROP TABLE ".$this->_database.".".$table.";";
		$run = mysql_query($sql);
		$this->disconnect();
	}
  
  /* Select All
      Select items in the table
  */
  public function select_all($table) {
		if($table != NULL) {
			$this->connect();
			$sql = "SELECT * FROM ".$this->_database.".".$table.";";
			return $this->query($sql);
			$this->disconnect();
		}
  }
  
	/* Select
      Select items in the table
  */
	public function select($table, $id, $where = 'id') {
		if($table != NULL) {
			$this->connect();
			if($where != 'id') { $id = "'".$id."'"; }
			$sql = "SELECT * FROM ".$this->_database.".".$table." WHERE $where = $id;";
			return $this->query($sql, 1);
			$this->disconnect();
		}
	}
	
	/* Create
      Create new rows in the table
			-- Schema $db->create('${TABLE}', array('${FIELD}' => '${VALUE}'));
  */
  public function create($table, $fields) {
		if($table || $fields != NULL) {
			unset($fields['submit']);
			$this->connect();
			$values = "";
			foreach($fields as $f) {
				$values .= "'".mysql_real_escape_string($f)."', ";
			}
			$sql = "INSERT INTO ".$this->_database.".".$table." VALUES ( NULL, ".$values." NULL, NULL);";
			$run = mysql_query($sql);
			$this->disconnect();
		}
  }
  
	/* Update
      Update new rows in the table
  */
  public function update($table, $fields) {
		if($table || $fields != NULL) {
			$id = $fields['id'];
			$this->connect();
			unset($fields['id']);
			unset($fields['submit']);
			$this->connect();
			$values = "";
			while(list($k, $v) = each($fields)) {
				$values .= "$k='".mysql_real_escape_string($v)."', ";
			}
			$sql = "UPDATE ".$this->_database.".".$table." SET $values updated_at=NULL WHERE id = ".$id.";";
			$run = mysql_query($sql);
			$this->disconnect();
		}
  }
	
	/* Find
			Find By ID
	*/
	function find($table, $id) {
		$this->connect();
		$sql = "SELECT id FROM ".$this->_database.".".$table." WHERE id = $id";
		$run = mysql_query($sql);
		if($run) {
			$item = mysql_fetch_assoc($run);
			return $item['id'];
		} else {
			return 0;
		}
		$this->disconnect();
	}
	
	function login($table, $fields = array()) {
		$this->connect();
		$sql = "SELECT * FROM ".$this->_database.".".$table." WHERE name= '".mysql_real_escape_string($fields['username'])."' && hashed_password='".mysql_real_escape_string($fields['password'])."' LIMIT 1;";
		$run = mysql_query($sql);
		if($run) {
			$user = mysql_fetch_assoc($run);
			return $user['id'];
		} else {
			return false;
		}
		$this->disconnect();
	}

  /* Delete
      Delete Rows in a table
			-- Schema $db->destroy('${TABLE}', '${VALUE}');
  */
  public function destroy($table, $id, $where = 'id') {
		if($table || $value != NULL) {
  		$this->connect();
			if($id == 'all') {
				$sql = "DELETE FROM ".$this->_database.".".$table.";";
			} else {
				if($where != 'id') { $id = "'".$id."'"; }
				$sql = "DELETE FROM ".$this->_database.".".$table." WHERE $where = $id;";
			}
			$run = mysql_query($sql);
			$this->disconnect();
		} 
  }

	public function query($query, $singleResult = 0) {
		$this->_result = mysql_query($query);
		if (preg_match("/select/i",$query)) {
			$result = array();
			$table = array();
			$field = array();
			$tempResults = array();
			$numOfFields = mysql_num_fields($this->_result);
			for ($i = 0; $i < $numOfFields; ++$i) {
				array_push($table, mysql_field_table($this->_result, $i));
		  	array_push($field, mysql_field_name($this->_result, $i));
			}

			while ($row = mysql_fetch_row($this->_result)) {
				for ($i = 0;$i < $numOfFields; ++$i) {
					$table[$i] = trim(ucfirst($table[$i]),"s");
					$tempResults[$table[$i]][$field[$i]] = $row[$i];
				}
				if ($singleResult == 1) {
		 			mysql_free_result($this->_result);
					return $tempResults;
				}
				array_push($result, $tempResults);
			}
			mysql_free_result($this->_result);
			return($result);
		}

	}
	

}