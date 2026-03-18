/**
 * @copyright Copyright (c) 2024, MetaProvide Pty Ltd
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * Compatibility layer for running the same plugin code on both
 * Nextcloud 32 (@nextcloud/files v3) and Nextcloud 33+ (@nextcloud/files v4).
 *
 * The plugin is built with @nextcloud/files v4 which is bundled into the
 * JS output. v3 and v4 use **different window-global registries**:
 *
 *   v3 (NC 32):  window._nc_fileactions         (array)
 *                window._nc_dav_properties       (array)
 *                window._nc_dav_namespaces        (object)
 *                window._nc_newfilemenu           (NewMenu instance)
 *
 *   v4 (NC 33+): window._nc_files_scope.v4_0.*   (scoped object)
 *
 * The bundled v4 functions always write to the v4 scope. On NC 32 we
 * must also (or instead) write into the v3 globals so the NC 32 Files
 * app picks the registrations up.
 */

import {
	addNewFileMenuEntry,
	getNewFileMenuEntries,
	registerFileAction,
	removeNewFileMenuEntry,
} from "@nextcloud/files";
import { registerDavProperty } from "@nextcloud/files/dav";

type AnyRecord = Record<string, any>;

/* ================================================================== */
/*  Version detection                                                  */
/* ================================================================== */

/**
 * Get the Nextcloud major version number from the runtime environment.
 * @returns {number} The major version (e.g. 32 or 33)
 */
function getNextcloudMajorVersion() {
	if (window.OC?.config?.version) {
		return parseInt(window.OC.config.version.split(".")[0], 10) || 0;
	}
	// Fallback: if the v4 scoped globals already exist, NC 33+ is running.
	if (window._nc_files_scope?.v4_0) {
		return 33;
	}
	console.warn(
		"[FilesCompatibility] Could not detect Nextcloud version, defaulting to 32",
	);
	return 32;
}

/** True when running on Nextcloud 32 or lower (v3 API). */
export function isNextcloud32OrLower() {
	return getNextcloudMajorVersion() <= 32;
}

/** True when running on Nextcloud 33 or higher (v4 API). */
export function isNextcloud33OrHigher() {
	return !isNextcloud32OrLower();
}

/* ================================================================== */
/*  File Actions                                                       */
/* ================================================================== */

/**
 * Register an action into the NC 32 / v3 global registry.
 *
 * v3 callback signatures (what the NC 32 Files app calls):
 *   displayName(nodes: Node[], view: View)
 *   iconSvgInline(nodes: Node[], view: View)
 *   enabled(nodes: Node[], view: View)
 *   exec(node: Node, view: View, dir: string)
 *   execBatch(nodes: Node[], view: View, dir: string)
 *   inline(node: Node, view: View)
 *   renderInline(node: Node, view: View)
 *
 * v4 callback signatures (what our action code uses):
 *   displayName({ nodes, view })
 *   iconSvgInline({ nodes, view })
 *   enabled({ nodes, view })
 *   exec({ nodes: [node], view })
 *   execBatch({ nodes, view })
 *   inline({ nodes: [node], view })
 *   renderInline({ nodes: [node], view })
 */
