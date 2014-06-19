<?php

elgg_load_js("lightbox");
elgg_load_css("lightbox");

$datasource_title = elgg_view("output/url", array(
	"class" => "elgg-button elgg-button-action elgg-lightbox float-alt profile-sync-admin-title-button",
	"href" => "ajax/view/profile_sync/forms/datasource",
	"text" => elgg_echo("add"),
	"data-colorbox-opts" => '{"width": 700}'
));
$datasource_title .= elgg_echo("profile_sync:admin:datasources");

echo elgg_view_module("inline", $datasource_title, elgg_view("profile_sync/datasources"));

$configs_title = elgg_echo("profile_sync:admin:sync_configs");

echo elgg_view_module("inline", $configs_title, elgg_view("profile_sync/sync_configs"));
