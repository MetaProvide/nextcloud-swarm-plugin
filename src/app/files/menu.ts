import { subscribe } from "@nextcloud/event-bus";
import {
	addNewFileMenuEntryCompat,
	getNewFileMenuEntriesCompat,
	removeNewFileMenuEntryCompat,
} from "@/util/FilesCompatibility";

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
			this.originalMenu = getNewFileMenuEntriesCompat().map((entry) => ({
				...entry,
			}));
		}
	},
	cleanup() {
		this.originalMenu.forEach((removeMenuEntry) => {
			if (removeMenuEntry.id !== "newFolder") {
				removeNewFileMenuEntryCompat(removeMenuEntry);
			}
		});
	},
	restore() {
		const currentEntries = getNewFileMenuEntriesCompat();
		this.originalMenu.forEach((backedUpMenuEntry) => {
			!currentEntries.some(
				(entry) => entry.id === backedUpMenuEntry.id,
			) && addNewFileMenuEntryCompat(backedUpMenuEntry);
		});
		this.originalMenu = [];
	},
};

subscribe("files:list:updated", (data) => filesMenu.resolver(data));
