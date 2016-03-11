<?php

elgg_admin_gatekeeper();

$guid = (int) get_input('guid');
$filename = get_input('file');

$entity = get_entity($guid);
if (empty($filename) || !elgg_instanceof($entity, 'object', 'profile_sync_config')) {
	return;
}

$fh = new ElggFile();
$fh->owner_guid = $entity->getGUID();
$fh->setFilename($filename);

if (!$fh->exists()) {
	echo elgg_echo('notfound');
	return;
}

list($time) = explode('.', $filename);
$datetime = date(elgg_echo('friendlytime:date_format'), $time);

$content = elgg_view('output/longtext', ['value' => $fh->grabFile()]);

$content .= elgg_view('output/url', [
	'text' => elgg_echo('back'),
	'href' => 'ajax/view/profile_sync/sync_logs/?guid=' . $entity->getGUID(),
	'is_trusted' => true,
	'class' => 'elgg-lightbox float-alt',
]);

echo elgg_view_module('inline', elgg_echo('profile_sync:view_log:title', [$entity->title, $datetime]), $content, ['class' => 'profile-sync-log-wrapper']);