function registerFileActionV3(actionConfig: AnyRecord) {
	if (typeof window._nc_fileactions === "undefined") {
		window._nc_fileactions = [];
	}

	if (window._nc_fileactions.find((a) => a.id === actionConfig.id)) {
		console.error(
			`[FilesCompatibility] FileAction ${actionConfig.id} already registered`,
		);
		return;
	}

	const wrapped: AnyRecord = {};

	if (typeof actionConfig.displayName === "function") {
		wrapped.displayName = (nodes, view) =>
			actionConfig.displayName({ nodes, view });
	}

	if (typeof actionConfig.iconSvgInline === "function") {
		wrapped.iconSvgInline = (nodes, view) =>
			actionConfig.iconSvgInline({
				nodes,
				view,
			});
	}

	if (typeof actionConfig.enabled === "function") {
		wrapped.enabled = (nodes, view) =>
			actionConfig.enabled({ nodes, view });
	}

	if (typeof actionConfig.exec === "function") {
		wrapped.exec = (node, view, dir) =>
			actionConfig.exec({
				nodes: [node],
				view,
			});
	}

	if (typeof actionConfig.execBatch === "function") {
		wrapped.execBatch = (nodes, view, dir) =>
			actionConfig.execBatch({ nodes, view });
	}

	if (typeof actionConfig.inline === "function") {
		wrapped.inline = (node, view) =>
			actionConfig.inline({
				nodes: [node],
				view,
			});
	}

	if (typeof actionConfig.renderInline === "function") {
		wrapped.renderInline = (node, view) =>
			actionConfig.renderInline({
				nodes: [node],
				view,
			});
	}

	const v3Action = {
		get id() {
			return actionConfig.id;
		},
		get displayName() {
			return wrapped.displayName || actionConfig.displayName;
		},
		get title() {
			return actionConfig.title;
		},
		get iconSvgInline() {
			return wrapped.iconSvgInline || actionConfig.iconSvgInline;
		},
		get enabled() {
			return wrapped.enabled || actionConfig.enabled;
		},
		get exec() {
			return wrapped.exec || actionConfig.exec;
		},
		get execBatch() {
			return wrapped.execBatch || actionConfig.execBatch;
		},
		get order() {
			return actionConfig.order;
		},
		get parent() {
			return actionConfig.parent;
		},
		get default() {
			return actionConfig.default;
		},
		get destructive() {
			return actionConfig.destructive;
		},
		get inline() {
			return wrapped.inline || actionConfig.inline;
		},
		get renderInline() {
			return wrapped.renderInline || actionConfig.renderInline;
		},
		get hotkey() {
			return actionConfig.hotkey;
		},
	};

	window._nc_fileactions.push(v3Action);
}

/**
 * Register a file action compatible with both NC 32 and NC 33+.
 *
 * Action code should use the v4 context-object style for callbacks,
 * e.g. `exec({ nodes, view }) { ... }`.
 */
export function registerFileActionCompat(actionConfig: AnyRecord) {
	if (isNextcloud32OrLower()) {
		registerFileActionV3(actionConfig);
	} else {
		registerFileAction(actionConfig as any);
	}
}

/* ================================================================== */
/*  DAV Properties                                                     */
/* ================================================================== */

const registeredDavProperties = new Set();

/**
 * Register a DAV property compatible with both NC 32 and NC 33+.
 *
 * v3 stores properties in `window._nc_dav_properties` and namespaces
 * in `window._nc_dav_namespaces`. The bundled v4 `registerDavProperty`
 * writes to the v4 scoped globals, so on NC 32 we additionally push
 * into the v3 globals.
 */
export function registerDavPropertyCompat(property: string) {
	if (registeredDavProperties.has(property)) {
		return;
	}

	// Always call the bundled v4 function (works on NC 33+)
	registerDavProperty(property);

	// On NC 32 also write into the v3 globals
	if (isNextcloud32OrLower()) {
		const defaultDavNamespaces = {
			d: "DAV:",
			nc: "http://nextcloud.org/ns",
			oc: "http://owncloud.org/ns",
			ocs: "http://open-collaboration-services.org/ns",
		};

		if (typeof window._nc_dav_properties === "undefined") {
			window._nc_dav_properties = [];
			window._nc_dav_namespaces = { ...defaultDavNamespaces };
		}

		if (!window._nc_dav_properties.find((p) => p === property)) {
			window._nc_dav_properties.push(property);
			window._nc_dav_namespaces = {
				...window._nc_dav_namespaces,
				nc: "http://nextcloud.org/ns",
			};
		}
	}

	registeredDavProperties.add(property);
}

/* ================================================================== */
/*  New-file menu                                                      */
/* ================================================================== */

/**
 * Get new-file menu entries, compatible with both NC 32 and NC 33+.
 */
export function getNewFileMenuEntriesCompat(context?: any) {
	if (isNextcloud32OrLower() && window._nc_newfilemenu) {
		return window._nc_newfilemenu.getEntries(context);
	}
	return getNewFileMenuEntries(context);
}

/**
 * Add a new-file menu entry, compatible with both NC 32 and NC 33+.
 */
export function addNewFileMenuEntryCompat(entry: any) {
	if (isNextcloud32OrLower() && window._nc_newfilemenu) {
		return window._nc_newfilemenu.registerEntry(entry);
	}
	return addNewFileMenuEntry(entry);
}

/**
 * Remove a new-file menu entry, compatible with both NC 32 and NC 33+.
 */
export function removeNewFileMenuEntryCompat(entry: any) {
	if (isNextcloud32OrLower() && window._nc_newfilemenu) {
		return window._nc_newfilemenu.unregisterEntry(entry);
	}
	return removeNewFileMenuEntry(entry);
}
