declare module "*.vue" {
	import type { DefineComponent } from "vue";

	const component: DefineComponent<{}, {}, any>;
	export default component;
}

declare module "*.svg" {
	const src: string;
	export default src;
}

declare module "*.svg?raw" {
	const content: string;
	export default content;
}

declare global {
	const OC: Nextcloud.v32.OC;
	const OCP: Nextcloud.v32.OCP;
	const t: typeof import("@nextcloud/l10n").translate;

	interface Window {
		OC: Nextcloud.v32.OC;
		OCP?: Nextcloud.v32.OCP;
	}
}

export {};
