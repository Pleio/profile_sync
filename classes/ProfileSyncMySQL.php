<?php
/**
 * ProfileSyncMySQL
 *
 * @package ProfileSync
 *
 */
class ProfileSyncMySQL {
	
	protected $datasource;
	protected $mysqli;
	
	protected $result;
	protected $result_row_count = 0;
	protected $result_row = 0;
	
	/**
	 * Create a Datasource connection to a MySQL DB
	 *
	 * @param ElggObject $datasource the datasource configuration
	 *
	 * @return void
	 */
	public function __construct(ElggObject $datasource) {
		$this->datasource = $datasource;
	}

	/**
	 * Connect to the MySQL DB
	 *
	 * @return bool
	 */
	protected function connect() {
		
		if ($this->mysqli) {
			return true;
		}
		
		$datasource = $this->datasource;
		if ($datasource->datasource_type != "mysql") {
			return false;
		}
		
		$mysqli = new mysqli($datasource->dbhost, $datasource->dbusername, $datasource->dbpassword, $datasource->dbname, (int) $datasource->dbport);
		if ($mysqli->connect_errno) {
			return false;
		}
		
		$this->mysqli = $mysqli;
		
		return true;
	}
	
	/**
	 * Get the available columns in the database
	 *
	 * @return bool|array:
	 */
	public function getColumns() {
		
		if (!$this->connect()) {
			return false;
		}
		
		$datasource = $this->datasource;
		if (!$datasource->dbquery) {
			return false;
		}
		
		$mysqli = $this->mysqli;
		$tmp = $mysqli->query($datasource->dbquery);
		
		if (!$tmp->num_rows) {
			return false;
		}
		
		return array_keys($tmp->fetch_assoc());
	}
	
	/**
	 * Get a row from the database
	 *
	 * @return bool|array
	 */
	public function fetchRow() {
		
		if (!$this->connect()) {
			return false;
		}
		
		if (!isset($this->result)) {
			$this->result = false;
			
			$datasource = $this->datasource;
			if (!$datasource->dbquery) {
				return false;
			}
			
			$mysqli = $this->mysqli;
			$tmp = $mysqli->query($datasource->dbquery);
			
			if (!$tmp->num_rows) {
				return false;
			}
			
			$this->result = $tmp;
			$this->result_row_count = $tmp->num_rows;
		}
		
		if ($this->result === false) {
			return false;
		}
		
		if ($this->result_row >= $this->result_row_count) {
			return false;
		}
		
		$this->result->data_seek($this->result_row);
		$row = $this->result->fetch_assoc();
		if ($row !== false) {
			$this->result_row++;
			return $row;
		}
		
		return false;
	}
}
