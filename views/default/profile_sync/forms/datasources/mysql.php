<?php

$dbhost = "";
$dbport = "";
$dbname = "";
$dbusername = "";
$dbpassword = "";
$dbquery = "";

$class = "hidden";

$entity = elgg_extract("entity", $vars);
if ($entity && ($entity->datasource_type === "mysql")) {
	$dbhost = $entity->dbhost;
	$dbport = $entity->dbport;
	$dbname = $entity->dbname;
	$dbusername = $entity->dbusername;
	$dbpassword = $entity->dbpassword;
	$dbquery = $entity->dbquery;
	
	$class = "";
}

echo "<div class='profile-sync-datasource-type profile-sync-datasource-type-mysql $class'>";

echo "<div>";
echo "<label>" . elgg_echo("profile_sync:admin:datasources:edit:mysql:dbhost") . "</label>";
echo elgg_view("input/text", array(
	"name" => "params[dbhost]",
	"value" => $dbhost,
	"required" => true
));
echo "</div>";

echo "<div>";
echo "<label>" . elgg_echo("profile_sync:admin:datasources:edit:mysql:dbport") . "</label>";
echo elgg_view("input/text", array(
	"name" => "params[dbport]",
	"value" => $dbport,
	"placeholder" => elgg_echo("profile_sync:admin:datasources:edit:mysql:dbport:default"),
	"required" => true
));
echo "</div>";

echo "<div>";
echo "<label>" . elgg_echo("profile_sync:admin:datasources:edit:mysql:dbname") . "</label>";
echo elgg_view("input/text", array(
	"name" => "params[dbname]",
	"value" => $dbname,
	"required" => true
));
echo "</div>";

echo "<div>";
echo "<label>" . elgg_echo("profile_sync:admin:datasources:edit:mysql:dbusername") . "</label>";
echo elgg_view("input/text", array(
	"name" => "params[dbusername]",
	"value" => $dbusername,
	"required" => true
));
echo "</div>";

echo "<div>";
echo "<label>" . elgg_echo("profile_sync:admin:datasources:edit:mysql:dbpassword") . "</label>";
echo elgg_view("input/password", array(
	"name" => "params[dbpassword]",
	"value" => $dbpassword,
	"required" => true,
	"class" => "elgg-input-text"
));
echo "</div>";

echo "<div>";
echo "<label>" . elgg_echo("profile_sync:admin:datasources:edit:mysql:dbquery") . "</label>";
echo elgg_view("input/plaintext", array(
	"name" => "params[dbquery]",
	"value" => $dbquery
));
echo "<div class='elgg-subtext'>" . elgg_echo("profile_sync:admin:datasources:edit:mysql:dbquery:description", array("[[lastrun]]")) . "</div>";
echo "</div>";

echo "</div>";
