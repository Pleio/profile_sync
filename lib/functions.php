<?php
/**
 * All helper functions are bundled here
 */

use ColdTrick\ProfileSync\Logger;
use ColdTrick\ProfileSync\APILogger;

/**
 * Run the profile synchronization based on the provided configuration
 *
 * @param ElggObject $sync_config The sync configuration
 *
 * @return void
 */
function profile_sync_proccess_configuration(ElggObject $sync_config) {
	global $DB_QUERY_CACHE;
	
	if (empty($sync_config) || !elgg_instanceof($sync_config, "object", "profile_sync_config")) {
		return;
	}
	
	$datasource = $sync_config->getContainerEntity();
	if (empty($datasource) || !elgg_instanceof($datasource, "object", "profile_sync_datasource")) {
		return;
	}
	
	$logger = profile_sync_get_log_handler($sync_config->getGUID());
	if (!($logger instanceof Logger)) {
		return;
	}
	
	$sync_match = json_decode($sync_config->sync_match, true);
	$datasource_id = $sync_config->datasource_id;
	$profile_id = $sync_config->profile_id;
	$lastrun = (int) $sync_config->lastrun;
	
	$logger->log("Last run timestamp: {$lastrun} (" . date(elgg_echo("friendlytime:date_format"), $lastrun) . ")" . PHP_EOL);
	
	$profile_fields = elgg_get_config("profile_fields");
	
	if (empty($sync_match) || ($datasource_id === "") || empty($profile_id)) {
		$logger->log("Configuration error", Logger::ERROR);
		return;
	}
	
	if (!in_array($profile_id, array("name", "username", "email")) && !array_key_exists($profile_id, $profile_fields)) {
		$logger->log("Invalid profile identifier: {$profile_id}", Logger::ERROR);
		return;
	}
	
	$sync_source = profile_sync_get_datasource_handler($datasource, $lastrun);
	if (empty($sync_source)) {
		$logger->log("Invalid datasource type: {$datasource->datasource_type}", Logger::ERROR);
		return;
	}
	
	if (!$sync_source->connect()) {
		$logger->log("Unable to connect to the datasource", Logger::ERROR);
		return;
	}
	
	$datasource_id_fallback = $sync_config->datasource_id_fallback;
	$profile_id_fallback = $sync_config->profile_id_fallback;
	
	$create_user = (bool) $sync_config->create_user;
	$ban_user = (bool) $sync_config->ban_user;
	$unban_user = (bool) $sync_config->unban_user;
	$notify_user = (bool) $sync_config->notify_user;
	
	$create_user_name = false;
	$create_user_email = false;
	$create_user_username = false;
	
	if ($create_user) {
		$logger->log("User creation is allowed");
		
		foreach ($sync_match as $datasource_col => $datasource_config) {
			switch ($datasource_config["profile_field"]) {
				case "name":
					$create_user_name = $datasource_col;
					break;
				case "email":
					$create_user_email = $datasource_col;
					break;
				case "username":
					$create_user_username = $datasource_col;
					break;
			}
		}
		
		if (($create_user_name === false) || ($create_user_username === false) || ($create_user_email === false)) {
			$logger->log("Missing information to create users", Logger::WARNING);
			$logger->log("- name: {$create_user_name}", Logger::WARNING);
			$logger->log("- email: {$create_user_email}", Logger::WARNING);
			$logger->log("- username: {$create_user_username}", Logger::WARNING);
			$create_user = false;
		}
	}
	
	if ($ban_user) {
		$logger->log("Matching users will be banned");
	}
	
	if ($unban_user) {
		$logger->log("Matching users will be unbanned");
	}
	
	if ($ban_user && $create_user) {
		$logger->log("Both create and ban users is allowed, don't know what to do", Logger::ERROR);
		return;
	}
	
	if ($unban_user && $create_user) {
		$logger->log("Both create and unban users is allowed, don't know what to do", Logger::ERROR);
		return;
	}
	
	if ($ban_user && $unban_user) {
		$logger->log("Both ban and unban users is allowed, don't know what to do", Logger::ERROR);
		return;
	}
	
	// start the sync process
	set_time_limit(0);
	$query_backup = $DB_QUERY_CACHE;
	$DB_QUERY_CACHE = false;
	
	$dbprefix = elgg_get_config("dbprefix");
	$default_access = get_default_access();
	$ia = elgg_set_ignore_access(true);
	$site = elgg_get_site_entity();
	
	// we want to cache entity metadata on first __get()
	$metadata_cache = elgg_get_metadata_cache();
	$metadata_cache->setIgnoreAccess(false);
	
	$counters = array(
		"source rows" => 0,
		"empty source id" => 0,
		"duplicate email" => 0,
		"duplicate name" => 0,
		"duplicate profile field" => 0,
		"user not found" => 0,
		"user created" => 0,
		"user banned" => 0,
		"user unbanned" => 0,
		"empty attributes" => 0,
		"invalid profile field" => 0,
		"invalid source field" => 0,
		"processed users" => 0
	);
	
	$base_location = "";
	if ($sync_source instanceof ProfileSyncCSV) {
		// get base path
		$csv_location = $datasource->csv_location;
		$csv_filename = basename($csv_location);
			
		$base_location = rtrim(str_ireplace($csv_filename, "", $csv_location), DIRECTORY_SEPARATOR);
	}
	
	while (($source_row = $sync_source->fetchRow()) !== false) {
		$counters["source rows"]++;
		
		// let other plugins change the row data
		$params = array(
			'datasource' => $datasource,
			'sync_config' => $sync_config,
			'source_row' => $source_row
		);
		$source_row = elgg_trigger_plugin_hook('source_row', 'profile_sync', $params, $source_row);
		
		if (!is_array($source_row) || empty($source_row[$datasource_id])) {
			$counters["empty source id"]++;
			continue;
		}
		
		// find user
		$datasource_used_id = $datasource_id;
		$profile_used_id = $profile_id;
		$datasource_unique_id = elgg_extract($datasource_id, $source_row);
		
		$user = profile_sync_find_user($profile_id, $datasource_unique_id, $sync_config, $counters);
		
		// fallback user
		if (empty($user) && ($datasource_id_fallback !== '') && !empty($source_row[$datasource_id_fallback]) && !empty($profile_id_fallback)) {
			$logger->log("User not found: {$profile_id} => {$datasource_unique_id} trying fallback", Logger::DEBUG);
			
			$profile_used_id = $profile_id_fallback;
			$datasource_used_id = $datasource_id_fallback;
			$datasource_unique_id = elgg_extract($datasource_id_fallback, $source_row);
			
			$user = profile_sync_find_user($profile_id_fallback, $datasource_unique_id, $sync_config, $counters);
		}
		
		// check if we need to create a user
		if (empty($user) && $create_user) {
			
			$pwd = generate_random_cleartext_password();
			
			try {
				// convert to utf-8
				$username = profile_sync_filter_var($source_row[$create_user_username]);
				$name = profile_sync_filter_var($source_row[$create_user_name]);
				$email = profile_sync_filter_var($source_row[$create_user_email]);
				
				$user_guid = register_user($username, $pwd, $name, $email);
				if (!empty($user_guid)) {
					$counters["user created"]++;
					$logger->log("Created user: {$name}");
					
					$user = get_user($user_guid);
					
					if ($notify_user) {
						$subject = elgg_echo("useradd:subject");
						$body = elgg_echo("useradd:body", array(
							$user->name,
							$site->name,
							$site->url,
							$user->username,
							$pwd,
						));
						
						notify_user($user->getGUID(), $site->getGUID(), $subject, $body);
					}
				}
			} catch (RegistrationException $r) {
				$name = profile_sync_filter_var($source_row[$create_user_name]);
				$logger->log("Failure creating user: {$name} - {$r->getMessage()}", Logger::WARNING);
			}
		}
		
		// did we get a user
		if (empty($user)) {
			$counters["user not found"]++;
			$logger->log("User not found: {$profile_used_id} => {$datasource_unique_id}", Logger::WARNING);
			continue;
		}
		
		$logger->log("Processing user: {$user->name} found by using {$profile_used_id} => {$datasource_unique_id}", Logger::DEBUG);
		$counters["processed users"]++;
		
		// ban the user
		if ($ban_user) {
			// already banned?
			if (!$user->isBanned()) {
				$counters["user banned"]++;
				$user->ban("Profile Sync: " . $sync_config->title);
				$logger->log("User banned: {$user->name} ({$user->username})");
			}
			
			continue;
		}
		
		// unban the user
		if ($unban_user) {
			// already banned?
			if ($user->isBanned()) {
				$counters["user unbanned"]++;
				$user->unban();
				$logger->log("User unbanned: {$user->name} ({$user->username})");
			}
			
			continue;
		}
		
		// start of profile sync
		$special_sync_fields = array(
			"name",
			"username",
			"email",
			"user_icon_relative_path",
			"user_icon_full_path",
			"user_icon_base64",
		);
		
		foreach ($sync_match as $datasource_col => $profile_config) {
			$profile_field = elgg_extract("profile_field", $profile_config);
			$access = (int) elgg_extract("access", $profile_config, $default_access);
			$override = (bool) elgg_extract("always_override", $profile_config, true);
			
			if (!in_array($profile_field, $special_sync_fields) && !array_key_exists($profile_field, $profile_fields)) {
				$counters["invalid profile field"]++;
				continue;
			}
			if (!isset($source_row[$datasource_col])) {
				$counters["invalid source field"]++;
				continue;
			}
			
			$value = elgg_extract($datasource_col, $source_row);
			$value = profile_sync_filter_var($value);
			
			// prepare user icon handling
			$new_user_icontime = false;
			$base64_icon = false;
			
			switch ($profile_field) {
				case 'user_icon_relative_path':
					// get a user icon based on a relative file path/url
					// only works with file based datasources (eg. csv)
					if (!($sync_source instanceof ProfileSyncCSV)) {
						$logger->log("Can't fetch relative user icon path in non CSV datasouces: trying user {$user->name}", Logger::WARNING);
						continue(2);
					}
					
					if (empty($value)) {
						// nothing to do
						break;
					}
					
					// make new icon path
					$value = sanitise_filepath($value, false); // prevent abuse (like ../../......)
					$value = ltrim($value, DIRECTORY_SEPARATOR); // remove beginning /
					$value = $base_location . DIRECTORY_SEPARATOR . $value; // concat base location and rel path
					
					// load the mtime for the icon
					$new_user_icontime = @filemtime($value);
					
					break;
				case 'user_icon_full_path':
					// this is already a full working path
					break;
				case 'user_icon_base64':
					
					if (empty($value)) {
						// nothing to do
						break;
					}
					
					// use a separate setting for this icontime
					$current_iconhash = hash('sha256', $value);
					$stored_iconhash = $user->getPrivateSetting('profile_sync_base64_iconhash');
					
					if (empty($stored_iconhash)) {
						// new icon, so update
						// and store hash for future use
						$user->setPrivateSetting('profile_sync_base64_iconhash', $current_iconhash);
					} elseif ($current_iconhash !== $stored_iconhash) {
						// updated icon, so update
						// and store hash for future use
						$user->setPrivateSetting('profile_sync_base64_iconhash', $current_iconhash);
					} else {
						// get last save icontime
						$new_user_icontime = $user->icontime;
					}
					
					// create a temp file to store the icon
					$base64_icon = tempnam(sys_get_temp_dir(), $user->getGUID());
					
					// write the base64 decoded data
					file_put_contents($base64_icon, base64_decode($value));
					
					// rewrite the value to the new filename
					$value = $base64_icon;
					break;
			}
			
			// handle the actual data
			switch ($profile_field) {
				case "email":
					if (!is_email_address($value)) {
						continue(2);
					}
				case "username":
					if ($override && ($user->username !== $value)) {
						// new username, check for availability
						if (get_user_by_username($value)) {
							// already taken
							$logger->log("New username: {$value} for {$user->name} is already taken", Logger::WARNING);
							continue(2);
						}
					}
				case "name":
					if (empty($value)) {
						$counters["empty attributes"]++;
						$logger->log("Empty user attribute: {$datasource_col} for user {$user->name}", Logger::WARNING);
						continue(2);
					}
					
					if (isset($user->$profile_field) && !$override) {
						// don't override profile field
						$logger->log("Profile field already set: " . $profile_field . " for user " . $user->name, Logger::DEBUG);
						continue(2);
					}
					
					// check for the same value
					if ($user->$profile_field === $value) {
						// same value, no need to update
						continue(2);
					}
					
					// save user attribute
					$user->$profile_field = $value;
					$user->save();
					break;
				case "user_icon_base64":
				case "user_icon_relative_path":
				case "user_icon_full_path":
					// get a user icon based on a full file path/url
					
					if (!empty($user->icontime) && !$override) {
						// don't override icon
						$logger->log("User already has an icon: " . $user->name, Logger::DEBUG);
						continue(2);
					}
					
					// upload new icon
					$icon_sizes = elgg_get_config("icon_sizes");
					
					$fh = new ElggFile();
					$fh->owner_guid = $user->getGUID();
					
					if (empty($value)) {
						// no icon, so unset current icon
						if ($user->icontime) {
							// the user has an icon
							$logger->log("Removing icon for user: {$user->name}");
							
							foreach ($icon_sizes as $size => $icon_info) {
								$fh->setFilename("profile/{$user->getGUID()}{$size}.jpg");
								$fh->delete();
							}
							
							unset($user->icontime);
							$user->removePrivateSetting('profile_sync_base64_iconhash');
							unset($fh);
						}
						
						// on to the next field
						continue(2);
					}
					
					// try to get the user icon
					$icon_contents = file_get_contents($value);
					if (empty($icon_contents)) {
						$logger->log("Unable to fetch user icon: {$value} for user {$user->name}", Logger::WARNING);
						continue(2);
					}
					
					// was the user icon image updated
					if (($new_user_icontime !== false) && isset($user->icontime)) {
						$new_user_icontime = sanitise_int($new_user_icontime);
						$icontime = sanitise_int($user->icontime);
						
						if ($new_user_icontime === $icontime) {
							// base image has same modified time as user icontime, so skipp
							$logger->log("No need to update user icon for user: {$user->name}", Logger::DEBUG);
							continue(2);
						}
					}
					
					if ($new_user_icontime === false) {
						$new_user_icontime = time();
					}
					
					// write icon to a temp location for further handling
					$tmp_icon = tempnam(sys_get_temp_dir(), $user->getGUID());
					file_put_contents($tmp_icon, $icon_contents);
					
					// resize icon
					$icon_updated = false;
					foreach ($icon_sizes as $size => $icon_info) {
						$icon_contents = get_resized_image_from_existing_file($tmp_icon, $icon_info["w"], $icon_info["h"], $icon_info["square"], 0, 0, 0, 0, $icon_info["upscale"]);
						
						if (empty($icon_contents)) {
							continue;
						}
						
						$fh->setFilename("profile/{$user->getGUID()}{$size}.jpg");
						$fh->open("write");
						$fh->write($icon_contents);
						$fh->close();
						
						$icon_updated = true;
					}
					
					// did we have a successfull icon upload?
					if ($icon_updated) {
						$user->icontime = $new_user_icontime;
					}
					
					// cleanup
					unlink($tmp_icon);
					if (!empty($base64_icon)) {
						// base64 temp file
						unlink($base64_icon);
					}
					unset($fh);
					
					break;
				default:
					// check overrides
					if (isset($user->$profile_field) && !$override) {
						// don't override profile field
						$logger->log("Profile field already set: " . $profile_field . " for user " . $user->name, Logger::DEBUG);
						continue(2);
					}
					
					// convert tags
					if ($profile_fields[$profile_field] === "tags") {
						$value = string_to_tag_array($value);
					}
					
					// remove existing value
					if (empty($value)) {
						if (isset($user->$profile_field)) {
							unset($user->$profile_field);
						}
						continue(2);
					}
					
					// check for the same value
					if ($user->$profile_field === $value) {
						// same value, no need to update
						continue(2);
					}
					
					$logger->log("Updating {$profile_field} with value {$value} old value {$user->$profile_field}", Logger::DEBUG);
					
					// get the access of existing profile data
					$access = profile_sync_get_profile_field_access($user->getGUID(), $profile_field, $access);
					
					// save new value
					// need to delete and recreate as there is no way to update the field and keep access intact in Elgg 1.8
					unset($user->$profile_field);
					if (is_array($value)) {
						$multiple = false;
						foreach ($value as $interval) {
							create_metadata($user->getGUID(), $profile_field, $interval, '', $user->getGUID(), $access, $multiple);
							$multiple = true;
						}
					} else {
						create_metadata($user->getGUID(), $profile_field, $value, '', $user->getGUID(), $access);
					}
					
					break;
			}
		}
		
		// let others know we updated the user
		$update_event_params = array(
			'entity' => $user,
			'source_row' => $source_row,
			'sync_config' => $sync_config,
			'datasource' => $datasource
		);
		elgg_trigger_event('update_user', 'profile_sync', $update_event_params);
		
		// cache cleanup
		_elgg_invalidate_cache_for_entity($user->getGUID());
		$metadata_cache->clear($user->getGUID());
	}
	
	$logger->log(PHP_EOL);
	foreach ($counters as $name => $count) {
		if ($count < 1) {
			// don't log empty counters
			continue;
		}
		
		$logger->log($name . ": " . $count, Logger::STATS);
	}
	$logger->log(PHP_EOL . "End processing: " . date(elgg_echo("friendlytime:date_format")));
	
	// save last run
	$sync_config->lastrun = time();
	
	// cleanup datasource cache
	$sync_source->cleanup();
	// re-enable db caching
	$DB_QUERY_CACHE = $query_backup;
	// restore access
	elgg_set_ignore_access($ia);
	$metadata_cache->unsetIgnoreAccess();
}

