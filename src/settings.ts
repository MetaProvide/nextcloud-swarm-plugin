document.addEventListener("DOMContentLoaded", () => {
	if (!window.location.href.includes("settings/admin/externalstorages")) {
		return;
	}

	// Informational banner
	const targetElement = document.querySelector(".settings-section__desc");
	if (targetElement) {
		const link =
			'<a href="https://app.hejbit.com" target="_blank" style="text-decoration: underline">app.hejbit.com</a>';
		const el = document.createElement("p");
		el.style.marginBottom = "10px";
		el.innerHTML = `To get <strong>HejBit Swarm</strong> external storage access key, please visit ${link}`;
		targetElement.insertAdjacentElement("afterend", el);
	}

	// The Vue-based external storage settings ignores DefinitionParameter
	// defaultValue for text inputs. Use a MutationObserver to detect when
	// the host_url input appears in the "Add external storage" dialog and
	// prefill it with "app.hejbit.com".
	prefillHostUrl();
});

/**
 * Watch for the host_url input element being added to the DOM (inside
 * the "Add external storage" Vue dialog) and set its value to
 * "app.hejbit.com" when it first appears empty.
 */
function prefillHostUrl() {
	const DEFAULT_HOST = "app.hejbit.com";

	const tryPrefill = (root: ParentNode) => {
		const input = root.querySelector(
			'input[name="host_url"]',
		) as HTMLInputElement | null;
		if (input && input.value === "") {
			// Use the native setter so Vue's reactivity picks up the change.
			const nativeSetter = Object.getOwnPropertyDescriptor(
				window.HTMLInputElement.prototype,
				"value",
			)?.set;
			if (nativeSetter) {
				nativeSetter.call(input, DEFAULT_HOST);
			}
			input.dispatchEvent(new Event("input", { bubbles: true }));
		}
	};

	const observer = new MutationObserver((mutations) => {
		for (const mutation of mutations) {
			for (const node of mutation.addedNodes) {
				if (node.nodeType === Node.ELEMENT_NODE) {
					tryPrefill(node as ParentNode);
				}
			}
		}
	});

	observer.observe(document.body, { childList: true, subtree: true });
}
