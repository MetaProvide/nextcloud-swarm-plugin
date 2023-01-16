/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
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
	const fileActionsTargetNode = document.getElementById("filestable");
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
