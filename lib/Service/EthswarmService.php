<?php

/*
 * @copyright Copyright (c) 2023, MetaProvide Holding EKF
 *
 * @author Ron Trevor <ecoron@proton.me>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Files_External_Ethswarm\Service;

use Exception;
use OC;
use OCA\Files_External_Ethswarm\Db\SwarmFile;
use OCA\Files_External_Ethswarm\Db\SwarmFileMapper;
use OCA\Files_External_Ethswarm\Storage\BeeSwarm;
use OCP\Files\Storage\IStorage;
use OCP\Files\StorageNotAvailableException;
use OCP\IDBConnection;
use OCP\IL10N;

class EthswarmService {
	/** @var IDBConnection */
	protected $dbConnection;

	/** @var IL10N */
	private $l10n;

	/** @var SwarmFileMapper */
	private $fileMapper;

	public function __construct(IL10N $l10n) {
		$this->l10n = $l10n;
		$dbConnection = OC::$server->get(IDBConnection::class);
		$this->fileMapper = new SwarmFileMapper($dbConnection);
	}

	/**
	 * @return string
	 *
	 * @throws StorageNotAvailableException
	 */
	public function getSwarmRef(string $fileName, int $storageId) {
		$swarmFile = $this->fileMapper->find($fileName, $storageId);

		return $swarmFile->getSwarmReference();
	}

	public function getVisibility(string $fileName, int $storageId): bool {
		$swarmFile = $this->fileMapper->find($fileName, $storageId);

		return 1 == $swarmFile->getVisibility();
	}

	public function setVisibility(string $fileName, int $storageId, int $visibility) {
		$swarmFile = $this->fileMapper->find($fileName, $storageId);
		$swarmFile->setVisibility($visibility);

		return $this->fileMapper->update($swarmFile);
	}

	public function getToken(string $id): string {
		$swarmFile = $this->fileMapper->findById($id);
		if (!$swarmFile) {
			throw new StorageNotAvailableException($this->l10n->t('No token found'));
		}

		return $swarmFile->getToken();
	}

	public function archiveNode(string $fileName, IStorage $storage): void {
		/** @var BeeSwarm $storage */
		$storageId = $storage->getCache()->getNumericStorageId();
		$file = $this->fileMapper->find($fileName, $storageId);
		if (!$file->getId()) {
			throw new StorageNotAvailableException($this->l10n->t('File not found'));
		}

		$newPath = $storage->addPathToArchive($fileName);

		if ($storage->getCache()->get($newPath)) {
			throw new StorageNotAvailableException($this->l10n->t('Name already exists in Archive folder. You have to rename before archiving.'));
		}

		try {
			$storage->getCache()->move($fileName, $newPath);
			$storage->rename($fileName, $newPath);
			$this->fileMapper->updatePath($fileName, $newPath, $storageId);
		} catch (Exception $e) {
			throw new StorageNotAvailableException($this->l10n->t('Failed to move to Archive folder'));
		}
	}

	public function moveNode(string $fileName, IStorage $storage, string $destination): void {
		$storageId = $storage->getCache()->getNumericStorageId();
		$file = $this->fileMapper->find($fileName, $storageId);
		if (!$file->getId()) {
			throw new StorageNotAvailableException($this->l10n->t('File not found'));
		}

		$fileBaseName = basename($fileName);
		$newPath = $destination ? "{$destination}/{$fileBaseName}" : $fileBaseName;

		if ($storage->getCache()->get($newPath)) {
			throw new StorageNotAvailableException($this->l10n->t('Name already exists in destination folder. You have to rename before re-trying.'));
		}

		try {
			$storage->getCache()->move($fileName, $newPath);
			$storage->rename($fileName, $newPath);
			$this->fileMapper->updatePath($fileName, $newPath, $storageId);
		} catch (Exception $e) {
			throw new StorageNotAvailableException($this->l10n->t("Could not restore file to {$destination}"));
		}
	}

	public function rename(string $fileName, string $newName, IStorage $storage): void {
		$storageId = $storage->getCache()->getNumericStorageId();
		$file = $this->fileMapper->find($fileName, $storageId);
		if (!$file->getId()) {
			throw new StorageNotAvailableException($this->l10n->t('File not found'));
		}

		$directory = dirname($fileName);
		$directory = '.' === $directory ? '' : "{$directory}/";
		$newPath = $directory.$newName;

		try {
			$storage->getCache()->move($fileName, $newPath);
			$storage->rename($fileName, $newPath);
			$this->fileMapper->updatePath($fileName, $newPath, $storageId);
		} catch (Exception $e) {
			throw new StorageNotAvailableException($this->l10n->t('Failed to rename'));
		}
	}

	public function exportReferences(IStorage $storage): array {
		$storageId = $storage->getCache()->getNumericStorageId();
		$files = $this->fileMapper->findAllByStorageId($storageId);

		try {
			return array_map(fn (SwarmFile $file) => [
				'path' => $file->getName(),
				'reference' => $file->getSwarmReference(),
				'visibility' => $file->getVisibility(),
				'token' => $file->getToken(),
				'mimetype' => $file->getMimetype(),
				'size' => $file->getSize(),
			], $files);
		} catch (Exception $e) {
			throw new StorageNotAvailableException($this->l10n->t('Failed to export references'));
		}
	}
}
