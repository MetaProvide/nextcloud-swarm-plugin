//const { debug } = require("webpack");
$(document).ready(function () {
	var actionSwarm = {
		init: function () {
			// Logic to show/hide the menu could be put here in this if statement?
			if (1 == 1) {
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
					shouldRender(context) {
						msg = "context=";
						alert(msg);
						for (let property in context) {
							msg = msg + (property + " = " + context[property]);
							msg = msg + "; "
						}
						debug.log("msg1=" + msg);
						console.debug("msg1=" + msg);
						msg = "context.$file=";
						for (let property in context.$file) {
							msg = msg + (property + " = " + context[property]);
							msg = msg + "; "
						}
						debug.log("msg2=" + msg);
						return true;
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
										OC.dialogs.alert(t('files_external_ethswarm', 'Clipboard copied: ' + swarmref));
									},
									() => {
										/* clipboard write failed */
										OC.dialogs.alert(t('files_external_ethswarm', 'Unable to copy the reference:' + swarmref));
									}
								);
							}
						});
					}
				});
			}
		},
	}
	actionSwarm.init();
});
