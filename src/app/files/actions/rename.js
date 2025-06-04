import { FileAction, registerFileAction } from "@nextcloud/files";
import { emit } from "@nextcloud/event-bus";
import EditSvg from "@material-design-icons/svg/filled/edit.svg";
import FilesHelper from "@/util/FilesHelper";
import SvgHelper from "@/util/SvgHelper";

registerFileAction(
	new FileAction({
		id: "renameAction",
		displayName(nodes) {
			return t(
				"files_external_ethswarm",
				"Rename"
			);
		},
		iconSvgInline(nodes) {
			return SvgHelper.convert(EditSvg);
		},
		enabled(files) {
			if (files[0].attributes["ethswarm-node"]) {
				return !(
					FilesHelper.isArchive(files) ||
					FilesHelper.isRoot(files)
				);
			}
			return false;
		},
		async exec(node) {
			emit('files:node:rename', node);
		},
	})
);
