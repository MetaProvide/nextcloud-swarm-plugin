<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c)
 *
 * @author
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Files_External_BeeSwarm\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use OCP\DB\Types;

class Version0002Date202202211730 extends SimpleMigrationStep {

	const _TABLENAME = "files_swarm";
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable(self::_TABLENAME)) {
			$table = $schema->getTable(self::_TABLENAME);
			$table->addColumn('storage', Types::BIGINT, [
				'notnull' => true,
				'length' => 20,
			]);
			if ($table->hasColumn('fileid')) {
				$table->changeColumn(
					'fileid', [
						'notnull' => false,
					]
				);
			}
			// if ($table->hasIndex('file_id_index')) {
			// 	$table->removeUniqueConstraint('file_id_index');
			// }
		}
		return $schema;
	}
	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
	}
}
