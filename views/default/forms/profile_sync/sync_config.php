<?php

$datasource = elgg_extract("datasource", $vars);
$sync_config = elgg_extract("sync_config", $vars);

// get field config
$ps = new ProfileSyncMySQL($datasource);

$datasource_cols = $ps->getColumns();
$profile_fields = elgg_get_config("profile_fields");

// show which datasource
echo "<div>";
echo"<label class='mrs'>" . elgg_echo("profile_sync:admin:sync_configs:edit:datasource") . ":</label>";
echo $datasource->title;
$body .= "</div>";

if (empty($datasource_cols) || empty($profile_fields)) {
	echo elgg_view("output/longtext", array("value" => elgg_echo("profile_sync:admin:sync_configs:edit:no_columns")));
	return;
}

$datasource_columns = array(
	"" => elgg_echo("profile_sync:admin:sync_configs:edit:select_datasource_column")
);
$datasource_columns = array_merge($datasource_columns, array_combine($datasource_cols, $datasource_cols));

$profile_columns = array(
	"" => elgg_echo("profile_sync:admin:sync_configs:edit:select_profile_column"),
	"name" => elgg_echo("name"),
	"username" => elgg_echo("username"),
	"email" => elgg_echo("email")
);
foreach ($profile_fields as $metadata_name => $type) {
	$lan_key = "profile:" . $metadata_name;
	$name = elgg_echo($lan_key);
	if ($name == $lan_key) {
		$name = $metadata_name;
	}
	$profile_columns[$metadata_name] = $name;
}



// unique fields to match
$body .= "<div class='mbs'>";
$body .= "<label>" . elgg_echo("profile_sync:admin:sync_configs:edit:match") . "</label><br />";
$body .= elgg_view("input/select", array("name" => "datasource_id", "options_values" => $datasource_columns));
$body .= elgg_view_icon("arrow-right");
$body .= elgg_view("input/select", array("name" => "profile_id", "options_values" => $profile_columns));
$body .= "</div>";

// fields to sync
$body .= "<label>" . elgg_echo("profile_sync:admin:sync_configs:edit:fields") . "</label>";

$body .= "<div class='mbs'>";
$body .= elgg_view("input/select", array("name" => "datasource_cols[]", "options_values" => $datasource_columns));
$body .= elgg_view_icon("arrow-right");
$body .= elgg_view("input/select", array("name" => "profile_cols[]", "options_values" => $profile_columns));
$body .= "</div>";

$body .= "<div id='profile-sync-field-config-template' class='hidden mbs'>";
$body .= elgg_view("input/select", array("name" => "datasource_cols[]", "options_values" => $datasource_columns));
$body .= elgg_view_icon("arrow-right");
$body .= elgg_view("input/select", array("name" => "profile_cols[]", "options_values" => $profile_columns));
$body .= "</div>";

$body .= "<div>";
$body .= elgg_view("output/url", array("text" => elgg_echo("add"), "href" => "#", "onclick" => "elgg.profile_sync.add_field_config(); return false;", "class" => "float-alt"));
$body .= "</div>";

$body .= "<div class='elgg-foot'>";
$body .= elgg_view("input/submit", array("value" => elgg_echo("save")));
$body .= "</div>";

echo $body;
?>
<script type="text/javascript">
	elgg.provide("elgg.profile_sync");

	elgg.profile_sync.add_field_config = function() {
		var $clone = $("#profile-sync-field-config-template").clone();
		$clone.removeAttr("id").removeClass("hidden");
		$clone.insertBefore("#profile-sync-field-config-template");

		$.colorbox.resize();
	}
</script>