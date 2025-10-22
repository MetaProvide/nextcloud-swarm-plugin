<?php

namespace OCA\Files_External_Ethswarm\AppInfo;

use OCA\Files_External_Ethswarm\Exception\BaseException;
use OCA\Files_External_Ethswarm\Utils\Env;
use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\IURLGenerator;
use Sentry;
use Sentry\State;

trait Telemetry {
	public const TELEMETRY_URL = 'https://c46a60056f22db1db257c1d99fa99e5f@sentry.metaprovide.org/2';
	public const TELEMETRY_MINIMUM_SUPPORTED_NEXTCLOUD_VERSION = '30.0.0';

	protected function loadTelemetry(): void {
		// Register autoloader of sentry
		$autoloadPath = __DIR__.'/../../vendor-bin/sentry/vendor/autoload.php';
		if (!file_exists($autoloadPath)) {
			throw new BaseException('Vendor autoload.php not found at: '.$autoloadPath);
		}

		require_once $autoloadPath;
	}

	protected function configureTelemetry(): void {
		// Initialize Sentry if telemetry is enabled and the nextcloud version is supported
		$environment = Env::get('ENV') ?? 'production';

		/** @var IConfig $config */
		$config = $this->container->get(IConfig::class);

		// Get telemetry enabled status and current nextcloud version
		$currentNextcloudVersion = $config->getSystemValue('version');
		$isSupported = version_compare($currentNextcloudVersion, Application::TELEMETRY_MINIMUM_SUPPORTED_NEXTCLOUD_VERSION, '>=');

		// if telemetry is not set, set it to true
		// but if it is set to false, don't override it
		if ('' === $config->getSystemValue('telemetry.enabled') && $isSupported) {
			$config->setSystemValue('telemetry.enabled', false);
			$this->logger->info('Telemetry option has not been set, setting it to true');
		}

		if ($config->getSystemValue('telemetry.enabled') && $isSupported) {
			/** @var IAppManager $appManager */
			$appManager = $this->container->get(IAppManager::class);
			$appInfo = $appManager->getAppInfo(Application::NAME);
			$pluginVersion = $appInfo['version'] ?? 'unknown';

			/** @var IURLGenerator $urlGenerator */
			$urlGenerator = $this->container->get(IURLGenerator::class);
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
			Sentry\configureScope(function (State\Scope $scope) use ($currentNextcloudVersion): void {
				$scope->setTag('nextcloud_version', $currentNextcloudVersion);
			});

			$this->logger->info('Telemetry is enabled and the nextcloud version is supported');
		} elseif ($config->getSystemValue('telemetry.enabled') && !$isSupported) {
			$this->logger->info('Telemetry is enabled but the nextcloud version '.$currentNextcloudVersion.' is not supported');
		} elseif (false === $config->getSystemValue('telemetry.enabled')) {
			$this->logger->info('Telemetry is disabled');
		}
	}
}
