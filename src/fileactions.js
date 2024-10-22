
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

import { emit,subscribe } from '@nextcloud/event-bus';
import { FileAction, registerDavProperty, registerFileAction, FileType } from "@nextcloud/files";
import HideSource from "@material-design-icons/svg/filled/hide_source.svg";
import SwarmSvg from "../img/swarm-logo.svg";
import HejBitSvg from "../img/hejbit-logo.svg";
import axios from '@nextcloud/axios';
import Close from "@material-design-icons/svg/filled/close.svg";
import CloudOff from "@material-design-icons/svg/filled/cloud_off.svg";

// https://marella.me/material-design-icons/demo/svg/



const canUnshareOnly = (nodes) => {
    return nodes.every(node => node.attributes['is-mount-root'] === true
        && node.attributes['mount-type'] === 'shared');
};
const canDisconnectOnly = (nodes) => {
    return nodes.every(node => node.attributes['is-mount-root'] === true
        && node.attributes['mount-type'] === 'external');
};
const isMixedUnshareAndDelete = (nodes) => {
    if (nodes.length === 1) {
        return false;
    }
    const hasSharedItems = nodes.some(node => canUnshareOnly([node]));
    const hasDeleteItems = nodes.some(node => !canUnshareOnly([node]));
    return hasSharedItems && hasDeleteItems;
};
const isAllFiles = (nodes) => {
    return !nodes.some(node => node.type !== FileType.File);
};
const isAllFolders = (nodes) => {
    return !nodes.some(node => node.type !== FileType.Folder);
};



registerDavProperty("nc:ethswarm-fileref");
registerDavProperty("nc:ethswarm-node");

const actionDataEthswarmOverlay = {
	id: 'EthswarmOverlay',


	displayName() {
		return '';
	},


	enabled(files, view) {
		if (files.length !== 1) // We don't support batch actions
			return false;

	// To fix fileaction navigation bug this action is now available for
	// files and folders on Swarm storage
		const attrs = files[0].attributes["ethswarm-node"];

		if (attrs === undefined)
			return false;
		else if (attrs === "")
			return false;

		return true;
	},

	iconSvgInline(files, view) {
		return Buffer.from(HejBitSvg.split(",")[1], 'base64');
	},


	inline(file, view) {
		return true;
	  },


	async renderInline(file, view) {
		// Create the overlay element
		const overlay = document.createElement('div');
		overlay.classList.add('hejbit-overlay');


		const img = document.createElement('img');
		img.src = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAJMSURBVDhPzZLPS5RBHMafd2bed/d9X3dd1yytQ0GHqIjUQz+MjS6FXUqki5EaGAQlFUFCnYKKTh2qSxiC1cESCyGki57sYCbRoVCKEixT0E3bd9/dd98fM82+LbT1F/iBYZiZ93m+833mxdrm66jROvPKTJWWaGhqj0ROL1xRu1ZGKk7O7CvuKeHJf4z1Jxobdti3KmN+s+Dg6VXx4NrDnnt9/NIjxdiwVxAC6uWgZ2evkpImZLS/qi79Rru/v8Ga0FTe7HkKPF8hpo5zx1LxVpDq3cTPgbkZCEVBwdh4oMxgO9u2JT9s6n63Hg1UlQlkbIacQxGNABFNcUE0wlUTQl6cM13OFGUGNq1OeElpjMUlDU6BoCbpIlnp4ZcFmHQ4jhWrT3iByxTZgu981wrp/n8yyL3HB91QdloWBaUCnP/ZJ0RW5L6Q04vbQ0PjNz/Vm0nnc+/Pl0eXQwP9xOTWvLb51EDq6XJL08Xjnk8Ox2Ictk1RbEXmgUyWhaZWNnhcl0Jn6CwhFW0fL7jJXVM0sf5629vuG/rlL+OLy/yJky9WFuFHlszClYGaegBDl82XQQp67XmIICH8PBjNV6GmtktT96wwCmSl0M5TGSBHhRFASD+nQH+UtCFEpskDZoIEDnxmAKouAi5vrgEx00dcDsY4sjk2v7AUPXvozLqekjaEUM9+rQjpTiJQiiVI5t3c/OTdTAaDfiDf2iWF9Kp2Z2AkUb/poNM7PT3vlrR/MdpnW6Id38b0jrnOZ2V/59Tz+JGJwcrG0nJNAvwG797lI53h7DYAAAAASUVORK5CYII=';
		overlay.appendChild(img);

		// Style the overlay icon
		overlay.style.position = 'absolute';
		overlay.style.top = '30px';
		overlay.style.left = '75px';
		overlay.style.width = '16px';
		overlay.style.height = '16px';
		overlay.style.pointerEvents = 'none';

		// Return the overlay element to be appended
		return overlay;
	  },

	  async exec(node, view) {
		if (node.type === FileType.Folder) {
			OC.dialogs.info(t('files_external_ethswarm', 'Folder structure is not yet supported on Swarm. This folder is only available on Nextcloud, although all files within it are accessible on Swarm.'), t('files_external_ethswarm', 'Hejbit'));
		}else if (node.type === FileType.File) {
			OC.dialogs.info(t('files_external_ethswarm', 'This file is on Swarm Network by Hejbit!'), t('files_external_ethswarm', 'Hejbit'));
		}

	},

	execBatch(nodes, view) {
		return Promise.all(nodes.map(node => this.exec(node, view)));
	}
};



