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

profile_sync_proccess_configuration($entity);
system_message(elgg_echo("profile_sync:action:sync_config:run"));

forward(REFERER);