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
					let msg = "var data=";
					for (let property in data) {
						msg = msg + (property + " = " + data[property]);
						msg = msg + "; "
					}
					alert(msg);

					msg = "context=";
					for (let property in context) {
						msg = msg + (property + " = " + context[property]);
						msg = msg + "; "
					}
					alert(msg);

					msg = "context.fileActions=";
					for (let property in context.fileActions) {
						msg = msg + (property + " = " + context.fileActions[property]);
						msg = msg + "; "
					}
					alert(msg);

					msg = "context.fileList=";
					for (let property in context.fileList) {
						msg = msg + (property + " = " + context.fileList[property]);
						msg = msg + "; "
					}
					alert(msg);

					msg = "context.$file=";
					for (let property in context.$file) {
						msg = msg + (property + " = " + context.$file[property]);
						msg = msg + "; "
					}
					alert(msg);

					msg = "context.fileInfoModel=";
					for (let property in context.fileInfoModel) {
						msg = msg + (property + " = " + context.fileInfoModel[property]);
						msg = msg + "; "
					}
					alert(msg);

					msg = "context.fileInfoModel.attributes=";
					for (let property in context.fileInfoModel.attributes) {
						msg = msg + (property + " = " + context.fileInfoModel.attributes[property]);
						msg = msg + "; "
					}
					alert(msg);
					var tr = context.fileList.findFileEl(filename);
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
