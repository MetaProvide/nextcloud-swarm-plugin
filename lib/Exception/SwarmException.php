<?php

namespace OCA\Files_External_Ethswarm\Exception;

use Exception;

class SwarmException extends Exception {
	public function __construct($message, $code = 0, ?Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}
