/**
 * @copyright Copyright (c) 2025, MetaProvide Holding EKF
 *
 * @author Mahyar Iranibazaz <mahiarirani@pm.me>
 * @author Henry Bergström <metahenry@metaprovide.org>
 * @author Ron Trevor <ecoron@proton.me>
 * @author Joao Raposo <joaosraposo@gmail.com>
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

// eslint-disable-next-line
__webpack_public_path__ = generateFilePath("files_external_ethswarm", "", "js/");

const requireContext = require.context('./app', true, /\.js$/);
requireContext.keys().forEach(requireContext);
