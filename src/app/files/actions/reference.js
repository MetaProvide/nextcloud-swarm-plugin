import { FileAction, FileType, registerFileAction } from "@nextcloud/files";
import SwarmSvg from "@/../img/swarm-logo.svg";
import { showInfo, showSuccess, showWarning } from "@nextcloud/dialogs";
import SvgHelper from "@/util/SvgHelper";

registerFileAction(
	new FileAction({
		id: "EthswarmCopyRef",

		displayName() {
			return t("files_external_ethswarm", "View Swarm Reference");
		},

		altText() {
			return t("files_external_ethswarm", "Swarm Reference");
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

		inline() {
			return false;
		},

		iconSvgInline() {
			return SvgHelper.convert(SwarmSvg);
		},

		async exec(node, view) {
			const swarmref = node.attributes["ethswarm-fileref"];
			if (node.type === FileType.Folder) {
				showWarning(
					t(
						"files_external_ethswarm",
						"Folder structure is not yet supported on Swarm. This folder is only available on Nextcloud, although all files within it are accessible on Swarm."
					),
					t("files_external_ethswarm", "Swarm reference")
				);
				return;
			}

			showInfo(
				`
			<div style="margin: 1rem 0; width: 35rem;" data-swram-ref="${swarmref}">
				<img src="${SwarmSvg}" alt="Swarm" style="height: 20px; vertical-align: middle;">
				<span>${t(
					"files_external_ethswarm",
					"Click on this message to copy the Swarm Reference into your clipboard"
				)}</span>
				<pre style="overflow-x: scroll; max-width: 100%;">${swarmref}</pre>
			</div>`,
				{
					isHTML: true,
					onClick: () => {
						document
							.querySelector(`div[data-swram-ref="${swarmref}"]`)
							.parentElement.remove();
						navigator.clipboard.writeText(swarmref).then(
							() => {
								showSuccess(
									t(
										"files_external_ethswarm",
										"The Swarm reference has been copied to your clipboard"
									)
								);
							},
							() => {
								showWarning(
									`
								<div style="margin: 1rem 0; width: 35rem;">
									<span>${t(
										"files_external_ethswarm",
										"Unable to write the Swarm Reference into your clipboard. Copy it manually"
									)}</span>
									<pre style="overflow-x: scroll; max-width: 100%;">${swarmref}</pre>
								</div>
							`,
									{
										isHTML: true,
									}
								);
							}
						);
					},
				}
			);
		},

		execBatch(nodes, view) {
			return Promise.all(nodes.map((node) => this.exec(node, view)));
		},
	})
);
