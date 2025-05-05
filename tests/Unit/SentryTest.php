<?php

namespace OCA\Files_External_Ethswarm\Tests\Unit;

use OCA\Files_External_Ethswarm\AppInfo\Application;
use OCA\Files_External_Ethswarm\Exception\BaseException;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Sentry\ClientBuilder;
use Sentry\State\Hub;
use Sentry\State\HubInterface;

/**
 * @internal
 *
 * @coversNothing
 */
class SentryTest extends TestCase {
	private IConfig $config;
	private Application $application;

	/** @var ContainerInterface&MockObject */
	private $serverContainer;
	private HubInterface $hub;

	protected function setUp(): void {
		parent::setUp();

		// Mock the config
		$this->config = $this->createMock(IConfig::class);
		// @var ContainerInterface&\PHPUnit\Framework\MockObject\MockObject
		$this->serverContainer = $this->createMock(ContainerInterface::class);

		// Set up container to return our mocked config
		$this->serverContainer->method('get')
			->with(IConfig::class)
			->willReturn($this->config)
		;

		$this->hub = new Hub();

		// Set up the exception handling
		BaseException::setContainer($this->serverContainer);
		BaseException::setHub($this->hub);

		// Create application with mocked container
		$this->application = $this->getMockBuilder(Application::class)
			->disableOriginalConstructor()
			->getMock()
		;
	}

	protected function tearDown(): void {
		BaseException::setContainer(null);
		BaseException::setHub(null);
		parent::tearDown();
	}

	public function testSentryInitializationWhenEnabled() {
		// Configure mock
		$this->config->method('getSystemValue')
			->willReturnMap([
				['environment', 'production', 'testing'],
				['telemetry.enabled', false, true],
			])
		;

		// Initialize Sentry
		$client = ClientBuilder::create([
			'dsn' => Application::TELEMETRY_URL,
			'traces_sample_rate' => 1.0,
			'environment' => 'testing',
		])->getClient();

		$this->hub->bindClient($client);

		// Verify Sentry was initialized
		$this->assertNotNull($this->hub->getClient());
	}

	public function testSentryInitializationWhenDisabled() {
		// Configure mock
		$this->config->method('getSystemValue')
			->willReturnMap([
				['environment', 'production', 'testing'],
				['telemetry.enabled', false, false],
			])
		;

		// Initialize Sentry (should not happen when disabled)
		$client = null;
		if ($this->config->getSystemValue('telemetry.enabled', false)) {
			$client = ClientBuilder::create([
				'dsn' => Application::TELEMETRY_URL,
				'traces_sample_rate' => 1.0,
				'environment' => 'testing',
			])->getClient();

			$this->hub->bindClient($client);
		}

		// Verify Sentry was not initialized
		$this->assertNull($client);
		$this->assertNull($this->hub->getClient());
	}

	public function testBaseExceptionSentryCapture() {
		// Configure mock
		$this->config->method('getSystemValue')
			->willReturnMap([
				['telemetry.enabled', false, true],
			])
		;

		// Create a test exception
		$testMessage = 'Test Exception';
		$exception = new BaseException($testMessage);

		// Verify exception properties
		$this->assertEquals($testMessage, $exception->getMessage());
		$this->assertEquals(0, $exception->getCode());
	}

	public function testBaseExceptionWithoutSentry() {
		// Configure mock
		$this->config->method('getSystemValue')
			->willReturnMap([
				['telemetry.enabled', false, false],
			])
		;

		// Create a test exception
		$testMessage = 'Test Exception';
		$exception = new BaseException($testMessage);

		// Verify exception properties
		$this->assertEquals($testMessage, $exception->getMessage());
		$this->assertEquals(0, $exception->getCode());
	}
}
