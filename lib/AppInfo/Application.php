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
use OCA\Files_External_Ethswarm\Backend\BeeSwarm;
use OCA\Files_External_Ethswarm\Auth\HttpBasicAuth;
use OCA\Files_External\Lib\Config\IBackendProvider;
use OCA\Files_External\Service\BackendService;
use OCA\Files_External\Lib\Config\IAuthMechanismProvider;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\EventDispatcher\IEventDispatcher;

/**
 * @package OCA\Files_external_beeswarm\AppInfo
 */
class Application extends App implements IBootstrap, IBackendProvider,IAuthMechanismProvider
{
	public const APP_ID = 'files_external_ethswarm';

    public function __construct(array $urlParams = array())
    {
        parent::__construct(self::APP_ID, $urlParams);
    }

	/**
     * @{inheritdoc}
     */
    public function getBackends()
    {
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


		 $this->getAuthMechanisms();
    }

	public function registerEventsScripts(IEventDispatcher $dispatcher) {
		// files scripts
		// $dispatcher->addListener('\OCP\Collaboration\Resources::loadAdditionalScripts', function () {
		// 	\OCP\Util::addScript(self::APP_ID, 'dist/collaboration');
		// });
		//\OCP\Util::addScript(self::APP_ID, 'beeswarmfilelist' );
		//\OCP\Util::addScript(self::APP_ID, "main");
	}

	public function register(IRegistrationContext $context): void
    {
    }

	public function getAuthMechanisms() {
		$container = $this->getContainer();
		return [
			// AuthMechanism::BASIC HTTP mechanisms
			$container->get(HttpBasicAuth::class),
		];
	}

}
