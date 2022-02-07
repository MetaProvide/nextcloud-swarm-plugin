<?php
/**
 * @author Metaprovide
 *
 * @copyright Copyright (c) 2022, Metaprovide
 * @license GPL-2.0
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

declare(strict_types=1);

namespace OCA\Files_External_BeeSwarm\AppInfo;
use OCA\Files_External_BeeSwarm\Backend\BeeSwarm;
use OCA\Files_External\Lib\Config\IBackendProvider;
use OCA\Files_External\Service\BackendService;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\EventDispatcher\IEventDispatcher;

/**
 * @package OCA\Files_external_beeswarm\AppInfo
 */
class Application extends App implements IBootstrap, IBackendProvider
{
	public const APP_ID = 'files_external_beeswarm';

    public function __construct(array $urlParams = array())
    {
        parent::__construct(self::APP_ID, $urlParams);
		//\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\AppInfo\\Application.php-__construct()");
    }

	/**
     * @{inheritdoc}
     */
    public function getBackends()
    {
		\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\AppInfo\\Application.php-getBackends()");
        $container = $this->getContainer();
        return [
			$container->query(BeeSwarm::class)
		];
    }

	// Example from tutorial
	public function boot(IBootContext $context): void {
		 //\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\AppInfo\\Application.php-boot()");
		 $context->injectFn([$this, 'registerEventsScripts']);
    //     /** @var IManager $manager */
    //     $manager = $context->getAppContainer()->query(IManager::class);
    //     $manager->registerNotifierService(Notifier::class);
    }

	public function registerEventsScripts(IEventDispatcher $dispatcher) {
		// files scripts
		// $dispatcher->addListener('\OCP\Collaboration\Resources::loadAdditionalScripts', function () {
		// 	\OCP\Util::addScript(self::APP_ID, 'dist/collaboration');
		// });
		\OCP\Util::addScript(self::APP_ID, 'beeswarmfilelist' );
	}

	public function register(IRegistrationContext $context): void
    {
		//\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\AppInfo\\Application.php-register()");
        $container = $this->getContainer();
        $server = $container->getServer();

        \OC::$server->getEventDispatcher()->addListener(
			'OCA\\Files_External::loadAdditionalBackends',
			function() use ($server) {
				$backendService = $server->query(BackendService::class);
				$backendService->registerBackendProvider($this);
			}
        );

    }
}