/**
 * Get a log handler
 *
 * @param int  $sync_config_guid the sync config to get the logger for
 * @param bool $close            close the logger (default: false)
 *
 * @return bool|ColdTrick\ProfileSync\Logger
 */
function profile_sync_get_log_handler($sync_config_guid, $close = false) {
	static $loggers;
	
	if (!isset($loggers)) {
		$loggers = array();
	}
	
	$sync_config_guid = sanitise_int($sync_config_guid, false);
	if (empty($sync_config_guid)) {
		return false;
	}
	
	if (!isset($loggers[$sync_config_guid])) {
		$loggers[$sync_config_guid] = false;
		
		$sync_config = get_entity($sync_config_guid);
		$datasource = $sync_config->getContainerEntity();
		
		switch ($datasource->datasource_type) {
			case 'api':
				$loggers[$sync_config_guid] = new APILogger($sync_config_guid);
				break;
			default:
				$loggers[$sync_config_guid] = new Logger($sync_config_guid);
				break;
		}
	}
	
	$close = (bool) $close;
	if ($close && !empty($loggers[$sync_config_guid])) {
		$loggers[$sync_config_guid]->close();
		unset($loggers[$sync_config_guid]);
		return true;
	}
	
	return elgg_extract($sync_config_guid, $loggers, false);
}

