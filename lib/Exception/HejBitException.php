<?php

namespace OCA\Files_External_Ethswarm\Exception;

use Exception;

class HejBitException extends BaseException
{
	public function __construct($message, $code = 0, ?Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}