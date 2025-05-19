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
use OCA\Files_External_Ethswarm\Exception\BaseException;
use OCA\Files_External_Ethswarm\Utils\Env;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Exceptions\AppConfigException;
use OCP\IConfig;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;
use OCP\Util;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Sentry;

class Application extends App implements IBootstrap
{
	public const NAME = 'files_external_ethswarm';
	public const API_URL = 'app.hejbit.com';
	public const TELEMETRY_URL = 'https://c46a60056f22db1db257c1d99fa99e5f@sentry.metaprovide.org/2';
	public const TELEMETRY_MINIMUM_SUPPORTED_NEXTCLOUD_VERSION = '30.0.0';
	public ContainerInterface $container;

	private readonly IEventDispatcher $dispatcher;
	private LoggerInterface $logger;
	private IConfig $config;

	/**
	 * @throws AppConfigException
	 */
	public function __construct()
	{
		parent::__construct(Application::NAME);
		$this->logger = $this->getContainer()->get(LoggerInterface::class);
		$this->config = $this->getContainer()->get(IConfig::class);
		$this->dispatcher = $this->getContainer()->getServer()->get(IEventDispatcher::class);

		$this->enableFilesExternalApp();
	}

	public function boot(IBootContext $context): void
	{
		new ExternalStorage($this->getContainer(), $context);

		$this->loadAssets($context);
		$this->configureTelemetry();
	}

	public function register(IRegistrationContext $context): void
	{
		$this->loadTelemetry();

		$this->dispatcher->addListener(
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

	/**
	 * @throws AppConfigException
	 */
	private function enableFilesExternalApp(): void
	{
		/** @var IAppManager $appManager */
		$appManager = $this->getContainer()->get(IAppManager::class);
		if (!$appManager->isInstalled('files_external')) {
			try {
				$this->logger->info('External Storage Support app is not enabled, enabling it now');
				$appManager->enableApp('files_external');
				if ($appManager->isInstalled('files_external')) {
					$this->logger->info('External Storage support enabled');

					return;
				}
				$this->logger->warning('Try enabling it now by force');
				$appManager->enableApp('files_external', true);
				if ($appManager->isInstalled('files_external')) {
					$this->logger->info('External Storage support enabled');

					return;
				}
				$this->logger->error('Failed to enable External Storage Support app');
			} catch (AppPathNotFoundException $e) {
			}

			throw new AppConfigException('External Storage Support app is required be installed and enabled. Please enable it to use this app.');
		}
	}

	private function loadAssets($context): void
	{
		Util::addStyle(Application::NAME, 'app');
		Util::addScript(Application::NAME, 'nextcloud-swarm-plugin-settings');

		/** @var IEventDispatcher $dispatcher */
		$dispatcher = $context->getAppContainer()->get(IEventDispatcher::class);
		$dispatcher->addListener(LoadAdditionalScriptsEvent::class, function () {
			Util::addStyle(Application::NAME, 'feedback');
			Util::addInitScript(Application::NAME, 'nextcloud-swarm-plugin-app');
		});
	}

	private function loadTelemetry(): void
	{
		// Register autoloader of sentry
		$autoloadPath = __DIR__ . '/../../vendor-bin/sentry/vendor/autoload.php';
		if (!file_exists($autoloadPath)) {
			throw new BaseException('Vendor autoload.php not found at: ' . $autoloadPath);
		}

		require_once $autoloadPath;
	}

	private function configureTelemetry(): void
	{
		// Initialize Sentry if telemetry is enabled and the nextcloud version is supported
		$environment = Env::get('ENV') ?? 'production';

		// Get telemetry enabled status and current nextcloud version
		$currentNextcloudVersion = $this->config->getSystemValue('version');
		$isSupported = version_compare($currentNextcloudVersion, Application::TELEMETRY_MINIMUM_SUPPORTED_NEXTCLOUD_VERSION, '>=');

		// if telemetry is not set, set it to true
		// but if it is set to false, don't override it
		if ('' === $this->config->getSystemValue('telemetry.enabled') && $isSupported) {
			$this->config->setSystemValue('telemetry.enabled', false);
			$this->logger->info('Telemetry option has not been set, setting it to true');
		}

		if ($this->config->getSystemValue('telemetry.enabled') && $isSupported) {
			/** @var IAppManager $appManager */
			$appManager = $this->getContainer()->get(IAppManager::class);
			$appInfo = $appManager->getAppInfo(self::NAME);
			$pluginVersion = $appInfo['version'] ?? 'unknown';

			/** @var \OCP\IURLGenerator $urlGenerator */
			$urlGenerator = $this->getContainer()->get(\OCP\IURLGenerator::class);
			$instanceUrl = $urlGenerator->getAbsoluteURL('/');

			Sentry\init([
				'dsn' => Application::TELEMETRY_URL,
				'traces_sample_rate' => 1.0,
				'environment' => $environment,
				'default_integrations' => false, // Disable default integrations to avoid sending unnecessary data
				'release' => $pluginVersion,
				'server_name' => $instanceUrl,
			]);

			// Set nextcloud version as a Sentry tag
			Sentry\configureScope(function (Sentry\State\Scope $scope) use ($currentNextcloudVersion): void {
				$scope->setTag('nextcloud_version', $currentNextcloudVersion);
			});

			$this->logger->info('Telemetry is enabled and the nextcloud version is supported');
		} elseif ($this->config->getSystemValue('telemetry.enabled') && !$isSupported) {
			$this->logger->info('Telemetry is enabled but the nextcloud version ' . $currentNextcloudVersion . ' is not supported');
		} elseif (false === $this->config->getSystemValue('telemetry.enabled')) {
			$this->logger->info('Telemetry is disabled');
		}
	}
}