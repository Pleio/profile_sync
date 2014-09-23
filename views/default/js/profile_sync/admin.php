<?php

?>
//<script>
elgg.provide("elgg.profile_sync.admin");

/**
 * Validate if there is no conflict with the ban user checkbox
 *
 * @return void
 */
elgg.profile_sync.admin.check_create_user = function(event) {
	var ban_checked = $("#profile-sync-edit-sync-ban-user").is(":checked");
	var create_checked = $(this).is(":checked");

	if (ban_checked && create_checked) {
		alert(elgg.echo("profile_sync:action:sync_config:edit:error:create_ban"));
		$(this).removeAttr("checked");
	}
}

/**
 * Validate if there is no conflict with the create user checkbox
 *
 * @return void
 */
elgg.profile_sync.admin.check_ban_user = function(event) {
	var ban_checked = $(this).is(":checked");
	var create_checked = $("#profile-sync-edit-sync-create-user").is(":checked");
	
	if (create_checked && ban_checked) {
		alert(elgg.echo("profile_sync:action:sync_config:edit:error:create_ban"));
		$(this).removeAttr("checked");

		ban_checked = false;
	}
	
	if (ban_checked) {
		$(".profile-sync-edit-sync-fields").hide();
	} else {
		$(".profile-sync-edit-sync-fields").show();
	}
	
	$.colorbox.resize();
}

/**
 * Add a new profile field to the sync list
 *
 * @return bool
 */
elgg.profile_sync.admin.add_field_config = function() {
	var $clone = $("#profile-sync-field-config-template").clone();
	$clone.removeAttr("id").removeClass("hidden");
	$clone.insertBefore("#profile-sync-field-config-template");

	$.colorbox.resize();

	return false
}

/**
 * Register callbacks when the document is done
 *
 * @return void
 */
elgg.profile_sync.admin.init = function() {

	$(document).on("change", "#profile-sync-edit-sync-create-user", elgg.profile_sync.admin.check_create_user);
	$(document).on("change", "#profile-sync-edit-sync-ban-user", elgg.profile_sync.admin.check_ban_user);
	$(document).on("click", "#profile-sync-edit-sync-add-field", elgg.profile_sync.admin.add_field_config);
};


elgg.register_hook_handler("init", "system", elgg.profile_sync.admin.init);
