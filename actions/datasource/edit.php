<?php

$guid = (int) get_input('guid');
$params = get_input('params', [], false);
$title = get_input('title');

if (!is_array($params)) {
	register_error(elgg_echo('profile_sync:action:datasource:edit:error:params'));
	forward(REFERER);
}

if (empty($title)) {
	register_error(elgg_echo('profile_sync:action:error:title'));
	forward(REFERER);
}

$datasource_type = elgg_extract('datasource_type', $params);
if (empty($datasource_type)) {
	register_error(elgg_echo('profile_sync:action:datasource:edit:error:type'));
	forward(REFERER);
}

$entity = get_entity($guid);
if (!elgg_instanceof($entity, 'object', 'profile_sync_datasource')) {
	$site = elgg_get_site_entity();
	
	$entity = new ElggObject();
	$entity->subtype = 'profile_sync_datasource';
	$entity->owner_guid = $site->getGUID();
	$entity->container_guid = $site->getGUID();
	$entity->access_id = ACCESS_PUBLIC;
	
	if (!$entity->save()) {
		unset($entity);
	}
}

if ($entity) {
	
	$entity->title = $title;
	
	// some inputs need to be unfiltered
	$unfiltered_params = [
		'dbquery',
	];
	
	foreach ($params as $key => $param) {
		// filter input
		if (!in_array($key, $unfiltered_params)) {
			$param = filter_tags($param);
		}
		
		if (empty($param)) {
			unset($entity->{$key});
		} else {
			$entity->{$key} = $param;
		}
	}
	
	$entity->save();
	system_message(elgg_echo('admin:configuration:success'));
} else {
	register_error(elgg_echo('profile_sync:action:datasource:edit:error:entity'));
}
	
forward(REFERER);
