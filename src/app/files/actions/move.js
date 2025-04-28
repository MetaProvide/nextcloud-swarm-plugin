import { FileAction, registerFileAction } from "@nextcloud/files";
import { emit } from "@nextcloud/event-bus";
import MoveSvg from "@material-design-icons/svg/filled/drive_file_move.svg";
import FilesHelper from "@/util/FilesHelper";
import SvgHelper from "@/util/SvgHelper";
import axios from "@nextcloud/axios";
import { showError, showSuccess } from "@nextcloud/dialogs";

registerFileAction(
	new FileAction({
		id: "moveAction",
		displayName(nodes) {
			return t(
				"files_external_ethswarm",
				"Move"
			);
		},
		iconSvgInline(nodes) {
			return SvgHelper.convert(MoveSvg);
		},
		enabled(files) {
			if (files[0].attributes["ethswarm-node"]) {
				return !(
					FilesHelper.isFolder(files) ||
					FilesHelper.isArchive(files) ||
					FilesHelper.isRoot(files)
				);
			}
			return false;
		},
		async exec(node) {
			const path = await FilesHelper.locationPicker(
				node,
				"Move",
				MoveSvg
			);
			const destination = FilesHelper.getPathParts(path)
				.slice(1)
				.join("/");
			await axios({
				method: "post",
				url: node.encodedSource,
				headers: {
					"Hejbit-Action": "move",
					Destination: destination,
				},
			}).then((response) => {
				if (response.data.status === true) {
					emit("files:node:deleted", node);
					showSuccess("Moved successfully");
				} else {
					console.error("Error while moving file", response);
					showError(response.data.message);
				}
			});
		},
		execBatch(nodes) {
			return Promise.all(nodes.map((node) => this.exec(node)));
		},
	})
);
