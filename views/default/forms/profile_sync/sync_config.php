<?php

$datasource = elgg_extract('datasource', $vars);
$sync_config = elgg_extract('sync_config', $vars);

$title = '';
$schedule = 'daily';
$datasource_id = '';
$datasource_id_fallback = '';
$profile_id = '';
$profile_id_fallback = '';
$create_user = false;
$ban_user = false;
$unban_user = false;
$notify_user = false;
$log_cleanup_count = '';

$ps = false;

if (!empty($sync_config)) {
	$title = $sync_config->title;
	$schedule = $sync_config->schedule;
	$datasource_id = $sync_config->datasource_id;
	$datasource_id_fallback = $sync_config->datasource_id_fallback;
	$profile_id = $sync_config->profile_id;
	$profile_id_fallback = $sync_config->profile_id_fallback;
	$create_user = (bool) $sync_config->create_user;
	$ban_user = (bool) $sync_config->ban_user;
	$unban_user = (bool) $sync_config->unban_user;
	$notify_user = (bool) $sync_config->notify_user;
	$log_cleanup_count = sanitise_int($sync_config->log_cleanup_count, false);
	if (empty($log_cleanup_count)) {
		$log_cleanup_count = '';
	}
}

// get field config
switch ($datasource->datasource_type) {
	case 'mysql':
		$ps = new ProfileSyncMySQL($datasource);
		break;
	case 'csv':
		$ps = new ProfileSyncCSV($datasource);
		break;
}

if (empty($ps)) {
	echo elgg_view('output/longtext', ['value' => elgg_echo('profile_sync:admin:sync_configs:edit:no_datasource')]);
	return;
}

$datasource_cols = $ps->getColumns();
$profile_fields = elgg_get_config('profile_fields');

$schedule_options = [
	'hourly' => elgg_echo('profile_sync:interval:hourly'),
	'daily' => elgg_echo('profile_sync:interval:daily'),
	'weekly' => elgg_echo('profile_sync:interval:weekly'),
	'monthly' => elgg_echo('profile_sync:interval:monthly'),
	'yearly' => elgg_echo('profile_sync:interval:yearly'),
	'manual' => elgg_echo('profile_sync:sync_configs:schedule:manual'),
];

$override_options = [
	'1' => elgg_echo('option:yes'),
	'0' => elgg_echo('option:no'),
];

// show which datasource
echo '<div>';
echo elgg_format_element('label', ['class' => 'mrs'], elgg_echo('profile_sync:admin:sync_configs:edit:datasource') . ':');
echo $datasource->title;
echo elgg_view('input/hidden', [
	'name' => 'datasource_guid',
	'value' => $datasource->getGUID(),
]);
echo '</div>';

if (empty($datasource_cols) || empty($profile_fields)) {
	echo elgg_view('output/longtext', ['value' => elgg_echo('profile_sync:admin:sync_configs:edit:no_columns')]);
	return;
}

$datasource_columns = ['' => elgg_echo('profile_sync:admin:sync_configs:edit:select_datasource_column')];
$datasource_columns = array_merge($datasource_columns, $datasource_cols);

$profile_columns = [
	'' => elgg_echo('profile_sync:admin:sync_configs:edit:select_profile_column'),
	'name' => elgg_echo('name'),
	'username' => elgg_echo('username'),
	'email' => elgg_echo('email'),
	'user_icon_full_path' => elgg_echo('profile_sync:admin:sync_configs:edit:profile_column:icon_full'),
	'user_icon_relative_path' => elgg_echo('profile_sync:admin:sync_configs:edit:profile_column:icon_relative'),
];
foreach ($profile_fields as $metadata_name => $type) {
	$name = $metadata_name;
	
	$lan_key = "profile:{$metadata_name}";
	if (elgg_language_key_exists($lan_key)) {
		$name = elgg_echo($lan_key);
	} else {
		
	}
	
	$profile_columns[$metadata_name] = $name;
}

$profile_columns_id = $profile_columns;
unset($profile_columns_id['user_icon_full_path']);
unset($profile_columns_id['user_icon_relative_path']);

$body = '';

// unique title
$title_input = elgg_format_element('label', [], elgg_echo('title'));
$title_input .= elgg_view('input/text', [
	'name' => 'title',
	'value' => $title,
	'required' => true,
]);
$body .= elgg_format_element('div', ['class' => 'mbs'], $title_input);

