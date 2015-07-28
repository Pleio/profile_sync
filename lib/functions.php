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
	
	profile_sync_log($sync_config->getGUID(), "Last run timestamp: {$lastrun} (" . date(elgg_echo("friendlytime:date_format"), $lastrun) . ")" . PHP_EOL);
	
	$profile_fields = elgg_get_config("profile_fields");
	
	if (empty($sync_match) || ($datasource_id === "") || empty($profile_id)) {
		profile_sync_log($sync_config->getGUID(), "Configuration error", true);
		return;
	}
	
	if (!in_array($profile_id, array("name", "username", "email")) && !array_key_exists($profile_id, $profile_fields)) {
		profile_sync_log($sync_config->getGUID(), "Invalid profile identifier: {$profile_id}", true);
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
			profile_sync_log($sync_config->getGUID(), "Invalid datasource type: {$datasource->datasource_type}", true);
			return;
			break;
	}
	
	if (!$sync_source->connect()) {
		profile_sync_log($sync_config->getGUID(), "Unable to connect to the datasource", true);
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
		
		if (($create_user_name === false) || ($create_user_username === false) || ($create_user_email === false)) {
			profile_sync_log($sync_config->getGUID(), "Missing information to create users");
			profile_sync_log($sync_config->getGUID(), "- name: {$create_user_name}");
			profile_sync_log($sync_config->getGUID(), "- email: {$create_user_email}");
			profile_sync_log($sync_config->getGUID(), "- username: {$create_user_username}");
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
	
	$default_access = get_default_access();
	$ia = elgg_set_ignore_access(true);
	$site = elgg_get_site_entity();
	
	// we want to cache entity metadata on first __get()
	$metadata_cache = _elgg_services()->metadataCache;
	if ($metadata_cache instanceof ElggVolatileMetadataCache) {
		// elgg 1.10
		$metadata_cache->setIgnoreAccess(false);
	}
	
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
// 			profile_sync_log($sync_config->getGUID(), "User not found: {$profile_id} => {$datasource_unique_id} trying fallback");
			
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
					profile_sync_log($sync_config->getGUID(), "Created user: {$name}");
					
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
				profile_sync_log($sync_config->getGUID(), "Failure creating user: {$name} - {$r->getMessage()}");
			}
		}
		
		// did we get a user
		if (empty($user)) {
			$counters["user not found"]++;
			profile_sync_log($sync_config->getGUID(), "User not found: {$profile_used_id} => {$datasource_unique_id}");
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
				profile_sync_log($sync_config->getGUID(), "User banned: {$user->name} ({$user->username})");
			}
			
			continue;
		}
		
		// unban the user
		if ($unban_user) {
			// already banned?
			if ($user->isBanned()) {
				$counters["user unbanned"]++;
				$user->unban();
				profile_sync_log($sync_config->getGUID(), "User unbanned: {$user->name} ({$user->username})");
			}
			
			continue;
		}
		
		// start of profile sync
		$special_sync_fields = array(
			"name",
			"username",
			"email",
			"user_icon_relative_path",
			"user_icon_full_path"
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
							profile_sync_log($sync_config->getGUID(), "New username: {$value} for {$user->name} is already taken");
							continue(2);
						}
					}
				case "name":
					if (empty($value)) {
						$counters["empty attributes"]++;
						profile_sync_log($sync_config->getGUID(), "Empty user attribute: {$datasource_col} for user {$user->name}");
						continue(2);
					}
					
					if (isset($user->$profile_field) && !$override) {
						// don't override profile field
// 						profile_sync_log($sync_config->getGUID(), "Profile field already set: " . $profile_field . " for user " . $user->name);
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
				case "user_icon_relative_path":
					// get a user icon based on a relative file path/url
					// only works with file based datasources (eg. csv)
					if (!($sync_source instanceof ProfileSyncCSV)) {
						profile_sync_log($sync_config->getGUID(), "Can't fetch relative user icon path in non CSV datasouces: trying user {$user->name}");
						continue(2);
					}
					
					// make new icon path
					if (!empty($value)) {
						$value = sanitise_filepath($value, false); // prevent abuse (like ../../......)
						$value = ltrim($value, DIRECTORY_SEPARATOR); // remove beginning /
						$value = $base_location . DIRECTORY_SEPARATOR . $value; // concat base location and rel path
					}
					
				case "user_icon_full_path":
					// get a user icon based on a full file path/url
					
					if (!empty($user->icontime) && !$override) {
						// don't override icon
// 						profile_sync_log($sync_config->getGUID(), "User already has an icon: " . $user->name);
						continue(2);
					}
					
					// upload new icon
					$icon_sizes = elgg_get_config("icon_sizes");
					
					$fh = new ElggFile();
					$fh->owner_guid = $user->getGUID();
						
					if (empty($value) && $user->icontime) {
						// no icon, so unset current icon
						profile_sync_log($sync_config->getGUID(), "Removing icon for user: {$user->name}");
						
						foreach ($icon_sizes as $size => $icon_info) {
							$fh->setFilename("profile/{$user->getGUID()}{$size}.jpg");
							$fh->delete();
						}
						
						unset($user->icontime);
						unset($fh);
						
						// on to the next field
						continue(2);
					}
					
					// try to get the user icon
					$icon_contents = file_get_contents($value);
					if (empty($icon_contents)) {
						profile_sync_log($sync_config->getGUID(), "Unable to fetch user icon: {$value} for user {$user->name}");
						continue(2);
					}
					
					// was csv image updated
					$csv_icontime = @filemtime($value);
					if (($csv_icontime !== false) && isset($user->icontime)) {
						$csv_icontime = sanitise_int($csv_icontime);
						$icontime = sanitise_int($user->icontime);
						
						if ($csv_icontime === $icontime) {
							// base image has same modified time as user icontime, so skipp
// 							profile_sync_log($sync_config->getGUID(), "No need to update user icon for user: {$user->name}");
							continue(2);
						}
					}
					
					if ($csv_icontime === false) {
						$csv_icontime = time();
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
						$user->icontime = $csv_icontime;
					}
					
					// cleanup
					unlink($tmp_icon);
					unset($fh);
					
					break;
				default:
					// check overrides
					if (isset($user->$profile_field) && !$override) {
						// don't override profile field
// 						profile_sync_log($sync_config->getGUID(), "Profile field already set: " . $profile_field . " for user " . $user->name);
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
					
// 					profile_sync_log($sync_config->getGUID(), "Updating {$profile_field} with value {$value} old value {$user->$profile_field}");
					
					// get the access of existing profile data
					$access = profile_sync_get_profile_field_access($user->getGUID(), $profile_field, $access);
					
					// save new value
					$user->setMetadata($profile_field, $value, '', false, $user->getGUID(), $access);
					
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
	
	profile_sync_log($sync_config->getGUID(), PHP_EOL . "End processing: " . date(elgg_echo("friendlytime:date_format")) . PHP_EOL);
	foreach ($counters as $name => $count) {
		profile_sync_log($sync_config->getGUID(), $name . ": " . $count);
	}
	
	// close logfile
	profile_sync_log($sync_config->getGUID(), null, true);
	
	// save last run
	$sync_config->lastrun = time();
	
	// cleanup datasource cache
	$sync_source->cleanup();
	// re-enable db caching
	_elgg_services()->db->enableQueryCache();
	// restore access
	elgg_set_ignore_access($ia);
	
	if ($metadata_cache instanceof ElggVolatileMetadataCache) {
		// elgg 1.10
		$metadata_cache->unsetIgnoreAccess();
	} elseif ($metadata_cache instanceof \Elgg\Cache\MetadataCache) {
		// elgg 1.11+
		$metadata_cache->clearAll();
	}
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
	
	$user = false;
	switch ($profile_field) {
		case "username":
			$user = get_user_by_username($field_value);
			break;
		case "email":
			$users = get_user_by_email($field_value);
			if (count($users) > 1) {
				$log_counters["duplicate email"]++;
				profile_sync_log($sync_config->getGUID(), "Duplicate email address: {$field_value}");
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
				profile_sync_log($sync_config->getGUID(), "Duplicate name: {$field_value}");
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
				profile_sync_log($sync_config->getGUID(), "Duplicate profile field: {$profile_field} => {$field_value}");
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
