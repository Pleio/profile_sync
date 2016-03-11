<?php

echo elgg_list_entities([
	'type' => 'object',
	'subtype' => 'profile_sync_datasource',
	'limit' => false,
	'no_results' => elgg_echo('notfound'),
]);
