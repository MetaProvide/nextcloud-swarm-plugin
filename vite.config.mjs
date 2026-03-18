import path from "node:path";
import { fileURLToPath } from "node:url";
import { createAppConfig } from "@nextcloud/vite-config";
import { defineConfig } from "vite";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const overrides = defineConfig({
	resolve: {
		alias: {
			"@": path.resolve(__dirname, "src"),
		},
	},
});

export default createAppConfig(
	{
		main: "src/main.ts",
		app: "src/app.ts",
		settings: "src/settings.ts",
	},
	{
		config: overrides,
	},
);
