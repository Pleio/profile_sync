<?php

$csv_location = '';
$csv_delimiter = ',';
$csv_enclosure = '\"';
$csv_first_row = false;

$entity = elgg_extract('entity', $vars);
$class = ['profile-sync-datasource-type', 'profile-sync-datasource-type-csv'];
if ($entity && ($entity->datasource_type === 'csv')) {
	$csv_location = $entity->csv_location;
	$csv_delimiter = $entity->csv_delimiter;
	$csv_enclosure = $entity->csv_enclosure;
	$csv_first_row = (bool) $entity->csv_first_row;
} else {
	$class[] = 'hidden';
}

$result = '';

$input = elgg_format_element('label', [], elgg_echo('profile_sync:admin:datasources:edit:csv:location'));
$input .= elgg_view('input/text', [
	'name' => 'params[csv_location]',
	'value' => $csv_location,
	'required' => true,
]);
$result .= elgg_format_element('div', [], $input);

$input = elgg_format_element('label', [], elgg_echo('profile_sync:admin:datasources:edit:csv:delimiter'));
$input .= elgg_view('input/text', [
	'name' => 'params[csv_delimiter]',
	'value' => $csv_delimiter,
	'maxlength' => 1,
	'required' => true,
]);
$result .= elgg_format_element('div', [], $input);

$input = elgg_format_element('label', [], elgg_echo('profile_sync:admin:datasources:edit:csv:enclosure'));
$input .= elgg_view('input/text', [
	'name' => 'params[csv_enclosure]',
	'value' => $csv_enclosure,
	'maxlength' => 1,
]);
$result .= elgg_format_element('div', [], $input);

$input = elgg_view('input/checkbox', [
	'name' => 'params[csv_first_row]',
	'value' => '1',
	'checked' => $csv_first_row,
	'class' => 'mrs',
]);
$input .= elgg_format_element('label', [], elgg_echo('profile_sync:admin:datasources:edit:csv:first_row'));
$result .= elgg_format_element('div', [], $input);

echo elgg_format_element('div', ['class' => $class], $result);
