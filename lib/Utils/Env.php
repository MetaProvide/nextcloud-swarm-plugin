<?php

namespace OCA\Files_External_Ethswarm\Utils;

class Env {
	/**
	 * @param string $name
	 * @return string
	 */
	public static function get(string $name): string {
		return getenv($name);
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @return bool
	 */
	public static function set(string $name, string $value): bool {
		return putenv($name . '=' . $value);
	}

	/**
	 * @return bool
	 */
	public static function isDevelopment(): bool {
		return self::get('ENV') === 'development';
	}
}
