/**
 *
 * @author Ron Trevor <ecoron@proton.me>
 *
 * This file is licensed under the Affero General Public License version 3 or later.
 *
 *
 * @copyright Copyright (C)
 */

function postSuccess(selector, id) {
	$(selector).after(
		" <span id='" + id + "' class='msg success'>" + t("beeswarm", "Saved") + "</span>"
	);
	setTimeout(function () {
		$("#" + id).remove();
	}, 3000);
}

function postError(selector, id) {
	$(selector).after(
		" <span id='" + id + "' class='msg error'>" + t("beeswarm", "Error") + "</span>"
	);
	setTimeout(function () {
		$("#" + id).remove();
	}, 3000);
}

let storageArray = [];
window.addEventListener("DOMContentLoaded", function () {
	$("#beeswarm-save-settings").click(function () {
		// read back comma-delimited storageIds.
		mountsIds = $("#mountsIds").val().split(",");

		// foreach control identified by its storageId
		mountsIds.forEach(addToStorageJson);
		storageConfig = JSON.stringify(storageArray);
		// reset temp array for next Save click.
		storageArray = [];
		$.post(OC.generateUrl("apps/files_external_ethswarm/settings/admin"), {
			storageconfig: storageConfig,
		})
			.done(function () {
				postSuccess("#beeswarm-save-settings", "beeswarm-save-settings-msg");
			})
			.fail(function () {
				postError("#beeswarm-save-settings", "beeswarm-save-settings-msg");
			});
	});
});

function addToStorageJson(item) {
	// Create json config for each batchid
	batchId = ($("#beeswarm_batchid_" + item).val());
	if (batchId) {
		jsonConfig = { "mount_id": item, "encrypt": $("#beeswarm_encrypt_" + item).prop("checked") ? 1 : 0, "batchid": batchId };
		// Add to temp array
		storageArray.push(jsonConfig);
	}
}

