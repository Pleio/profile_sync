<?php

elgg_admin_gatekeeper();

$guid = (int) get_input("guid");
$entity = get_entity($guid);
if (empty($entity) || !elgg_instanceof($entity, "object", "profile_sync_config")) {
	return;
}

$files = profile_sync_get_ordered_log_files($entity);

if (empty($files)) {
	echo elgg_echo("notfound");
	return;
}

$content = "<table class='elgg-table-alt'>";
$content .= "<tr>";
$content .= "<th>" . elgg_echo("profile_sync:interval:date") . "</th>";
$content .= "<th>&nbsp;</th>";
$content .= "<tr>";

foreach ($files as $file => $datetime) {
	$content .= "<tr>";
	$content .= "<td>" . $datetime . "</td>";
	$content .= "<td>" . elgg_view("output/url", array(
		"text" => elgg_echo("show"),
		"href" => "ajax/view/profile_sync/view_log?guid=" . $entity->getGUID() . "&file=" . $file,
		"is_trusted" => true,
		"class" => "elgg-lightbox"
	)) . "</td>";
	$content .= "</tr>";
}
$content .= "</table>";

echo elgg_view_module("inline", elgg_echo("profile_sync:sync_logs:title", array($entity->title)), $content, array("class" => "profile-sync-logs-wrapper"));