// unique fields to match
$unique_id_input = elgg_format_element('label', [], elgg_echo('profile_sync:admin:sync_configs:edit:unique_id')) . '<br />';
$unique_id_input .= elgg_view('input/select', [
	'name' => 'datasource_id',
	'options_values' => $datasource_columns,
	'value' => $datasource_id,
	'required' => true,
]);
$unique_id_input .= elgg_view_icon('arrow-right');
$unique_id_input .= elgg_view('input/select', [
	'name' => 'profile_id',
	'options_values' => $profile_columns_id,
	'value' => $profile_id,
	'required' => true,
]);

$body .= elgg_format_element('div', ['class' => 'mbs'], $unique_id_input);

// fallback fields to match
$unique_id_fallback_input = elgg_format_element('label', [], elgg_echo('profile_sync:admin:sync_configs:edit:unique_id_fallback'));
$unique_id_fallback_input .= elgg_format_element('div', ['class' => 'elgg-subtext'], elgg_echo('profile_sync:admin:sync_configs:edit:unique_id_fallback:description'));
$unique_id_fallback_input .= elgg_view('input/select', [
	'name' => 'datasource_id_fallback',
	'options_values' => $datasource_columns,
	'value' => $datasource_id_fallback,
]);
$unique_id_fallback_input .= elgg_view_icon('arrow-right');
$unique_id_fallback_input .= elgg_view('input/select', [
	'name' => 'profile_id_fallback',
	'options_values' => $profile_columns_id,
	'value' => $profile_id_fallback,
]);
$body .= elgg_format_element('div', ['class' => 'mbs'], $unique_id_fallback_input);

// fields to sync
$field_class = 'profile-sync-edit-sync-fields';
if ($ban_user || $unban_user) {
	$field_class .= ' hidden';
}
$body .= "<div class='" . $field_class . "'>";
$body .= elgg_format_element('label', [], elgg_echo("profile_sync:admin:sync_configs:edit:fields"));

$body .= "<table class='elgg-table-alt'>";
$body .= "<thead><tr>";
$body .= "<th>" . elgg_echo("profile_sync:admin:sync_configs:edit:datasource_column") . "</th>";
$body .= "<th class='profile-sync-arrow'>&nbsp;</th>";
$body .= "<th>" . elgg_echo("profile_sync:admin:sync_configs:edit:profile_column") . "</th>";
$body .= "<th>" . elgg_echo("default_access:label") . "</th>";
$body .= "<th>" . elgg_echo("profile_sync:admin:sync_configs:edit:always_override") . "</th>";
$body .= "</tr></thead>";

$body .= "<tbody>";
if (!empty($sync_config)) {
	$sync_match = json_decode($sync_config->sync_match, true);
	
	foreach ($sync_match as $datasource_name => $profile_config) {
		$profile_name = elgg_extract("profile_field", $profile_config);
		$access = (int) elgg_extract("access", $profile_config);
		$always_override = (int) elgg_extract("always_override", $profile_config, true);
		
		$body .= "<tr>";
		$body .= "<td>" . elgg_view("input/select", array(
			"name" => "datasource_cols[]",
			"options_values" => $datasource_columns,
			"value" => $datasource_name
		)) . "</td>";
		$body .= "<td>" . elgg_view_icon("arrow-right") . "</td>";
		$body .= "<td>" . elgg_view("input/select", array(
			"name" => "profile_cols[]",
			"options_values" => $profile_columns,
			"value" => $profile_name
		)) . "</td>";
		$body .= "<td>" . elgg_view("input/access", array(
			"name" => "access[]",
			"value" => $access
		)) . "</td>";
		$body .= "<td class='center'>" . elgg_view("input/select", array(
			"name" => "always_override[]",
			"value" => $always_override,
			"options_values" => $override_options
		)) . "</td>";
		$body .= "</tr>";
	}
} else {
	$body .= "<tr>";
	$body .= "<td>" . elgg_view("input/select", array(
		"name" => "datasource_cols[]",
		"options_values" => $datasource_columns
	)) . "</td>";
	$body .= "<td>" . elgg_view_icon("arrow-right") . "</td>";
	$body .= "<td>" . elgg_view("input/select", array(
		"name" => "profile_cols[]",
		"options_values" => $profile_columns
	)) . "</td>";
	$body .= "<td>" . elgg_view("input/access", array("name" => "access[]")) . "</td>";
	$body .= "<td class='center'>" . elgg_view("input/select", array(
		"name" => "always_override[]",
		"options_values" => $override_options
	)) . "</td>";
	$body .= "</tr>";
}

