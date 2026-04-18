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
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Add `encryption_auth_tag` column and migrate data from `encryption_key`.
 *
 * The `encryption_key` column was misleadingly named — it stores the
 * AES-256-GCM authentication tag (16 bytes, base64-encoded), not a key.
 * This migration adds the correctly-named `encryption_auth_tag` column
 * and copies existing data over.
 */
class Version0008Date202604181430 extends SimpleMigrationStep {
	public const _TABLENAME = 'files_swarm';

	public function __construct(
		private IDBConnection $db,
	) {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable(self::_TABLENAME)) {
			return null;
		}

		$table = $schema->getTable(self::_TABLENAME);

		// Add the new column if it doesn't exist yet
		if (!$table->hasColumn('encryption_auth_tag')) {
			$table->addColumn('encryption_auth_tag', Types::STRING, [
				'notnull' => false,
				'length' => 64,
			]);
		}

		return $schema;
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		// Copy data from encryption_key to encryption_auth_tag where not null
		$qb = $this->db->getQueryBuilder();
		$qb->update(self::_TABLENAME)
			->set('encryption_auth_tag', 'encryption_key')
			->where($qb->expr()->isNotNull('encryption_key'));
		$qb->executeStatement();

		$output->info('Migrated encryption_key data to encryption_auth_tag');

		$this->db->executeStatement(
			'ALTER TABLE ' . self::_TABLENAME . ' DROP COLUMN encryption_key'
		);

		$output->info('Dropped old encryption_key column');
	}
}