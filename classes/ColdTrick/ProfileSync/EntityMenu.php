<?php

namespace ColdTrick\ProfileSync;

class EntityMenu {
	
	/**
	 * Add menu items to the datasource entity_menu
	 *
	 * @param string          $hook         the name of the hook
	 * @param string          $type         the type of the hook
	 * @param \ElggMenuItem[] $return_value current return value
	 * @param array           $params       supplied params
	 *
	 * @return void|\ElggMenuItem[]
	 */
	public static function addDataSourceMenus($hook, $type, $return_value, $params) {
		
		$entity = elgg_extract('entity', $params);
		if (!($entity instanceof \ElggObject)) {
			return;
		}
		
		if (!elgg_instanceof($entity, 'object', 'profile_sync_datasource')) {
			return;
		}
		
		elgg_load_js('lightbox');
		elgg_load_css('lightbox');
		
		foreach ($return_value as $key => $menu_item) {
			
			switch ($menu_item->getName()) {
				case 'edit':
					// edit in lightbox
					$menu_item->setHref("ajax/view/profile_sync/forms/datasource?guid={$entity->getGUID()}");
					$menu_item->setLinkClass('elgg-lightbox');
					$menu_item->setTooltip('');
					break;
				case 'delete':
					break;
				default:
					unset($return_value[$key]);
					break;
			}
		}
		
		$return_value[] = \ElggMenuItem::factory([
			'name' => 'add_sync_config',
			'text' => elgg_echo('profile_sync:admin:sync_configs:add'),
			'href' => "ajax/view/profile_sync/forms/sync_config?datasource_guid={$entity->getGUID()}",
			'priority' => 10,
			'link_class' => 'elgg-lightbox',
		]);
		
		return $return_value;
	}
	
	/**
	 * Add menu items to the sync_config entity_menu
	 *
	 * @param string          $hook         the name of the hook
	 * @param string          $type         the type of the hook
	 * @param \ElggMenuItem[] $return_value current return value
	 * @param array           $params       supplied params
	 *
	 * @return void|\ElggMenuItem[]
	 */
	public static function addSyncConfigMenus($hook, $type, $return_value, $params) {
		
		$entity = elgg_extract('entity', $params);
		if (!($entity instanceof \ElggObject)) {
			return;
		}
		
		if (!elgg_instanceof($entity, 'object', 'profile_sync_config')) {
			return;
		}
		
		elgg_load_js('lightbox');
		elgg_load_css('lightbox');
		
		foreach ($return_value as $key => $menu_item) {
			$name = $menu_item->getName();
			switch ($name) {
				case 'edit':
					// edit in lightbox
					$menu_item->setHref("ajax/view/profile_sync/forms/sync_config?guid={$entity->getGUID()}");
					$menu_item->setLinkClass('elgg-lightbox');
					$menu_item->setTooltip('');
					break;
				case 'delete':
					break;
				default:
					unset($return_value[$key]);
					break;
			}
		}
		
		$schedule_text = elgg_echo("profile_sync:interval:{$entity->schedule}");
		if ($entity->schedule === 'manual') {
			$schedule_text = elgg_echo('profile_sync:sync_configs:schedule:manual');
		}
		$return_value[] = \ElggMenuItem::factory([
			'name' => 'sync_config_interval',
			'text' => $schedule_text,
			'href' => false,
			'priority' => 10,
		]);
		
		$return_value[] = \ElggMenuItem::factory([
			'name' => 'run',
			'text' => elgg_echo('profile_sync:sync_config:run'),
			'href' => "ajax/view/profile_sync/sync_config/run?guid={$entity->getGUID()}",
			'priority' => 50,
			'is_action' => true,
			'link_class' => 'elgg-lightbox',
		]);
		
		$return_value[] = \ElggMenuItem::factory([
			'name' => 'logs',
			'text' => elgg_echo('profile_sync:sync_config:logs'),
			'href' => "ajax/view/profile_sync/sync_logs/?guid={$entity->getGUID()}",
			'priority' => 100,
			'link_class' => 'elgg-lightbox',
		]);
		
		return $return_value;
	}
}
