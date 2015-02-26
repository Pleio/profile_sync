<?php
/**
 * All helper functions are bundled here
 */

/**
 * Run the profile synchronization based on the provided configuration
 *
 * @param ElggObject $sync_config The sync configuration
 *
 * @return void
 */
function profile_sync_proccess_configuration(ElggObject $sync_config) {
	if (empty($sync_config) || !elgg_instanceof($sync_config, "object", "profile_sync_config")) {
		return;
	}
	
	$datasource = $sync_config->getContainerEntity();
	if (empty($datasource) || !elgg_instanceof($datasource, "object", "profile_sync_datasource")) {
		return;
	}
	
	$sync_match = json_decode($sync_config->sync_match, true);
	$datasource_id = $sync_config->datasource_id;
	$profile_id = $sync_config->profile_id;
	$lastrun = (int) $sync_config->lastrun;
	
	$profile_fields = elgg_get_config("profile_fields");
	
	if (empty($sync_match) || ($datasource_id === "") || empty($profile_id)) {
		profile_sync_log($sync_config->getGUID(), "Configuration error", true);
		return;
	}
	
	if (!in_array($profile_id, array("name", "username", "email")) && !array_key_exists($profile_id, $profile_fields)) {
		profile_sync_log($sync_config->getGUID(), "Invalid profile identifier", true);
		return;
	}
	
	switch ($datasource->datasource_type) {
		case "mysql":
			$sync_source = new ProfileSyncMySQL($datasource, $lastrun);
			break;
		case "csv":
			$sync_source = new ProfileSyncCSV($datasource, $lastrun);
			break;
		default:
			profile_sync_log($sync_config->getGUID(), "Invalid datasource type", true);
			return;
			break;
	}
	
	if (!$sync_source->connect()) {
		profile_sync_log($sync_config->getGUID(), "Unable to connect to the datasource", true);
		return;
	}
	
	$create_user = (bool) $sync_config->create_user;
	$ban_user = (bool) $sync_config->ban_user;
	$unban_user = (bool) $sync_config->unban_user;
	$notify_user = (bool) $sync_config->notify_user;
	$create_user_name = false;
	$create_user_email = false;
	$create_user_username = false;
	
	if ($create_user) {
		profile_sync_log($sync_config->getGUID(), "User creation is allowed");
		
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
		
		if (empty($create_user_name) || empty($create_user_username) || empty($create_user_email)) {
			profile_sync_log($sync_config->getGUID(), "Missing information to create users");
			$create_user = false;
		}
	}
	
	if ($ban_user) {
		profile_sync_log($sync_config->getGUID(), "Matching users will be banned");
	}
	
	if ($unban_user) {
		profile_sync_log($sync_config->getGUID(), "Matching users will be unbanned");
	}
	
	if ($ban_user && $create_user) {
		profile_sync_log($sync_config->getGUID(), "Both create and ban users is allowed, don't know what to do", true);
		return;
	}
	
	if ($unban_user && $create_user) {
		profile_sync_log($sync_config->getGUID(), "Both create and unban users is allowed, don't know what to do", true);
		return;
	}
	
	if ($ban_user && $unban_user) {
		profile_sync_log($sync_config->getGUID(), "Both ban and unban users is allowed, don't know what to do", true);
		return;
	}
	
	// start the sync process
	set_time_limit(0);
	_elgg_services()->db->disableQueryCache();
	
	$dbprefix = elgg_get_config("dbprefix");
	$default_access = get_default_access();
	$ia = elgg_set_ignore_access(true);
	$site = elgg_get_site_entity();
	
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
	
	while (($source_row = $sync_source->fetchRow()) !== false) {
		$counters["source rows"]++;
		
		if (empty($source_row[$datasource_id])) {
			$counters["empty source id"]++;
			continue;
		}
		
		$user = false;
		switch ($profile_id) {
			case "username":
				$user = get_user_by_username($source_row[$datasource_id]);
				break;
			case "email":
				$users = get_user_by_email($source_row[$datasource_id]);
				if (count($users) == 1) {
					$user = $users[0];
				} else {
					$counters["duplicate email"]++;
					profile_sync_log($sync_config->getGUID(), "Duplicate email address: " . $source_row[$datasource_id]);
				}
				break;
			case "name":
				$options = array(
					"type" => "user",
					"limit" => false,
					"joins" => array("JOIN " . $dbprefix . "users_entity ue ON e.guid = ue.guid"),
					"wheres" => array("ue.name LIKE '" . sanitise_string($source_row[$datasource_id]) . "'")
				);
				$users = elgg_get_entities($options);
				if (count($users) == 1) {
					$user = $users[0];
				} else {
					$counters["duplicate name"]++;
					profile_sync_log($sync_config->getGUID(), "Duplicate name: " . $source_row[$datasource_id]);
				}
				break;
			default:
				$options = array(
					"type" => "user",
					"limit" => false,
					"metadata_name_value_pairs" => array(
						"name" => $profile_id,
						"value" => $source_row[$datasource_id]
					)
				);
				$users = elgg_get_entities_from_metadata($options);
				if (count($users) == 1) {
					$user = $users[0];
				} else {
					$counters["duplicate profile field"]++;
					profile_sync_log($sync_config->getGUID(), "Duplicate profile field: " . $source_row[$datasource_id]);
				}
				break;
		}
		
		// check if we need to create a user
		if (empty($user) && $create_user) {
			
			$pwd = generate_random_cleartext_password();
			
			try {
				$user_guid = register_user($source_row[$create_user_username], $pwd, $source_row[$create_user_name], $source_row[$create_user_email]);
				if (!empty($user_guid)) {
					$counters["user created"]++;
					profile_sync_log($sync_config->getGUID(), "Created user: " . $source_row[$create_user_name]);
					
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
				profile_sync_log($sync_config->getGUID(), "Failure creating user: " . $source_row[$create_user_name] . " - " . $r->getMessage());
			}
		}
		
		// did we get a user
		if (empty($user)) {
			$counters["user not found"]++;
			profile_sync_log($sync_config->getGUID(), "User not found: " . $datasource_id . " => " . $source_row[$datasource_id]);
			continue;
		} else {
			$counters["processed users"]++;
		}
		
		// ban the user
		if ($ban_user) {
			// already banned?
			if (!$user->isBanned()) {
				$counters["user banned"]++;
				$user->ban("Profile Sync: " . $sync_config->title);
				profile_sync_log($sync_config->getGUID(), "User banned: " . $user->name . " ( " . $user->username . ")");
			}
			
			continue;
		}
		
		// unban the user
		if ($unban_user) {
			// already banned?
			if ($user->isBanned()) {
				$counters["user unbanned"]++;
				$user->unban();
				profile_sync_log($sync_config->getGUID(), "User unbanned: " . $user->name . " ( " . $user->username . ")");
			}
			
			continue;
		}
		
		foreach ($sync_match as $datasource_col => $profile_config) {
			$profile_field = elgg_extract("profile_field", $profile_config);
			$access = (int) elgg_extract("access", $profile_config, $default_access);
			
			if (!in_array($profile_field, array("name", "username", "email")) && !array_key_exists($profile_field, $profile_fields)) {
				$counters["invalid profile field"]++;
				continue;
			}
			if (!isset($source_row[$datasource_col])) {
				$counters["invalid source field"]++;
				continue;
			}
			
			switch ($profile_field) {
				case "email":
					if (!is_email_address($source_row[$datasource_col])) {
						continue(2);
					}
				case "name":
				case "username":
					if (empty($source_row[$datasource_col])) {
						$counters["empty attributes"]++;
						profile_sync_log($sync_config->getGUID(), "Empty user attribute: " . $datasource_id . " for user " . $user->name);
						continue(2);
					}
					
					// save user attribute
					$user->$profile_field = $source_row[$datasource_col];
					$user->save();
					break;
				default:
					if ($profile_fields[$profile_field] == "tags") {
						$value = string_to_tag_array($source_row[$datasource_col]);
					} else {
						$value = $source_row[$datasource_col];
					}
					
					// set metadata field
					if (empty($value)) {
						unset($user->$profile_field);
					} elseif (!isset($user->$profile_field)) {
						create_metadata($user->getGUID(), $profile_field, $value, '', $user->getGUID(), $access);
					} else {
						$metadata_options = array(
							"guid" => $user->getGUID(),
							"metadata_name" => $profile_field,
							"limit" => 1
						);
						$metadata = elgg_get_metadata($metadata_options);
						$access = (int) $metadata[0]->access_id;
						
						create_metadata($user->getGUID(), $profile_field, $value, '', $user->getGUID(), $access);
					}
					break;
			}
		}
	}
	
	profile_sync_log($sync_config->getGUID(), PHP_EOL . "End processing: " . date(elgg_echo("friendlytime:date_format")) . PHP_EOL);
	foreach ($counters as $name => $count) {
		profile_sync_log($sync_config->getGUID(), $name . ": " . $count);
	}
	
	profile_sync_log($sync_config->getGUID(), null, true);
	
	$sync_config->lastrun = time();
	
	// cleanup datasource cache
	$sync_source->cleanup();
	// re-enable db caching
	_elgg_services()->db->enableQueryCache();
	// restore access
	elgg_set_ignore_access($ia);
}

/**
 * Write information to a log file
 *
 * @param int    $sync_config_guid the guid of a sync config where to write the log file
 * @param string $text             the text to log
 * @param bool   $close            close the log file (default: false)
 *
 * @return void
 */
function profile_sync_log($sync_config_guid, $text, $close = false) {
	static $file_handlers;
	
	if (!isset($file_handlers)) {
		$file_handlers = array();
	}
	
	$sync_config_guid = sanitise_int($sync_config_guid, false);
	if (empty($sync_config_guid)) {
		return;
	}
	
	if (empty($text) && empty($close)) {
		return;
	}
	
	if (!isset($file_handlers[$sync_config_guid])) {
		$log_file = new ElggFile();
		$log_file->owner_guid = $sync_config_guid;
		$log_file->setFilename(time() . ".log");
		
		$log_file->open("write");
		$log_file->write("Start processing: " . date(elgg_echo("friendlytime:date_format")) . PHP_EOL);
		$file_handlers[$sync_config_guid] = $log_file->open("append");
	}
	
	if (!empty($text)) {
		fwrite($file_handlers[$sync_config_guid], $text . PHP_EOL);
		elgg_log("Profile sync log({$sync_config_guid}): " . $text, "NOTICE");
	}
	
	if (!empty($close)) {
		fclose($file_handlers[$sync_config_guid]);
		unset($file_handlers[$sync_config_guid]);
	}
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
