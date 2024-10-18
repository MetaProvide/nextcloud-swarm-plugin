<?php
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

declare(strict_types=1);

namespace OCA\Files_External_Ethswarm\AppInfo;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Files_External_Ethswarm\AppInfo\AppConstants;
use OCA\Files_External_Ethswarm\Backend\BeeSwarm;
use OCA\Files_External_Ethswarm\Auth\License;
use OCA\Files_External\Lib\Config\IBackendProvider;
use OCA\Files_External\Service\BackendService;
use OCA\Files_External\Lib\Config\IAuthMechanismProvider;
use OCA\Files_External_Ethswarm\Notification\Notifier;
use OCP\AppFramework\App;
use OCP\Util;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\EventDispatcher\IEventDispatcher;

/**
 * @package OCA\Files_external_beeswarm\AppInfo
 */
class Application extends App implements IBootstrap, IBackendProvider, IAuthMechanismProvider {

	public function __construct(array $urlParams = []) {
		parent::__construct(AppConstants::APP_NAME, $urlParams);
	}

	/**
	 * @{inheritdoc}
	 */
	public function getBackends() {
		$container = $this->getContainer();
		return [
			$container->query(BeeSwarm::class)
		];
	}

	public function boot(IBootContext $context): void {
		$context->injectFn([$this, 'registerEventsScripts']);

		$context->injectFn(function (BackendService $backendService) {
			$backendService->registerBackendProvider($this);
			$backendService->registerAuthMechanismProvider($this);
		});

		// Load custom JS
		Util::addScript(AppConstants::APP_NAME, 'admin-settings');

		/** @var IEventDispatcher $dispatcher */
		$dispatcher = $context->getAppContainer()->get(IEventDispatcher::class);
		$dispatcher->addListener('OCA\Files::loadAdditionalScripts', function () {
			Util::addScript(AppConstants::APP_NAME, 'fileactions');
			Util::addScript(AppConstants::APP_NAME, 'menuobserver');
		});
		$dispatcher->addListener(LoadAdditionalScriptsEvent::class, function () {
			Util::addScript(AppConstants::APP_NAME, 'nextcloud-swarm-plugin-fileactions');
		});

		$this->getAuthMechanisms();

	}

	public function registerEventsScripts(IEventDispatcher $dispatcher) {
	}

	public function register(IRegistrationContext $context): void {
		// Register AddContentSecurityPolicyEvent for CSPListener class listenser here
	}

	public function getAuthMechanisms() {
		$container = $this->getContainer();
		return [
			// AuthMechanism::BASIC HTTP mechanisms
			$container->get(License::class),
		];
	}
}
