<?php

namespace OCA\Files_External_Ethswarm\Tests\Unit;

use OCA\Files_External_Ethswarm\Storage\BeeSwarmTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \OCA\Files_External_Ethswarm\Storage\BeeSwarmTrait
 */
class ImportLinkFlowTest extends TestCase {
	public function testFetchesImportLinkFromAuthAppWithApiKey(): void {
		$client = new ImportLinkFlowHarness('https://auth.example', 'hejbit-api-key');
		$client->import('swarm', 'swarm-reference');

		$this->assertSame('https://auth.example/api/import', $client->requests[0]->url);
		$this->assertSame(['accept: application/json'], $client->requests[0]->headers);
		$this->assertSame('hejbit-api-key', $client->requests[0]->authorization);
	}

	public function testFetchesGatewayUrlTemplateFromAuthAppWithApiKey(): void {
		$client = new ImportLinkFlowHarness('https://auth.example', 'hejbit-api-key');
		$template = $client->gatewayUrlTemplate();

		$this->assertSame('https://auth.example/api/gateway-link', $client->requests[0]->url);
		$this->assertSame(['accept: application/json'], $client->requests[0]->headers);
		$this->assertSame('hejbit-api-key', $client->requests[0]->authorization);
		$this->assertSame('https://gateway.example/bzz/{reference}/', $template['template']);
		$this->assertSame(3600, $template['ttl']);
	}

	public function testFallsBackToBzzLinkForAnInvalidGatewayUrlTemplate(): void {
		$client = new ImportLinkFlowHarness(
			'https://auth.example',
			'hejbit-api-key',
			['urlTemplate' => 'http://gateway.example/bzz/{reference}/'],
		);

		$this->assertSame([
			'template' => 'https://bzz.link/bzz/{reference}/',
			'ttl' => 300,
		], $client->gatewayUrlTemplate());
	}

	public function testFallsBackToBzzLinkWhenGatewayUrlRequestFails(): void {
		$client = new ImportLinkFlowHarness(
			'https://auth.example',
			'hejbit-api-key',
			[],
			false,
		);

		$this->assertSame([
			'template' => 'https://bzz.link/bzz/{reference}/',
			'ttl' => 300,
		], $client->gatewayUrlTemplate());
	}

	public function testImportsSwarmReferenceUsingSignedGatewayLink(): void {
		$client = new ImportLinkFlowHarness('https://auth.example', 'hejbit-api-key');
		$client->import('swarm', 'swarm-reference');

		$this->assertSame('https://gateway.example/import/swarm/swarm-reference', $client->requests[1]->url);
		$this->assertSame('opaque-import-token', $client->requests[1]->authorization);
		$this->assertSame('POST', $client->requests[1]->execOptions[CURLOPT_CUSTOMREQUEST]);
		$this->assertSame(['accept: application/json'], $client->requests[1]->execHeaders);
	}

	public function testImportsIpfsCidUsingSignedGatewayLink(): void {
		$client = new ImportLinkFlowHarness('https://auth.example', 'hejbit-api-key');
		$client->import('ipfs', 'bafybeigdyrzt');

		$this->assertSame('https://gateway.example/import/ipfs/bafybeigdyrzt', $client->requests[1]->url);
		$this->assertSame('opaque-import-token', $client->requests[1]->authorization);
		$this->assertSame('POST', $client->requests[1]->execOptions[CURLOPT_CUSTOMREQUEST]);
	}

	public function testDoesNotSendBodyToGatewayImportEndpoint(): void {
		$client = new ImportLinkFlowHarness('https://auth.example', 'hejbit-api-key');
		$client->import('swarm', 'swarm-reference');

		$this->assertArrayNotHasKey(CURLOPT_POSTFIELDS, $client->requests[1]->execOptions);
	}

	public function testUsesImportResponseFilenameAndMetadata(): void {
		$client = new ImportLinkFlowHarness('https://auth.example', 'hejbit-api-key');
		$result = $client->import('ipfs', 'QmRtgApHvzorfhYVVtEfBPqQtfNcsJ951FsRPkx8V9udm3');

		$this->assertSame('new-swarm-reference', $result['reference']);
		$this->assertSame('QmRtgApHvzorfhYVVtEfBPqQtfNcsJ951FsRPkx8V9udm3.png', $result['name']);
		$this->assertSame('image/png', $result['mimetype']);
		$this->assertSame(13444, $result['size']);
	}
}

class ImportLinkFlowHarness {
	use BeeSwarmTrait;

	/** @var FakeImportCurl[] */
	public array $requests = [];

	/** @var array<int, array<string, mixed>> */
	private array $responses;

	/** @var array<string, mixed> */
	private array $gatewayResponse;

	private bool $gatewayRequestSuccessful;

	public function __construct(string $apiUrl, string $accessKey, ?array $gatewayResponse = null, bool $gatewayRequestSuccessful = true) {
		$this->api_url = $apiUrl;
		$this->access_key = $accessKey;
		$this->gatewayResponse = $gatewayResponse ?? [
			'urlTemplate' => 'https://gateway.example/bzz/{reference}/',
			'cacheTtlSeconds' => 3600,
		];
		$this->gatewayRequestSuccessful = $gatewayRequestSuccessful;
		$this->responses = [
			[
				'url' => 'https://gateway.example/import',
				'token' => 'opaque-import-token',
				'method' => 'POST',
			],
			[
				'reference' => 'new-swarm-reference',
				'filename' => 'QmRtgApHvzorfhYVVtEfBPqQtfNcsJ951FsRPkx8V9udm3.png',
				'contentType' => 'image/png',
				'size' => 13444,
			],
		];
	}

	public function import(string $type, string $reference): array {
		return $this->importReferenceToSwarm($type, $reference);
	}

	/**
	 * @return array{template: string, ttl: int}
	 */
	public function gatewayUrlTemplate(): array {
		return $this->getGatewayUrlTemplate();
	}

	protected function createCurl(string $url, array $options = [], array $headers = [], ?string $authorization = null) {
		$isGatewayRequest = str_ends_with($url, '/api/gateway-link');
		$request = new FakeImportCurl(
			$url,
			$options,
			$headers,
			$authorization,
			$isGatewayRequest ? $this->gatewayResponse : array_shift($this->responses),
			$isGatewayRequest ? $this->gatewayRequestSuccessful : true,
		);
		$this->requests[] = $request;

		return $request;
	}
}

class FakeImportCurl {
	public array $execHeaders = [];
	public array $execOptions = [];

	public function __construct(
		public string $url,
		public array $options,
		public array $headers,
		public ?string $authorization,
		private array $response,
		private bool $successful = true,
	) {}

	public function get(bool $array = false): array|string {
		return $this->response;
	}

	public function exec(bool $array = false, array $options = [], array $headers = []): array|string {
		$this->execOptions = $options;
		$this->execHeaders = $headers;

		return $this->response;
	}

	public function isResponseSuccessful(): bool {
		return $this->successful;
	}
}
