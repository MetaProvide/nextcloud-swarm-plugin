/*
 * @copyright Copyright (c) 2022 Henry Bergström <metahenry@metaprovide.org>
 *
 * @author Henry Bergström <metahenry@metaprovide.org>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import { generateFilePath } from "@nextcloud/router";

import Vue from "vue";
import App from "./App";

const appName = "files_external_ethswarm";

// eslint-disable-next-line
__webpack_public_path__ = generateFilePath(appName, "", "js/");

export default new Vue({
	el: "#app",
	data() {
		// State varible to hold settings coming from backend
		return { settings: {} };
	},
	beforeMount() {
		// Importing params from backend
		const dataset = document.querySelector("#app").dataset;
		this.settings = JSON.parse(dataset.params);

		// TODO - add swarm access key to settings
	},
	render(h) {
		// Render with settings passed as props to App component
		return h(App, { props: { settings: this.settings } });
	},
});
