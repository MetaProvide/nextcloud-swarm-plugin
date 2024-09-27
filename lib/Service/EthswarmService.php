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

use OCP\Files\StorageNotAvailableException;
use OCP\IDBConnection;
use OCA\Files_External_Ethswarm\Db\SwarmFileMapper;
use OCP\IL10N;

class EthswarmService {

	/** @var IL10N */
	private $l10n;

	/** @var SwarmFileMapper */
	private $filemapper;

	/** @var \OCP\IDBConnection */
	protected $dbConnection;

	public function __construct(IL10N $l10n) {
		$this->l10n = $l10n;
		$dbConnection = \OC::$server->get(IDBConnection::class);
		$this->filemapper = new SwarmFileMapper($dbConnection);
	}

	/**
	 * @param string $filename
	 * @param int $storageid
	 * @throws StorageNotAvailableException
	 * @return string
	 */
	public function getSwarmRef(string $filename, int $storageid) {
		$swarmFile = $this->filemapper->find($filename, $storageid);

		return $swarmFile->getSwarmReference();
	}

	public function getVisiblity(string $filename, int $storageid) {
		$swarmFile = $this->filemapper->find($filename, $storageid);
		return $swarmFile->getVisibility();
	}

	public function setVisiblity(string $filename, int $storageid, int $visibility) {
		$swarmFile = $this->filemapper->find($filename, $storageid);
		$swarmFile->setVisibility($visibility);
		return $this->filemapper->update($swarmFile);
	}

	public function findExists(string $filename, int $storageid) {
		return $this->filemapper->findExists($filename, $storageid);
	}
}
