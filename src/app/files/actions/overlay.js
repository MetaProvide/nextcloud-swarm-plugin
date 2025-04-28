import { FileAction, registerFileAction } from "@nextcloud/files";
import { showInfo } from "@nextcloud/dialogs";
import HejBitSvg from "@/../img/hejbit-logo.svg";
import InfoSvg from "@material-design-icons/svg/filled/info.svg";
import FilesHelper from "@/util/FilesHelper";
import SvgHelper from "@/util/SvgHelper";

registerFileAction(
	new FileAction({
		id: "overlayAction",

		displayName() {
			return "";
		},

		enabled(files, view) {
			if (files.length !== 1)
				// We don't support batch actions
				return false;

			// To fix fileaction navigation bug this action is now available for
			// files and folders on Swarm storage
			const attrs = files[0].attributes["ethswarm-node"];

			if (attrs === undefined) return false;
			else if (attrs === "") return false;

			return true;
		},

		iconSvgInline(files, view) {
			return SvgHelper.convert(
				FilesHelper.isArchiveFolder(files) ? InfoSvg : HejBitSvg
			);
		},

		inline(file, view) {
			return true;
		},

		async renderInline(node, view) {
			// Create the overlay element
			const overlay = document.createElement("div");
			overlay.classList.add("hejbit-overlay");

			if (FilesHelper.isArchive(node)) {
				overlay.classList.add("hejbit-archive");
			}

			overlay.innerHTML = SvgHelper.convert(HejBitSvg);

			return overlay;
		},

		async exec(node, view) {
			if (FilesHelper.isArchiveFolder(node)) {
				showInfo(
					`
						<div style="display: block; margin: 1rem 0;">
							<div style="margin-bottom: 0.5rem;">${t(
								"files_external_ethswarm",
								"Archive folder is for keeping your HejBit storage more organized."
							)}</div>
							<div style="font-weight: lighter">${t(
								"files_external_ethswarm",
								"You can archive files and folder from menu action."
							)}</div>
							<div style="font-weight: lighter">${t(
								"files_external_ethswarm",
								"You can restore archived files in the archive folder from menu action."
							)}</div>
						</div>
						`,
					{
						isHTML: true,
					},
					t("files_external_ethswarm", "Hejbit")
				);
			} else if (FilesHelper.isFolder(node)) {
				showInfo(
					t(
						"files_external_ethswarm",
						"Folder structure is not yet supported on Swarm. This folder is only available on Nextcloud, although all files within it are accessible on Swarm."
					),
					t("files_external_ethswarm", "Hejbit")
				);
			} else {
				showInfo(
					t(
						"files_external_ethswarm",
						"This file is on Swarm Network by Hejbit!"
					),
					t("files_external_ethswarm", "Hejbit")
				);
			}
		},

		execBatch(nodes, view) {
			return Promise.all(nodes.map((node) => this.exec(node, view)));
		},
	})
);

