<?php

$sync_config = false;

$datasource_guid = (int) get_input("datasource_guid");
$datasource = get_entity($datasource_guid);
if (!elgg_instanceof($datasource, "object", "profile_sync_datasource")) {
	return;
}

$title = elgg_echo("profile_sync:admin:sync_configs:add");

// add form
$body_vars = array(
	"sync_config" => $sync_config,
	"datasource" => $datasource,
);

$body .= elgg_view_form("profile_sync/sync_config", array(), $body_vars);

echo elgg_view_module("inline", $title, $body, array("class" => "mvn"));