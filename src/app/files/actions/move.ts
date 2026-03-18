import MoveSvg from "@material-design-icons/svg/filled/drive_file_move.svg?raw";
import axios from "@nextcloud/axios";
import { showError, showSuccess } from "@nextcloud/dialogs";
import { emit } from "@nextcloud/event-bus";
import { registerFileActionCompat } from "@/util/FilesCompatibility";
import FilesHelper from "@/util/FilesHelper";
import SvgHelper from "@/util/SvgHelper";

registerFileActionCompat({
	id: "moveAction",
	displayName() {
		return t("files_external_ethswarm", "Move");
	},
	iconSvgInline() {
		return SvgHelper.convert(MoveSvg);
	},
	enabled({ nodes }) {
		if (nodes[0].attributes["ethswarm-node"]) {
			return !(FilesHelper.isArchive(nodes) || FilesHelper.isRoot(nodes));
		}
		return false;
	},
	async exec({ nodes }) {
		const node = nodes[0];
		const path = await FilesHelper.locationPicker(node, "Move", MoveSvg);
		const destination = FilesHelper.getPathParts(path).slice(1).join("/");
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
	execBatch({ nodes, view }) {
		return Promise.all(
			nodes.map((node) => this.exec({ nodes: [node], view })),
		);
	},
});
