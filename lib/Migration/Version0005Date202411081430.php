<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022, MetaProvide Holding EKF
 *
 * @author Ron Trevor <ecoron@proton.me>
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
namespace OCA\Files_External_Ethswarm\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use OCP\DB\Types;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class Version0005Date202411081430 extends SimpleMigrationStep {
	private $db;
	public const _TABLENAMEREF = "files_swarm_tokens";


	public function __construct(IDBConnection $db) {
		$this->db = $db;
	}


	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable(self::_TABLENAMEREF)) {
			$table = $schema->createTable(self::_TABLENAMEREF);
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 20,
			]);
			$table->addColumn('files_swarm_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 20,
			]);
			$table->addColumn('token_id', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['token_id'], 'token_index');
		}
		return $schema;


	}

	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		$gqbNI = $this->db->getQueryBuilder();
		$gqbFI = $this->db->getQueryBuilder();
		$iqb = $this->db->getQueryBuilder();

		// Get all the numeric_id's of the swarm storages

		$resultNI = $gqbNI->select('numeric_id', 'id')
		   ->from('storages')
		   ->where($gqbNI->expr()->like('id', $gqbNI->createNamedParameter('ethswarm::%')))
		   ->executeQuery();

		while ($row = $resultNI->fetch()) {
			// Get all the files on each swarm storage
			$numeric_id = $row['numeric_id'];
			$token_id = $row['id'];

			$resultFileIds = $gqbFI->select('id')
			->from('files_swarm')
			->where($gqbFI->expr()->eq('storage', $gqbFI->createNamedParameter($numeric_id)))
			->executeQuery();

			while ($row = $resultFileIds->fetch()) {
				$file_id = $row['id'];
				// Insert the file_id and token_id into the new table
				$result = $iqb->insert(self::_TABLENAMEREF)
					->values([
						'files_swarm_id' => $iqb->createNamedParameter($file_id),
						'token_id' => $iqb->createNamedParameter($token_id)
					])
					->executeStatement();
			}

		}
	}}
