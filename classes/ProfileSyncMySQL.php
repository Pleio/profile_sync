<?php
/**
 * ProfileSyncMySQL connect to a mysql datasource
 *
 * @package ProfileSync
 *
 */
class ProfileSyncMySQL extends ProfileSync {
	
	protected $mysqli;
	
	protected $result;
	protected $result_row_count = 0;
	protected $result_row = 0;
	
	/**
	 * Connect to the MySQL DB
	 *
	 * @return bool
	 */
	public function connect() {
		
		if ($this->mysqli) {
			return true;
		}
		
		$datasource = $this->getDatasource();
		if (empty($datasource)) {
			return false;
		}
		
		if ($datasource->datasource_type !== "mysql") {
			return false;
		}
		
		$mysqli = new mysqli($datasource->dbhost, $datasource->dbusername, $datasource->dbpassword, $datasource->dbname, (int) $datasource->dbport);
		if ($mysqli->connect_errno) {
			return false;
		}
		
		// @todo make the charset into a setting of the datasource
		$mysqli->set_charset("utf8");
		
		$this->mysqli = $mysqli;
		
		return true;
	}
	
	/**
	 * Get the database query
	 *
	 * This helper function will fill in placeholders with actual values
	 *
	 * @return false|string
	 */
	protected function getDbQuery() {
		$datasource = $this->getDatasource();
		if (empty($datasource)) {
			return false;
		}
		
		$dbquery = $datasource->dbquery;
		if (empty($dbquery)) {
			return false;
		}
		
		// make sure we have the actual text, not the encoded version
		$dbquery = html_entity_decode($dbquery);
		
		// replace placeholders with actual values
		$dbquery = str_ireplace("[[lastrun]]", $this->lastrun, $dbquery);
		
		return $dbquery;
	}
	
	/**
	 * Get the available columns in the database
	 *
	 * @return false|array
	 */
	public function getColumns() {
		
		if (!$this->connect()) {
			return false;
		}
		
		// fake last run to get more results for the columns
		$lastrun = $this->lastrun;
		$this->lastrun = 0;
		
		$dbquery = $this->getDbQuery();
		if (empty($dbquery)) {
			// restore actual lastrun
			$this->lastrun = $lastrun;
			return false;
		}
		
		// restore actual lastrun
		$this->lastrun = $lastrun;
		
		$mysqli = $this->mysqli;
		$tmp = $mysqli->query($dbquery);
		
		if (!$tmp->num_rows) {
			return false;
		}
		
		$columns = array_keys($tmp->fetch_assoc());
		
		return array_combine($columns, $columns);
	}
	
	/**
	 * Get a row from the database
	 *
	 * @return false|array
	 */
	public function fetchRow() {
		
		if (!$this->connect()) {
			return false;
		}
		
		if (!isset($this->result)) {
			$this->result = false;
			
			$dbquery = $this->getDbQuery();
			if (empty($dbquery)) {
				return false;
			}
			
			$mysqli = $this->mysqli;
			$tmp = $mysqli->query($dbquery);
			
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
	
	/**
	 * Invalidate all cached data, run this after the sync is done
	 *
	 * @return void
	 */
	public function cleanup() {
		
		if ($this->result) {
			$this->result->free();
			
			unset($this->result);
		}
		
		if ($this->mysqli) {
			$this->mysqli->close();
			
			unset($this->mysqli);
		}
	}
}
