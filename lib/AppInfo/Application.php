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
use OCA\Files_External_Ethswarm\Backend\BeeSwarm;
use OCA\Files_External_Ethswarm\Auth\License;
use OCA\Files_External\Lib\Config\IBackendProvider;
use OCA\Files_External\Service\BackendService;
use OCA\Files_External\Lib\Config\IAuthMechanismProvider;
use OCP\AppFramework\App;
use OCP\Util;
use OCP\App\IAppManager;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\EventDispatcher\IEventDispatcher;

use Sentry;
use Sentry\State\Scope;

/**
 * @package OCA\Files_external_beeswarm\AppInfo
 */
class Application extends App implements IBootstrap, IBackendProvider, IAuthMechanismProvider {
	public const APP_ID = 'files_external_ethswarm';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
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
		$context->injectFn(static function() {
			Sentry\init([
				//TODO: Change me!!!
				'dsn' => 'https://80b89142f9d5083a2143f4e0899ccd6b@o4508025104367616.ingest.de.sentry.io/4508025107054672',
				'max_breadcrumbs' => 50,
				//'sample_rate' => .5,  // This would send 50% of issues
			]);

			Sentry\configureScope(function (Scope $scope): void {
				$appManager = \OC::$server->get(IAppManager::class);
				$scope->setContext('appInfo', $appManager->getAppInfo(SELF::APP_ID));
				$scope->setContext('nextcloudVersion', [ 'version' => Util::getVersion() ]);
			});
		});

		$context->injectFn([$this, 'registerEventsScripts']);

		$context->injectFn(function (BackendService $backendService) {
			$backendService->registerBackendProvider($this);
			$backendService->registerAuthMechanismProvider($this);
		});

		// Load custom JS
		Util::addScript(SELF::APP_ID, 'admin-settings');

		/** @var IEventDispatcher $dispatcher */
		$dispatcher = $context->getAppContainer()->get(IEventDispatcher::class);
		$dispatcher->addListener('OCA\Files::loadAdditionalScripts', function () {
			Util::addScript(SELF::APP_ID, 'fileactions');
			Util::addScript(SELF::APP_ID, 'menuobserver');
		});
		$dispatcher->addListener(LoadAdditionalScriptsEvent::class, function () {
			Util::addScript(SELF::APP_ID, 'nextcloud-swarm-plugin-fileactions');
		});

		$this->getAuthMechanisms();

	}

	public function registerEventsScripts(IEventDispatcher $dispatcher) {
	}

	public function register(IRegistrationContext $context): void {
		include_once __DIR__.'/../../vendor/autoload.php';
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
