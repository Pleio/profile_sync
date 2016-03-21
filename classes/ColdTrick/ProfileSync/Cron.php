<?php

namespace ColdTrick\ProfileSync;

class Cron {
	
	/**
	 * Listen to the cron to perform sync tasks
	 *
	 * @param string $hook         the name of the hook
	 * @param string $type         the type of the hook
	 * @param string $return_value current return value
	 * @param array  $params       supplied params
	 *
	 * @return void
	 */
	public static function runSyncs($hook, $type, $return_value, $params) {
		
		$allowed_intervals = [
			'hourly',
			'daily',
			'weekly',
			'monthly',
			'yearly',
		];
		
		if (!in_array($type, $allowed_intervals)) {
			return;
		}
		
		// get current memory limit
		$old_memory_limit = ini_get('memory_limit');
		
		// set new memory limit
		$setting = elgg_get_plugin_setting('memory_limit', 'profile_sync');
		if (!empty($setting)) {
			ini_set('memory_limit', $setting);
		}
		
		// get sync configs
		$options = [
			'type' => 'object',
			'subtype' => 'profile_sync_config',
			'limit' => false,
			'metadata_name_value_pairs' => [
				'name' => 'schedule',
				'value' => $type,
			],
		];
		$batch = new \ElggBatch('elgg_get_entities_from_metadata', $options);
		foreach ($batch as $sync_config) {
			// start the sync
			profile_sync_proccess_configuration($sync_config);
			
			// log cleanup
			profile_sync_cleanup_logs($sync_config);
		}
		
		// reset memory limit
		ini_set('memory_limit', $old_memory_limit);
	}
}
