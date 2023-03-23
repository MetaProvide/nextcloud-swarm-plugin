<?php
/**
 * External Storage: Swarm
 *
 * @copyright Copyright (C) 2022  Henry Bergström <metahenry@metaprovide.org>
 *
 * @author Henry Bergström <metahenry@metaprovide.org>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
?>
<!-- Element to inject Vue app, anchoring server params in data attribute -->
<div id="app" data-params="<?php p(json_encode($_['params'])); ?>"></div>
