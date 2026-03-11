import { Dialog, showSuccess, showWarning } from "@nextcloud/dialogs";
import SwarmSvg from "@/../img/swarm-logo.svg";
import ClipboardSvg from "@material-design-icons/svg/filled/content_paste.svg";
import OpenSvg from "@material-design-icons/svg/filled/open_in_new.svg";
import SvgHelper from "@/util/SvgHelper";
import FilesHelper from "@/util/FilesHelper";
import { registerFileActionCompat } from "@/util/FilesCompatibility";

registerFileActionCompat({
	id: "EthswarmCopyRef",

	displayName() {
		return t("files_external_ethswarm", "Swarm Reference");
	},

	altText() {
		return t("files_external_ethswarm", "Swarm Reference");
	},

	enabled({ nodes }) {
		return (
			FilesHelper.isSwarmNode(nodes) &&
			!FilesHelper.isRoot(nodes) &&
			!FilesHelper.isArchiveFolder(nodes)
		);
	},

	inline() {
		return false;
	},

	iconSvgInline() {
		return SvgHelper.convert(SwarmSvg);
	},

	async exec({ nodes, view }) {
		const node = nodes[0];
		const swarmref = FilesHelper.getSwarmRef(node);
		if (FilesHelper.isFolder(node)) {
			showWarning(
				t(
					"files_external_ethswarm",
					"Folder structure is not yet supported on Swarm. This folder is only available on Nextcloud, although all files within it are accessible on Swarm."
				)
			);
			return;
		}

		const gatewayUrl = `https://gateway.ethswarm.org/access/${swarmref}`;

		await new Dialog("Swarm Reference", swarmref, [
			{
				label: t("files_external_ethswarm", "Copy to Clipboard"),
				variant: "secondary",
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
			{
				label: t("files_external_ethswarm", "Access on Swarm Gateway"),
				variant: "secondary",
				icon: SvgHelper.convert(OpenSvg),
				callback: () => {
					window.open(gatewayUrl, "_blank", "noopener,noreferrer");
				},
			},
		]).show();
	},
});
