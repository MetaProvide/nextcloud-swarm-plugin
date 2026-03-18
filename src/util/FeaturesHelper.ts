import { FlagdWebProvider } from "@openfeature/flagd-web-provider";
import { OpenFeature } from "@openfeature/web-sdk";

const FLAGD_HOST = "features-test.hejbit.com";
let client: any = null;

/**
 * Initialize the OpenFeature client with the FlagdWebProvider
 */
async function init() {
	if (client === null) {
		await OpenFeature.setProviderAndWait(
			new FlagdWebProvider({
				host: FLAGD_HOST,
				port: 443,
				tls: true,
				maxRetries: 10,
				maxDelay: 30000,
			}),
		);
		await OpenFeature.setContext({
			hejbit: {
				version: "1.0.0",
				platform: "web",
			},
		});
		const logMessage = (
			level: "debug" | "info" | "warn" | "error",
			...messages: unknown[]
		) => {
			console[level]("[OpenFeature]", ...messages);
		};
		OpenFeature.setLogger({
			debug: (...messages) => logMessage("debug", ...messages),
			info: (...messages) => logMessage("info", ...messages),
			warn: (...messages) => logMessage("warn", ...messages),
			error: (...messages) => logMessage("error", ...messages),
		});
		client = await OpenFeature.getClient();
	}
	return client;
}

/*
 * Feature flag utility functions
 */
const FeaturesHelper = {
	async bool(key: string, defaultValue = false) {
		try {
			const featureClient = await init();
			return featureClient.getBooleanValue(key, defaultValue);
		} catch (error) {
			console.error(`Error fetching feature flag '${key}':`, error);
			return defaultValue;
		}
	},
};

export default FeaturesHelper;
