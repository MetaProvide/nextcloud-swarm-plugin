<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022, MetaProvide Holding EKF
 * @author Ron Trevor <ecoron@proton.me>
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
 */

return [
	'routes' => [
		[
			'name' => 'Settings#save',
			'url' => '/settings',
			'verb' => 'POST',
		],
		[
			'name' => 'Bee#createPostageBatch',
			'url' => '/bee/createPostageBatch',
			'verb' => 'POST',
		],
		[
			'name' => 'Bee#verifyBeeNodeAccess',
			'url' => '/bee/verifyBeeNodeAccess',
			'verb' => 'POST',
		],
		[
			'name' => 'Bee#topUpBatch',
			'url' => '/bee/topUpBatch',
			'verb' => 'POST',
		],
		[
			'name' => 'Feedback#submit',
			'url' => '/feedback/submit',
			'verb' => 'POST',
		],
	],
];
