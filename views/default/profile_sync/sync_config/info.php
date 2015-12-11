<?php

$sync_config = elgg_extract('sync_config', $vars);
$datasource = elgg_extract('datasource', $vars);

if (empty($sync_config) || empty($datasource)) {
	return;
}

if ($datasource->datasource_type === 'api') {
	echo '<div>';
	
	echo elgg_echo('profile_sync:admin:sync_configs:info:api:description');
	echo '<br />';
	// sync_config_id
	echo elgg_echo('profile_sync:admin:sync_configs:info:api:sync_config_id', array($sync_config->getGUID()));
	echo '<br />';
	// sync_secret
	$secret = profile_sync_get_sync_secret($sync_config);
	echo elgg_echo('profile_sync:admin:sync_configs:info:api:sync_secret', array($secret));
	
	echo '</div>';
}