import { subscribe } from "@nextcloud/event-bus";
import { NewMenuEntryCategory } from "@nextcloud/files";
import ImportSvg from "@material-design-icons/svg/filled/input.svg?raw";
import {
	addNewFileMenuEntryCompat,
	getNewFileMenuEntriesCompat,
	removeNewFileMenuEntryCompat,
} from "@/util/FilesCompatibility";
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

		const currentEntries = getNewFileMenuEntriesCompat();
		if (
			!currentEntries.some(
				(entry) => entry.id === hejBitImportMenuEntry.id,
			)
		) {
			addNewFileMenuEntryCompat(hejBitImportMenuEntry);
		}
	},
	restore() {
		const currentEntries = getNewFileMenuEntriesCompat();
		if (
			currentEntries.some(
				(entry) => entry.id === hejBitImportMenuEntry.id,
			)
		) {
			removeNewFileMenuEntryCompat(hejBitImportMenuEntry);
		}

		this.originalMenu.forEach((backedUpMenuEntry) => {
			!currentEntries.some(
				(entry) => entry.id === backedUpMenuEntry.id,
			) && addNewFileMenuEntryCompat(backedUpMenuEntry);
		});
		this.originalMenu = [];
	},
};

subscribe("files:list:updated", (data) => filesMenu.resolver(data));
