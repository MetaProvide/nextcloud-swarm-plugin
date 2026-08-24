<?php

namespace OCA\Files_External_Ethswarm\Storage;

use OCA\Files_External_Ethswarm\Db\SwarmFile;

interface IBeeSwarm {
	public function isSwarm(): true;

	public function importReference(string $directory, string $type, string $reference): SwarmFile;

	public function getGatewayUrl(string $reference): string;
}
