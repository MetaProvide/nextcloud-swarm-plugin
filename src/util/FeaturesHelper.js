import { OpenFeature } from "@openfeature/web-sdk";
import { FlagdWebProvider } from "@openfeature/flagd-web-provider";

const FLAGD_HOST = "features-test.hejbit.com";
let client = null;

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
            })
        );
		await OpenFeature.setContext({
			"hejbit": {
				"version": "1.0.0",
				"platform": "web"
			}
		});
		OpenFeature.setLogger({
			log: (level, message) => {
				console.log(`[${level}] ${message}`);
			},
		});
        client = await OpenFeature.getClient();
    }
    return client;
}

/*
 * Feature flag utility functions
 */
const FeaturesHelper = {
    async bool(key, defaultValue = false) {
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
