<?php
/**
 * The main plugin file for Profile Sync
 */

// load libs
require_once(dirname(__FILE__) . "/lib/functions.php");
require_once(dirname(__FILE__) . "/lib/hooks.php");

// register default Elgg events
elgg_register_event_handler("plugins_boot", "system", "profile_sync_boot");
elgg_register_event_handler("init", "system", "profile_sync_init");

/**
 * Called during plugins_boot (before init)
 *
 * @return void
 */
function profile_sync_boot() {
	
	// try to load composer autoloader
	if (file_exists(dirname(__FILE__) . "/vendor/autoload.php")) {
		// plugins own version of composer (supplied with the plugin)
		require_once(dirname(__FILE__) . "/vendor/autoload.php");
	} elseif (file_exists(dirname(dirname(dirname(__FILE__))) . "/vendor/autoload.php")) {
		// core autoloader
		require_once(dirname(dirname(dirname(__FILE__))) . "/vendor/autoload.php");
	} else {
		// missing required files, so disable the plugin
		elgg_add_admin_notice('profile_sync_missing_autoload', 'No composer autoloader found. This is required for the operation of this plugin, please run composer install. Or contact your system administrator.');
		
		$plugin = elgg_get_plugin_from_id('profile_sync');
		$plugin->deactivate();
	}
	
	// is the autoloader working correctly
	if (!class_exists('\ColdTrick\ProfileSync\Logger')) {
		// missing required files, so disable the plugin
		elgg_add_admin_notice('profile_sync_incomplete_autoload', 'The composer autoloader is not up-to-date. please run composer install. Or contact your system administrator.');
		
		$plugin = elgg_get_plugin_from_id('profile_sync');
		$plugin->deactivate();
	}
}

/**
 * Init function for Profile Sync
 *
 * @return void
 */
function profile_sync_init() {
		
	elgg_extend_view("css/admin", "css/profile_sync/admin");
	elgg_extend_view("js/admin", "js/profile_sync/admin");
	
	elgg_register_css("elgg.icons", elgg_get_simplecache_url("css", "elements/icons"));
	
	// register ajax views
	elgg_register_ajax_view("profile_sync/forms/datasource");
	elgg_register_ajax_view("profile_sync/forms/sync_config");
	elgg_register_ajax_view("profile_sync/sync_logs");
	elgg_register_ajax_view("profile_sync/view_log");
	elgg_register_ajax_view("profile_sync/sync_config/run");
	
	elgg_register_admin_menu_item("administer", "profile_sync", "administer_utilities");
	
	// register hooks
	elgg_register_plugin_hook_handler("register", "menu:entity", "profile_sync_entity_register_menu");
	elgg_register_plugin_hook_handler("cron", "all", "profile_sync_cron_handler");
	elgg_register_plugin_hook_handler("permissions_check:comment", "object", "profile_sync_can_comment");
	elgg_register_plugin_hook_handler("rest", "init", "profile_sync_rest_init");
	
	// register actions
	elgg_register_action("profile_sync/datasource/edit", dirname(__FILE__) . "/actions/datasource/edit.php", "admin");
	elgg_register_action("profile_sync/datasource/delete", elgg_get_root_path() . "/actions/entities/delete.php", "admin");
	elgg_register_action("profile_sync/sync_config", dirname(__FILE__) . "/actions/sync_config/edit.php", "admin");
	elgg_register_action("profile_sync/sync_config/delete", elgg_get_root_path() . "/actions/entities/delete.php", "admin");
	elgg_register_action("profile_sync/sync_config/run", dirname(__FILE__) . "/actions/sync_config/run.php", "admin");
	
}
