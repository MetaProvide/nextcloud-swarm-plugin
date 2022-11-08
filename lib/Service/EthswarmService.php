<?php

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

}
