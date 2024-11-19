/*
 * @copyright Copyright (c) 2024, MetaProvide Holding EKF
 *
 * @author Ron Trevor <ecoron@proton.me> @author
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
import { subscribe } from '@nextcloud/event-bus';
import { getNewFileMenuEntries, removeNewFileMenuEntry, registerDavProperty } from '@nextcloud/files';

registerDavProperty("nc:ethswarm-node");

// //////////////////////////////////////////////////
// Common functions for manipulating New file menu entries.
let previousPathIsSwarm = false;
let originalMenu = [];
const removalMenuEntries = [];
console.log('Hejbit-files-new-menu:previousPathIsSwarm=' + previousPathIsSwarm + ";originalMenu=" + originalMenu.length);

/**  Store the Swarm-specific menu entries */
function storeNewFileMenu() {
	if (!originalMenu || !originalMenu.length) {
		originalMenu = getNewFileMenuEntries();
		console.log("Load getNewFileMenuEntries()=" + originalMenu.length);
	}
	// Store the Swarm-specfic file menu entries
	if (!removalMenuEntries || !removalMenuEntries.length) {
		originalMenu.forEach(function remove(fileMenuEntry) {
			if (fileMenuEntry.id !== 'newFolder') {
				removalMenuEntries.push(fileMenuEntry);
			}
		});
	}
};
// //////////////////////////////////////////////////

// Listeners to detect changes in listing.
subscribe('files:list:updated', (data) => {
	if (typeof(data.folder) === 'undefined') {
		// Not a valid response so ignore.
		return;
	}

	let currentPathIsSwarm = false;
	if (data.folder?.attributes["ethswarm-node"]){
		currentPathIsSwarm = true;
	}

	console.log('Hejbit-files-new-menu:list:updated=previousPathIsSwarm=' + previousPathIsSwarm + ";currentPathIsSwarm=" + currentPathIsSwarm + ";originalMenu=" + originalMenu.length);
	// First condition checks for 1st navigation in Swarm storage
	// 2nd condition is for direct navigation by URL
	if ((currentPathIsSwarm && !previousPathIsSwarm) || (currentPathIsSwarm && previousPathIsSwarm)) {
		// Remove unwanted entries
		storeNewFileMenu();
		console.log('Removing ' + removalMenuEntries.length + ' menu entries from ' + originalMenu.length + ' menus.');
		removalMenuEntries.forEach(function (removeMenuEntry) {
			removeNewFileMenuEntry(removeMenuEntry);
		});
	} else if (!currentPathIsSwarm && !previousPathIsSwarm) {
		console.log("Default entry - store settings");
		// Store a copy of the current file menu entries
		storeNewFileMenu();
	}
	else {
		originalMenu = getNewFileMenuEntries();
		console.log("Reload originalMenu=" + originalMenu.length);
	}
	previousPathIsSwarm = currentPathIsSwarm;
});
