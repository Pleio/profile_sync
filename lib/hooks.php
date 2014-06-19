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
	
	$entity = $params["entity"];
	if (elgg_instanceof($entity, "object", "profile_sync_datasource")) {
		elgg_load_js("lightbox");
		elgg_load_css("lightbox");
		
		foreach ($return as $key => $menu_item) {
			$name = $menu_item->getName();
			switch ($name) {
				case "edit":
					
					$menu_item->setHref("ajax/view/profile_sync/forms/datasource?guid=" . $entity->getGUID());
					$menu_item->setLinkClass("elgg-lightbox");
					$menu_item->{"data-colorbox-opts"} = '{"width": 700}';
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
			"data-colorbox-opts" => '{"width": 700}',
		));
	}
	
	return $return;
}