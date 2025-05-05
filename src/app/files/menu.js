import {
	addNewFileMenuEntry,
	getNewFileMenuEntries,
	removeNewFileMenuEntry,
} from "@nextcloud/files";
import { subscribe } from "@nextcloud/event-bus";

const filesMenu = {
	originalMenu: [],
	resolver(data) {
		if (data.folder === undefined) return;

		const isHejBit = data.folder.attributes["ethswarm-node"] !== undefined;

		if (isHejBit && !this.originalMenu.length) {
			this.backup();
			this.cleanup(this);
		} else if (!isHejBit) {
			this.restore(this);
		}
	},
	backup() {
		if (!this.originalMenu.length) {
			this.originalMenu = getNewFileMenuEntries().map((entry) => ({
				...entry,
			}));
		}
	},
	cleanup() {
		this.originalMenu.forEach(function (removeMenuEntry) {
			if (removeMenuEntry.id !== "newFolder") {
				removeNewFileMenuEntry(removeMenuEntry);
			}
		});
	},
	restore() {
		const currentEntries = getNewFileMenuEntries();
		this.originalMenu.forEach(function (backedUpMenuEntry) {
			!currentEntries.some(
				(entry) => entry.id === backedUpMenuEntry.id
			) && addNewFileMenuEntry(backedUpMenuEntry);
		});
		this.originalMenu = [];
	},
};

subscribe("files:list:updated", (data) => filesMenu.resolver(data));
