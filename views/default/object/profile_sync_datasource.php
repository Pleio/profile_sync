<?php

$entity = elgg_extract('entity', $vars);

echo elgg_view_menu('entity', [
	'entity' => $entity,
	'handler' => 'profile_sync/datasource',
	'class' => 'elgg-menu-hz',
	'sort_by' => 'priority',
]);

echo $entity->title;
echo ' (' . elgg_echo('profile_sync:admin:datasources:type:' . $entity->datasource_type) . ')';
