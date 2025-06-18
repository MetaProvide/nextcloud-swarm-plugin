<?php

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

declare(strict_types=1);

namespace OCA\Files_External_Ethswarm\AppInfo;

use OC\Security\CSP\ContentSecurityPolicy;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Files_External_Ethswarm\Listener\PreventExternalStorageDisableListener;
use OCA\Files_External_Ethswarm\Utils\Env;
use OCP\App\Events\AppDisableEvent;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Exceptions\AppConfigException;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;
use OCP\Util;

class Application extends BaseApp {
	public const NAME = 'files_external_ethswarm';
	public const API_URL = 'app.hejbit.com';

	/**
	 * @throws AppConfigException
	 */
	public function __construct() {
		parent::__construct(Application::NAME);
	}

	public function boot(IBootContext $context): void {
		$this->installApp('files_external', 'external_mounts');

		new ExternalStorage($this->container, $context);

		$this->loadAssets($context);
		$this->configureTelemetry();
	}

	public function register(IRegistrationContext $context): void {
		$this->loadTelemetry();

		// Register the listener to prevent files_external from being disabled
		$context->registerEventListener(
			AppDisableEvent::class,
			PreventExternalStorageDisableListener::class
		);

		/** @var IEventDispatcher $dispatcher */
		$dispatcher = $this->container->get(IEventDispatcher::class);
		$dispatcher->addListener(
			AddContentSecurityPolicyEvent::class,
			function (AddContentSecurityPolicyEvent $event): void {
				$policy = new ContentSecurityPolicy();
				$policy->addAllowedConnectDomain('https://*.hejbit.com');
				if (Env::isDevelopment()) {
					$policy->addAllowedConnectDomain('http://*.hejbit.local');
				}
				$event->addPolicy($policy);
			}
		);
	}

	private function loadAssets($context): void {
		Util::addStyle(Application::NAME, 'app');
		Util::addScript(Application::NAME, 'nextcloud-swarm-plugin-settings');

		/** @var IEventDispatcher $dispatcher */
		$dispatcher = $context->getAppContainer()->get(IEventDispatcher::class);
		$dispatcher->addListener(LoadAdditionalScriptsEvent::class, function () {
			Util::addStyle(Application::NAME, 'feedback');
			Util::addInitScript(Application::NAME, 'nextcloud-swarm-plugin-app');
		});
	}
}
