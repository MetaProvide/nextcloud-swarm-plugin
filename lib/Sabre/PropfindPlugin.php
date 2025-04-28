<?php

declare(strict_types=1);
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

namespace OCA\Files_External_Ethswarm\Sabre;

use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\File;
use OCA\Files_External_Ethswarm\Service\EthswarmService;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

class PropfindPlugin extends ServerPlugin {
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

	public function initialize(Server $server) {
		$this->server = $server;

		$this->server->on('propFind', [$this, 'propFind']);
	}

	public function propFind(PropFind $propFind, INode $node) {
		$fileInfo = $node->getFileInfo();
		$storageId = $fileInfo->getStorage()->getCache()->getNumericStorageId();
		$mountPoint = $fileInfo->getMountPoint()->getStorageId();
		$fileName = $fileInfo->getinternalPath();

		if (!str_starts_with($mountPoint, 'ethswarm')) {
			return '';
		}

		$propFind->set(self::ETHSWARM_NODE, true, 200);

		if ($node instanceof File) {
			$ref = $this->EthswarmService->getSwarmRef($fileName, $storageId);
			$propFind->set(self::ETHSWARM_FILEREF, $ref, 200);
		}

		if ($node instanceof Directory) {
			if ('' == $fileName) {
				return '';
			}
		}

		$propFind->set(
			self::ETHSWARM_HIDDEN,
			1 != $this->EthswarmService->getVisibility($fileName, $storageId) ? 'true' : 'false',
			200
		);
	}
}
