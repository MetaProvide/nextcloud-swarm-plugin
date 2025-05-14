<?php

declare(strict_types=1);
/*
 * @copyright Copyright (c) 2025, MetaProvide Holding EKF
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

namespace OCA\Files_External_Ethswarm\Sabre;

use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\File;
use OCA\Files_External_Ethswarm\Service\EthswarmService;
use OCA\Files_External_Ethswarm\Utils\Storage;
use Sabre\DAV\Exception\NotImplemented;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class WebDavPlugin extends ServerPlugin {
	public const ETHSWARM_FILEREF = '{http://nextcloud.org/ns}ethswarm-fileref';
	public const ETHSWARM_NODE = '{http://nextcloud.org/ns}ethswarm-node';
	public const ETHSWARM_HIDDEN = '{http://nextcloud.org/ns}hidden';

	/** @var Server */
	private $server;

	/** @var EthswarmService */
	private $EthswarmService;

	public function __construct(EthswarmService $service) {
		$this->EthswarmService = $service;
	}

	public function initialize(Server $server): void {
		$this->server = $server;
		$this->server->on('propFind', [$this, 'propFind']);
		$this->server->on('method:POST', [$this, 'httpPost']);
		$this->server->on('method:MOVE', [$this, 'httpMove']);
	}

	public function propFind(PropFind $propFind, INode $node): void {
		if (!$node instanceof Directory && !$node instanceof File) {
			return;
		}

		$fileInfo = $node->getFileInfo();
		$storageId = $fileInfo->getStorage()->getCache()->getNumericStorageId();
		$mountPoint = $fileInfo->getMountPoint()->getStorageId();
		$fileName = $fileInfo->getinternalPath();

		if (!str_starts_with($mountPoint, 'ethswarm')) {
			return;
		}

		$propFind->set(self::ETHSWARM_NODE, true, 200);

		if ($node instanceof File) {
			$ref = $this->EthswarmService->getSwarmRef($fileName, $storageId);
			$propFind->set(self::ETHSWARM_FILEREF, $ref, 200);
		}

		if ($node instanceof Directory) {
			if ('' == $fileName) {
				return;
			}
		}

		$propFind->set(
			self::ETHSWARM_HIDDEN,
			1 != $this->EthswarmService->getVisibility($fileName, $storageId) ? 'true' : 'false',
			200
		);
	}

	public function httpPost(RequestInterface $request, ResponseInterface $response): bool {
		$action = $request->getRawServerValue('HTTP_HEJBIT_ACTION');

		if (!$action) {
			return true;
		}

		try {
			switch ($action) {
				case 'hide':
					$path = $request->getPath();
					$node = $this->server->tree->getNodeForPath($path);
					if ($node instanceof File) {
						$storageid = $node->getFileInfo()->getStorage()->getCache()->getNumericStorageId();
						$filename = $node->getFileInfo()->getinternalPath();
					}
					if ($node instanceof Directory) {
						$storageid = $node->getFileInfo()->getStorage()->getCache()->getNumericStorageId();
						$filename = $node->getFileInfo()->getinternalPath();
					}

					$this->EthswarmService->setVisibility($filename, $storageid, 0);

					break;

				case 'unhide':
					$path = $request->getPath();
					$node = $this->server->tree->getNodeForPath($path);
					if ($node instanceof File) {
						$storageid = $node->getFileInfo()->getStorage()->getCache()->getNumericStorageId();
						$filename = $node->getFileInfo()->getinternalPath();
					}
					if ($node instanceof Directory) {
						$storageid = $node->getFileInfo()->getStorage()->getCache()->getNumericStorageId();
						$filename = $node->getFileInfo()->getinternalPath();
					}

					$this->EthswarmService->setVisibility($filename, $storageid, 1);

					break;

				case 'archive':
					$path = $request->getPath();
					$node = $this->server->tree->getNodeForPath($path);
					if ($node instanceof File || $node instanceof Directory) {
						$fileInfo = $node->getFileInfo();
						$filename = $fileInfo->getInternalPath();
						$storage = $fileInfo->getMountPoint()->getStorage();
						$this->EthswarmService->archiveNode($filename, $storage);
					}

					break;

				case 'unarchive' || 'move':
					$path = $request->getPath();
					$destination = $request->getHeader('Destination');
					$node = $this->server->tree->getNodeForPath($path);
					if ($node instanceof File || $node instanceof Directory) {
						$fileInfo = $node->getFileInfo();
						$filename = $fileInfo->getInternalPath();
						$storage = $fileInfo->getMountPoint()->getStorage();
						$this->EthswarmService->moveNode($filename, $storage, $destination);
					}

					break;

				default:
					throw new NotImplemented('Action not implemented');
			}

			$response->setBody(json_encode([
				'status' => true,
				'message' => 'success',
			]));
		} catch (\Exception $ex) {
			$response->setBody(json_encode([
				'status' => false,
				'message' => $ex->getMessage(),
			]));
		}

		$response->setHeader('Content-Type', 'application/json');
		$response->setStatus(200);

		return false;
	}

	public function httpMove(RequestInterface $request, ResponseInterface $response): bool {
		$path = $request->getPath();
		$node = $this->server->tree->getNodeForPath($path);
		$nodeInfo = $node->getFileInfo();
		$fileName = $nodeInfo->getInternalPath();
		$destination = $request->getHeader('Destination');
		$newName = urldecode(basename($destination));
		$storage = $nodeInfo->getMountPoint()->getStorage();
		if (Storage::isSwarm($storage)) {
			try {
				$this->EthswarmService->rename($fileName, $newName, $storage);
				$response->setStatus(200);
			} catch (Exception $ex) {
				$response->setStatus(500);
			}
			$response->setHeader('Content-Length', '0');

			return false;
		}

		return true;
	}
}
