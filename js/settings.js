/**
  *
 * @copyright Copyright (C)
 *
 * @author
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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

