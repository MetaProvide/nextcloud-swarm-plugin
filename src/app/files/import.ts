import { createApp } from "vue";
import ImportModal from "./components/ImportModal.vue";

export function openImportModal(context) {
	const container = document.createElement("div");
	document.body.appendChild(container);

	const app = createApp(ImportModal, {
		context,
		onClose: () => {
			app.unmount();
			container.remove();
		},
	});

	app.mount(container);
}
