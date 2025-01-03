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

namespace OCA\Files_External_Ethswarm\Db;

use OC;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\IDBConnection;
use OCP\Files\IMimeTypeLoader;

/**
 * @template-extends QBMapper<SwarmFile>
 */
class SwarmFileMapper extends QBMapper
{
	public const TABLE_NAME = 'files_swarm';

	public function __construct(IDBConnection $db)
	{
		parent::__construct($db, self::TABLE_NAME);
	}

	/**
	 * @return SwarmFile[]
	 * @throws Exception
	 */
	public function findAll(string $fileId): array
	{
		$qb = $this->db->getQueryBuilder();

		$select = $qb
			->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('fileId', $qb->createNamedParameter($fileId)));

		return $this->findEntities($select);
	}

	/**
	 * @param string $name
	 * @param int $storage
	 *
	 * @return SwarmFile
	 * @throws DoesNotExistException
	 * @throws Exception
	 * @throws MultipleObjectsReturnedException
	 */
	public function find(string $name, int $storage): SwarmFile
	{
		$qb = $this->db->getQueryBuilder();

		$select = $qb
			->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('name', $qb->createNamedParameter($name, $qb::PARAM_STR)))
			->andWhere($qb->expr()->eq('storage', $qb->createNamedParameter($storage, $qb::PARAM_INT)));
		return $this->findEntity($select);
	}

	/**
	 * @param string $name
	 * @param int $storage
	 *
	 * @return int
	 * @throws Exception
	 */
	public function findExists(string $name, int $storage): int
	{
		$qb = $this->db->getQueryBuilder();

		$select = $qb
			->select('id')
			->from($this->getTableName())
			->where($qb->expr()->eq('name', $qb->createNamedParameter($name, $qb::PARAM_STR)))
			->andWhere($qb->expr()->eq('storage', $qb->createNamedParameter($storage, $qb::PARAM_INT)));
		return count($this->findEntities($select));
	}

	/**
	 * @param string $path
	 * @param int $storage
	 * @param string $token
	 * @return SwarmFile
	 * @throws Exception
	 */
	public function createDirectory(string $path, int $storage, string $token): SwarmFile
	{
		$swarm = new SwarmFile();
		$swarm->setName($path);
		$swarm->setMimetype(OC::$server->get(IMimeTypeLoader::class)->getId('httpd/unix-directory'));
		$swarm->setSize(1);
		$swarm->setStorageMtime(time());
		$swarm->setStorage($storage);
		$swarm->setToken($token);
		return $this->insert($swarm);
	}

	/**
	 * @param array $data
	 * @return SwarmFile
	 * @throws Exception
	 */
	public function createFile(array $data): SwarmFile
	{
		$swarm = new SwarmFile();
		$swarm->setName($data["name"]);
		$swarm->setSwarmReference($data["reference"]);
		$swarm->setSwarmTag($data["etag"]);
		$swarm->setMimetype($data["mimetype"]);
		$swarm->setSize($data["size"]);
		$swarm->setStorageMtime($data["storage_mtime"]);
		$swarm->setStorage($data["storage"]);
		$swarm->setToken($data["token"]);
		return $this->insert($swarm);
	}

	/**
	 * @param string $path
	 * @param int $storage
	 * @param bool $incSelf
	 * @param bool $recursive
	 * @return array
	 * @throws Exception|MultipleObjectsReturnedException
	 */
	public function getPathTree(string $path, int $storage, bool $incSelf = true, bool $recursive = true): array
	{
		// Get files from directory tree based on path parameter
		$dir = array();
		if ($incSelf) {
			try {
				$dir[] = $this->find($path, $storage);
			} catch (DoesNotExistException $e) {
			}
		}

		if ($path !== '')
			$path .= '/';

		$qb = $this->db->getQueryBuilder();
		$select = $qb
			->select('*')
			->from($this->getTableName())
			->where($qb->expr()->like('name', $qb->createNamedParameter($this->db->escapeLikeParameter($path) . '%'), $qb::PARAM_STR))
			->andWhere($qb->expr()->eq('storage', $qb->createNamedParameter($storage, $qb::PARAM_INT)));
		if (!$recursive)
			$select->andWhere($qb->expr()->notLike('name', $qb->createNamedParameter($this->db->escapeLikeParameter($path) . '%/%'), $qb::PARAM_STR));
		return array_merge($dir, $this->findEntities($select));
	}

	/**
	 * @param string $path1
	 * @param string $path2
	 * @param int $storage
	 * @return int
	 * @throws Exception
	 */
	public function updatePath(string $path1, string $path2, int $storage): int
	{
		$qb = $this->db->getQueryBuilder();
		$qb
			->update($this->getTableName())
			->set('name', $qb->createNamedParameter($path2))
			->where($qb->expr()->eq('name', $qb->createNamedParameter($path1, $qb::PARAM_STR)))
			->andWhere($qb->expr()->eq('storage', $qb->createNamedParameter($storage, $qb::PARAM_INT)));
		$qb->getSQL();
		return $qb->executeStatement();
	}

	/**
	 * @return SwarmFile[]
	 * @throws Exception
	 */
	public function findAllWithToken(string $token): array
	{
		$qb = $this->db->getQueryBuilder();

		$select = $qb
			->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('token', $qb->createNamedParameter($token)));

		return $this->findEntities($select);
	}


	/**
	 * @param string $token
	 * @param int $storageId
	 * @return void
	 * @throws \OCP\DB\Exception
	 */
	public function updateStorageIds(string $token, int $storageId): void
	{
		foreach ($this->findAllWithToken($token) as $swarmFile) {
			$swarmFile->setStorage($storageId);
			$this->update($swarmFile);
		};
	}

}
