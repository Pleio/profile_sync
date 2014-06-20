<?php
/**
 * The main plugin file for Profile Sync
 */

// load libs
require_once(dirname(__FILE__) . "/lib/functions.php");
require_once(dirname(__FILE__) . "/lib/hooks.php");

// register default Elgg events
elgg_register_event_handler("init", "system", "profile_sync_init");

/**
 * Init function for Profile Sync
 *
 * @return void
 */
function profile_sync_init() {
		
	elgg_extend_view("css/admin", "css/profile_sync/admin");
	
	elgg_register_css("elgg.icons", elgg_get_simplecache_url("css", "elements/icons"));
	
	// register ajax views
	elgg_register_ajax_view("profile_sync/forms/datasource");
	elgg_register_ajax_view("profile_sync/forms/sync_config");
	
	elgg_register_admin_menu_item("configure", "profile_sync", "configure_utilities");
	
	// register hooks
	elgg_register_plugin_hook_handler("register", "menu:entity", "profile_sync_entity_register_menu");
	elgg_register_plugin_hook_handler("cron", "all", "profile_sync_cron_handler");
	
	// register actions
	elgg_register_action("profile_sync/datasource/edit", dirname(__FILE__) . "/actions/datasource/edit.php", "admin");
	elgg_register_action("profile_sync/datasource/delete", elgg_get_root_path() . "/actions/entities/delete.php", "admin");
	elgg_register_action("profile_sync/sync_config", dirname(__FILE__) . "/actions/sync_config/edit.php", "admin");
	elgg_register_action("profile_sync/sync_config/delete", elgg_get_root_path() . "/actions/entities/delete.php", "admin");
	
}
