<?php

$entity = $vars["entity"];

echo elgg_view_menu('entity', array(
	'entity' => $entity,
	'handler' => 'profile_sync/sync_config',
	'class' => 'elgg-menu-hz',
	'sort_by' => "priority"
));

echo $entity->title;
