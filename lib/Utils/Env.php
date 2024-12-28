<?php

namespace OCA\Files_External_Ethswarm\Utils;

class Env {
	public static function get(string $name): string {
		return getenv($name);
	}

	public static function set(string $name, string $value): bool {
		return putenv($name.'='.$value);
	}

	public static function isDevelopment(): bool {
		return 'development' === self::get('ENV');
	}
}