$body .= "<tr id='profile-sync-field-config-template' class='hidden'>";
$body .= "<td>" . elgg_view("input/select", array(
	"name" => "datasource_cols[]",
	"options_values" => $datasource_columns
)) . "</td>";
$body .= "<td>" . elgg_view_icon("arrow-right") . "</td>";
$body .= elgg_format_element('td', [], elgg_view("input/select", [
	"name" => "profile_cols[]",
	"options_values" => $profile_columns,
]));
$body .= elgg_format_element('td', [], elgg_view("input/access", ["name" => "access[]"]));
$body .= elgg_format_element('td', ['class' => 'center'], elgg_view("input/select", [
	"name" => "always_override[]",
	"options_values" => $override_options,
]));
$body .= "</tr>";

$body .= "</tbody>";
$body .= "</table>";

$body .= elgg_format_element('div', [], elgg_view('output/url', [
	'id' => 'profile-sync-edit-sync-add-field',
	'text' => elgg_echo('add'),
	'href' => '#',
	'class' => 'float-alt',
]));
$body .= "</div>";

// schedule
$schedule_input = elgg_format_element('label', [], elgg_echo('profile_sync:admin:sync_configs:edit:schedule'));
$schedule_input .= elgg_view('input/select', [
	'name' => 'schedule',
	'value' => $schedule,
	'options_values' => $schedule_options,
	'class' => 'mls',
]);
$body .= elgg_format_element('div', ['class' => 'mbs'], $schedule_input);

// special actions
$body .= "<div class='mbs'>";
$body .= "<label>" . elgg_view("input/checkbox", [
	"id" => "profile-sync-edit-sync-create-user",
	"name" => "create_user",
	"value" => 1,
	"checked" => $create_user,
]);
$body .= elgg_echo("profile_sync:admin:sync_configs:edit:create_user") . "</label>";
$body .= elgg_format_element('div', ['class' => 'elgg-subtext'], elgg_echo("profile_sync:admin:sync_configs:edit:create_user:description"));
$body .= '<label>' . elgg_view('input/checkbox', [
	'name' => 'notify_user',
	'value' => 1,
	'checked' => $notify_user,
	'class' => 'mlm',
]);
$body .= elgg_echo("profile_sync:admin:sync_configs:edit:notify_user") . "</label>";
$body .= "</div>";

$body .= "<div class='mbs'>";
$body .= "<label>" . elgg_view("input/checkbox", [
	"id" => "profile-sync-edit-sync-ban-user",
	"name" => "ban_user",
	"value" => 1,
	"checked" => $ban_user,
]);
$body .= elgg_echo("profile_sync:admin:sync_configs:edit:ban_user") . "</label>";
$body .= elgg_format_element('div', ['class' => 'elgg-subtext'], elgg_echo("profile_sync:admin:sync_configs:edit:ban_user:description"));

$body .= "<div class='mbs'>";
$body .= "<label>" . elgg_view("input/checkbox", [
	"id" => "profile-sync-edit-sync-unban-user",
	"name" => "unban_user",
	"value" => 1,
	"checked" => $unban_user,
]);
$body .= elgg_echo("profile_sync:admin:sync_configs:edit:unban_user") . "</label>";
$body .= elgg_format_element('div', ['class' => 'elgg-subtext'], elgg_echo("profile_sync:admin:sync_configs:edit:unban_user:description"));

// log cleanup
$body .= "<div class='mbs'>";
$body .= elgg_format_element('div', [], elgg_echo('profile_sync:admin:sync_configs:edit:log_cleanup_count'));
$body .= elgg_view('input/text', [
	'name' => 'log_cleanup_count',
	'value' => $log_cleanup_count,
]);
$body .= elgg_format_element('div', ['class' => 'elgg-subtext'], elgg_echo('profile_sync:admin:sync_configs:edit:log_cleanup_count:description'));

$foot = '';
if (!empty($sync_config)) {
	$foot .= elgg_view('input/hidden', ['name' => 'guid', 'value' => $sync_config->getGUID()]);
}
$foot .= elgg_view('input/submit', ['value' => elgg_echo('save')]);

$body .= elgg_format_element('div', ['class' => 'elgg-foot'], $foot);

echo $body;
