<?php

namespace ColdTrick\ProfileSync;

class Logger {
	
	const ERROR = 'ERROR';
	const WARNING = 'WARNING';
	const NOTICE = 'NOTICE';
	const DEBUG = 'DEBUG';
	const STATS = 'STATS';
	
	protected $fh;
	protected $sync_config_guid;
	protected $elgg_debug_level;
	
	protected $log_errors;
	
	/**
	 * Constructor
	 *
	 * @param int $sync_config_guid the sync_config_guid for this logger
	 *
	 * @return void
	 */
	public function __construct($sync_config_guid) {
		
		$sync_config_guid = sanitise_int($sync_config_guid);
		if (!empty($sync_config_guid)) {
			$this->sync_config_guid = $sync_config_guid;
		}
		
		$this->elgg_debug_level = elgg_get_config('debug');
		$this->log_errors = array();
	}
	
	/**
	 * Write a line to the log file
	 *
	 * @param string $text        the text to log
	 * @param string $level       the log level see \ColdTrick\ProfileSync\Logger constants
	 * @param int    $status_code optional status code for a log entry (only use in case of ERROR/WARNING)
	 *
	 * @return bool
	 */
	public function log($text, $level = self::NOTICE, $status_code = 0) {
		
		$status_code = sanitise_int($status_code);
		
		$fh = $this->getLogFilehandler();
		if (empty($fh)) {
			return false;
		}
		
		if (($level === self::DEBUG) && ($this->elgg_debug_level !== 'NOTICE')) {
			// don't write debugging to the log
			return true;
		}
		
		if (in_array($level, array(self::ERROR, self::WARNING))) {
			if ($status_code === 0) {
				$status_code = -1;
			}
			
			$this->log_errors[] = array(
				'text' => $text,
				'level' => $level,
				'status_code' => $status_code,
			);
		}
		
		fwrite($fh, $text . PHP_EOL);
		return true;
	}
	
	/**
	 * Close the log file
	 *
	 * @return void
	 */
	public function close() {
		
		$fh = $this->getLogFilehandler();
		if (empty($fh)) {
			return;
		}
		
		fclose($fh);
		unset($this->fh);
	}
	
	/**
	 * Get all the errors/warnings that where logged
	 *
	 * @return array
	 */
	public function getLogErrors() {
		return $this->log_errors;
	}
	
	/**
	 * Get the filehandler for writing in the log
	 *
	 * @return false|resource
	 */
	protected function getLogFilehandler() {
		
		if (!isset($this->fh)) {
			$this->initializeLogFilehandler();
		}
		
		return $this->fh;
	}
	
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
		$log_file->setFilename(time() . ".log");
		
		$log_file->open("write");
		$log_file->write("Start processing: " . date(elgg_echo("friendlytime:date_format")) . PHP_EOL);
		
		$this->fh = $log_file->open("append");
	}
}
