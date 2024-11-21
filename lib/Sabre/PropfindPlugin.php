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
		if ($node instanceof File) {
			$storageid = $node->getFileInfo()->getStorage()->getCache()->getNumericStorageId();
			$filename = $node->getFileInfo()->getinternalPath();
			$mountpoint = $node->getFileInfo()->getMountPoint()->getStorageId();

			if (!str_starts_with($mountpoint, 'ethswarm')) {
				return '';
			}
			$class = $this->EthswarmService;
			$propFind->handle(self::ETHSWARM_FILEREF, function () use ($class, $storageid, $filename) {
				return $class->getSwarmRef($filename, $storageid);
			});

			if($class->getVisiblity($filename, $storageid)==1){
				$propFind->set("{http://nextcloud.org/ns}hidden","false",200);
			}
			else{
				$propFind->set("{http://nextcloud.org/ns}hidden","true",200);
			}

			$propFind->handle(self::ETHSWARM_NODE, function () use ($class, $storageid, $filename)
			{
					return "true";
			});
		}

		if ($node instanceof Directory) {
			$storageid = $node->getFileInfo()->getStorage()->getCache()->getNumericStorageId();
			$filename = $node->getFileInfo()->getinternalPath();
			$mountpoint = $node->getFileInfo()->getMountPoint()->getStorageId();

			if (!str_starts_with($mountpoint, "ethswarm")) {
				return "";
			}
			$class = $this->EthswarmService;

			$propFind->handle(self::ETHSWARM_NODE, function () use ($class, $storageid, $filename)
			{
					return "true";
			});
			if ($filename === "") {
				return "";
			}

			if($class->getVisiblity($filename, $storageid)==1){
				$propFind->set("{http://nextcloud.org/ns}hidden","false",200);
			}
			else{
				$propFind->set("{http://nextcloud.org/ns}hidden","true",200);
			}


		}
	}
}
