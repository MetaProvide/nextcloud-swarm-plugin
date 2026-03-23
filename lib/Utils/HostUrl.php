<?php

declare(strict_types=1);

namespace OCA\Files_External_Ethswarm\Utils;

final class HostUrl {
	public static function normalize(string $hostUrl): ?string {
		$normalizedHostUrl = trim($hostUrl);

		if ('' === $normalizedHostUrl) {
			return null;
		}

		if (!preg_match('/^https?:\/\//i', $normalizedHostUrl)) {
			$normalizedHostUrl = 'https://'.$normalizedHostUrl;
		}

		return false !== filter_var($normalizedHostUrl, FILTER_VALIDATE_URL)
			? $normalizedHostUrl
			: null;
	}
}
