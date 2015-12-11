<?php

namespace ColdTrick\ProfileSync;

class APILogger extends Logger {
	
	/**
	 * Initialize the filehandler for logging
	 *
	 * @return void
	 */
	protected function initializeLogFilehandler() {
		
		if (isset($this->fh)) {
			return;
		}
		
		$this->fh = false;
		
		if (empty($this->sync_config_guid)) {
			return;
		}
		
		$log_file = new \ElggFile();
		$log_file->owner_guid = $this->sync_config_guid;
		$log_file->setFilename(mktime(date('H'), 0, 0) . ".log"); // filename per hour
		
		if (!$log_file->exists()) {
			$log_file->open("write");
			$log_file->write("Starting new logfile: " . date(elgg_echo("friendlytime:date_format")) . PHP_EOL);
		}
		
		$this->fh = $log_file->open("append");
		
		$log_file->write("Start processing: " . date(elgg_echo("friendlytime:date_format")) . PHP_EOL);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \ColdTrick\ProfileSync\Logger::close()
	 */
	public function close() {
		
		$this->log('=====================================================');
		
		return parent::close();
	}
}
