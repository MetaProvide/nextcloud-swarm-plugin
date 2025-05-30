<?php

namespace OCA\Files_External_Ethswarm\Exception;

use Exception;
use OC;
use OCP\IConfig;
use Sentry;

class BaseException extends Exception {
	public function __construct($message, $code = 0, ?Exception $previous = null) {
		parent::__construct($message, $code, $previous);

		// Report exception to Sentry if enabled
		$config = OC::$server->get(IConfig::class);
		$telemetryEnabled = $config->getSystemValue('telemetry.enabled', false);

		if ($telemetryEnabled) {
			Sentry\captureException($this);
		}
	}
}
