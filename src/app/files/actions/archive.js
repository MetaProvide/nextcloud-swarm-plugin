import { FileAction, registerFileAction } from "@nextcloud/files";
import axios from "@nextcloud/axios";
import { showError, showSuccess } from "@nextcloud/dialogs";
import { emit } from "@nextcloud/event-bus";
import ArchiveSvg from "@material-design-icons/svg/filled/archive.svg";
import UnarchiveSvg from "@material-design-icons/svg/filled/unarchive.svg";
import FilesHelper from "@/util/FilesHelper";
import SvgHelper from "@/util/SvgHelper";

registerFileAction(
	new FileAction({
		id: "archiveAction",
		displayName(nodes) {
			return t(
				"files_external_ethswarm",
				FilesHelper.isArchive(nodes) ? "Restore" : "Archive"
			);
		},
		iconSvgInline(nodes) {
			const logo = FilesHelper.isArchive(nodes)
				? UnarchiveSvg
				: ArchiveSvg;
			return SvgHelper.convert(logo);
		},
		enabled(files) {
			if (files[0].attributes["ethswarm-node"]) {
				return !(
					FilesHelper.isRoot(files) ||
					FilesHelper.isArchiveFolder(files)
				);
			}
			return false;
		},
		async exec(node) {
			const action = FilesHelper.isArchive(node)
				? "unarchive"
				: "archive";
			if (action === "unarchive") {
				const path = await FilesHelper.locationPicker(
					node,
					"Restore",
					UnarchiveSvg
				);
				const destination = FilesHelper.getPathParts(path)
					.slice(1)
					.join("/");
				await axios({
					method: "post",
					url: node.encodedSource,
					headers: {
						"Hejbit-Action": action,
						Destination: destination,
					},
				}).then((response) => {
					if (response.data.status === true) {
						emit("files:node:deleted", node);
						showSuccess("Restored successfully");
					} else {
						console.error("Error while restoring file", response);
						showError(response.data.message);
					}
				});
			} else {
				await axios({
					method: "post",
					url: node.encodedSource,
					headers: { "Hejbit-Action": action },
				}).then((response) => {
					if (response.data.status === true) {
						emit("files:node:deleted", node);
						showSuccess("Archived successfully");
						if (FilesHelper.isRootLevel(node)) {
							emit('files:config:updated');
						}
					} else {
						console.error("Error while archiving file", response);
						showError(response.data.message);
					}
				});
			}
		},
		execBatch(nodes) {
			return Promise.all(nodes.map((node) => this.exec(node)));
		},
	})
);
