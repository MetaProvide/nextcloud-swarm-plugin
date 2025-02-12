<?php

namespace OCA\Files_External_Ethswarm\Exception;

use Exception;
use OCP\IConfig;
use Psr\Container\ContainerInterface;
use Sentry\State\HubInterface;

class BaseException extends Exception {
	private static ?ContainerInterface $container = null;
	private static ?HubInterface $hub = null;

	public function __construct($message, $code = 0, ?Exception $previous = null) {
		parent::__construct($message, $code, $previous);

		// Report exception to Sentry if enabled
		if (null !== self::$container) {
			$config = self::$container->get(IConfig::class);
			$telemetryEnabled = $config->getSystemValue('telemetry.enabled', false);
			if ($telemetryEnabled && null !== self::$hub) {
				self::$hub->captureException($this);
			}
		}
	}

	public static function setContainer(?ContainerInterface $container): void {
		self::$container = $container;
	}

	public static function setHub(?HubInterface $hub): void {
		self::$hub = $hub;
	}
}
