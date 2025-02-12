<?php

namespace OCA\Files_External_Ethswarm\Exception;

use Exception;
use OCP\IConfig;
use Psr\Container\ContainerInterface;
use Sentry\State\Hub;
use Sentry\State\HubInterface;

class BaseException extends Exception
{
	private static ?ContainerInterface $container = null;
	private static ?HubInterface $hub = null;

	public static function setContainer(?ContainerInterface $container): void
	{
		self::$container = $container;
	}

	public static function setHub(?HubInterface $hub): void
	{
		self::$hub = $hub;
	}

	public function __construct($message, $code = 0, ?Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);

		// Report exception to Sentry if enabled
		if (self::$container !== null) {
			$config = self::$container->get(IConfig::class);
			$telemetryEnabled = $config->getSystemValue('telemetry.enabled', false);
			if ($telemetryEnabled && self::$hub !== null) {
				self::$hub->captureException($this);
			}
		}
	}
}
