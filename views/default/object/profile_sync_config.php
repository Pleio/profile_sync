<?php

$entity = elgg_extract('entity', $vars);
$datasource = $entity->getContainerEntity();

$entity_menu = elgg_view_menu('entity', [
	'entity' => $entity,
	'handler' => 'profile_sync/sync_config',
	'class' => 'elgg-menu-hz',
	'sort_by' => 'priority',
]);

$title = $entity->title;
$title .= elgg_format_element('span', ['class' => 'mls elgg-quiet'], '(' . $datasource->title . ')');

if ($entity->create_user) {
	$title .= ' - ' . elgg_echo('profile_sync:sync_config:sync_status:create');
} elseif ($entity->ban_user) {
	$title .= ' - ' . elgg_echo('profile_sync:sync_config:sync_status:ban');
} elseif ($entity->unban_user) {
	$title .= ' - ' . elgg_echo('profile_sync:sync_config:sync_status:unban');
} else {
	$title .= ' - ' . elgg_echo('profile_sync:sync_config:sync_status:default');
}

$subtitle = '';
if ($entity->lastrun) {
	$subtitle .= elgg_echo('profile_sync:interval:friendly') . ': ' . elgg_view_friendly_time($entity->lastrun);
}

$params = [
	'entity' => $entity,
	'title' => $title,
	'metadata' => $entity_menu,
	'subtitle' => $subtitle,
];
$params = $params + $vars;
$list_body = elgg_view('object/elements/summary', $params);

echo elgg_view_image_block('', $list_body);