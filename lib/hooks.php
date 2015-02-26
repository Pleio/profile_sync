<?php
/**
 * All plugin hook callback functions are bundled in this file
 */

/**
 * Change menu items of the entity
 *
 * @param string         $hook   'register'
 * @param string         $type   'menu:entity'
 * @param ElggMenuItem[] $return the current menu items
 * @param array          $params parameters
 *
 * @return ElggMenuItem[]
 */
function profile_sync_entity_register_menu($hook, $type, $return, $params) {
	
	if (!empty($params) && is_array($params)) {
		$entity = elgg_extract("entity", $params);
		
		if (elgg_instanceof($entity, "object", "profile_sync_datasource")) {
			elgg_load_js("lightbox");
			elgg_load_css("lightbox");
			
			foreach ($return as $key => $menu_item) {
				$name = $menu_item->getName();
				switch ($name) {
					case "edit":
						
						$menu_item->setHref("ajax/view/profile_sync/forms/datasource?guid=" . $entity->getGUID());
						$menu_item->setLinkClass("elgg-lightbox");
						$menu_item->{"data-colorbox-opts"} = json_encode(array(
							"width" => 700
						));
						break;
					case "delete":
						break;
					default:
						unset($return[$key]);
						break;
				}
			}
			
			$return[] = ElggMenuItem::factory(array(
				"name" => "add_sync_config",
				"text" => elgg_echo("profile_sync:admin:sync_configs:add"),
				"href" => "ajax/view/profile_sync/forms/sync_config?datasource_guid=" . $entity->getGUID(),
				"priority" => 10,
				"link_class" => "elgg-lightbox",
				"data-colorbox-opts" => json_encode(array(
					"width" => 700
				)),
			));
		} elseif (elgg_instanceof($entity, "object", "profile_sync_config")) {
			elgg_load_js("lightbox");
			elgg_load_css("lightbox");
			
			foreach ($return as $key => $menu_item) {
				$name = $menu_item->getName();
				switch ($name) {
					case "edit":
			
						$menu_item->setHref("ajax/view/profile_sync/forms/sync_config?guid=" . $entity->getGUID());
						$menu_item->setLinkClass("elgg-lightbox");
						$menu_item->{"data-colorbox-opts"} = json_encode(array(
							"width" => 700
						));
						break;
					case "delete":
						break;
					default:
						unset($return[$key]);
						break;
				}
			}
			
			$schedule_text = elgg_echo("interval:" . $entity->schedule);
			if ($entity->schedule == "manual") {
				$schedule_text = elgg_echo("profile_sync:sync_configs:schedule:manual");
			}
			$return[] = ElggMenuItem::factory(array(
				"name" => "sync_config_interval",
				"text" => $schedule_text,
				"href" => false,
				"priority" => 10,
			));
			
			$return[] = ElggMenuItem::factory(array(
				"name" => "run",
				"text" => elgg_echo("profile_sync:sync_config:run"),
				"href" => "action/profile_sync/sync_config/run?guid=" . $entity->getGUID(),
				"priority" => 50,
				"is_action" => true
			));
			
			if ($entity->lastrun) {
				$return[] = ElggMenuItem::factory(array(
					"name" => "logs",
					"text" => elgg_echo("profile_sync:sync_config:logs"),
					"href" => "ajax/view/profile_sync/sync_logs/?guid=" . $entity->getGUID(),
					"priority" => 100,
					"link_class" => "elgg-lightbox",
					"data-colorbox-opts" => json_encode(array(
						"width" => 500
					)),
				));
			}
			
		}
	}
	
	return $return;
}

/**
 * Listen to the cron to perform sync tasks
 *
 * @param string $hook   'cron'
 * @param string $type   the cron interval (eg. daily, weekly, etc)
 * @param string $return current output to screen
 * @param array  $params parameters
 *
 * @return void
 */
function profile_sync_cron_handler($hook, $type, $return, $params) {
	
	$allowed_intervals = array(
		"hourly",
		"daily",
		"weekly",
		"monthly",
		"yearly"
	);
	
	if (empty($type) || !in_array($type, $allowed_intervals)) {
		return $return;
	}
	
	// get current memory limit
	$old_memory_limit = ini_get("memory_limit");
	
	// set new memory limit
	$setting = elgg_get_plugin_setting("memory_limit", "profile_sync");
	if (!empty($setting)) {
		ini_set("memory_limit", $setting);
	}
	
	// get sync configs
	$options = array(
		"type" => "object",
		"subtype" => "profile_sync_config",
		"limit" => false,
		"metadata_name_value_pairs" => array(
			"name" => "schedule",
			"value" => $type
		)
	);
	$batch = new ElggBatch("elgg_get_entities_from_metadata", $options);
	foreach ($batch as $sync_config) {
		profile_sync_proccess_configuration($sync_config);
		
		// log cleanup
		profile_sync_cleanup_logs($entity);
	}
	
	// reset memory limit
	ini_set("memory_limit", $old_memory_limit);
}
