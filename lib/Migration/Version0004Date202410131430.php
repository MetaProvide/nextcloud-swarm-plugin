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

class Version0004Date202410131430 extends SimpleMigrationStep {
	private $db;

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

	}

	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		$qb = $this->db->getQueryBuilder();


		$resultNI = $qb->select('numeric_id')
		   ->from('storages')
		   ->where($qb->expr()->like('id', $qb->createNamedParameter('ethswarm::https:%')))
		   ->executeQuery();

		// This migration step only runs if there is a license that contains the api_url (previous verion of plugin)
		while ($row = $resultNI->fetch()) {
			$numeric_id = $row['numeric_id'];

			// This is assuming we only have one folder with Hejbit Plug (also true on the previous version of the plugin)
			$result = $qb->select('mount_id')
			->from('external_mounts')
			->where($qb->expr()->eq('storage_backend', $qb->createNamedParameter('files_external_ethswarm')))
			->executeQuery();
			$mountid = $result->fetchOne();

			$result = $qb->select('value')
			->from('external_config','m')
			->where($qb->expr()->eq('m.mount_id', $qb->createNamedParameter($mountid)))
			->andWhere($qb->expr()->eq('m.key', $qb->createNamedParameter('access_key')))
			->executeQuery();
			$key = $result->fetchOne();

			$updateQb = $this->db->getQueryBuilder();
			$result = $updateQb->update('storages')
				->set('id', $updateQb->createNamedParameter('ethswarm::'.$key))
				->where($updateQb->expr()->eq('numeric_id', $updateQb->createNamedParameter($numeric_id)))
				->executeStatement();
		}

	}}
