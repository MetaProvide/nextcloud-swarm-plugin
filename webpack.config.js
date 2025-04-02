const path = require('path');
const webpackConfig = require('@nextcloud/webpack-vue-config')

webpackConfig.module.rules.push({
	test: /\.tsx?$/,
	loader: 'ts-loader',
	options: {
		appendTsSuffixTo: [/\.vue$/],
		transpileOnly: true
	}
});

webpackConfig.resolve = {
	modules: [path.resolve(__dirname, 'node_modules'), 'node_modules'],
	extensions: ['.js', '.jsx', '.ts', '.tsx'],
}

const entryPoints = {
	'fileactions': 'src/fileactions.js',
	'newfilemenu': 'src/newfilemenu.js',
	'feedbackform': 'src/feedbackform.js',
	'renamingEthswarmStore': 'src/store/renamingEthswarm.ts'
};

Object.entries(entryPoints).forEach(([name, file]) => {
	webpackConfig.entry[name] = path.join(__dirname, file);
});

module.exports = webpackConfig
