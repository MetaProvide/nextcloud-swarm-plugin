<?php

namespace OCA\Files_External_Ethswarm\Dto;

class LinkDto {
	public function __construct(
		public string $url,
		public string $token,
		public string $method,
	) {}
}
