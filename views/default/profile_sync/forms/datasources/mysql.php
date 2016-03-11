<?php

$dbhost = '';
$dbport = '';
$dbname = '';
$dbusername = '';
$dbpassword = '';
$dbquery = '';

$class = ['profile-sync-datasource-type', 'profile-sync-datasource-type-mysql'];

$entity = elgg_extract('entity', $vars);
if ($entity && ($entity->datasource_type === 'mysql')) {
	$dbhost = $entity->dbhost;
	$dbport = $entity->dbport;
	$dbname = $entity->dbname;
	$dbusername = $entity->dbusername;
	$dbpassword = $entity->dbpassword;
	$dbquery = $entity->dbquery;
} else {
	$class[] = 'hidden';
}

$result = '';

$input = elgg_format_element('label', [], elgg_echo('profile_sync:admin:datasources:edit:mysql:dbhost'));
$input .= elgg_view('input/text', [
	'name' => 'params[dbhost]',
	'value' => $dbhost,
	'required' => true,
]);
$result .= elgg_format_element('div', [], $input);

$input = elgg_format_element('label', [], elgg_echo('profile_sync:admin:datasources:edit:mysql:dbport'));
$input .= elgg_view('input/text', [
	'name' => 'params[dbport]',
	'value' => $dbport,
	'placeholder' => elgg_echo('profile_sync:admin:datasources:edit:mysql:dbport:default'),
	'required' => true,
]);
$result .= elgg_format_element('div', [], $input);

$input = elgg_format_element('label', [], elgg_echo('profile_sync:admin:datasources:edit:mysql:dbname'));
$input .= elgg_view('input/text', [
	'name' => 'params[dbname]',
	'value' => $dbname,
	'required' => true,
]);
$result .= elgg_format_element('div', [], $input);

$input = elgg_format_element('label', [], elgg_echo('profile_sync:admin:datasources:edit:mysql:dbusername'));
$input .= elgg_view('input/text', [
	'name' => 'params[dbusername]',
	'value' => $dbusername,
	'required' => true,
]);
$result .= elgg_format_element('div', [], $input);

$input = elgg_format_element('label', [], elgg_echo('profile_sync:admin:datasources:edit:mysql:dbpassword'));
$input .= elgg_view('input/password', [
	'name' => 'params[dbpassword]',
	'value' => $dbpassword,
	'class' => 'elgg-input-text',
]);
$result .= elgg_format_element('div', [], $input);

$input = elgg_format_element('label', [], elgg_echo('profile_sync:admin:datasources:edit:mysql:dbquery'));
$input .= elgg_view('input/plaintext', [
	'name' => 'params[dbquery]',
	'value' => $dbquery,
	'required' => true,
]);
$input .= elgg_format_element('div', ['class' => 'elgg-subtext'], elgg_echo('profile_sync:admin:datasources:edit:mysql:dbquery:description', ['[[lastrun]]']));
$result .= elgg_format_element('div', [], $input);

echo elgg_format_element('div', ['class' => $class], $result);
