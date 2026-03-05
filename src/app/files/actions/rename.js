import { registerFileAction } from "@nextcloud/files";
import { emit } from "@nextcloud/event-bus";
import EditSvg from "@material-design-icons/svg/filled/edit.svg";
import FilesHelper from "@/util/FilesHelper";
import SvgHelper from "@/util/SvgHelper";

registerFileAction({
	id: "renameAction",
	displayName({ nodes }) {
		return t("files_external_ethswarm", "Rename");
	},
	iconSvgInline({ nodes }) {
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
