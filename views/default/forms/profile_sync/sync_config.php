<?php

$datasource = elgg_extract("datasource", $vars);
$sync_config = elgg_extract("sync_config", $vars);

$title = "";
$schedule = "daily";
$datasource_id = "";
$profile_id = "";
$create_user = false;
$ban_user = false;
$unban_user = false;
$notify_user = false;
$log_cleanup_count = "";

$ps = false;

if (!empty($sync_config)) {
	$title = $sync_config->title;
	$schedule = $sync_config->schedule;
	$datasource_id = $sync_config->datasource_id;
	$profile_id = $sync_config->profile_id;
	$create_user = (bool) $sync_config->create_user;
	$ban_user = (bool) $sync_config->ban_user;
	$unban_user = (bool) $sync_config->unban_user;
	$notify_user = (bool) $sync_config->notify_user;
	$log_cleanup_count = sanitise_int($sync_config->log_cleanup_count, false);
	if (empty($log_cleanup_count)) {
		$log_cleanup_count = "";
	}
}

// get field config
switch ($datasource->datasource_type) {
	case "mysql":
		$ps = new ProfileSyncMySQL($datasource);
		break;
	case "csv":
		$ps = new ProfileSyncCSV($datasource);
		break;
}

if (empty($ps)) {
	echo elgg_view("output/longtext", array("value" => elgg_echo("profile_sync:admin:sync_configs:edit:no_datasource")));
	return;
}

$datasource_cols = $ps->getColumns();
$profile_fields = elgg_get_config("profile_fields");

$schedule_options = array(
	"hourly" => elgg_echo("profile_sync:interval:hourly"),
	"daily" => elgg_echo("profile_sync:interval:daily"),
	"weekly" => elgg_echo("profile_sync:interval:weekly"),
	"monthly" => elgg_echo("profile_sync:interval:monthly"),
	"yearly" => elgg_echo("profile_sync:interval:yearly"),
	"manual" => elgg_echo("profile_sync:sync_configs:schedule:manual")
);

// show which datasource
echo "<div>";
echo"<label class='mrs'>" . elgg_echo("profile_sync:admin:sync_configs:edit:datasource") . ":</label>";
echo $datasource->title;
echo elgg_view("input/hidden", array("name" => "datasource_guid", "value" => $datasource->getGUID()));
echo "</div>";

if (empty($datasource_cols) || empty($profile_fields)) {
	echo elgg_view("output/longtext", array("value" => elgg_echo("profile_sync:admin:sync_configs:edit:no_columns")));
	return;
}

$datasource_columns = array(
	"" => elgg_echo("profile_sync:admin:sync_configs:edit:select_datasource_column")
);
$datasource_columns = array_merge($datasource_columns, $datasource_cols);

$profile_columns = array(
	"" => elgg_echo("profile_sync:admin:sync_configs:edit:select_profile_column"),
	"name" => elgg_echo("name"),
	"username" => elgg_echo("username"),
	"email" => elgg_echo("email"),
	"user_icon_full_path" => elgg_echo("profile_sync:admin:sync_configs:edit:profile_column:icon_full"),
	"user_icon_relative_path" => elgg_echo("profile_sync:admin:sync_configs:edit:profile_column:icon_relative"),
);
foreach ($profile_fields as $metadata_name => $type) {
	$lan_key = "profile:" . $metadata_name;
	$name = elgg_echo($lan_key);
	if ($name == $lan_key) {
		$name = $metadata_name;
	}
	$profile_columns[$metadata_name] = $name;
}

$profile_columns_id = $profile_columns;
unset($profile_columns_id["user_icon_full_path"]);
unset($profile_columns_id["user_icon_relative_path"]);

$body = "";

// unique fields to match
$body .= "<div class='mbs'>";
$body .= "<label>" . elgg_echo("title") . "</label>";
$body .= elgg_view("input/text", array("name" => "title", "value" => $title, "required" => true));
$body .= "</div>";

// unique fields to match
$body .= "<div class='mbs'>";
$body .= "<label>" . elgg_echo("profile_sync:admin:sync_configs:edit:unique_id") . "</label><br />";
$body .= elgg_view("input/dropdown", array(
	"name" => "datasource_id",
	"options_values" => $datasource_columns,
	"value" => $datasource_id,
	"required" => true
));
$body .= elgg_view_icon("arrow-right");
$body .= elgg_view("input/dropdown", array(
	"name" => "profile_id",
	"options_values" => $profile_columns_id,
	"value" => $profile_id,
	"required" => true
));
$body .= "</div>";

// fields to sync
$field_class = "profile-sync-edit-sync-fields";
if ($ban_user) {
	$field_class .= " hidden";
}
$body .= "<div class='" . $field_class . "'>";
$body .= "<label>" . elgg_echo("profile_sync:admin:sync_configs:edit:fields") . "</label>";

$body .= "<table class='elgg-table-alt'>";
$body .= "<thead><tr>";
$body .= "<th>" . elgg_echo("profile_sync:admin:sync_configs:edit:datasource_column") . "</th>";
$body .= "<th class='profile-sync-arrow'>&nbsp;</th>";
$body .= "<th>" . elgg_echo("profile_sync:admin:sync_configs:edit:profile_column") . "</th>";
$body .= "<th>" . elgg_echo("default_access:label") . "</th>";
$body .= "</tr></thead>";

