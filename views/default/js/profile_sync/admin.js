elgg.provide("elgg.profile_sync.admin");

/**
 * Validate if there is no conflict with the ban/unban user checkbox
 *
 * @return void
 */
elgg.profile_sync.admin.check_create_user = function(event) {
	var ban_checked = $("#profile-sync-edit-sync-ban-user").is(":checked");
	var unban_checked = $("#profile-sync-edit-sync-unban-user").is(":checked");
	var create_checked = $(this).is(":checked");

	if (ban_checked && create_checked) {
		alert(elgg.echo("profile_sync:action:sync_config:edit:error:create_ban"));
		$(this).prop("checked", false);
	}
	
	if (unban_checked && create_checked) {
		alert(elgg.echo("profile_sync:action:sync_config:edit:error:create_unban"));
		$(this).prop("checked", false);
	}
};

/**
 * Validate if there is no conflict with the create/unban user checkbox
 *
 * @return void
 */
elgg.profile_sync.admin.check_ban_user = function(event) {
	var ban_checked = $(this).is(":checked");
	var unban_checked = $("#profile-sync-edit-sync-unban-user").is(":checked");
	var create_checked = $("#profile-sync-edit-sync-create-user").is(":checked");
	
	if (create_checked && ban_checked) {
		alert(elgg.echo("profile_sync:action:sync_config:edit:error:create_ban"));
		$(this).prop("checked", false);

		ban_checked = false;
	}
	
	if (unban_checked && ban_checked) {
		alert(elgg.echo("profile_sync:action:sync_config:edit:error:ban_unban"));
		$(this).prop("checked", false);
	}
	
	if (ban_checked) {
		$(".profile-sync-edit-sync-fields").hide();
	} else {
		$(".profile-sync-edit-sync-fields").show();
	}
	
	$.colorbox.resize();
};

/**
 * Validate if there is no conflict with the create/ban user checkbox
 *
 * @return void
 */
elgg.profile_sync.admin.check_unban_user = function(event) {
	var unban_checked = $(this).is(":checked");
	var ban_checked = $("#profile-sync-edit-sync-ban-user").is(":checked");
	var create_checked = $("#profile-sync-edit-sync-create-user").is(":checked");
	
	if (create_checked && unban_checked) {
		alert(elgg.echo("profile_sync:action:sync_config:edit:error:create_unban"));
		$(this).prop("checked", false);

		unban_checked = false;
	}
	
	if (ban_checked && unban_checked) {
		alert(elgg.echo("profile_sync:action:sync_config:edit:error:ban_unban"));
		$(this).prop("checked", false);
	}
	
	if (unban_checked) {
		$(".profile-sync-edit-sync-fields").hide();
	} else {
		$(".profile-sync-edit-sync-fields").show();
	}
	
	$.colorbox.resize();
};

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
};

elgg.profile_sync.admin.datasource_type = function() {
	var $form = $('form.elgg-form-profile-sync-datasource-edit');

	$form.find(".profile-sync-datasource-type").hide();
	var type = $('#profile-sync-edit-datasource-type').val();
	if (type !== "") {
		$form.find(".profile-sync-datasource-type-" + type).show();
	}

	$form.find('[required]').prop('disabled', true);
	$form.find('[required]:visible').prop('disabled', false);

	$.colorbox.resize();
};

/**
 * Register callbacks when the document is done
 *
 * @return void
 */
elgg.profile_sync.admin.init = function() {
	$(document).on("change", "#profile-sync-edit-sync-create-user", elgg.profile_sync.admin.check_create_user);
	$(document).on("change", "#profile-sync-edit-sync-ban-user", elgg.profile_sync.admin.check_ban_user);
	$(document).on("change", "#profile-sync-edit-sync-unban-user", elgg.profile_sync.admin.check_unban_user);
	$(document).on("change", "#profile-sync-edit-datasource-type", elgg.profile_sync.admin.datasource_type);
	$(document).on("click", "#profile-sync-edit-sync-add-field", elgg.profile_sync.admin.add_field_config);
	$(document).bind("cbox_complete", elgg.profile_sync.admin.datasource_type);
};


elgg.register_hook_handler("init", "system", elgg.profile_sync.admin.init);
