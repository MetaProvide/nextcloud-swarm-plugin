/*
 * Copyright (c) 2022 Metaprovide
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */
(function () {
	/**
	 * @class OCA.Files_External_BeeSwarm.FileList
	 * @augments OCA.Files.FileList
	 *
	 * @classdesc BeeSwarm External storage file list.
	 *
	 * @param $el container element with existing markup for the #controls
	 * and a table
	 * @param [options] map of options, see other parameters
	 **/
	var FileList = function ($el, options) {
		this.initialize($el, options);
	};

	FileList.prototype = _.extend(
		{},
		OCA.Files.FileList.prototype,
		/** @lends OCA.Files_External.FileList.prototype */ {
			appName: "External_BeeSwarm",

			_allowSelection: false,

			/**
			 * @private
			 */
			initialize: function ($el, options) {
				alert("beeswarm initialize");
				OCA.Files.FileList.prototype.initialize.apply(this, arguments);
				if (this.initialized) {
					return;
				}
			},

			/**
			 * @param {OCA.Files_External_BeeSwarm.MountPointInfo} fileData
			 */
			_createRow: function (fileData) {
				// TODO: hook earlier and render the whole row here
				var $tr = OCA.Files.FileList.prototype._createRow.apply(
					this,
					arguments
				);
				var $scopeColumn = $(
					'<td class="column-scope column-last"><span></span></td>'
				);
				var $backendColumn = $('<td class="column-backend"></td>');
				var scopeText = t("files_external", "Personal!");
				if (fileData.scope === "system") {
					scopeText = t("files_external", "System!");
				}
				$tr.find(".filesize,.date").remove();
				$scopeColumn.find("span").text(scopeText);
				$backendColumn.text(fileData.backend);
				$tr.find("td.filename")
					.after($scopeColumn)
					.after($backendColumn);
				return $tr;
			},

			updateEmptyContent: function () {
				var dir = this.getCurrentDirectory();
				if (dir === "/") {
					// root has special permissions
					this.$el
						.find("#emptycontent")
						.toggleClass("hidden", !this.isEmpty);
					this.$el
						.find("#filestable thead th")
						.toggleClass("hidden", this.isEmpty);
				} else {
					OCA.Files.FileList.prototype.updateEmptyContent.apply(
						this,
						arguments
					);
				}
			},

			getDirectoryPermissions: function () {
				return OC.PERMISSION_ALL;
			},

			_updateDirectoryPermissions: function () {
				//var isCreatable = (this.dirInfo.permissions & OC.PERMISSION_CREATE) !== 0 && this.$el.find('#free_space').val() !== '0';
				alert("I am here 2!");
				var isCreatable = true;
				this.$el.find("#permissions").val(this.dirInfo.permissions);
				this.$el.find(".creatable").toggleClass("hidden", false);
				//this.$el.find('.notCreatable').toggleClass('hidden', true);
			},
			/**
			 * Shows/hides action buttons
			 *
			 * @param show true for enabling, false for disabling
			 */
			showActions: function (show) {
				alert("I am here!");
				this.$el
					.find(".actions,#file_action_panel")
					.toggleClass("hidden", !show);
				if (show) {
					// make sure to display according to permissions
					var permissions = this.getDirectoryPermissions();
					var isCreatable =
						(permissions & OC.PERMISSION_CREATE) !== 0;
					this.$el.find(".creatable").toggleClass("hidden", false);
					this.$el.find(".notCreatable").toggleClass("hidden", true);
					// remove old style breadcrumbs (some apps might create them)
					this.$el.find("#controls .crumb").remove();
					// refresh breadcrumbs in case it was replaced by an app
					this.breadcrumb.render();
				} else {
					this.$el
						.find(".creatable, .notCreatable")
						.addClass("hidden");
				}
			},

			updateStorageStatistics: function () {
				// no op because it doesn't have
				// storage info like free space / used space
			},

			reload: function () {
				alert(
					"\\apps\\nextcloud-swarm-plugin\\js\\beeswarmfilelist.js-reload()"
				);
				this.showMask();
				if (this._reloadCall) {
					this._reloadCall.abort();
				}

				// there is only root
				this._setCurrentDir("/", false);

				this._reloadCall = $.ajax({
					url: OC.linkToOCS("apps/files_external/api/v1") + "mounts",
					data: {
						format: "json",
					},
					type: "GET",
					beforeSend: function (xhr) {
						xhr.setRequestHeader("OCS-APIREQUEST", "true");
					},
				});
				var callBack = this.reloadCallback.bind(this);
				return this._reloadCall.then(callBack, callBack);
			},

			reloadCallback: function (result) {
				delete this._reloadCall;
				this.hideMask();

				if (result.ocs && result.ocs.data) {
					this.setFiles(this._makeFiles(result.ocs.data));
					return true;
				}
				return false;
			},

			/**
			 * Converts the OCS API  response data to a file info
			 * list
			 * @param OCS API mounts array
			 * @return array of file info maps
			 */
			_makeFiles: function (data) {
				var files = _.map(data, function (fileData) {
					fileData.icon = OC.imagePath(
						"core",
						"filetypes/folder-external"
					);
					fileData.mountType = "external";
					return fileData;
				});

				files.sort(this._sortComparator);

				return files;
			},
		}
	);

	/**
	 * Mount point info attributes.
	 *
	 * @typedef {Object} OCA.Files_External_BeeSwarm.MountPointInfo
	 *
	 * @property {String} name mount point name
	 * @property {String} scope mount point scope "personal" or "system"
	 * @property {String} backend external storage backend name
	 */

	//OCA.Files_External_BeeSwarm.FileList = FileList;
	OCA.Files.FileList = FileList;
})();