/**
 * Close the log handler for a file
 *
 * @param int $sync_config_guid the sync config to close the logger for
 *
 * @return bool
 */
function profile_sync_close_log($sync_config_guid) {
	
	$close = profile_sync_get_log_handler($sync_config_guid, true);
	
	return ($close === true);
}

/**
 * Get the sync logs, newest log first
 *
 * @param ElggObject $sync_config the sync config to get the logs for
 * @param bool       $with_label  add readable labels to the output (default: true)
 *
 * @return false|array
 */
function profile_sync_get_ordered_log_files(ElggObject $sync_config, $with_label = true) {
	
	if (empty($sync_config) || !elgg_instanceof($sync_config, "object", "profile_sync_config")) {
		return false;
	}
	
	$with_label = (bool) $with_label;
	
	$fh = new ElggFile();
	$fh->owner_guid = $sync_config->getGUID();
	$fh->setFilename("temp");
	
	$dir = $fh->getFilenameOnFilestore();
	$dir = substr($dir, 0, strlen($dir) - 4);
	
	$dh = opendir($dir);
	if (empty($dh)) {
		return false;
	}
	
	$files = array();
	while (($file = readdir($dh)) !== false) {
		if (is_dir($dir . $file)) {
			continue;
		}
		
		if ($with_label) {
			list($time) = explode(".", $file);
			$files[$file] = date(elgg_echo("friendlytime:date_format"), $time);
		} else {
			$files[] = $file;
		}
	}
	
	closedir($dh);
	
	if ($with_label) {
		krsort($files);
	} else {
		natcasesort($files);
		$files = array_reverse($files);
	}
	
	return $files;
}

