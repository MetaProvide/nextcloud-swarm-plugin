/*
 * @copyright Copyright (c) 2023, MetaProvide Holding EKF
 *
 * @author Ron Trevor <ecoron@proton.me>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
window.addEventListener('DOMContentLoaded', () => {
	OCA.Files.fileActions.registerAction({
		name: 'EthswarmCopyRef',
		displayName: t('files_external_ethswarm', 'Copy Swarm reference'),
		altText: t('files_external_ethswarm', 'Copy Swarm reference to clipboard'),
		mime: 'all',
		permissions: OC.PERMISSION_READ,
		type: OCA.Files.FileActions.TYPE_DROPDOWN,
		iconClass: 'icon-clippy',
		actionHandler: function (filename, context) {
			if (context.$file) {
				 if (context.fileInfoModel.attributes.mountType != "external" || !context.$file.attr('data-type') === 'file'){
				return; }
			}

			remoteurl = OC.linkToRemoteBase("dav/files/" + OC.currentUser + context.fileInfoModel.attributes['path'] + "/" + filename);

			$.ajax({
				type: "PROPFIND",
				async: "false",
				url: remoteurl,
				data: '<?xml version="1.0" encoding="UTF-8"?>' +
					'<d:propfind xmlns:d="DAV:" xmlns:nc="http://nextcloud.org/ns">' +
					'<d:prop><nc:ethswarm-fileref/></d:prop>' +
					'</d:propfind>',
				success: function (element) {
					swarmref = element.firstChild.firstChild.lastChild.firstChild.firstChild.textContent;
					navigator.clipboard.writeText(swarmref).then(
						() => {
							/* clipboard successfully set */
							OC.dialogs.info(t('files_external_ethswarm', 'The following Swarm reference has been copied to the clipboard: ') + swarmref, t('files_external_ethswarm', 'Swarm reference'));
						},
						() => {
							/* clipboard write failed */
							OC.dialogs.info(t('files_external_ethswarm', 'Unable to write to the clipboard, you can manually copy the Swarm reference below: ' + swarmref), t('files_external_ethswarm', 'Swarm reference'));
						}
					);
				}
			});
		}
	});
});
