import EditSvg from "@material-design-icons/svg/filled/edit.svg?raw";
import { emit } from "@nextcloud/event-bus";
import { registerFileActionCompat } from "@/util/FilesCompatibility";
import FilesHelper from "@/util/FilesHelper";
import SvgHelper from "@/util/SvgHelper";

registerFileActionCompat({
	id: "renameAction",
	displayName() {
		return t("files_external_ethswarm", "Rename");
	},
	iconSvgInline() {
		return SvgHelper.convert(EditSvg);
	},
	enabled({ nodes }) {
		if (nodes[0].attributes["ethswarm-node"]) {
			return !(FilesHelper.isArchive(nodes) || FilesHelper.isRoot(nodes));
		}
		return false;
	},
	async exec({ nodes }) {
		emit("files:node:rename", nodes[0]);
	},
});
