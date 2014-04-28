<?php

$entity = $vars["entity"];

echo elgg_view_menu('entity', array(
	'entity' => $entity,
	'handler' => 'profile_sync/datasource',
	'class' => 'elgg-menu-hz',
	'sort_by' => "priority"
));

echo $entity->title;
