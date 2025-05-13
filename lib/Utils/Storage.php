<?php

namespace OCA\Files_External_Ethswarm\Utils;

use OCP\Files\Storage\IStorage;
use Throwable;

class Storage {
	public static function isSwarm(IStorage $storage): bool {
		try {
			return $storage->isSwarm();
		} catch (Throwable $e) {
			return false;
		}
	}
}
