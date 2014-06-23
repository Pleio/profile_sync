<?php

$entity = elgg_extract("entity", $vars);
$datasource = $entity->getContainerEntity();

echo elgg_view_menu("entity", array(
	"entity" => $entity,
	"handler" => "profile_sync/sync_config",
	"class" => "elgg-menu-hz",
	"sort_by" => "priority"
));

echo $entity->title;
echo "<span class='mls elgg-quiet'>(" . $datasource->title . ")</span>";

if ($entity->lastrun) {
	echo "<div class='elgg-subtext'>";
	echo elgg_echo("admin:cron:friendly") . ": " . elgg_view_friendly_time($entity->lastrun);
	echo "</div>";
}