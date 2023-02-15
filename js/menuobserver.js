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
	var actions = {
		isSwarm: function () {
			OCA.Files_External.StatusManager.getMountPointList(function (mounts) {
				mountdir = new URLSearchParams(location.search).get("dir").split('/')[1];
				isSwarm = mounts?.find(el => el.mount_point == mountdir && el.backend == "files_external_ethswarm");
			});
			return isSwarm;
		},
	}

	// fileActionsMenu mutation observer
	const fileActionsTargetNode = document.querySelector("#app-content-files > table.list-container");

	const fileActionsConfig = { attributes: false, childList: true, subtree: true };
	const fileActionsCallback = (mutationList, observer) => {
		for (const mutation of mutationList) {
			if (
				mutation.addedNodes.length > 0 &&
				mutation.target.classList.contains("fileActionsMenu")
			) {
				isSwarmDir = actions.isSwarm();
				if (!isSwarmDir && document.body.getElementsByClassName("action-ethswarmcopyref-container").length > 0) {
					document.body
						.getElementsByClassName("action-ethswarmcopyref-container")[0]
						.classList.add("hidden");
				}
			}
		}
	};
	const fileActionsObserver = new MutationObserver(fileActionsCallback);
	fileActionsObserver.observe(fileActionsTargetNode, fileActionsConfig);

	// Right click menu mutation observer
	const rightClickTargetNode = document.getElementById("rightClickMenus");
	const rightClickConfig = { attributes: false, childList: true, subtree: true };
	const rightClickCallback = (mutationList, observer) => {
		for (const mutation of mutationList) {
			if (
				mutation.addedNodes.length > 0 &&
				mutation.addedNodes[0].classList.contains("rightClickMenu")
			) {
				isSwarmDir = actions.isSwarm();
				if (!isSwarmDir && document.body.getElementsByClassName("option-ethswarmcopyref").length > 0) {
					document.body.getElementsByClassName("option-ethswarmcopyref")[0].parentElement.classList.add("hidden");
				}
			}
		}
	};
	const rightClickObserver = new MutationObserver(rightClickCallback);
	rightClickObserver.observe(rightClickTargetNode, rightClickConfig);
});
