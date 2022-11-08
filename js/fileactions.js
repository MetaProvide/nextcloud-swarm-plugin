$(document).ready(function () {
	var actionSwarm = {
		init: function () {
			var self = this;
			OCA.Files.fileActions.registerAction({
				name: 'EthswarmCopyHash',
				displayName: t('hash', 'Copy Swarm hash'),
				mime: 'all',
				permissions: OC.PERMISSION_READ,
				type: OCA.Files.FileActions.TYPE_DROPDOWN,
				iconClass: 'icon-extract',
				actionHandler: function (filename, context) {
					var data = {
						nameOfFile: filename,
						directory: context.dir,
						external: context.fileInfoModel.attributes.mountType && context.fileInfoModel.attributes.mountType.startsWith("xxxternal") ? 1 : 0,
						type: 'all'
					};
					// let msg = "var data=";
					// for (let property in data) {
					// 	msg = msg + (property + " = " + data[property]);
					// 	msg = msg + "; "
					// }
					// alert(msg);

					// msg = "context=";
					// for (let property in context) {
					// 	msg = msg + (property + " = " + context[property]);
					// 	msg = msg + "; "
					// }
					// alert(msg);

					// msg = "context.fileActions=";
					// for (let property in context.fileActions) {
					// 	msg = msg + (property + " = " + context.fileActions[property]);
					// 	msg = msg + "; "
					// }
					// alert(msg);

					// msg = "context.fileList=";
					// for (let property in context.fileList) {
					// 	msg = msg + (property + " = " + context.fileList[property]);
					// 	msg = msg + "; "
					// }
					// alert(msg);

					// msg = "context.$file=";
					// for (let property in context.$file) {
					// 	msg = msg + (property + " = " + context.$file[property]);
					// 	msg = msg + "; "
					// }
					// alert(msg);

					// msg = "context.fileInfoModel=";
					// for (let property in context.fileInfoModel) {
					// 	msg = msg + (property + " = " + context.fileInfoModel[property]);
					// 	msg = msg + "; "
					// }
					// alert(msg);

					msg = "context.fileInfoModel.attributes=";
					for (let property in context.fileInfoModel.attributes) {
						msg = msg + (property + " = " + context.fileInfoModel.attributes[property]);
						msg = msg + "; "
					}
					alert(msg);
					remoteurl = OC.linkToRemoteBase("dav/files/test" + context.fileInfoModel.attributes['path'] + "/" + filename);
					alert("url1 " + remoteurl);

					// nextcloud/server/-/blob/core/js/setupchecks.js?L45
					// $.ajax({
					// 	type: 'PROPFIND',
					// 	url: remoteurl,
					// 	data: '<?xml version="1.0" encoding="UTF-8"?>' +
					// 		'<d:propfind xmlns:nc="http://nextcloud.org/ns">' +
					// 		'<d:prop><nc:ethswarm-fileref/></d:prop>' +
					// 		'</d:propfind>',
					// 	contentType: 'application/xml; charset=utf-8',
					// 	complete: afterCall,
					// 	allowAuthErrors: true
					// });
					// alert(deferred.promise());
					// return deferred.promise();

					$.ajax({
						type: "PROPFIND",
						async: "false",
						url: remoteurl,
						data: '<?xml version="1.0" encoding="UTF-8"?>' +
							'<d:propfind xmlns:d="DAV:" xmlns:nc="http://nextcloud.org/ns">' +
							'<d:prop><nc:ethswarm-fileref/></d:prop>' +
							'</d:propfind>',
						success: function (element) {
							alert("element=" + (element));
							// alert("element.data=" + (element.data));
							// alert("element.xml=" + (element.xml));
							// alert("element.ms=" + (element.multistatus));
							//xml = $.parseXML(element);

							// https://sourcegraph.com/github.com/nextcloud/server/-/blob/apps/dav/src/service/CalendarService.js
							//alert("parse2=" + parseXML(element.data));

							alert("ajax1 = " + $(element).find('nc:ethswarm-fileref').text());
							alert("ajax2 = " + $(element).find('ethswarm-fileref').text());
							alert("ajax3 = " + $(element).find('d:response').text());
							alert("ajax4 = " + $(element).find('response').text());
							alert("ajax5 = " + $(element).find('d:propstat').text());
							alert("ajax6 = " + $(element).find('propstat').text());
							alert("ajax7 = " + $(element).find('multistatus').text());
							alert("ajax8 = " + $(element).find('d:multistatus').text());

							$(element).find("prop").each(function () {
								var _name = 'Name1: ' + $(this).find('nc:ethswarm-fileref').text();
								alert(_name);
							});

							$(element).find("d:prop").each(function () {
								var _name = 'Name2: ' + $(this).find('nc:ethswarm-fileref').text();
								alert(_name);
							});

							element = element.replace(/null/g, '');
							response = JSON.parse(element);
							alert("r=" + response);
							alert("r.code=" + response.code);
							alert("r.data=" + response.data);
							if (response.code == 1) {
								alert(response.desc);
								alert(response.data);
							} else {
								OC.dialogs.alert(
									t('ethswarm', response.desc),
									t('ethswarm', 'Error retrieving swarm reference for ' + filename)
								);
							}
						}
					});

					// var tr = context.fileList.findFileEl(filename);

					//context.fileList.showFileBusyState(tr, true);
					// $.ajax({
					// 	type: "POST",
					// 	async: "false",
					// 	url: OC.filePath('extract', 'ajax', 'extract.php'),
					// 	data: data,
					// 	success: function (element) {
					// 		console.log(element);
					// 		element = element.replace(/null/g, '');
					// 		response = JSON.parse(element);
					// 		if (response.code == 1) {
					// 			context.fileList.reload();
					// 		} else {
					// 			context.fileList.showFileBusyState(tr, false);
					// 			OC.dialogs.alert(
					// 				t('extract', response.desc),
					// 				t('extract', 'Error extracting ' + filename)
					// 			);
					// 		}
					// 	}
					// });
				}
			});
		},
	}
	actionSwarm.init();
});
