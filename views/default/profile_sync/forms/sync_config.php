<?php

$sync_config_guid = (int) get_input('guid');
$sync_config = get_entity($sync_config_guid);
if (!elgg_instanceof($sync_config, 'object', 'profile_sync_config')) {
	$sync_config = false;
	$datasource_guid = (int) get_input('datasource_guid');
	$title = elgg_echo('profile_sync:admin:sync_configs:add');
} else {
	$datasource_guid = (int) $sync_config->getContainerGUID();
	$title = $sync_config->title;
}

$datasource = get_entity($datasource_guid);
if (!elgg_instanceof($datasource, 'object', 'profile_sync_datasource')) {
	return;
}

$body = elgg_view_form('profile_sync/sync_config', [], ['sync_config' => $sync_config, 'datasource' => $datasource]);

echo elgg_view_module('inline', $title, $body, ['class' => 'mvn profile-sync-config-wrapper']);