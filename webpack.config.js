const path = require("path");
const webpackConfig = require("@nextcloud/webpack-vue-config");

// Remove vue$ alias set by @nextcloud/webpack-vue-config
// This is required for @nextcloud/dialogs v7+ which uses Vue 3 internally
// and must not be forced to resolve to the project's Vue 2
// See: https://github.com/nextcloud-libraries/nextcloud-dialogs/blob/master/CHANGELOG.md#v700
delete webpackConfig.resolve.alias["vue$"];

webpackConfig.resolve.alias = {
	...webpackConfig.resolve.alias,
	"@": path.resolve(__dirname, "src"),
};
webpackConfig.entry = {
	...webpackConfig.entry,
	app: path.join(__dirname, "src", "app.js"),
	settings: path.join(__dirname, "src", "settings.js"),
};
module.exports = webpackConfig;
