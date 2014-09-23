<?php

return array(

	'profile_sync' => "Profile Sync",
	'admin:configure_utilities:profile_sync' => "Profile Sync",
	'item:object:profile_sync_datasource' => "Profile Sync Datasource",
	'item:object:profile_sync_config' => "Profile Sync Configuration",

	'profile_sync:sync_config:run' => "Run now",
	'profile_sync:sync_configs:schedule:manual' => "Manual",
	
	'profile_sync:admin:datasources' => "Datasources",
	'profile_sync:admin:datasources:add' => "Add a datasource",
	'profile_sync:admin:sync_configs' => "Sync configurations",
	'profile_sync:admin:sync_configs:add' => "Add a sync configuration",

	'profile_sync:admin:datasources:edit:mysql:dbhost' => "Database host",
	'profile_sync:admin:datasources:edit:mysql:dbport' => "Database port",
	'profile_sync:admin:datasources:edit:mysql:dbport:default' => "Default: 3306",
	'profile_sync:admin:datasources:edit:mysql:dbname' => "Database name",
	'profile_sync:admin:datasources:edit:mysql:dbusername' => "Database username",
	'profile_sync:admin:datasources:edit:mysql:dbpassword' => "Database password",
	'profile_sync:admin:datasources:edit:mysql:dbquery' => "Database query",
	'profile_sync:admin:datasources:edit:mysql:dbquery:description' => "You can use %s as a placeholder for the last time (as a unix timestamp) the sync config used this datasource. This way you can limit the number of results returned.",
	
	'profile_sync:admin:sync_configs:edit:no_columns' => "No columns found in the datasource.",
	'profile_sync:admin:sync_configs:edit:datasource' => "Using datasource",
	'profile_sync:admin:sync_configs:edit:select_datasource_column' => "Select a datasource column",
	'profile_sync:admin:sync_configs:edit:select_profile_column' => "Select a target profile field",
	'profile_sync:admin:sync_configs:edit:fields' => "Field configuration",
	'profile_sync:admin:sync_configs:edit:unique_id' => "Unique matching fields",
	'profile_sync:admin:sync_configs:edit:schedule' => "Schedule",
	'profile_sync:admin:sync_configs:edit:create_user' => "Create missing users",
	'profile_sync:admin:sync_configs:edit:create_user:description' => "When a user could not be found, create it. This requires a field for Displayname, username and e-mail.",
	'profile_sync:admin:sync_configs:edit:notify_user' => "Notify the newly created user with their username/password",
	'profile_sync:admin:sync_configs:edit:ban_user' => "Ban matching users",
	'profile_sync:admin:sync_configs:edit:ban_user:description' => "When a matching user is found ban him/her from the system. This will not sync profile data for the matching user.",
	
	'profile_sync:sync_config:logs' => "List logs",
	'profile_sync:sync_logs:title' => "Sync logs: %s",
	'profile_sync:view_log:title' => "Log file for %s from %s",
	
	// actions
	'profile_sync:action:error:title' => "Please provide a title",
	
	'profile_sync:action:datasource:edit:error:params' => "No parameters supplied, please check the form",
	'profile_sync:action:datasource:edit:error:entity' => "No datasource could be found/created, please try again",
	
	'profile_sync:action:sync_config:edit:error:guid' => "Invalid GUID provided, please try again",
	'profile_sync:action:sync_config:edit:error:unique_id' => "Please provide a set of unique IDs",
	'profile_sync:action:sync_config:edit:error:fields' => "No fields are configured for synchronization",
	'profile_sync:action:sync_config:edit:error:entity' => "The provided GUID is not a synchronization configuration",
	'profile_sync:action:sync_config:edit:error:create_ban' => "You can't create and ban users at the same time",
	
	'profile_sync:action:sync_config:run' => "Synchronisation run",
);
