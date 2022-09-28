module.exports = {
	extends: ["@nextcloud", "prettier"],
	rules: {
		semi: [2, "always"],
		"no-console": "off",
		"vue/first-attribute-linebreak": [
			"error",
			{
				singleline: "ignore",
				multiline: "below",
			},
		],
	},
};
