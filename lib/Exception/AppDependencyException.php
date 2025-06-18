<?php

namespace OCA\Files_External_Ethswarm\Exception;

use Exception;

class AppDependencyException extends BaseException {
	public const MESSAGE = 'External Storage Support app is required be installed and enabled. Please enable it to use this app.';

	public function __construct($message = self::MESSAGE, $code = 0, ?Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}