/**
 * Cleanup the oldes logfiles of a sync config, based of the settings of the sync config
 *
 * @param ElggObject $sync_config the sync config to cleanup the logs for
 *
 * @return bool
 */
function profile_sync_cleanup_logs(ElggObject $sync_config) {
	
	if (empty($sync_config) || !elgg_instanceof($sync_config, "object", "profile_sync_config")) {
		return false;
	}
	
	$log_cleanup_count = sanitise_int($sync_config->log_cleanup_count, false);
	if (empty($log_cleanup_count)) {
		return true;
	}
	
	$log_files = profile_sync_get_ordered_log_files($sync_config, false);
	if (empty($log_files) || (count($log_files) < $log_cleanup_count)) {
		return true;
	}
	
	$to_be_removed = array_slice($log_files, $log_cleanup_count);
	if (empty($to_be_removed)) {
		return true;
	}
	
	$fh = new ElggFile();
	$fh->owner_guid = $sync_config->getGUID();
	
	$result = true;
	foreach ($to_be_removed as $filename) {
		$fh->setFilename($filename);
		if (!$fh->exists()) {
			continue;
		}
		
		$result = $result & $fh->delete();
	}
	
	return $result;
}

/**
 * Convert string to UTF-8 charset
 *
 * @param string $string the input string
 *
 * @return string
 */
