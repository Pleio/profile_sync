<?php

namespace ColdTrick\ProfileSync;

class Comments {
	
	/**
	 * Disallow commenting on profile_sync objects
	 *
	 * @param string $hook                 the name of the hook
	 * @param string $type                 the type of the hook
	 * @param bool   $return_value current current return value
	 * @param array  $params               supplied params
	 *
	 * @return void|false
	 */
	public static function disallowComments($hook, $type, $return_value, $params) {
		
		if ($return_value === false) {
			// already not allowed
			return;
		}
		
		$entity = elgg_extract('entity', $params);
		if (!($entity instanceof \ElggObject)) {
			return;
		}
		
		if (!elgg_instanceof($entity, 'object', 'profile_sync_datasource') && !elgg_instanceof($entity, 'object', 'profile_sync_config')) {
			return;
		}
		
		return false;
	}
}
