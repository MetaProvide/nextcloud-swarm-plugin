const path = require("path");
const webpackConfig = require("@nextcloud/webpack-vue-config");
webpackConfig.resolve.alias = {
	...webpackConfig.resolve.alias,
	"@": path.resolve(__dirname, "src"),
};
webpackConfig.entry = {
	...webpackConfig.entry,
	app: path.join(__dirname, "src", "app.js"),
};
module.exports = webpackConfig;
