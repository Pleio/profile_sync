<?php

$title = elgg_echo("profile_sync:admin:datasources:add");

$entity = $vars["entity"];
if (elgg_instanceof($entity, "object", "profile_sync_datasource")) {
	$title = $entity->title;
}

$form_body = "<label>" . elgg_echo("title") . "</label>";
$form_body .= elgg_view("input/text", array(
	"name" => "title",
	"value" => $title
));

$form_body .= "<div class='mvm elgg-divide-bottom'></div>";
$form_body .= elgg_view("profile_sync/forms/datasources/mysql", $vars);
$form_body .= elgg_view("input/submit", array("value" => elgg_echo("save"), "class" => "elgg-button-submit mtm"));
if ($entity) {
	$form_body .= elgg_view("input/hidden", array("name" => "guid", "value" => $entity->guid));
}

$body = elgg_view("input/form", array("action" => "action/profile_sync/datasource/edit", "body" => $form_body, "class" => "phs"));

echo elgg_view_module("inline", $title, $body);
