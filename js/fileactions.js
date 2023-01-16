/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */
window.addEventListener('DOMContentLoaded', () => {
	var actionSwarm = {
		registerMenu: function (mimetype) {
			OCA.Files.fileActions.registerAction({
				name: 'EthswarmCopyRef',
				displayName: t('files_external_ethswarm', 'Copy Swarm reference'),
				altText: t('files_external_ethswarm', 'Copy Swarm reference to clipboard'),
				mime: mimetype,
				permissions: OC.PERMISSION_READ,
				type: OCA.Files.FileActions.TYPE_DROPDOWN,
				iconClass: 'icon-clippy',
				actionHandler: function (filename, context) {
					if (context.$file && context.fileInfoModel.attributes.mountType != "external") {
						return;
					}
					remoteurl = OC.linkToRemoteBase("dav/files/" + OC.currentUser + context.fileInfoModel.attributes['path'] + "/" + filename);

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
			// Logic to show/hide the menu. Obtain querystring to determine storage name
			if (!location.search) {
				return;
			}
			mountdir = new URLSearchParams(location.search).get("dir").split('/')[1];
			isSwarmMount = mountPointList?.find(el => el.mount_point == mountdir && el.backend == "files_external_ethswarm");
			if (isSwarmMount) {
				actionSwarm.registerMenu('all');
			}
		},
	}
	// For initial page load
	if (OCA?.Files_External?.StatusManager) {
		//OCA.Files_External.StatusManager.getMountPointList(actionSwarm.init);
		OCA.Files_External.StatusManager.getMountPointList(actionSwarm.registerMenu('all'));
	}
});
