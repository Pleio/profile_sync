<?php

$guid = (int) get_input("guid");
$params = get_input("params");
$title = get_input("title");

if (!is_array($params)) {
	register_error("no params");
	forward(REFERER);
}

if (empty($title)) {
	register_error("no title");
	forward(REFERER);
}

$entity = get_entity($guid);
if (!elgg_instanceof($entity, "object", "profile_sync_datasource")) {
	$site = elgg_get_site_entity();
	
	$entity = new ElggObject();
	$entity->subtype = "profile_sync_datasource";
	$entity->owner_guid = $site->guid;
	$entity->container_guid = $site->guid;
	$entity->access_id = ACCESS_PUBLIC;
	
	$entity->save();
}

if ($entity) {
	
	$entity->title = $title;
	
	foreach($params as $key => $param) {
		$entity->{$key} = $param;
	}
	
	$entity->save();
}
	
forward(REFERER);