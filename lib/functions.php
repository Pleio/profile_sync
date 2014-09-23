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
	
	// create log file handler
	$log_file = new ElggFile();
	$log_file->owner_guid = $sync_config->getGUID();
	$log_file->setFilename(time() . ".log");
	
	$log_file->open("write");
	$log_file->write("start processing: " . date(elgg_echo("friendlytime:date_format")) . PHP_EOL);
	$fh = $log_file->open("append");
	
	$datasource = $sync_config->getContainerEntity();
	if (empty($datasource) || !elgg_instanceof($datasource, "object", "profile_sync_datasource")) {
		return;
	}
	
	$sync_match = json_decode($sync_config->sync_match, true);
	$datasource_id = $sync_config->datasource_id;
	$profile_id = $sync_config->profile_id;
	$lastrun = (int) $sync_config->lastrun;
	
	$profile_fields = elgg_get_config("profile_fields");
	
	if (empty($sync_match) || empty($datasource_id) || empty($profile_id)) {
		fwrite($fh, "configuration error" . PHP_EOL);
		return;
	}
	if (!in_array($profile_id, array("name", "username", "email")) && !array_key_exists($profile_id, $profile_fields)) {
		fwrite($fh, "invalid profile identifier" . PHP_EOL);
		return;
	}
	
	switch ($datasource->datasource_type) {
		case "mysql":
			$sync_source = new ProfileSyncMySQL($datasource, $lastrun);
			break;
		default:
			fwrite($fh, "invalid datasource type" . PHP_EOL);
			return;
			break;
	}
	
	$create_user = (bool) $sync_config->create_user;
	$notify_user = (bool) $sync_config->notify_user;
	$create_user_name = false;
	$create_user_email = false;
	$create_user_username = false;
	
	if ($create_user) {
		fwrite($fh, "User creation is allowed" . PHP_EOL);
		
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
			fwrite($fh, "Missing information to create users" . PHP_EOL);
			$create_user = false;
		}
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
					fwrite($fh, "duplicate email address: " . $source_row[$datasource_id] . PHP_EOL);
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
					fwrite($fh, "duplicate name: " . $source_row[$datasource_id] . PHP_EOL);
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
					fwrite($fh, "duplicate profile field: " . $source_row[$datasource_id] . PHP_EOL);
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
					fwrite($fh, "Created user: " . $source_row[$create_user_name] . PHP_EOL);
					
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
				fwrite($fh, "Failure creating user: " . $source_row[$create_user_name] . " - " . $r->getMessage() . PHP_EOL);
			}
		}
		
		// did we get a user
		if (empty($user)) {
			$counters["user not found"]++;
			fwrite($fh, "user not found: " . $datasource_id . " => " . $source_row[$datasource_id] . PHP_EOL);
			continue;
		} else {
			$counters["processed users"]++;
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
						fwrite($fh, "empty user attribute: " . $datasource_id . " for user " . $user->name . PHP_EOL);
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
	
	fwrite($fh, "end processing: " . date(elgg_echo("friendlytime:date_format")) . PHP_EOL);
	fwrite($fh, PHP_EOL);
	foreach ($counters as $name => $count) {
		fwrite($fh, $name . ": " . $count . PHP_EOL);
	}
	
	$sync_config->lastrun = time();
	
	// re-enable db caching
	_elgg_services()->db->enableQueryCache();
	// restore access
	elgg_set_ignore_access($ia);
}
