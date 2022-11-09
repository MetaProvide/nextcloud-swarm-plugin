<?php

declare(strict_types=1);

/**
 * @copyright 2022,
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
namespace OCA\Files_External_Ethswarm\Sabre;

use OCA\DAV\Connector\Sabre\FilesPlugin;
use OCP\IPreview;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use OCA\Files_External_Ethswarm\Storage\BeeSwarm;
use OCA\Files_External_Ethswarm\Sabre\IEthswarm;
use OCA\Files_External_Ethswarm\Service\EthswarmService;

class PropfindPlugin extends ServerPlugin {
	public const ETHSWARM_FILEREF = '{http://nextcloud.org/ns}ethswarm-fileref';

	/** @var Server */
	private $server;

	/** @var BeeSwarm */
	private $bee;

	/** @var IEthswarm */
	private $IEthswarm;

	/** @var EthswarmService */
	private $EthswarmService;

	public function __construct(EthswarmService $service) { //BeeSwarm $bee
									// IEthswarm $iEthswarm
		// $params = [];
		// $this->bee = new BeeSwarm($params);
		//$this->IEthswarm = $iSwarm;
		$this->EthswarmService = $service;
		// $class = \OC::$server->get(IEthswarm::class);
		// $this->IEthswarm = $class;
	}

	public function initialize(Server $server) {
		$this->server = $server;

		$this->server->on('propFind', [$this, 'propFind']);
	}

	public function propFind(PropFind $propFind, INode $node) {
		if (!($node instanceof \OCA\DAV\Connector\Sabre\File)) {
			return;
		}
		$storageid = $node->getFileInfo()->getStorage()->getCache()->getNumericStorageId();
		$filename = $node->getFileInfo()->getName();
		$mountpoint = $node->getFileInfo()->getMountPoint()->getStorageId();

		if (!str_starts_with($mountpoint, "ethswarm")) {
			return "";
		}
		$class = $this->EthswarmService;
		$propFind->handle(self::ETHSWARM_FILEREF, function () use ($class, $storageid, $filename)
		{
			return $class->getSwarmRef($filename, $storageid);
		});
	}
}