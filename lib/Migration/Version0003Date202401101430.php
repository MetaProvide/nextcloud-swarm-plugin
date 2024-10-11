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


class Version0003Date202401101430 extends SimpleMigrationStep {

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

		$updateQb = $this->db->getQueryBuilder();
		$updateQb->update('files_swarm')
			->set('mimetype', $updateQb->createNamedParameter(4, IQueryBuilder::PARAM_INT))
			->where($updateQb->expr()->like('name', $updateQb->createNamedParameter('%.md')))
			->executeStatement();

	}}
