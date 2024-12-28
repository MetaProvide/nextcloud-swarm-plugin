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

namespace OCA\Files_External_Ethswarm\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version0005Date202411081430 extends SimpleMigrationStep {
	public const _TABLENAME = 'files_swarm';
	private $db;

	public function __construct(IDBConnection $db) {
		$this->db = $db;
	}

	/**
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 *
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$table = $schema->getTable(self::_TABLENAME);

		if (!$table->hasColumn('token')) {
			$table->addColumn('token', Types::STRING, [
				'notnull' => true,
				'length' => 64,
				'default' => 'none',
			]);
			$table->addIndex(['token'], 'hejbit_token_index');
		}

		return $schema;
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
		$gqbNI = $this->db->getQueryBuilder();

		// Get all the numeric_id's of the swarm storages
		$resultNI = $gqbNI->select('numeric_id', 'id')
			->from('storages')
			->where($gqbNI->expr()->like('id', $gqbNI->createNamedParameter('ethswarm::%')))
			->executeQuery()
		;

		while ($row = $resultNI->fetch()) {
			// Get all the files on each swarm storage
			$numeric_id = $row['numeric_id'];
			$token_id = $row['id'];

			$updateQb = $this->db->getQueryBuilder();

			$result = $updateQb->update(self::_TABLENAME)
				->set('token', $updateQb->createNamedParameter($token_id))
				->where($updateQb->expr()->eq('storage', $updateQb->createNamedParameter($numeric_id)))
				->executeStatement()
			;
		}
	}
}
