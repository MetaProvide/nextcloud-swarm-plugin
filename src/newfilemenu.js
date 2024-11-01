/*
 * @copyright Copyright (c) 2022
 *
 * @author
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
import {
	getNewFileMenuEntries, removeNewFileMenuEntry, getNavigation,
	davRootPath,
    davGetClient,
    davGetDefaultPropfind,
    davResultToNode,
    davRemoteURL,
	registerDavProperty
} from '@nextcloud/files';

registerDavProperty("nc:mount-type");
registerDavProperty("nc:ethswarm-node");

// //////////////////////////////////////////////////
// Test 1: Try to obtain storage info from Webdav.
// Based on example on https://github.com/nextcloud-libraries/nextcloud-files?tab=readme-ov-file#using-webdav-to-list-all-nodes-in-directory
const path = ''; // swarm-license/'; // the directory you want to list
const fullpath = davRootPath + path;
// Query the directory content using the webdav library
// `davRootPath` is the files root, for Nextcloud this is '/files/USERID', by default the current user is used
console.log('davRootPath=' + davRootPath + ';davRemoteURL=' + davRemoteURL);
console.log("fullpath=" + fullpath);

const client = davGetClient();
console.log("client=" + JSON.stringify(client));
const results = client.getDirectoryContents(fullpath, {
    details: true,
    // Query all required properties for a Node
    data: davGetDefaultPropfind(),
});

// Convert the result to an array of Node
console.log("results=" + JSON.stringify(results));
if (results) {
	const nodes = results?.data?.map((result) => davResultToNode(result));
	console.log(JSON.stringify(nodes));
}
// //////////////////////////////////////////////////

// //////////////////////////////////////////////////
// Test 2: Inspect the getNavigation object.
const navigation = getNavigation();
const views = navigation.views;
let c1 = 1;
views.forEach(function (eachView) {
	console.log("view iteration #" + c1);
    console.log(eachView);
	c1++;
});
// //////////////////////////////////////////////////

// //////////////////////////////////////////////////
// Main code: Manipulate the New file menu entries.
const entries = getNewFileMenuEntries();	// Get current file menu entries
const removals = [];	// store the File Menu entries to be removed
console.log(entries);

let c = 1;
entries.forEach(function (fileMenuEntry) {
	console.log("iteration #" + c);
    const name = fileMenuEntry.displayName;
    console.log(fileMenuEntry);
	if (fileMenuEntry.id !== 'newFolder') {
		console.log('"' + name + '" with id "' + fileMenuEntry.id + '" to be removed');
		removals.push(fileMenuEntry);

	}
	c=c+1;
});
// Remove unwanted entries
removals.forEach(function (removeMenuEntry) {
	console.log('"' + removeMenuEntry.displayName + '" with id "' + removeMenuEntry.id + '" removing');
	removeNewFileMenuEntry(removeMenuEntry);
});
// //////////////////////////////////////////////////


// Listeners to detect changes in listing.
subscribe('files:list:updated', (data) => {
	console.log('Hejbit-files-new-menu:list:updated');
	console.log('data=' + JSON.stringify(data));
	// if (data.contents.length >= 1){
	// 	if (previousPathHasSwarm && !data.contents[1]._data.attributes["ethswarm-node"]){
	// 		previousPathHasSwarm = false;
	// 	}
	// }
});

// Test this listener
subscribe('files:navigation:changed', () => {
	console.log('Hejbit-files:navigation:changed' + this);
	// console.log('dataview=' + JSON.stringify(view));
});
