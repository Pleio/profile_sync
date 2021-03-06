<?php
/**
 * create/edit a sync config
 */

$guid = (int) get_input('guid');
$datasource_guid = (int) get_input('datasource_guid');

$title = get_input('title');

$datasource_id = get_input('datasource_id');
$datasource_id_fallback = get_input('datasource_id_fallback');
$profile_id = get_input('profile_id');
$profile_id_fallback = get_input('profile_id_fallback');

$datasource_cols = get_input('datasource_cols');
$profile_cols = get_input('profile_cols');
$access = get_input('access');
$always_override = get_input('always_override');

$schedule = get_input('schedule');
$create_user = (int) get_input('create_user');
$ban_user = (int) get_input('ban_user');
$unban_user = (int) get_input('unban_user');
$notify_user = (int) get_input('notify_user');
$log_cleanup_count = sanitise_int(get_input('log_cleanup_count'), false);

if (empty($guid) && empty($datasource_guid)) {
	register_error(elgg_echo('profile_sync:action:sync_config:edit:error:guid'));
	forward(REFERER);
}

if (empty($title)) {
	register_error(elgg_echo('profile_sync:action:error:title'));
	forward(REFERER);
}

if (($datasource_id === '') || empty($profile_id)) {
	register_error(elgg_echo('profile_sync:action:sync_config:edit:error:unique_id'));
	forward(REFERER);
}

if ((!$ban_user && !$unban_user) && (empty($datasource_cols) || empty($profile_cols))) {
	register_error(elgg_echo('profile_sync:action:sync_config:edit:error:fields'));
	forward(REFERER);
}

if ($create_user && $ban_user) {
	register_error(elgg_echo('profile_sync:action:sync_config:edit:error:create_ban'));
	forward(REFERER);
}
if ($create_user && $unban_user) {
	register_error(elgg_echo('profile_sync:action:sync_config:edit:error:create_unban'));
	forward(REFERER);
}
if ($ban_user && $unban_user) {
	register_error(elgg_echo('profile_sync:action:sync_config:edit:error:ban_unban'));
	forward(REFERER);
}

// translate datasource_cols and profile_cols
$default_access = get_default_access();
$sync_match = [];
foreach ($datasource_cols as $index => $datasource_col_name) {
	if (($datasource_col_name === '') || ($profile_cols[$index] === '')) {
		continue;
	}
	
	$sync_match[$datasource_col_name . PROFILE_SYNC_DATASOURCE_COL_SEPERATOR . $index] = [
		'profile_field' => $profile_cols[$index],
		'access' => (int) elgg_extract($index, $access, $default_access),
		'always_override' => (int) elgg_extract($index, $always_override, true),
	];
}

if ((!$ban_user && !$unban_user) && empty($sync_match)) {
	register_error(elgg_echo('profile_sync:action:sync_config:edit:error:fields'));
	forward(REFERER);
}

if (empty($guid)) {
	$site = elgg_get_site_entity();
	
	$entity = new ElggObject();
	$entity->subtype = 'profile_sync_config';
	$entity->owner_guid = $site->getGUID();
	$entity->container_guid = $datasource_guid;
	$entity->access_id = ACCESS_PUBLIC;
	
	if (!$entity->save()) {
		register_error(elgg_echo('save:fail'));
		forward(REFERER);
	}
} else {
	$entity = get_entity($guid);
	if (!elgg_instanceof($entity, 'object', 'profile_sync_config')) {
		register_error(elgg_echo('profile_sync:action:sync_config:edit:error:entity'));
		forward(REFERER);
	}
}

// save all the data
$entity->title = $title;
$entity->datasource_id = $datasource_id;
$entity->datasource_id_fallback = $datasource_id_fallback;
$entity->profile_id = $profile_id;
$entity->profile_id_fallback = $profile_id_fallback;

$entity->sync_match = json_encode($sync_match);
$entity->schedule = $schedule;
$entity->create_user = $create_user;
$entity->ban_user = $ban_user;
$entity->unban_user = $unban_user;
$entity->notify_user = $notify_user;

$entity->log_cleanup_count = $log_cleanup_count;

$entity->save();

system_message(elgg_echo('admin:configuration:success'));
forward(REFERER);