const EthswarmOverlay = new FileAction(actionDataEthswarmOverlay);

registerFileAction(EthswarmOverlay);


const actionDataEthswarmCopyRef = {
	id: 'EthswarmCopyRef',

	displayName() {
		return t('files_external_ethswarm', "Copy Swarm reference to clipboard");
	},

	altText() {
		return t('files_external_ethswarm', "Copy Swarm reference to clipboard");
	},

	enabled(files, view) {
		if (files.length !== 1) // We don't support batch actions
			return false;

	// To fix fileaction navigation bug this action is now available for
	// files and folders on Swarm storage
		const attrs = files[0].attributes["ethswarm-node"];

		if (attrs === undefined)
			return false;
		else if (attrs === "")
			return false;

		return true;
	},

	inline(file, view) {
		// Determine whether to render the inline element
		// For example, only for PDF files
		return false;
	  },

	iconSvgInline(files, view) {
		return Buffer.from(SwarmSvg.split(",")[1], 'base64');
	},


	async exec(node, view) {
		const swarmref = node.attributes["ethswarm-fileref"];
		if (node.type === FileType.Folder) {
			OC.dialogs.info(t('files_external_ethswarm', 'Folder structure is not yet supported on Swarm. This folder is only available on Nextcloud, although all files within it are accessible on Swarm.'), t('files_external_ethswarm', 'Swarm reference'));
			return;
		}
		navigator.clipboard.writeText(swarmref)
				 .then(() => {
					// clipboard successfully set
					OC.dialogs.info(t('files_external_ethswarm', 'The following Swarm reference has been copied to the clipboard: ') + swarmref, t('files_external_ethswarm', 'Swarm reference'));
				 }, () => {
					// clipboard write failed
					OC.dialogs.info(t('files_external_ethswarm', 'Unable to write to the clipboard, you can manually copy the Swarm reference below: ') + swarmref, t('files_external_ethswarm', 'Swarm reference'));
				 });
	},

	execBatch(nodes,view) {
		return Promise.all(nodes.map(node => this.exec(node, view)));
	}
};

const EthswarmCopyRef = new FileAction(actionDataEthswarmCopyRef);

registerFileAction(EthswarmCopyRef);



