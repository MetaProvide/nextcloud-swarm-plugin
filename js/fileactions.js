//const { debug } = require("webpack");
$(window).on('load', function () {
	//$(document).ready(function () {
	var actionSwarm = {
		init: function () {
			// Logic to show/hide the menu could be put here in this if statement?
			//console.log(OCA.Files.FileList);
			mountdir = new URLSearchParams(location.search).get("dir").replace("/", "");
			console.log("mountdir=" + mountdir);
			//console.log("ms=" + OCA.Files_External.StatusManager.mountStatus);
			//console.log("issw=" + OCA.Files_External.StatusManager.mountStatus["Bee-Remote"].backend);
			//console.log("issw2=" + OCA.Files_External.StatusManager.getMountPointListElement(mountdir).backend);
			console.log("issw3=" + OCA.Files_External.StatusManager.getMountPointListElement("Bee-Remote"));
			console.log("issw4=" + JSON.stringify(OCA.Files_External.StatusManager.mountPointList));
			//isSwarmMount = (mountdir && OCA.Files_External.StatusManager.getMountPointListElement(mountdir).backend == "files_external_ethswarm");
			isSwarmMount = true;

			if (isSwarmMount) {
				OCA.Files.fileActions.registerAction({
					name: 'EthswarmCopyRef',
					displayName: t('files_external_ethswarm', 'Copy Swarm reference'),
					altText: t('files_external_ethswarm', 'Copy Swarm reference to clipboard'),
					mime: 'all',
					permissions: OC.PERMISSION_READ,
					type: OCA.Files.FileActions.TYPE_DROPDOWN,
					iconClass: 'icon-clippy',
					render: function (actionSpec, isDefault, context) {
						alert("isDefault " + isDefault + " actionSpec" + actionSpec);
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
			}
		},
	}
	// mountdir = new URLSearchParams(location.search).get("dir").replace("/", "");
	// mountpoint = OCA.Files_External.StatusManager.getMountPointListElement(mountdir).backend;
	actionSwarm.init();

	if (!OCA?.Sharing?.ShareTabSections) {
		return
	}
	import(/* webpackChunkName: "sharing" */'./SharingSidebarApp.js').then((Module) => {
		OCA.Sharing.ShareTabSections.registerSection((el, fileInfo) => {
			if (fileInfo.mountType !== 'external') {
				return
			}
			return Module.default
		})
	})
});

// window.addEventListener('DOMContentLoaded', () => {
// 	console.log("issw3=" + OCA.Files_External.StatusManager); //.getMountPointListElement("Bee-Remote"));
// 	alert("here");
// })
