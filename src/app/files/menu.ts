import ImportSvg from "@material-design-icons/svg/filled/input.svg?raw";
import { subscribe } from "@nextcloud/event-bus";
import {
	addNewFileMenuEntry,
	getNewFileMenuEntries,
	NewMenuEntryCategory,
	removeNewFileMenuEntry,
} from "@nextcloud/files";
import SvgHelper from "@/util/SvgHelper";
import { openImportModal } from "./import";

const hejBitImportMenuEntry = {
	id: "hejbitImport",
	category: NewMenuEntryCategory.Other,
	displayName: t("files_external_ethswarm", "Import"),
	iconSvgInline: SvgHelper.convert(ImportSvg),
	order: 20,
	enabled(context) {
		return context?.attributes?.["ethswarm-node"] !== undefined;
	},
	handler(context) {
		openImportModal(context);
	},
};

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
		this.originalMenu.forEach((removeMenuEntry) => {
			if (removeMenuEntry.id !== "newFolder") {
				removeNewFileMenuEntry(removeMenuEntry);
			}
		});

		const currentEntries = getNewFileMenuEntries();
		if (
			!currentEntries.some(
				(entry) => entry.id === hejBitImportMenuEntry.id,
			)
		) {
			addNewFileMenuEntry(hejBitImportMenuEntry);
		}
	},
	restore() {
		const currentEntries = getNewFileMenuEntries();
		if (
			currentEntries.some(
				(entry) => entry.id === hejBitImportMenuEntry.id,
			)
		) {
			removeNewFileMenuEntry(hejBitImportMenuEntry);
		}

		this.originalMenu.forEach((backedUpMenuEntry) => {
			!currentEntries.some(
				(entry) => entry.id === backedUpMenuEntry.id,
			) && addNewFileMenuEntry(backedUpMenuEntry);
		});
		this.originalMenu = [];
	},
};

subscribe("files:list:updated", (data) => filesMenu.resolver(data));