function profile_sync_convert_string_encoding($string) {
	
	if (function_exists('mb_convert_encoding')) {
		$source_encoding = mb_detect_encoding($string);
		if (!empty($source_encoding)) {
			$source_aliases = mb_encoding_aliases($source_encoding);
			
			return mb_convert_encoding($string, 'UTF-8', $source_aliases);
		}
	}
	
	// if no mbstring extension, we just try to convert to UTF-8 (from ISO-8859-1)
	return utf8_encode($string);
}

/**
 * Find a user based on a profile field and it's value
 *
 * @param stirng     $profile_field profile field name
 * @param string     $field_value   profile field value
 * @param ElggObject $sync_config   sync configuration (for logging)
 * @param array      $log_counters  array with logging counters
 *
 * @return false|ElggUser
 */
function profile_sync_find_user($profile_field, $field_value, ElggObject $sync_config, &$log_counters) {
	static $profile_fields;
	static $dbprefix;
	
	if (!isset($profile_fields)) {
		$profile_fields = elgg_get_config('profile_fields');
	}
	if (!isset($dbprefix)) {
		$dbprefix = elgg_get_config('dbprefix');
	}
	
	if (empty($sync_config) || !elgg_instanceof($sync_config, 'object', 'profile_sync_config')) {
		return false;
	}
	
	if (empty($log_counters) || !is_array($log_counters)) {
		return false;
	}
	
	if (!in_array($profile_field, array("name", "username", "email")) && !array_key_exists($profile_field, $profile_fields)) {
		return false;
	}
	
	$field_value = profile_sync_filter_var($field_value);
	if (empty($field_value)) {
		return false;
	}
	
	$logger = profile_sync_get_log_handler($sync_config->getGUID());
	
	$user = false;
	switch ($profile_field) {
		case "username":
			$user = get_user_by_username($field_value);
			break;
		case "email":
			$users = get_user_by_email($field_value);
			if (count($users) > 1) {
				$log_counters["duplicate email"]++;
				$logger->log("Duplicate email address: {$field_value}", Logger::WARNING);
			} elseif (count($users) == 1) {
				$user = $users[0];
			}
			break;
		case "name":
			$options = array(
				"type" => "user",
				"limit" => false,
				"joins" => array("JOIN " . $dbprefix . "users_entity ue ON e.guid = ue.guid"),
				"wheres" => array("ue.name LIKE '" . sanitise_string($field_value) . "'")
			);
			$users = elgg_get_entities($options);
			if (count($users) > 1) {
				$log_counters["duplicate name"]++;
				$logger->log("Duplicate name: {$field_value}", Logger::WARNING);
			} elseif(count($users) == 1) {
				$user = $users[0];
			}
			break;
		default:
			$options = array(
				"type" => "user",
				"limit" => false,
				"metadata_name_value_pairs" => array(
					"name" => $profile_field,
					"value" => $field_value
				)
			);
			$users = elgg_get_entities_from_metadata($options);
			if (count($users) > 1) {
				$log_counters["duplicate profile field"]++;
				$logger->log("Duplicate profile field: {$profile_field} => {$field_value}", Logger::WARNING);
			} elseif(count($users) == 1) {
				$user = $users[0];
			}
			break;
	}
	
	return $user;
}

