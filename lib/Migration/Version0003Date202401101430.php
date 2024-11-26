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
use OC;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\IMimeTypeLoader;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version0003Date202401101430 extends SimpleMigrationStep {
	private $db;

	private IMimeTypeLoader $mimeTypeHandler;

	public function __construct(IDBConnection $db) {
		$this->db = $db;
		$this->mimeTypeHandler = OC::$server->get(IMimeTypeLoader::class);
	}

	/**
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 *
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
		$currentVersion = $this->getCurrentPluginVersion();
		$mimetype = 'text/markdown';

		if (version_compare($currentVersion, '0.5.4', '==')) {

			$updateQb = $this->db->getQueryBuilder();
			$updateQb->update('files_swarm')
				->set('mimetype', $updateQb->createNamedParameter($this->mimeTypeHandler->getId($mimetype), IQueryBuilder::PARAM_INT))
				->where($updateQb->expr()->like('name', $updateQb->createNamedParameter('%.md')))
				->executeStatement()
			;
		}
	}

	private function getCurrentPluginVersion() {
		$qb = $this->db->getQueryBuilder();

		$result = $qb->select('configvalue')
			->from('appconfig')
			->where($qb->expr()->eq('appid', $qb->createNamedParameter('files_external_ethswarm')))
			->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter('installed_version')))
			->executeQuery()
		;

		return $result->fetchOne();
	}
}
