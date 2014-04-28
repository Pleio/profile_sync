<?php

$entity = $vars["entity"];
if ($entity) {
	$dbhost = $entity->dbhost;
	$dbport = $entity->dbport;
	$dbname = $entity->dbname;
	$dbusername = $entity->dbusername;
	$dbpassword = $entity->dbpassword;
}

echo elgg_view("input/hidden", array(
	"name" => "params[datasource_type]",
	"value" => "mysql",
));

echo "<label>" . elgg_echo("profile_sync:admin:datasources:edit:mysql:dbhost") . "</label>";
echo elgg_view("input/text", array(
	"name" => "params[dbhost]",
	"value" => $dbhost,
	"required" => true	
));
echo "<label>" . elgg_echo("profile_sync:admin:datasources:edit:mysql:dbport") . "</label>";
echo elgg_view("input/text", array(
	"name" => "params[dbport]",
	"value" => $dbport,
	"required" => true	
));
echo "<label>" . elgg_echo("profile_sync:admin:datasources:edit:mysql:dbname") . "</label>";
echo elgg_view("input/text", array(
	"name" => "params[dbname]",
	"value" => $dbname,
	"required" => true	
));
echo "<label>" . elgg_echo("profile_sync:admin:datasources:edit:mysql:dbusername") . "</label>";
echo elgg_view("input/text", array(
	"name" => "params[dbusername]",
	"value" => $dbusername,
	"required" => true	
));
echo "<label>" . elgg_echo("profile_sync:admin:datasources:edit:mysql:dbpassword") . "</label>";
echo elgg_view("input/text", array(
	"name" => "params[dbpassword]",
	"value" => $dbpassword,
	"required" => true	
));