<?php

elgg_admin_gatekeeper();

$guid = (int) get_input('guid');
$entity = get_entity($guid);
if (!elgg_instanceof($entity, 'object', 'profile_sync_config')) {
	return;
}

$files = profile_sync_get_ordered_log_files($entity);
if (empty($files)) {
	echo elgg_echo('notfound');
	return;
}

$head = elgg_format_element('th', [], elgg_echo('profile_sync:interval:date'));
$head .= elgg_format_element('th', [], '&nbsp;');
$table = elgg_format_element('tr', [], $head);

foreach ($files as $file => $datetime) {
	$row_data = elgg_format_element('td', [], $datetime);
	$row_data .= elgg_format_element('td', [], elgg_view('output/url', [
		'text' => elgg_echo('show'),
		'href' => 'ajax/view/profile_sync/view_log?guid=' . $entity->getGUID() . '&file=' . $file,
		'is_trusted' => true,
		'class' => 'elgg-lightbox',
	]));
	$table .= elgg_format_element('tr', [], $row_data);
}
$content = elgg_format_element('table', ['class' => 'elgg-table-alt'], $table);

echo elgg_view_module('inline', elgg_echo('profile_sync:sync_logs:title', [$entity->title]), $content, ['class' => 'profile-sync-logs-wrapper']);
