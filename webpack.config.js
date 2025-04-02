const path = require('path');
const webpackConfig = require('@nextcloud/webpack-vue-config')

// Add TypeScript handling
webpackConfig.module.rules.push({
	test: /\.tsx?$/,
	loader: 'ts-loader',
	options: {
	appendTsSuffixTo: [/\.vue$/],
	transpileOnly: true
	}
});
// Add .ts and .tsx to resolved extensions
// webpackConfig.resolve.extensions.push('.*','.ts', '.js', '.vue', '.*', '.tsx');
webpackConfig.resolve.extensions.push('.ts', '.tsx');

webpackConfig.entry['fileactions'] = path.join(__dirname, 'src', 'fileactions.js');
webpackConfig.entry['newfilemenu'] = path.join(__dirname, 'src', 'newfilemenu.js');
webpackConfig.entry['feedbackform'] = path.join(__dirname, 'src', 'feedbackform.js');
webpackConfig.entry['renamingEthswarmStore']= path.join(__dirname, 'src/store', 'renamingEthswarm.ts');
module.exports = webpackConfig
