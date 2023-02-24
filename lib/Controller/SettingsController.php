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

namespace OCA\Files_External_Ethswarm\Controller;

use OCP\AppFramework\Controller;
use OCP\IConfig;
use OCP\IRequest;
use OCP\Constants;
use OCA\Files_External_Ethswarm\Storage\BeeSwarm;

class SettingsController extends Controller {

	/** @var string */
	protected $appName;

	/** @var IConfig */
	private $config;

	/**
	 * @param IConfig $config
	 * @param IRequest $request
	 */
	public function __construct(
		string $appName,
		IConfig $config,
		IRequest $request
	) {
		parent::__construct($appName, $request);
		$this->config = $config;
	}

	/**
	 * Set the storage config settings
	 */
	public function admin(): void {
		if ($this->request->getParam("storageconfig")) {
			$this->config->setAppValue($this->appName, "storageconfig", $this->request->getParam("storageconfig"));
		} else {
			$this->config->setAppValue($this->appName, "storageconfig", "");
		}
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * Save the storage config settings
	 */
	public function save(): void {
		if ($this->request->getParam("storageconfig")) {
			$this->config->setAppValue($this->appName, "storageconfig", $this->request->getParam("storageconfig"));
		} else {
			$this->config->setAppValue($this->appName, "storageconfig", "");
		}
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * Add a swarm file reference to the Swarm External storage in NextCloud
	 */
	public function addFileToSwarm(): void {
		$params = $this->request->getParam("addswarmParam");
		$tmpFilesize = 0;
		if ($params) {
			$params = json_decode($params, true);
			if (is_array($params)) {
				// Write metadata to table
				$addfile = [
					"name" => $params['swarmfilename'],
					"permissions" => Constants::PERMISSION_READ,
					"mimetype" => "21", //$this->mimeTypeHandler->getId($mimetype),
					"mtime" => time(),
					"storage_mtime" => time(),
					"size" => $tmpFilesize,
					"etag" => null,
					"reference" => $params['swarmfileref'],
					"storage" =>  $params['mount_id'],
				];
				$beeclass = new BeeSwarm($params);
				$beeclass->addSwarmRef($addfile);
			}
		}
	}
}
