<?php

elgg_admin_gatekeeper();

$guid = (int) get_input("guid");
$entity = get_entity($guid);
if (empty($entity) || !elgg_instanceof($entity, "object", "profile_sync_config")) {
	return;
}

$fh = new ElggFile();
$fh->owner_guid = $entity->getGUID();
$fh->setFilename("temp");

$dir = $fh->getFilenameOnFilestore();
$dir = substr($dir, 0, strlen($dir) - 4);

$dh = opendir($dir);
$files = array();
while (($file = readdir()) !== false) {
	if (is_dir($dir . $file)) {
		continue;
	}
	
	list($time) = explode(".", $file);
	$files[$file] = date(elgg_echo("friendlytime:date_format"), $time);
}

if (empty($files)) {
	echo elgg_echo("notfound");
	return;
}

$content = "<table class='elgg-table-alt'>";
$content .= "<tr>";
$content .= "<th>" . elgg_echo("admin:cron:date") . "</th>";
$content .= "<th>&nbsp;</th>";
$content .= "<tr>";

foreach ($files as $file => $datetime) {
	$content .= "<tr>";
	$content .= "<td>" . $datetime . "</td>";
	$content .= "<td>" . elgg_view("output/url", array(
		"text" => elgg_echo("show"),
		"href" => "ajax/view/profile_sync/view_log?guid=" . $entity->getGUID() . "&file=" . $file,
		"is_trusted" => true,
		"class" => "elgg-lightbox",
		"data-colorbox-opts" => '{"width": 750, "maxHeight": 900}'
	)) . "</td>";
	$content .= "</tr>";
}
$content .= "</table>";

echo elgg_view_module("inline", elgg_echo("profile_sync:sync_logs:title", array($entity->title)), $content);