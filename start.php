<?php

// require_once(dirname(__FILE__) . "/lib/functions.php");
require_once(dirname(__FILE__) . "/lib/hooks.php");
// require_once(dirname(__FILE__) . "/lib/events.php");

/**
 * Init function for Profile Sync
 *
 * @return void
 */
function profile_sync_init() {
		
	elgg_extend_view("css/admin", "css/profile_sync/admin");
// 	elgg_extend_view("js/elgg", "js/menu_builder/site");
	
// 	// register pagehandler for nice URL's
// 	elgg_register_page_handler("menu_builder", "menu_builder_page_handler");
	
// 	// switch mode
// 	if (elgg_is_admin_logged_in()) {
// 		elgg_register_plugin_hook_handler("access:collections:write", "user", "menu_builder_write_access_hook");
		
// 		if (get_input("menu_builder_edit_mode") == "on") {
// 			elgg_load_js("lightbox");
// 			elgg_load_css("lightbox");
			
// 			$_SESSION["menu_builder_edit_mode"] = true;
// 		} elseif (get_input("menu_builder_edit_mode") == "off") {
// 			unset($_SESSION["menu_builder_edit_mode"]);
// 			unset($_SESSION["menu_builder_logged_out"]);
// 		}
		
// 		if (get_input("menu_builder_logged_out") == "on") {
// 			elgg_load_js("lightbox");
// 			elgg_load_css("lightbox");
			
// 			$_SESSION["menu_builder_logged_out"] = true;
// 		} elseif (get_input("menu_builder_logged_out") == "off") {
// 			unset($_SESSION["menu_builder_logged_out"]);
// 		}
// 	} else {
// 		unset($_SESSION["menu_builder_edit_mode"]);
// 		unset($_SESSION["menu_builder_logged_out"]);
// 	}
	
// 	// register url handler for menu_builder objects
// 	elgg_register_plugin_hook_handler("entity:url", "object", "menu_builder_menu_item_url_handler");
	
// 	// take control of menu setup
// 	elgg_unregister_plugin_hook_handler('prepare', 'menu:site', '_elgg_site_menu_setup');
// 	elgg_register_plugin_hook_handler('prepare', 'menu:site', 'menu_builder_site_menu_prepare');

	elgg_register_plugin_hook_handler('register', 'menu:entity', 'profile_sync_entity_register_menu');

	// register ajax views
	elgg_register_ajax_view("profile_sync/forms/datasource");
	elgg_register_ajax_view("profile_sync/forms/sync_config");
	
	elgg_register_admin_menu_item('configure', 'profile_sync', 'configure_utilities');
}

// register default Elgg events
elgg_register_event_handler("init", "system", "profile_sync_init");

elgg_register_event_handler("delete", "object", "menu_builder_delete_event_handler");

// register actions
elgg_register_action("profile_sync/datasource/edit", dirname(__FILE__) . "/actions/datasource/edit.php", "admin");
elgg_register_action("profile_sync/datasource/delete", elgg_get_root_path() . "/actions/entities/delete.php", "admin");
elgg_register_action("profile_sync/sync_profile/edit", dirname(__FILE__) . "/actions/sync_profile/edit.php", "admin");
	