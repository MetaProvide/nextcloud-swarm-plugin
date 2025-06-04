import { FileAction, registerFileAction } from "@nextcloud/files";
import axios from "@nextcloud/axios";
import { showError, showSuccess } from "@nextcloud/dialogs";
import { emit } from "@nextcloud/event-bus";
import DownloadSvg from "@material-design-icons/svg/filled/download.svg";
import FilesHelper from "@/util/FilesHelper";
import SvgHelper from "@/util/SvgHelper";

registerFileAction(
	new FileAction({
		id: "exportAction",
		displayName(nodes) {
			return t(
				"files_external_ethswarm",
				"Export"
			);
		},
		iconSvgInline(nodes) {
			return SvgHelper.convert(DownloadSvg);
		},
		enabled(files) {
			if (files[0].attributes["ethswarm-node"]) {
				return FilesHelper.isRoot(files);
			}
			return false;
		},
		async exec(node) {
			await axios({
				method: "post",
				url: node.encodedSource,
				headers: {
					"Hejbit-Action": "export",
				},
			}).then((response) => {
				if (response.data.status === true) {
					const blob = new Blob([JSON.stringify(response.data.data)], {
						type: "application/json",
					});
					const storageName = FilesHelper.getStoragePath(node.path);
					const date = new Date().toISOString().split("T")[0];
					FilesHelper.downloadFile(blob, `hejbit-export-${storageName}-${date}.json`);
					showSuccess("Exported references successfully");
				} else {
					console.error("Error while exporting references", response);
					showError(response.data.message);
				}
			});
		},
	})
);
