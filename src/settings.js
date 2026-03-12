import { isNextcloud33OrHigher } from "@/util/FilesCompatibility";

document.addEventListener("DOMContentLoaded", () => {
	if (!window.location.href.includes("settings/admin/externalstorages")) {
		return;
	}

	// Informational banner
	// NC32: .settings-hint  |  NC33: .settings-section__desc
	const targetElement =
		document.querySelector(".settings-hint") ||
		document.querySelector(".settings-section__desc");
	if (targetElement) {
		const link =
			'<a href="https://app.hejbit.com" target="_blank" style="text-decoration: underline">app.hejbit.com</a>';
		const el = document.createElement("p");
		el.style.marginBottom = "10px";
		el.innerHTML = `To get <strong>Swarm</strong> external storage access key, please visit ${link}`;
		targetElement.insertAdjacentElement("afterend", el);
	}

	// NC33 Vue-based external storage settings ignores DefinitionParameter
	// defaultValue for text inputs. Use a MutationObserver to detect when
	// the host_url input appears in the "Add external storage" dialog and
	// prefill it with "app.hejbit.com".
	if (isNextcloud33OrHigher()) {
		prefillHostUrlForNc33();
	}
});

/**
 * Watch for the host_url input element being added to the DOM (inside
 * the NC33 "Add external storage" Vue dialog) and set its value to
 * "app.hejbit.com" when it first appears empty.
 */
function prefillHostUrlForNc33() {
	const DEFAULT_HOST = "app.hejbit.com";

	const tryPrefill = (root) => {
		const input = root.querySelector
			? root.querySelector('input[name="host_url"]')
			: null;
		if (input && input.value === "") {
			// Use the native setter so Vue's reactivity picks up the change.
			const nativeSetter = Object.getOwnPropertyDescriptor(
				window.HTMLInputElement.prototype,
				"value"
			).set;
			nativeSetter.call(input, DEFAULT_HOST);
			input.dispatchEvent(new Event("input", { bubbles: true }));
		}
	};

	const observer = new MutationObserver((mutations) => {
		for (const mutation of mutations) {
			for (const node of mutation.addedNodes) {
				if (node.nodeType === Node.ELEMENT_NODE) {
					tryPrefill(node);
				}
			}
		}
	});

	observer.observe(document.body, { childList: true, subtree: true });
}
