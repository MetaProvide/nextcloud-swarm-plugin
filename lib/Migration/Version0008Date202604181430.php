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
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Rename `encryption_key` column to `encryption_auth_tag`.
 *
 * The column was originally named `encryption_key` but it actually stores
 * the AES-256-GCM authentication tag (16 bytes, base64-encoded), not a key.
 * Renaming to `encryption_auth_tag` clarifies the actual purpose and aligns
 * with the naming used in the JS crypto module and gateway DTOs.
 */
class Version0008Date202604181430 extends SimpleMigrationStep {
	public const _TABLENAME = 'files_swarm';

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable(self::_TABLENAME)) {
			return null;
		}

		$table = $schema->getTable(self::_TABLENAME);

		// Rename encryption_key → encryption_auth_tag if the old column exists
		// and the new column doesn't exist yet
		if ($table->hasColumn('encryption_key') && !$table->hasColumn('encryption_auth_tag')) {
			$table->changeColumn('encryption_key', [
				'newName' => 'encryption_auth_tag',
			]);
		}

		return $schema;
	}
}