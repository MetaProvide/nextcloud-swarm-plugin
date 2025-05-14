import { FileAction, FileType, registerFileAction } from "@nextcloud/files";
import { showError, showInfo } from "@nextcloud/dialogs";
import axios from "@nextcloud/axios";
import { emit } from "@nextcloud/event-bus";
import Close from "@material-design-icons/svg/filled/close.svg";
import CloudOff from "@material-design-icons/svg/filled/cloud_off.svg";
import HideSource from "@material-design-icons/svg/filled/visibility_off.svg";
import UnhideSource from "@material-design-icons/svg/filled/settings_backup_restore.svg";
import FilesHelper from "@/util/FilesHelper";
import SvgHelper from "@/util/SvgHelper";

registerFileAction(
	new FileAction({
		id: "unhideAction",
		displayName(nodes, view) {
			/**
			 * If we're only selecting files, use proper wording
			 */
			if (FilesHelper.isAllFiles(nodes)) {
				if (nodes.length === 1) {
					return t("files_external_ethswarm", "Unhide");
				}
				return t("files_external_ethswarm", "Unhide");
			}
			/**
			 * If we're only selecting folders, use proper wording
			 */
			if (FilesHelper.isAllFolders(nodes)) {
				if (nodes.length === 1) {
					return t("files_external_ethswarm", "Unhide");
				}
				return t("files_external_ethswarm", "Unhide");
			}
			return t("files_external_ethswarm", "Unhide");
		},
		iconSvgInline: (nodes) => {
			return SvgHelper.convert(UnhideSource);
		},
		inline(file, view) {
			return true;
		},

		enabled(files, view) {
			if (files.length !== 1)
				// We don't support batch actions
				return false;
			const attrs = files[0].attributes["ethswarm-node"];
			const hidden = files[0].attributes.hidden;

			if (attrs === undefined) return false;
			else if (attrs === "") return false;
			if (!hidden) return false;

			return attrs;
		},
		async exec(node, view, dir) {
			try {
				await axios({
					method: "post",
					url: node.encodedSource,
					headers: {
						"Hejbit-Action": "unhide",
					},
				});

				emit('files:config:updated');

				return true;
			} catch (error) {
				showError("Error while unhidding file");
				console.log("Error while unhidding a file", {
					error,
					source: node.source,
					node,
				});
				return false;
			}
		},
		execBatch(nodes, view) {
			return Promise.all(nodes.map((node) => this.exec(node, view)));
		},
		order: 150,
	})
);

registerFileAction(
	new FileAction({
		id: "hideAction",
		displayName(nodes, view) {
			/**
			 * If we're in the sharing view, we can only unshare
			 */
			if (FilesHelper.isMixedUnshareAndDelete(nodes)) {
				return t("files_external_ethswarm", "Hide and unshare");
			}
			/**
			 * If those nodes are all the root node of a
			 * share, we can only unshare them.
			 */
			if (FilesHelper.canUnshareOnly(nodes)) {
				if (nodes.length === 1) {
					return t("files_external_ethswarm", "Leave this share");
				}
				return t("files_external_ethswarm", "Leave these shares");
			}
			/**
			 * If those nodes are all the root node of an
			 * external storage, we can only disconnect it.
			 */
			if (FilesHelper.canDisconnectOnly(nodes)) {
				if (nodes.length === 1) {
					return t("files_external_ethswarm", "Disconnect storage");
				}
				return t("files_external_ethswarm", "Disconnect storages");
			}
			/**
			 * If we're only selecting files, use proper wording
			 */
			if (FilesHelper.isAllFiles(nodes)) {
				if (nodes.length === 1) {
					return t("files_external_ethswarm", "Hide file");
				}
				return t("files_external_ethswarm", "Hide files");
			}
			/**
			 * If we're only selecting folders, use proper wording
			 */
			if (FilesHelper.isAllFolders(nodes)) {
				if (nodes.length === 1) {
					return t("files_external_ethswarm", "Hide folder");
				}
				return t("files_external_ethswarm", "Hide folders");
			}
			return t("files_external_ethswarm", "Hide");
		},
		iconSvgInline: (nodes) => {
			if (FilesHelper.canUnshareOnly(nodes)) {
				return SvgHelper.convert(Close);
			}
			if (FilesHelper.canDisconnectOnly(nodes)) {
				return SvgHelper.convert(CloudOff);
			}
			return SvgHelper.convert(HideSource);
		},
		enabled(files, view) {
			if (files.length !== 1)
				// We don't support batch actions
				return false;
			const attrs = files[0].attributes["ethswarm-node"];
			const hidden = files[0].attributes.hidden;

			if (attrs === undefined) return false;
			else if (attrs === "") return false;
			if (hidden) return false;

			return attrs;
		},
		async exec(node, view, dir) {
			let message = "";
			if (node.type === FileType.File) {
				message = t(
					"files_external_ethswarm",
					"The file will be set to hide on the folder view. The file will continue to exist on the Swarm network."
				);
			} else if (node.type === FileType.Folder) {
				message = t(
					"files_external_ethswarm",
					"The folder will be set to hide on the folder view. All the files inside the folder will continue to exist on the Swarm network."
				);
			}
			showInfo(message);
			try {
				await axios({
					method: "post",
					url: node.encodedSource,
					headers: {
						"Hejbit-Action": "hide",
					},
				});

				emit('files:config:updated');
				return true;
			} catch (error) {
				showError("Error while hiding file");
				console.log("Error while hidding a file", {
					error,
					source: node.source,
					node,
				});
				return false;
			}
		},
		execBatch(nodes, view) {
			return Promise.all(nodes.map((node) => this.exec(node, view)));
		},
		order: 150,
	})
);
