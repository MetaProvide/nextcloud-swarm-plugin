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

import { registerFileAction } from "@nextcloud/files";

/**
 * Get the Nextcloud version from the global OC config
 * @returns {string} The Nextcloud version string
 */
function getNextcloudVersion() {
	if (window.OC && window.OC.config && window.OC.config.version) {
		return window.OC.config.version;
	}
	// Fallback: try to get from document meta tags
	const versionMeta = document.querySelector('meta[name="nextcloud-version"]');
	if (versionMeta) {
		return versionMeta.getAttribute("content");
	}
	// Default to NC32 as a safe conservative assumption if we can't detect
	// This ensures compatibility mode is used when version is unknown
	console.warn("Nextcloud version could not be detected, defaulting to 32.0.0");
	return "32.0.0";
}

/**
 * Parse version string to comparable parts
 * @param {string} version - Version string like "32.0.0" or "33.0.0"
 * @returns {number[]} Array of version parts
 */
function parseVersion(version) {
	return version.split(".").map((v) => parseInt(v, 10) || 0);
}

/**
 * Check if current Nextcloud version is 32 or lower
 * @returns {boolean} True if version is 32 or lower
 */
export function isNextcloud32OrLower() {
	const version = getNextcloudVersion();
	const parts = parseVersion(version);
	return parts[0] <= 32;
}

/**
 * Check if current Nextcloud version is 33 or higher
 * @returns {boolean} True if version is 33 or higher
 */
export function isNextcloud33OrHigher() {
	return !isNextcloud32OrLower();
}

/**
 * Register a file action with compatibility for both Nextcloud 32 and 33
 *
 * This wrapper handles the API differences between versions:
 * - Nextcloud 32: Uses @nextcloud/files v3.x with different callback signatures
 * - Nextcloud 33: Uses @nextcloud/files v4.x with updated callback signatures
 *
 * Key differences:
 * - In NC32, callbacks receive context with nodes as Node[] and view properties
 * - In NC33, callbacks receive destructured parameters ({nodes, view})
 * - The action config structure is largely the same, but we ensure compatibility
 *
 * @param {Object} actionConfig - The action configuration object containing:
 *   - id: Unique identifier for the action
 *   - displayName: Function or string for the action name
 *   - iconSvgInline: Function that returns SVG string
 *   - enabled: Function to determine if action is available
 *   - exec: Function to execute when action is triggered
 *   - execBatch: Optional function for batch operations
 *   - order: Optional order number for action placement
 *   - inline: Optional boolean or function for inline display
 *   - renderInline: Optional function for custom inline rendering
 *   - altText: Optional alt text for accessibility
 */
export function registerFileActionCompat(actionConfig) {
	const isNc32 = isNextcloud32OrLower();

	// Create a normalized action config that works for both versions
	const normalizedConfig = {
		id: actionConfig.id,
		displayName: actionConfig.displayName,
		iconSvgInline: actionConfig.iconSvgInline,
		enabled: actionConfig.enabled,
		exec: actionConfig.exec,
		order: actionConfig.order,
	};

	// Add optional properties if they exist
	if (actionConfig.execBatch) {
		normalizedConfig.execBatch = actionConfig.execBatch;
	}

	if (actionConfig.inline !== undefined) {
		normalizedConfig.inline = actionConfig.inline;
	}

	if (actionConfig.renderInline) {
		normalizedConfig.renderInline = actionConfig.renderInline;
	}

	if (actionConfig.altText) {
		normalizedConfig.altText = actionConfig.altText;
	}

	// For NC32 (@nextcloud/files v3), callbacks receive separate parameters (nodes, view)
	// For NC33 (@nextcloud/files v4), callbacks receive a single object { nodes, view }
	// We need to wrap callbacks to normalize the parameter format
	if (isNc32) {
		// Wrap callbacks to ensure they receive the correct context object format
		if (typeof normalizedConfig.displayName === "function") {
			const originalDisplayName = normalizedConfig.displayName;
			normalizedConfig.displayName = function (nodes, view) {
				// Convert separate parameters to object format for consistency
				const context = typeof nodes === "object" && nodes !== null
					? nodes
					: { nodes, view };
				return originalDisplayName.call(this, context);
			};
		}

		if (typeof normalizedConfig.iconSvgInline === "function") {
			const originalIconSvgInline = normalizedConfig.iconSvgInline;
			normalizedConfig.iconSvgInline = function (nodes, view) {
				const context = typeof nodes === "object" && nodes !== null
					? nodes
					: { nodes, view };
				return originalIconSvgInline.call(this, context);
			};
		}

		if (typeof normalizedConfig.enabled === "function") {
			const originalEnabled = normalizedConfig.enabled;
			normalizedConfig.enabled = function (nodes, view) {
				const context = typeof nodes === "object" && nodes !== null
					? nodes
					: { nodes, view };
				return originalEnabled.call(this, context);
			};
		}

		if (typeof normalizedConfig.exec === "function") {
			const originalExec = normalizedConfig.exec;
			normalizedConfig.exec = function (nodes, view) {
				const context = typeof nodes === "object" && nodes !== null
					? nodes
					: { nodes, view };
				return originalExec.call(this, context);
			};
		}

		if (typeof normalizedConfig.execBatch === "function") {
			const originalExecBatch = normalizedConfig.execBatch;
			normalizedConfig.execBatch = function (nodes, view) {
				const context = typeof nodes === "object" && nodes !== null
					? nodes
					: { nodes, view };
				return originalExecBatch.call(this, context);
			};
		}

		if (typeof normalizedConfig.inline === "function") {
			const originalInline = normalizedConfig.inline;
			normalizedConfig.inline = function (nodes, view) {
				const context = typeof nodes === "object" && nodes !== null
					? nodes
					: { nodes, view };
				return originalInline.call(this, context);
			};
		}
	}

	// Register the action using the standard API
	// Both NC32 and NC33 use registerFileAction, but the internal handling differs
	registerFileAction(normalizedConfig);
}

export default {
	registerFileActionCompat,
	isNextcloud32OrLower,
	isNextcloud33OrHigher,
};
