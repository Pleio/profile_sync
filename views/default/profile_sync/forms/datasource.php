<?php

$title_text = elgg_echo('profile_sync:admin:datasources:add');
$title = '';

$form_body = '';

$datasource_type = '';
$type_options = [
	'' => elgg_echo('profile_sync:admin:datasources:type:choose'),
	'mysql' => elgg_echo('profile_sync:admin:datasources:type:mysql'),
	'csv' => elgg_echo('profile_sync:admin:datasources:type:csv'),
];

$entity = elgg_extract('entity', $vars);
if (elgg_instanceof($entity, 'object', 'profile_sync_datasource')) {
	$title_text = $entity->title;
	$title = $entity->title;
	$datasource_type = $entity->datasource_type;
	
	$form_body .= elgg_view('input/hidden', ['name' => 'guid', 'value' => $entity->guid]);
}

$title_input = elgg_format_element('label', [], elgg_echo('title'));
$title_input .= elgg_view('input/text', [
	'name' => 'title',
	'value' => $title,
]);
$form_body .= elgg_format_element('div', [], $title_input);

$datasource_type_input = elgg_format_element('label', [], elgg_echo('profile_sync:admin:datasources:type'));
$datasource_type_input .= elgg_view('input/select', [
	'id' => 'profile-sync-edit-datasource-type',
	'name' => 'params[datasource_type]',
	'options_values' => $type_options,
	'value' => $datasource_type,
	'class' => 'mls',
]);
$form_body .= elgg_format_element('div', [], $datasource_type_input);

$form_body .= elgg_format_element('div', ['class' => 'mvm elgg-divide-bottom']);
$form_body .= elgg_view('profile_sync/forms/datasources/mysql', $vars);
$form_body .= elgg_view('profile_sync/forms/datasources/csv', $vars);

$form_body .= elgg_format_element('div', ['class' => 'elgg-foot'], elgg_view('input/submit', ['value' => elgg_echo('save'), 'class' => 'elgg-button-submit mtm']));

// make the form
$body = elgg_view('input/form', [
	'action' => 'action/profile_sync/datasource/edit',
	'body' => $form_body,
	'class' => 'phs elgg-form-profile-sync-datasource-edit',
]);

echo elgg_view_module('inline', $title_text, $body, ['class' => 'profile-sync-datasource-wrapper']);