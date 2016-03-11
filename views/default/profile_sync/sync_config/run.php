<?php
/**
 * Start a sync config now
 */

elgg_admin_gatekeeper();

$guid = (int) get_input('guid');

echo elgg_view('graphics/ajax_loader', ['hidden' => false]);

echo elgg_format_element('div', ['class' => 'center'], elgg_echo('profile_sync:sync_config:processing'));

?>
<script>
	elgg.action("action/profile_sync/sync_config/run", {
		data: {
			guid: <?php echo $guid; ?>
		},
		success: function(json, one, two, three) {
			if (json && json.system_messages) {
				if (json.system_messages.error && json.system_messages.error.length) {
					$.colorbox.close();
					return;
				}
			}
			
			elgg.forward("admin/configure_utilities/profile_sync");
		}
	});
</script>