const path = require('path');
const webpackConfig = require('@nextcloud/webpack-vue-config')

webpackConfig.entry['fileactions'] = path.join(__dirname, 'src', 'fileactions.js');
webpackConfig.entry['newfilemenu'] = path.join(__dirname, 'src', 'newfilemenu.js');
webpackConfig.entry['feedbackform'] = path.join(__dirname, 'src', 'feedbackform.js');
module.exports = webpackConfig
