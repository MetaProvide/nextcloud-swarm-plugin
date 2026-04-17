<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2025, MetaProvide Holding EKF
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

namespace OCA\Files_External_Ethswarm\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version0006Date202604111430 extends SimpleMigrationStep {
	public const _TABLENAME = 'files_swarm';

	/**
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 *
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$table = $schema->getTable(self::_TABLENAME);

		if (!$table->hasColumn('encryption_version')) {
			$table->addColumn('encryption_version', Types::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'unsigned' => true,
			]);
		}

		if (!$table->hasColumn('encryption_nonce')) {
			$table->addColumn('encryption_nonce', Types::STRING, [
				'notnull' => false,
				'length' => 24,
			]);
		}

		return $schema;
	}
}