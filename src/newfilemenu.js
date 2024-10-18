
/*
 * @copyright Copyright (c) 2022 Henry Bergström <metahenry@metaprovide.org>
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

import { getNewFileMenuEntries, removeNewFileMenuEntry,/* , addNewFileMenuEntry */ } from "@nextcloud/files";

const entries = getNewFileMenuEntries();
const removals = [];
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

removals.forEach(function (removeMenuEntry) {
	console.log('"' + removeMenuEntry.displayName + '" with id "' + removeMenuEntry.id + '" removing');
	removeNewFileMenuEntry(removeMenuEntry);
});
