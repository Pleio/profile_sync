<?php
/**
 * ProfileSyncMySQL
 *
 * @package ProfileSync
 *
 */
class ProfileSyncMySQL {
	
	protected $datasource;
	protected $lastrun;
	protected $mysqli;
	
	protected $result;
	protected $result_row_count = 0;
	protected $result_row = 0;
	
	/**
	 * Create a Datasource connection to a MySQL DB
	 *
	 * @param ElggObject $datasource the datasource configuration
	 * @param int        $lastrun    the timestamp of the sync config last run
	 *
	 * @return void
	 */
	public function __construct(ElggObject $datasource, $lastrun = 0) {
		$this->datasource = $datasource;
		$this->lastrun = (int) $lastrun;
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
	 * @return bool|string
	 */
	protected function getDbQuery() {
		$datasource = $this->datasource;
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
	 * @return bool|array:
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
}
