<?php

declare(strict_types=1);

/**
 * @copyright
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
namespace OCA\Files_External_BeeSwarm\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<SwarmFile>
 */
class SwarmFileMapper extends QBMapper {
	public const TABLE_NAME = 'files_swarm';

	public function __construct(IDBConnection $db) {
		parent::__construct($db, self::TABLE_NAME);
	}

	/**
	 * @return SwarmFile[]
	 */
	public function findAll(string $fileid): array {
		$qb = $this->db->getQueryBuilder();

		$select = $qb
			->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('fileid', $qb->createNamedParameter($fileid)));

		return $this->findEntities($select);
	}

	/**
	 * @param string $reference
	 * @param int $id
	 *
	 * @return SwarmFile
	 * @throws DoesNotExistException
	 */
	public function find(string $reference, int $id): SwarmFile {
		$qb = $this->db->getQueryBuilder();

		$select = $qb
			->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('swarm_reference', $qb->createNamedParameter($reference, $qb::PARAM_INT)));
		return $this->findEntity($select);
	}

	public function createFile(array $filearray): SwarmFile {
		$swarm = new SwarmFile();
		$swarm->setFileid(100);
		$swarm->setName($filearray["name"]);
		$swarm->setSwarmReference($filearray["reference"]);
		$swarm->setSwarmTag($filearray["etag"]);
		$swarm->setMimeType($filearray["mimetype"]);
		$swarm->setSize($filearray["size"]);
		$swarm->setMtime($filearray["storage_mtime"]);

		return $this->insert($swarm);
	}
}
