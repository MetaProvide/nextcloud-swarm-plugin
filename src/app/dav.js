import { registerDavProperty } from "@nextcloud/files";

const registeredProperties = new Set();

const registerDavPropertyIfNeeded = (property) => {
	if (!registeredProperties.has(property)) {
		registerDavProperty(property);
		registeredProperties.add(property);
	}
};

registerDavPropertyIfNeeded("nc:ethswarm-fileref");
registerDavPropertyIfNeeded("nc:ethswarm-node");
