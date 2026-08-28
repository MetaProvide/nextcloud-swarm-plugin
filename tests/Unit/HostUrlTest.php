<?php

namespace OCA\Files_External_Ethswarm\Tests\Unit;

use OCA\Files_External_Ethswarm\Utils\HostUrl;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \OCA\Files_External_Ethswarm\Utils\HostUrl
 */
class HostUrlTest extends TestCase {
	public function testNormalizeRemovesTrailingSlashes(): void {
		$this->assertSame('https://auth.example', HostUrl::normalize('auth.example///'));
	}
}
