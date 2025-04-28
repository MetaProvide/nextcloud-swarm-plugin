import { FileType } from "@nextcloud/files";
import { getFilePickerBuilder } from "@nextcloud/dialogs";
import { basename, dirname } from "path";
import SvgHelper from "@/util/SvgHelper";

const FilesHelper = {
	canUnshareOnly: (nodes) => {
		return nodes.every(
			(node) =>
				node.attributes["is-mount-root"] === true &&
				node.attributes["mount-type"] === "shared"
		);
	},
	canDisconnectOnly: (nodes) => {
		return nodes.every(
			(node) =>
				node.attributes["is-mount-root"] === true &&
				node.attributes["mount-type"] === "external"
		);
	},
	isMixedUnshareAndDelete: (nodes) => {
		if (nodes.length === 1) {
			return false;
		}
		const hasSharedItems = nodes.some((node) =>
			this.canUnshareOnly([node])
		);
		const hasDeleteItems = nodes.some(
			(node) => !this.canUnshareOnly([node])
		);
		return hasSharedItems && hasDeleteItems;
	},
	isAllFiles: (nodes) => {
		return !nodes.some((node) => node.type !== FileType.File);
	},
	isAllFolders: (nodes) => {
		return !nodes.some((node) => node.type !== FileType.Folder);
	},
	getStoragePath: (nodes) => getStoragePath(nodes),
	getPathParts: (nodes) => getPathParts(nodes),
	isFolder: (nodes) => getMainNode(nodes).type === FileType.Folder,
	isRoot: (node) => getPathParts(node).length === 1,
	isRootLevel: (node) => getPathParts(node).length === 2,
	isArchive: (node) => isArchive(node),
	isArchiveFolder: (node) =>
		getPathParts(node).length === 2 && isArchive(node),
	locationPicker: (node, action, logo) =>
		getFilePickerBuilder(`Select ${action} Location`)
			.setButtonFactory((selection, path) => {
				return FilesHelper.getStoragePath(path) === ""
					? []
					: [
							{
								label: basename(path)
									? t(
											"files",
											"{action} to {path}",
											{ path: basename(path), action },
											undefined,
											{
												escape: false,
												sanitize: false,
											}
									  )
									: t("files", action),
								type: "primary",
								icon: SvgHelper.convert(logo),
								callback: (destination) => destination,
							},
					  ];
			})
			.allowDirectories(true)
			.setFilter((n) => {
				const isFolder = FilesHelper.isFolder(n);
				const isNotArchiveFolder = !FilesHelper.isArchiveFolder(n);
				console.log("node:" + FilesHelper.getStoragePath(n));
				console.log("file:" + FilesHelper.getStoragePath(node));
				const isSameStorage =
					FilesHelper.getStoragePath(n) ===
					FilesHelper.getStoragePath(node);
				return isFolder && isNotArchiveFolder && isSameStorage;
			})
			.setMimeTypeFilter([])
			.setMultiSelect(false)
			.disableNavigation(true)
			.startAt(
				dirname(node.path).substring(0, dirname(node.path).lastIndexOf("/"))
			)
			.build()
			.pick(),
};

function getMainNode(nodes) {
	if (Array.isArray(nodes)) {
		return nodes[0];
	}
	return nodes;
}

function getRelativePath(nodes) {
	if (typeof nodes === "string") {
		return nodes;
	}
	const node = getMainNode(nodes);
	const fullPath = node.attributes.filename;
	const root = node.root;
	return fullPath.replace(root, "");
}

function getPathParts(path) {
	return getRelativePath(path)
		.split("/")
		.filter((part) => part !== "");
}

function getStoragePath(path) {
	const parts = getPathParts(path);
	return parts.length > 0 ? parts[0] : "";
}

function isArchive(nodes) {
	return getPathParts(nodes)[1] === "Archive";
}

export default FilesHelper;
