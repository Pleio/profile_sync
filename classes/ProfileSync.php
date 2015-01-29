<?php

/**
 * ProfileSync defines the functions available for synchronization
 *
 * @package ProfileSync
 *
 */
abstract class ProfileSync {
	
	protected $datasource;
	protected $lastrun;
	
	/**
	 * Create a connection to a datasource
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
	 * Fetch the datasource object
	 *
	 * @return false|ElggObject
	 */
	protected function getDatasource() {
		
		$datasource = $this->datasource;
		if (empty($datasource)) {
			return false;
		}
		
		if (!elgg_instanceof($datasource, "object", "profile_sync_datasource")) {
			return false;
		}
		
		return $datasource;
	}
	
	/**
	 * Connect to the datasource
	 *
	 * @return bool
	 */
	abstract public function connect();
	
	/**
	 * Get the available columns in the datasource
	 *
	 * @return false|array
	 */
	abstract public function getColumns();
	
	/**
	 * Get a row from the datasource
	 *
	 * @return false|array
	 */
	abstract public function fetchRow();
	
	/**
	 * Invalidate all cached data, run this after the sync is done
	 *
	 * @return void
	 */
	abstract public function cleanup();
}