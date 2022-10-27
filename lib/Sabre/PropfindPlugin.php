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

class PropfindPlugin extends ServerPlugin {
	public const ETHSWARM_FILEREF = '{http://nextcloud.org/ns}ethswarm-fileref';
	// public const TRASHBIN_ORIGINAL_LOCATION = '{http://nextcloud.org/ns}trashbin-original-location';
	// public const TRASHBIN_DELETION_TIME = '{http://nextcloud.org/ns}trashbin-deletion-time';
	// public const TRASHBIN_TITLE = '{http://nextcloud.org/ns}trashbin-title';

	/** @var Server */
	private $server;

	// /** @var IPreview */
	// private $previewManager;

	public function __construct() {
		$x = 9;
		// IPreview $previewManager
	//	$this->previewManager = $previewManager;
	}

	public function initialize(Server $server) {
		$this->server = $server;

		$this->server->on('propFind', [$this, 'propFind']);
	}


	public function propFind(PropFind $propFind, INode $node) {
		if (!($node instanceof IEthswarm)) {
			return;
		}

		$propFind->handle(self::ETHSWARM_FILEREF, function () use ($node) {
			return "heresmyhashcode"; //$node->getSwarmRef();
		});

		// $propFind->handle(self::TRASHBIN_ORIGINAL_LOCATION, function () use ($node) {
		// 	return $node->getOriginalLocation();
		// });

		// $propFind->handle(self::TRASHBIN_TITLE, function () use ($node) {
		// 	return $node->getTitle();
		// });

		// $propFind->handle(self::TRASHBIN_DELETION_TIME, function () use ($node) {
		// 	return $node->getDeletionTime();
		// });

		// $propFind->handle(FilesPlugin::SIZE_PROPERTYNAME, function () use ($node) {
		// 	return $node->getSize();
		// });

		// $propFind->handle(FilesPlugin::FILEID_PROPERTYNAME, function () use ($node) {
		// 	return $node->getFileId();
		// });

		// $propFind->handle(FilesPlugin::PERMISSIONS_PROPERTYNAME, function () {
		// 	return 'GD'; // read + delete
		// });

		// $propFind->handle(FilesPlugin::GETETAG_PROPERTYNAME, function () use ($node) {
		// 	// add fake etag, it is only needed to identify the preview image
		// 	return $node->getLastModified();
		// });

		// $propFind->handle(FilesPlugin::INTERNAL_FILEID_PROPERTYNAME, function () use ($node) {
		// 	// add fake etag, it is only needed to identify the preview image
		// 	return $node->getFileId();
		// });

		// $propFind->handle(FilesPlugin::HAS_PREVIEW_PROPERTYNAME, function () use ($node) {
		// 	return $this->previewManager->isAvailable($node->getFileInfo());
		// });

		// $propFind->handle(FilesPlugin::MOUNT_TYPE_PpROPERTYNAME, function () {
		// 	return '';
		// });
	}
}
