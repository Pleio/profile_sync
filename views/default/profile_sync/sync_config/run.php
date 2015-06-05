<?php
/**
 * Start a sync config now
 */

admin_gatekeeper();

$guid = (int) get_input("guid");

echo elgg_view("graphics/ajax_loader", array("hidden" => false));

echo "<div class='center'>";
echo elgg_echo("profile_sync:sync_config:processing");
echo "</div>";

?>
<script>
	elgg.action("action/profile_sync/sync_config/run", {
		data: {
			guid: <?php echo $guid; ?>
		},
		success: function(json, one, two, three) {
			if (json && json.system_messages) {
				if (json.system_messages.error && json.system_messages.error.length) {
					$.fancybox.close();
					return;
				}
			}
			
			elgg.forward("admin/administer_utilities/profile_sync");
		}
	});
</script>