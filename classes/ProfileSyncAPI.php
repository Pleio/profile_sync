<?php

class ProfileSyncAPI extends ProfileSync {
	
	protected $processed;
	protected $profile_data;
	
	/**
	 * Set some initial data/attributes
	 *
	 * @return void
	 */
	protected function initialize() {
		parent::initialize();
		
		$this->processed = false;
		$this->profile_data = get_input('profile_data');
	}
	
	
	/**
	 * Connect to the datasource
	 *
	 * @return bool
	 */
	public function connect() {
		
		if (empty($this->profile_data)) {
			return false;
		}
		
		if (!is_array($this->profile_data)) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Get the available columns in the datasource
	 *
	 * @return false|array
	*/
	public function getColumns() {
		
		$datasource = $this->getDatasource();
		
		$available_fields = $datasource->api_available_fields;
		if (empty($available_fields)) {
			return false;
		}
		
		$columns = array();
		$lines = explode(PHP_EOL, $available_fields);
		foreach ($lines as $line) {
			$fields = explode(',', $line);
			if (empty($fields)) {
				continue;
			}
			
			foreach ($fields as $field) {
				$field = trim($field);
				if ($field === '') {
					continue;
				}
				
				$columns[] = $field;
			}
		}
		
		if (empty($columns)) {
			return false;
		}
		
		return array_combine($columns, $columns);
	}
	
	/**
	 * Get a row from the datasource
	 *
	 * @return false|array
	*/
	public function fetchRow() {
		
		if (!$this->connect()) {
			return false;
		}
		
		if ($this->processed) {
			return false;
		}
		
		// only one row
		$this->processed = true;
		
		return $this->profile_data;
	}
	
	/**
	 * Invalidate all cached data, run this after the sync is done
	 *
	 * @return void
	*/
	public function cleanup() {
		// nothing to do here
	}
}
