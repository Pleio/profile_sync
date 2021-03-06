<?php
/**
 * The main plugin file for Profile Sync
 */

define('PROFILE_SYNC_DATASOURCE_COL_SEPERATOR', '||$||');

// load libs
require_once(dirname(__FILE__) . '/lib/functions.php');

// register default Elgg events
elgg_register_event_handler('init', 'system', 'profile_sync_init');

/**
 * Init function for Profile Sync
 *
 * @return void
 */
function profile_sync_init() {
		
	elgg_extend_view('css/admin', 'css/profile_sync/admin');
	
	// register ajax views
	elgg_register_ajax_view('profile_sync/forms/datasource');
	elgg_register_ajax_view('profile_sync/forms/sync_config');
	elgg_register_ajax_view('profile_sync/sync_logs');
	elgg_register_ajax_view('profile_sync/view_log');
	elgg_register_ajax_view('profile_sync/sync_config/run');
	
	elgg_register_admin_menu_item('configure', 'profile_sync', 'configure_utilities');
	
	// register hooks
	elgg_register_plugin_hook_handler('register', 'menu:entity', '\ColdTrick\ProfileSync\EntityMenu::addDataSourceMenus');
	elgg_register_plugin_hook_handler('register', 'menu:entity', '\ColdTrick\ProfileSync\EntityMenu::addSyncConfigMenus');
	elgg_register_plugin_hook_handler('cron', 'all', '\ColdTrick\ProfileSync\Cron::runSyncs');
	elgg_register_plugin_hook_handler('permissions_check:comment', 'object', '\ColdTrick\ProfileSync\Comments::disallowComments');
	
	// register actions
	elgg_register_action('profile_sync/datasource/edit', dirname(__FILE__) . '/actions/datasource/edit.php', 'admin');
	elgg_register_action('profile_sync/datasource/delete', elgg_get_root_path() . '/actions/entities/delete.php', 'admin');
	elgg_register_action('profile_sync/sync_config', dirname(__FILE__) . '/actions/sync_config/edit.php', 'admin');
	elgg_register_action('profile_sync/sync_config/delete', elgg_get_root_path() . '/actions/entities/delete.php', 'admin');
	elgg_register_action('profile_sync/sync_config/run', dirname(__FILE__) . '/actions/sync_config/run.php', 'admin');
	
}
