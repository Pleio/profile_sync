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
		foreach($return as $key => $menu_item) {
			$name = $menu_item->getName();
			switch ($name) {
				case "access":
				case "like":
					unset($return[$key]);
					break;
				case "edit":
					$menu_item->setHref("ajax/view/profile_sync/forms/datasource?guid=" . $entity->guid);
					$menu_item->setLinkClass("elgg-lightbox");
					$menu_item->{"data-colorbox-opts"} = '{"width": 500}';
					break;
				case "delete":
					break;
			}
		}
		
		$options = array(
			'name' => 'add_sync_config',
			'text' => elgg_echo("profile_sync:admin:sync_configs:add"),
			'href' => "ajax/view/profile_sync/forms/sync_config?datasource_guid=" . $entity->guid,
			'priority' => 10,
			"link_class" => "elgg-lightbox",
			"data-colorbox-opts" => '{"width": 500}',
		);
		$return[] = ElggMenuItem::factory($options);
	}
	
	return $return;
}