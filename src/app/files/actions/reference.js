import { FileAction, registerFileAction } from "@nextcloud/files";
import { Dialog, showSuccess, showWarning } from "@nextcloud/dialogs";
import SwarmSvg from "@/../img/swarm-logo.svg";
import ClipboardSvg from "@material-design-icons/svg/filled/content_paste.svg";
import SvgHelper from "@/util/SvgHelper";
import FilesHelper from "@/util/FilesHelper";

registerFileAction(
	new FileAction({
		id: "EthswarmCopyRef",

		displayName() {
			return t("files_external_ethswarm", "View Swarm Reference");
		},

		altText() {
			return t("files_external_ethswarm", "Swarm Reference");
		},

		enabled(files) {
			return (
				FilesHelper.isSwarmNode(files) &&
				!FilesHelper.isRoot(files) &&
				!FilesHelper.isArchiveFolder(files)
			);
		},

		inline() {
			return false;
		},

		iconSvgInline() {
			return SvgHelper.convert(SwarmSvg);
		},

		async exec(node, view) {
			const swarmref = FilesHelper.getSwarmRef(node);
			if (FilesHelper.isFolder(node)) {
				showWarning(
					t(
						"files_external_ethswarm",
						"Folder structure is not yet supported on Swarm. This folder is only available on Nextcloud, although all files within it are accessible on Swarm."
					),
					t("files_external_ethswarm", "Swarm Reference")
				);
				return;
			}

			await new Dialog("View Swarm Reference", swarmref, [
				{
					label: t("files_external_ethswarm", "Copy to Clipboard"),
					type: "secondary",
					icon: SvgHelper.convert(ClipboardSvg),
					callback: () =>
						navigator.clipboard.writeText(swarmref).then(
							() =>
								showSuccess(
									t(
										"files_external_ethswarm",
										"The Swarm reference has been copied to your clipboard"
									)
								),
							() =>
								showWarning(
									`
											<div style="margin: 1rem 0; width: 35rem;">
												<span>${t(
													"files_external_ethswarm",
													"Unable to write the Swarm Reference into your clipboard. Copy it manually"
												)}</span>
											</div>
										`,
									{
										isHTML: true,
									}
								)
						),
				},
			]).show();
		},
	})
);
