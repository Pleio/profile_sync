<?php

$available_fields = "";

$class = "hidden";

$entity = elgg_extract("entity", $vars);
if (!empty($entity) && ($entity->datasource_type === "api")) {
	$class = "";
	
	$available_fields = $entity->api_available_fields;
}

echo "<div class='profile-sync-datasource-type profile-sync-datasource-type-api $class'>";

echo elgg_view("output/longtext", array(
	"value" => elgg_echo("profile_sync:admin:datasources:edit:api:description"),
));

if (elgg_get_config('disable_api')) {
	echo '<div class="elgg-admin-notices pbn">';
	echo '<p class="mbn">' . elgg_echo('profile_sync:admin:datasources:edit:api:disabled') . '</p>';
	echo '</div>';
}

echo "<div>";
echo "<label>" . elgg_echo('profile_sync:admin:datasources:edit:api:available_fields') . "</label>";
echo elgg_view("input/plaintext", array(
	"name" => "params[api_available_fields]",
	"value" => $available_fields,
));
echo "<div class='elgg-subtext'>" . elgg_echo('profile_sync:admin:datasources:edit:api:available_fields:description') . "</div>";
echo "</div>";

echo "</div>";
