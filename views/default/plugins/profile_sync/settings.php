<?php

$plugin = elgg_extract('entity', $vars);

$memory_limit_options = [
	'64M' => elgg_echo('profile_sync:settings:memory_limit:64'),
	'128M' => elgg_echo('profile_sync:settings:memory_limit:128'),
	'256M' => elgg_echo('profile_sync:settings:memory_limit:256'),
	'512M' => elgg_echo('profile_sync:settings:memory_limit:512'),
	'-1' => elgg_echo('profile_sync:settings:memory_limit:unlimited'),
];

$memory_limit = elgg_echo('profile_sync:settings:memory_limit');
$memory_limit .= elgg_view('input/select', [
	'name' => 'params[memory_limit]',
	'value' => $plugin->memory_limit,
	'options_values' => $memory_limit_options,
	'class' => 'mlm',
]);
$memory_limit .= elgg_format_element('div', ['class' => 'elgg-subtext'], elgg_echo('profile_sync:settings:memory_limit:description'));
echo elgg_format_element('div', [], $memory_limit);