$body .= "<tbody>";
if (!empty($sync_config)) {
	$sync_match = json_decode($sync_config->sync_match, true);
	
	foreach ($sync_match as $datasource_name => $profile_config) {
		$profile_name = elgg_extract("profile_field", $profile_config);
		$access = (int) elgg_extract("access", $profile_config);
		
		$body .= "<tr>";
		$body .= "<td>" . elgg_view("input/dropdown", array(
			"name" => "datasource_cols[]",
			"options_values" => $datasource_columns,
			"value" => $datasource_name
		)) . "</td>";
		$body .= "<td>" . elgg_view_icon("arrow-right") . "</td>";
		$body .= "<td>" . elgg_view("input/dropdown", array(
			"name" => "profile_cols[]",
			"options_values" => $profile_columns,
			"value" => $profile_name
		)) . "</td>";
		$body .= "<td>" . elgg_view("input/access", array(
			"name" => "access[]",
			"value" => $access
		)) . "</td>";
		$body .= "</tr>";
	}
} else {
	$body .= "<tr>";
	$body .= "<td>" . elgg_view("input/dropdown", array(
		"name" => "datasource_cols[]",
		"options_values" => $datasource_columns
	)) . "</td>";
	$body .= "<td>" . elgg_view_icon("arrow-right") . "</td>";
	$body .= "<td>" . elgg_view("input/dropdown", array(
		"name" => "profile_cols[]",
		"options_values" => $profile_columns
	)) . "</td>";
	$body .= "<td>" . elgg_view("input/access", array("name" => "access[]")) . "</td>";
	$body .= "</tr>";
}

$body .= "<tr id='profile-sync-field-config-template' class='hidden'>";
$body .= "<td>" . elgg_view("input/dropdown", array(
	"name" => "datasource_cols[]",
	"options_values" => $datasource_columns
)) . "</td>";
$body .= "<td>" . elgg_view_icon("arrow-right") . "</td>";
$body .= "<td>" . elgg_view("input/dropdown", array(
	"name" => "profile_cols[]",
	"options_values" => $profile_columns
)) . "</td>";
$body .= "<td>" . elgg_view("input/access", array("name" => "access[]")) . "</td>";
$body .= "</tr>";

$body .= "</tbody>";
$body .= "</table>";

$body .= "<div>";
$body .= elgg_view("output/url", array(
	"id" => "profile-sync-edit-sync-add-field",
	"text" => elgg_echo("add"),
	"href" => "#",
	"class" => "float-alt"
));
$body .= "</div>";
$body .= "</div>";

// schedule
$body .= "<div class='mbs'>";
$body .= "<label>" . elgg_echo("profile_sync:admin:sync_configs:edit:schedule") . "</label>";
$body .= elgg_view("input/dropdown", array("name" => "schedule", "value" => $schedule, "options_values" => $schedule_options, "class" => "mls"));
$body .= "</div>";

// special actions
$body .= "<div class='mbs'>";
$body .= "<label>" . elgg_view("input/checkbox", array(
	"id" => "profile-sync-edit-sync-create-user",
	"name" => "create_user",
	"value" => 1,
	"checked" => $create_user
));
$body .= elgg_echo("profile_sync:admin:sync_configs:edit:create_user") . "</label>";
$body .= "<div class='elgg-subtext'>" . elgg_echo("profile_sync:admin:sync_configs:edit:create_user:description") . "</div>";
$body .= "<label>" . elgg_view("input/checkbox", array(
	"name" => "notify_user",
	"value" => 1,
	"checked" => $notify_user,
	"class" => "mlm"
));
$body .= elgg_echo("profile_sync:admin:sync_configs:edit:notify_user") . "</label>";
$body .= "</div>";

$body .= "<div class='mbs'>";
$body .= "<label>" . elgg_view("input/checkbox", array(
	"id" => "profile-sync-edit-sync-ban-user",
	"name" => "ban_user",
	"value" => 1,
	"checked" => $ban_user
));
$body .= elgg_echo("profile_sync:admin:sync_configs:edit:ban_user") . "</label>";
$body .= "<div class='elgg-subtext'>" . elgg_echo("profile_sync:admin:sync_configs:edit:ban_user:description") . "</div>";

$body .= "<div class='mbs'>";
$body .= "<label>" . elgg_view("input/checkbox", array(
	"id" => "profile-sync-edit-sync-unban-user",
	"name" => "unban_user",
	"value" => 1,
	"checked" => $ban_user
));
$body .= elgg_echo("profile_sync:admin:sync_configs:edit:unban_user") . "</label>";
$body .= "<div class='elgg-subtext'>" . elgg_echo("profile_sync:admin:sync_configs:edit:unban_user:description") . "</div>";

// log cleanup
$body .= "<div class='mbs'>";
$body .= "<label>" . elgg_echo("profile_sync:admin:sync_configs:edit:log_cleanup_count") . "</label>";
$body .= elgg_view("input/text", array(
	"name" => "log_cleanup_count",
	"value" => $log_cleanup_count,
));
$body .= "<div class='elgg-subtext'>" . elgg_echo("profile_sync:admin:sync_configs:edit:log_cleanup_count:description") . "</div>";


$body .= "<div class='elgg-foot'>";
if (!empty($sync_config)) {
	$body .= elgg_view("input/hidden", array("name" => "guid", "value" => $sync_config->getGUID()));
}
$body .= elgg_view("input/submit", array("value" => elgg_echo("save")));
$body .= "</div>";

echo $body;
