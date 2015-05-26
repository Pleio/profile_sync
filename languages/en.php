<?php

$english = array(

	'profile_sync' => "Profile Sync",
	'admin:administer_utilities:profile_sync' => "Profile Sync",
	'item:object:profile_sync_datasource' => "Profile Sync Datasource",
	'item:object:profile_sync_config' => "Profile Sync Configuration",
	
	'profile_sync:interval:friendly' => "Last run",
	'profile_sync:interval:date' => "Date",
	'profile_sync:interval:hourly' => "Hourly",
	'profile_sync:interval:daily' => "Daily",
	'profile_sync:interval:weekly' => "Weekly",
	'profile_sync:interval:monthly' => "Monthly",
	'profile_sync:interval:yearly' => "Yearly",
	
	'profile_sync:csv:column' => "Column %d: %s",

	'profile_sync:settings:memory_limit' => "Set PHP memory limit for sync jobs",
	'profile_sync:settings:memory_limit:description' => "Increase the limit if your sync jobs fail and you find Out of Memory error in the errorlog.",
	'profile_sync:settings:memory_limit:64' => "64M",
	'profile_sync:settings:memory_limit:128' => "128M",
	'profile_sync:settings:memory_limit:256' => "256M",
	'profile_sync:settings:memory_limit:512' => "512M",
	'profile_sync:settings:memory_limit:unlimited' => "Unlimited",
	
	'profile_sync:sync_config:run' => "Run now",
	'profile_sync:sync_config:processing' => "Processing....",
	'profile_sync:sync_configs:schedule:manual' => "Manual",
	
	'profile_sync:admin:datasources' => "Datasources",
	'profile_sync:admin:datasources:add' => "Add a datasource",
	'profile_sync:admin:sync_configs' => "Sync configurations",
	'profile_sync:admin:sync_configs:add' => "Add a sync configuration",

	'profile_sync:admin:datasources:type' => "Type",
	'profile_sync:admin:datasources:type:choose' => "Pick a datasource type",
	'profile_sync:admin:datasources:type:mysql' => "MySQL",
	'profile_sync:admin:datasources:type:csv' => "CSV",
	
	'profile_sync:admin:datasources:edit:mysql:dbhost' => "Database host",
	'profile_sync:admin:datasources:edit:mysql:dbport' => "Database port",
	'profile_sync:admin:datasources:edit:mysql:dbport:default' => "Default: 3306",
	'profile_sync:admin:datasources:edit:mysql:dbname' => "Database name",
	'profile_sync:admin:datasources:edit:mysql:dbusername' => "Database username",
	'profile_sync:admin:datasources:edit:mysql:dbpassword' => "Database password",
	'profile_sync:admin:datasources:edit:mysql:dbquery' => "Database query",
	'profile_sync:admin:datasources:edit:mysql:dbquery:description' => "You can use %s as a placeholder for the last time (as a unix timestamp) the sync config used this datasource. This way you can limit the number of results returned.",
	
	'profile_sync:admin:datasources:edit:csv:location' => "CSV location",
	'profile_sync:admin:datasources:edit:csv:delimiter' => "Field delimiter",
	'profile_sync:admin:datasources:edit:csv:enclosure' => "Text enclosure",
	'profile_sync:admin:datasources:edit:csv:first_row' => "First row contains headers",
	
	'profile_sync:admin:sync_configs:edit:no_datasource' => "No datasource could be created.",
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
	'profile_sync:admin:sync_configs:edit:unban_user' => "Unban matching users",
	'profile_sync:admin:sync_configs:edit:unban_user:description' => "When a matching user is found unban him/her from the system. This will not sync profile data for the matching user.",
	'profile_sync:admin:sync_configs:edit:log_cleanup_count' => "Number of logfiles to keep",
	'profile_sync:admin:sync_configs:edit:log_cleanup_count:description' => "If you wish to remove older log files enter the amount of logfiles you wish to keep. Leave empty to keep them all.",
	
	'profile_sync:sync_config:logs' => "List logs",
	'profile_sync:sync_logs:title' => "Sync logs: %s",
	'profile_sync:view_log:title' => "Log file for %s from %s",
	
	'profile_sync:sync_config:sync_status:create' => "Sync data and create users",
	'profile_sync:sync_config:sync_status:ban' => "Ban users",
	'profile_sync:sync_config:sync_status:default' => "Sync data",
	
	// actions
	'profile_sync:action:error:title' => "Please provide a title",
	
	'profile_sync:action:datasource:edit:error:params' => "No parameters supplied, please check the form",
	'profile_sync:action:datasource:edit:error:entity' => "No datasource could be found/created, please try again",
	'profile_sync:action:datasource:edit:error:type' => "Please select a datasource type",
	
	'profile_sync:action:sync_config:edit:error:guid' => "Invalid GUID provided, please try again",
	'profile_sync:action:sync_config:edit:error:unique_id' => "Please provide a set of unique IDs",
	'profile_sync:action:sync_config:edit:error:fields' => "No fields are configured for synchronization",
	'profile_sync:action:sync_config:edit:error:entity' => "The provided GUID is not a synchronization configuration",
	'profile_sync:action:sync_config:edit:error:create_ban' => "You can't create and ban users at the same time",
	'profile_sync:action:sync_config:edit:error:create_unban' => "You can't create and unban users at the same time",
	'profile_sync:action:sync_config:edit:error:ban_unban' => "You can't ban and unban users at the same time",
	
	'profile_sync:action:sync_config:run' => "Synchronisation run",
);

add_translation("en", $english);
