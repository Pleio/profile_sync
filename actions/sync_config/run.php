<?php
/**
 * Start a sync config now
 */

$guid = (int) get_input("guid");

if (empty($guid)) {
	forward(REFERER);
}

$entity = get_entity($guid);
if (empty($entity) || !elgg_instanceof($entity, "object", "profile_sync_config")) {
	forward(REFERER);
}

// get current memory limit
$old_memory_limit = ini_get("memory_limit");

// set new memory limit
$setting = elgg_get_plugin_setting("memory_limit", "profile_sync");
if (!empty($setting)) {
	ini_set("memory_limit", $setting);
}

profile_sync_proccess_configuration($entity);

// log cleanup
profile_sync_cleanup_logs($entity);

// reset memory limit
ini_set("memory_limit", $old_memory_limit);

system_message(elgg_echo("profile_sync:action:sync_config:run"));

forward(REFERER);