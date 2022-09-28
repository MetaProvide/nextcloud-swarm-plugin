<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c)
 *
 * @author
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Files_External_Ethswarm\Listener;

use OCP\IConfig;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;
use OCA\Files_External\Service\GlobalStoragesService;

class CSPListener implements IEventListener {
	public const APP_NAME = 'files_external_ethswarm';

	/** @var GlobalStoragesService */
	private $globalStoragesService;

	public function __construct(GlobalStoragesService $globalStoragesService) {
		$this->config = \OC::$server->get(IConfig::class);
		$this->globalStoragesService = $globalStoragesService;
	}

	public function handle(Event $event): void {
		if (!($event instanceof AddContentSecurityPolicyEvent)) {
			return;
		}

		// Get all valid Beeswarm storages
		$storageBackends = array_filter($this->globalStoragesService->getStorages(), function ($storageBackend) {
			return $storageBackend->getBackend()->getStorageClass() == '\OCA\Files_External_Ethswarm\Storage\BeeSwarm';
		});

		$csp = new ContentSecurityPolicy();
		foreach ($storageBackends as $backend) {
			$urlOptions = $backend->getBackendOptions();
			$url_endpoint = $urlOptions['ip'] . ":" . $urlOptions['debug_api_port'];
			$csp->addAllowedConnectDomain($url_endpoint);
		}
		$event->addPolicy($csp);
	}
}
