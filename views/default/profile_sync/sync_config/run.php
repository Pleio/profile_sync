<?php
/**
 * Start a sync config now
 */

elgg_admin_gatekeeper();

$guid = (int) get_input("guid");

echo elgg_view("graphics/ajax_loader", array("hidden" => false));

echo elgg_format_element("div", array("class" => "center"), elgg_echo("profile_sync:sync_config:processing"));

?>
<script>
	elgg.action("action/profile_sync/sync_config/run?guid=<?php echo $guid; ?>", {
		success: function(json, one, two, three) {
			if (json && json.system_messages) {
				if (json.system_messages.error && json.system_messages.error.length) {
					elgg.ui.lightbox.close();
					return;
				}
			}
			
			elgg.forward("admin/configure_utilities/profile_sync");
		}
	});
</script>