/**
 * Do the same as get_input() and /action/profile/edit on sync data values
 *
 * @param string $value the value to filter
 *
 * @see get_input()
 *
 * @return string
 */
function profile_sync_filter_var($value) {
	
	// convert to UTF-8
	$value = profile_sync_convert_string_encoding($value);
	
	// filter tags
	$value = filter_tags($value);
	
	// correct html encoding
	if (is_array($value)) {
		array_walk_recursive($value, 'profile_sync_array_decoder');
	} else {
		$value = trim(_elgg_html_decode($value));
	}
	
	return $value;
}

/**
 * Wrapper for recursive array walk decoding
 *
 * @param string $value the value of array_walk_recursive
 *
 * @see array_walk_recursive()
 *
 * @return void
 */
function profile_sync_array_decoder(&$value) {
	$value = trim(_elgg_html_decode($value));
}

/**
 * Get the access of a profile field (if exists) for the given user
 *
 * @param int    $user_guid      the user_guid to check
 * @param string $profile_field  the name of the profile field
 * @param int    $default_access the default access if profile field doesn't exist for the user
 *
 * @return int
 */
function profile_sync_get_profile_field_access($user_guid, $profile_field, $default_access) {
	static $field_access;
	static $running_user_guid;
	
	$user_guid = sanitise_int($user_guid, false);
	$default_access = sanitise_int($default_access);
	
	if (empty($user_guid)) {
		return $default_access;
	}
	
	if (empty($profile_field) || !is_string($profile_field)) {
		return $default_access;
	}
	
	$update = ($running_user_guid !== $user_guid);
	
	if ($update) {
		$field_access = array();
		$running_user_guid = $user_guid;
		
		$profile_fields = elgg_get_config('profile_fields');
		$profile_names = array_keys($profile_fields);
		
		$options = array(
			'guid' => $user_guid,
			'metadata_names' => $profile_names,
			'limit' => false
		);
		$metadata = elgg_get_metadata($options);
		if (!empty($metadata)) {
			foreach ($metadata as $md) {
				$field_access[$md->name] = (int) $md->access_id;
			}
		}
	}
	
	return elgg_extract($profile_field, $field_access, $default_access);
}

