alert("Enter");
window.addEventListener('DOMContentLoaded', () => {
	var actionSwarm = {
		registerMenu: function () {
			OCA.Files.fileActions.registerAction({
				name: 'EthswarmCopyRef',
				displayName: t('files_external_ethswarm', 'Copy Swarm reference'),
				altText: t('files_external_ethswarm', 'Copy Swarm reference to clipboard'),
				mime: 'all',
				permissions: OC.PERMISSION_READ,
				type: OCA.Files.FileActions.TYPE_DROPDOWN,
				iconClass: 'icon-clippy',
				render: function (actionSpec, isDefault, context) {
					console.log("isDefault " + isDefault + " actionSpec" + actionSpec);
					// don't render anything
					// if (context.fileInfoModel.attributes.mountType && !context.fileInfoModel.attributes.mountType.startsWith("external")) {
					// 	return null;
					// }
				},
				actionHandler: function (filename, context) {
					msg = "context.fileInfoModel.attributes=";
					for (let property in context.fileInfoModel.attributes) {
						msg = msg + (property + " = " + context.fileInfoModel.attributes[property]);
						msg = msg + "; "
					}
					console.debug(msg);
					if (context.$file && context.fileInfoModel.attributes.mountType != "external") {
						console.debug("mountType = " + context.$file.attr('data-mounttype'));
						return;
					}
					remoteurl = OC.linkToRemoteBase("dav/files/" + OC.currentUser + context.fileInfoModel.attributes['path'] + "/" + filename);
					console.debug("url " + remoteurl);

					$.ajax({
						type: "PROPFIND",
						async: "false",
						url: remoteurl,
						data: '<?xml version="1.0" encoding="UTF-8"?>' +
							'<d:propfind xmlns:d="DAV:" xmlns:nc="http://nextcloud.org/ns">' +
							'<d:prop><nc:ethswarm-fileref/></d:prop>' +
							'</d:propfind>',
						success: function (element) {
							swarmref = element.firstChild.firstChild.lastChild.firstChild.firstChild.textContent;
							navigator.clipboard.writeText(swarmref).then(
								() => {
									/* clipboard successfully set */
									OC.dialogs.info(t('files_external_ethswarm', 'Copied: ' + swarmref), t('files_external_ethswarm', 'Swarm reference to clipboard'));
								},
								() => {
									/* clipboard write failed */
									OC.dialogs.info(t('files_external_ethswarm', 'Unable to copy:' + swarmref), t('files_external_ethswarm', 'Swarm reference to clipboard'));
								}
							);
						}
					});
				}
			});
		},
		init: function (mountPointList) {
			// Logic to show/hide the menu
			//console.log(OCA.Files.FileList);
			if (!location.search) {
				return;
			}
			mountdir = new URLSearchParams(location.search).get("dir").split('/')[1];
			console.log("mountdir=" + mountdir);

			console.log("parse OC=", OC.parseQueryString(location.search)?.dir);
			isSwarmMount = mountPointList?.find(el => el.mount_point == mountdir && el.backend == "files_external_ethswarm");
			if (isSwarmMount) {
				console.log("register 1");
				actionSwarm.registerMenu();
			}
		},
	}
	// For initial page load
	if (OCA?.Files_External?.StatusManager) {
		OCA.Files_External.StatusManager.getMountPointList(actionSwarm.init);
	}

	// Declare callback function
	var actionExternal = {
		start: function (mountPointList, e) {
			var self = this;
			console.log("actionExternal.start mountPointList", mountPointList, self);
			console.log("actionExternal.start args", arguments);
			console.log("actionExternal.start find=", mountPointList?.find(el => el.mount_point == e.dir && el.backend == "files_external_ethswarm"));
		},
	}

	// Detect changes in directory navigation
	$('#app-content-files').on('changeDirectory', function (e) {
		currentDir = e.dir.split('/')[1];
		prevDir = e.previousDir.split('/')[1];
		console.log("app-content changedir", currentDir);

		if (currentDir == prevDir) {
			return;
		}

		// Get mounts with callback function
		OCA.Files_External.StatusManager.getMountPointList(function (mounts) {
			actionExternal.start();
			console.log("mounts find=", mounts?.find(el => el.mount_point == currentDir && el.backend == "files_external_ethswarm"));

			isSwarmDir = mounts?.find(el => el.mount_point == currentDir && el.backend == "files_external_ethswarm");
			console.log("isSwarms", isSwarmDir);

			if (isSwarmDir) {
				console.log("Swarm menu", "actionSwarm.registerMenu()");
				actionSwarm.registerMenu();
			}
			else {
				if (OCA?.Files?.fileActions?.actions) {
					console.log("Un Register actions", OCA.Files.fileActions.actions);
					if (OCA?.Files?.fileActions?.actions.all) {
						OCA.Files.fileActions.actions.all.EthswarmCopyRef = {};
					}
				}
			}
		});
	})
});
