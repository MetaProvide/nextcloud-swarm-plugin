<?php

/**
 * Minimal bootstrap for pure unit tests (no Nextcloud core needed).
 * Used for CryptoServiceTest and other standalone services.
 */

spl_autoload_register(static function (string $class): void {
	$prefix = 'OCA\\Files_External_Ethswarm\\';
	if (!str_starts_with($class, $prefix)) {
		return;
	}
	$relative = substr($class, strlen($prefix));
	$file = __DIR__.'/../lib/'.str_replace('\\', '/', $relative).'.php';
	if (is_file($file)) {
		require_once $file;
	}
});
