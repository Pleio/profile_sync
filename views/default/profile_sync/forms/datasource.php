<?php

$title_text = elgg_echo("profile_sync:admin:datasources:add");
$title = "";

$form_body = "";

$datasource_type = "";
$type_options = array(
	"" => elgg_echo("profile_sync:admin:datasources:type:choose"),
	"mysql" => elgg_echo("profile_sync:admin:datasources:type:mysql"),
	"csv" => elgg_echo("profile_sync:admin:datasources:type:csv"),
);

$entity = $vars["entity"];
if (elgg_instanceof($entity, "object", "profile_sync_datasource")) {
	$title_text = $entity->title;
	$title = $entity->title;
	$datasource_type = $entity->datasource_type;
	
	$form_body .= elgg_view("input/hidden", array("name" => "guid", "value" => $entity->guid));
}

$form_body .= "<div>";
$form_body .= "<label>" . elgg_echo("title") . "</label>";
$form_body .= elgg_view("input/text", array(
	"name" => "title",
	"value" => $title
));
$form_body .= "</div>";

$form_body .= "<div>";
$form_body .= "<label>" . elgg_echo("profile_sync:admin:datasources:type") . "</label>";
$form_body .= elgg_view("input/select", array(
	"id" => "profile-sync-edit-datasource-type",
	"name" => "params[datasource_type]",
	"options_values" => $type_options,
	"value" => $datasource_type,
	"class" => "mls"
));
$form_body .= "</div>";

$form_body .= "<div class='mvm elgg-divide-bottom'></div>";
$form_body .= elgg_view("profile_sync/forms/datasources/mysql", $vars);
$form_body .= elgg_view("profile_sync/forms/datasources/csv", $vars);

$form_body .= "<div class='elgg-foot'>";
$form_body .= elgg_view("input/submit", array("value" => elgg_echo("save"), "class" => "elgg-button-submit mtm"));
$form_body .= "</div>";

// make the form
$body = elgg_view("input/form", array(
	"action" => "action/profile_sync/datasource/edit",
	"body" => $form_body,
	"class" => "phs elgg-form-profile-sync-datasource-edit"
));

echo elgg_view_module("inline", $title_text, $body, array("class" => "profile-sync-datasource-wrapper"));