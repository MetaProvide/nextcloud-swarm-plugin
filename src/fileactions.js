/*
 * @copyright Copyright (c) 2022 Henry Bergström <metahenry@metaprovide.org>
 *
 * @author Henry Bergström <metahenry@metaprovide.org>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import { FileAction, registerDavProperty, registerFileAction } from "@nextcloud/files";
import ContentPaste from "@material-design-icons/svg/filled/content_paste.svg";

registerDavProperty("nc:ethswarm-fileref");

const actionData = {
	id: 'EthswarmCopyRef',

	displayName() {
		return t('files_external_ethswarm', "Copy Swarm reference");
	},

	altText() {
		return t('files_external_ethswarm', "Copy Swarm reference to clipboard");
	},

	enabled(files, view) {
		if (files.length !== 1) // We don't support batch actions
			return false;
		const attrs = files[0].attributes["ethswarm-fileref"];

		if (attrs === undefined)
			return false;
		else if (attrs === "")
			return false;

		return true;
	},

	iconSvgInline(files, view) {
		return Buffer.from(ContentPaste.split(",")[1], 'base64');
	},

	async exec(node, view) {
		const swarmref = node.attributes["ethswarm-fileref"];
		navigator.clipboard.writeText(swarmref)
				 .then(() => {
					/* clipboard successfully set */
					OC.dialogs.info(t('files_external_ethswarm', 'The following Swarm reference has been copied to the clipboard: ') + swarmref, t('files_external_ethswarm', 'Swarm reference'));
				 }, () => {
					/* clipboard write failed */
					OC.dialogs.info(t('files_external_ethswarm', 'Unable to write to the clipboard, you can manually copy the Swarm reference below: ') + swarmref, t('files_external_ethswarm', 'Swarm reference'));
				 });
	},

	execBatch() {
		// Not currently supported.
	}
};

const EthswarmCopyRef = new FileAction(actionData);

registerFileAction(EthswarmCopyRef);
