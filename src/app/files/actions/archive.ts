import ArchiveSvg from "@material-design-icons/svg/filled/archive.svg?raw";
import UnarchiveSvg from "@material-design-icons/svg/filled/unarchive.svg?raw";
import axios from "@nextcloud/axios";
import { showError, showSuccess } from "@nextcloud/dialogs";
import { emit } from "@nextcloud/event-bus";
import { registerFileActionCompat } from "@/util/FilesCompatibility";
import FilesHelper from "@/util/FilesHelper";
import SvgHelper from "@/util/SvgHelper";

registerFileActionCompat({
	id: "archiveAction",
	displayName({ nodes }) {
		return t(
			"files_external_ethswarm",
			FilesHelper.isArchive(nodes) ? "Restore" : "Archive",
		);
	},
	iconSvgInline({ nodes }) {
		const logo = FilesHelper.isArchive(nodes) ? UnarchiveSvg : ArchiveSvg;
		return SvgHelper.convert(logo);
	},
	enabled({ nodes }) {
		if (nodes[0].attributes["ethswarm-node"]) {
			return !(
				FilesHelper.isRoot(nodes) || FilesHelper.isArchiveFolder(nodes)
			);
		}
		return false;
	},
	async exec({ nodes }) {
		const node = nodes[0];
		const action = FilesHelper.isArchive(node) ? "unarchive" : "archive";
		if (action === "unarchive") {
			const path = await FilesHelper.locationPicker(
				node,
				"Restore",
				UnarchiveSvg,
			);
			if (!path) {
				return;
			}
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
						emit("files:config:updated", undefined);
					}
				} else {
					console.error("Error while archiving file", response);
					showError(response.data.message);
				}
			});
		}
	},
	execBatch({ nodes, view }) {
		return Promise.all(
			nodes.map((node) => this.exec({ nodes: [node], view })),
		);
	},
});
