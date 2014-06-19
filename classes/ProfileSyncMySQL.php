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
	
	public function __construct(ElggObject $datasource) {
		$this->datasource = $datasource;
	}

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
}