/**
 * Get the datasource handler for the datasource type
 *
 * @param ElggObject $datasource the datasource
 * @param int        $lastrun    last run for the config
 *
 * @return false|ProfileSync
 */
function profile_sync_get_datasource_handler(ElggObject $datasource, $last_run = 0) {
	
	if (empty($datasource) || !elgg_instanceof($datasource, 'object', 'profile_sync_datasource')) {
		return false;
	}
	
	$last_run = sanitise_int($last_run);
	
	switch ($datasource->datasource_type) {
		case 'mysql':
			return new ProfileSyncMySQL($datasource, $last_run);
			break;
		case 'csv':
			return new ProfileSyncCSV($datasource, $last_run);
			break;
		case 'api':
			return new ProfileSyncAPI($datasource, $last_run);
			break;
	}
	
	return false;
}

/**
 * REST API callback for profile_sync.sync_data
 *
 * @param int    $sync_config_guid the GUID of the sync config
 * @param string $sync_secret      a validation secret code
 * @param array  $profile_data     the profile data to process
 *
 * @return ErrorResult|SuccessResult
 */
function profile_sync_process_api($sync_config_guid, $sync_secret, $profile_data) {
	
	$sync_config = get_entity($sync_config_guid);
	if (empty($sync_config) || !elgg_instanceof($sync_config, 'object', 'profile_sync_config')) {
		return new ErrorResult(elgg_echo('profile_sync:rest:api:sync_data:error:sync_config_id'));
	}
	
	// validate secret
	if (!profile_sync_validate_sync_secret($sync_config, $sync_secret)) {
		return new ErrorResult(elgg_echo('profile_sync:rest:api:sync_data:error:sync_secret'));
	}
	
	$result = true;
	
	// proccess the data
	profile_sync_proccess_configuration($sync_config);
	
	// check if errors occured
	$logger = profile_sync_get_log_handler($sync_config->getGUID());
	if ($logger instanceof Logger) {
		$errors = $logger->getLogErrors();
		if (!empty($errors)) {
			// report the first error only
			$error = $errors[0];
			$result = new ErrorResult(elgg_extract('text', $error), elgg_extract('status', $error));
		}
	}
	
	// close logfile
	profile_sync_close_log($sync_config->getGUID());
	
	// cleanup log files (if needed)
	profile_sync_cleanup_logs($sync_config);
	
	// an error occured
	if ($result !== true) {
		return $result;
	}
	
	// report success
	return new SuccessResult(elgg_echo('profile_sync:rest:api:sync_data:success'));
}