// TODO: Support Batch Option - Challenge: Import p-queue
// TODO: Batch option import PQueue from 'p-queue';
// TODO: Batch option const queue = new PQueue({ concurrency: 5 });
const actionDataUnviewFile ={
    id: 'unviewFile',
    displayName(nodes, view) {
        /**
         * If we're in the sharing view, we can only unshare
         */
        if (isMixedUnshareAndDelete(nodes)) {
            return t('files_external_ethswarm', 'Unview and unshare');
        }
        /**
         * If those nodes are all the root node of a
         * share, we can only unshare them.
         */
        if (canUnshareOnly(nodes)) {
            if (nodes.length === 1) {
                return t('files_external_ethswarm', 'Leave this share');
            }
            return t('files_external_ethswarm', 'Leave these shares');
        }
        /**
         * If those nodes are all the root node of an
         * external storage, we can only disconnect it.
         */
        if (canDisconnectOnly(nodes)) {
            if (nodes.length === 1) {
                return t('files_external_ethswarm', 'Disconnect storage');
            }
            return t('files_external_ethswarm', 'Disconnect storages');
        }
        /**
         * If we're only selecting files, use proper wording
         */
        if (isAllFiles(nodes)) {
            if (nodes.length === 1) {
                return t('files_external_ethswarm', 'Unview file');
            }
            return t('files_external_ethswarm', 'Unview files');
        }
        /**
         * If we're only selecting folders, use proper wording
         */
        if (isAllFolders(nodes)) {
            if (nodes.length === 1) {
                return t('files_external_ethswarm', 'Unview folder');
            }
            return t('files_external_ethswarm', 'Unview folders');
        }
        return t('files_external_ethswarm', 'Unview');
    },
    iconSvgInline: (nodes) => {
        if (canUnshareOnly(nodes)) {
             return Buffer.from(Close.split(",")[1], 'base64');;
        }
        if (canDisconnectOnly(nodes)) {
            return Buffer.from(CloudOff.split(",")[1], 'base64');
        }
        return Buffer.from(HideSource.split(",")[1], 'base64');
    },
	enabled(files, view) {
		if (files.length !== 1) // We don't support batch actions
			return false;
		const attrs = files[0].attributes["ethswarm-node"];

		if (attrs === undefined)
			return false;
		else if (attrs === "")
			return false;

		return attrs;
	},
    async exec(node, view, dir) {
		let message = '';
		if (node.type === FileType.File) {
			message = t('files_external_ethswarm', 'The file will be set to unview on the folder view. The file will continue to exist on the Swarm network.');
		}else if (node.type === FileType.Folder) {
			message = t('files_external_ethswarm', 'The folder will be set to unview on the folder view. All the files inside the folder will continue to exist on the Swarm network.');
		}
		alert(message);
        try {
		   await axios({
			method: 'post',
			url: node.encodedSource,
			headers: {
			 'Hejbit-Action': 'unview'
			}
		  });

            // Let's delete even if it's moved to the trashbin
            // since it has been removed from the current view
            // and changing the view will trigger a reload anyway.
            emit('files:node:deleted', node);
            return true;
        }
        catch (error) {
			console.log('Error while deleting a file', { error, source: node.source, node });
            // TODO: update to this? logger.error('Error while deleting a file', { error, source: node.source, node });
            return false;
        }
    },   /* TODO: Batch option
	async execBatch(nodes, view, dir) {
        // Map each node to a promise that resolves with the result of exec(node)
        const promises = nodes.map(node => {
            // Create a promise that resolves with the result of exec(node)
            const promise = new Promise(resolve => {
                queue.add(async () => {
                    const result = await this.exec(node, view, dir);
                    resolve(result !== null ? result : false);
                });
            });
            return promise;
        });
        return Promise.all(promises);
    }, */
	execBatch(nodes, view) {
		return Promise.all(nodes.map(node => this.exec(node, view)));
	},
    order: 150,
};

const AddUnviewAction = new FileAction(actionDataUnviewFile);

registerFileAction(AddUnviewAction);

let previousPathHasSwarm = false;

subscribe('files:list:updated', (data) => {
	console.log('Hejbit-files:list:updated');
	let ethswarmNode = data?.contents?.[1]?._data?.attributes?.["ethswarm-node"];
	if (ethswarmNode === undefined) {
		ethswarmNode = false;
	}

	if (previousPathHasSwarm && !ethswarmNode){
		previousPathHasSwarm = false;
		window.location.reload();
	}
	if (ethswarmNode){
		previousPathHasSwarm = true;
	}


});

