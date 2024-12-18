<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022, MetaProvide Holding EKF
 * @author Ron Trevor <ecoron@proton.me>
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
 */

namespace OCA\Files_External_Ethswarm\Controller;

use OC;
use OCA\Files_External\Service\DBConfigService;
use OCA\Files_External\Service\GlobalStoragesService;
use OCA\Files_External_Ethswarm\AppInfo\AppConstants;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\IRequest;

class SettingsController extends Controller
{

	/** @var string */
	protected $appName;

	/** @var IConfig */
	private $config;

	/**
	 * @param string $appName
	 * @param IConfig $config
	 * @param IRequest $request
	 */
	public function __construct(
		string   $appName,
		IConfig  $config,
		IRequest $request
	)
	{
		parent::__construct($appName, $request);
		$this->config = $config;
	}

	/**
	 * Set the storage config settings.
	 */
	public function admin(): void
	{
		if ($this->request->getParam("storageconfig")) {
			$this->config->setAppValue($this->appName, "storageconfig", $this->request->getParam("storageconfig"));
		} else {
			$this->config->setAppValue($this->appName, "storageconfig", "");
		}
	}

	/**
	 * Get the current settings.
	 */
	public function getSettings(): array {
		return [
			'telemetry_enabled' => $this->config->getSystemValue('telemetry.enabled', false),
		];
	}

	/**
	 * @NoCSRFRequired
	 *
	 * @NoAdminRequired
	 * Save the storage config settings
	 */
	public function save(): void
	{
		if ($this->request->getParam("storageconfig")) {
			$this->config->setAppValue($this->appName, "storageconfig", $this->request->getParam("storageconfig"));
		} else {
			$this->config->setAppValue($this->appName, "storageconfig", "");
		}
	}
}