/**
 * Generate a secret for use in the API
 *
 * @param ElggObject $sync_config the sync config to generate for
 *
 * @return false|string
 */
function profile_sync_get_sync_secret(ElggObject $sync_config) {
	
	if (empty($sync_config) || !elgg_instanceof($sync_config, 'object', 'profile_sync_config')) {
		return false;
	}
	
	$datasource = $sync_config->getContainerEntity();
	if (empty($datasource) || !elgg_instanceof($datasource, 'object', 'profile_sync_datasource')) {
		return false;
	}
	
	$parts = array();
	$parts[] = $sync_config->getGUID();
	$parts[] = $datasource->getGUID();
	$parts[] = $sync_config->time_created;
	$parts[] = $datasource->time_created;
	
	$string = implode('|', $parts);
	
	return hash('sha256', $string);
}

/**
 * Validate a sync config secret for the API
 *
 * @param ElggObject $sync_config the sync config
 * @param string     $secret      the secret provided in the API
 *
 * @return bool
 */
function profile_sync_validate_sync_secret(ElggObject $sync_config, $secret) {
	
	if (empty($sync_config) || !elgg_instanceof($sync_config, 'object', 'profile_sync_config')) {
		return false;
	}
	
	$correct_secret = profile_sync_get_sync_secret($sync_config);
	if (empty($correct_secret)) {
		return false;
	}
	
	return ($secret === $correct_secret);
}
