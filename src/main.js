/**
 * @copyright Copyright (c) 2025, MetaProvide Holding EKF
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
import { createApp } from "vue";
import "@nextcloud/dialogs/style.css";
import Setting from "./pages/Setting.vue";

const rootElement = document.querySelector("#app");
const settings = rootElement?.dataset?.params
	? JSON.parse(rootElement.dataset.params)
	: {};

export default createApp(Setting, { settings }).mount("#app");
