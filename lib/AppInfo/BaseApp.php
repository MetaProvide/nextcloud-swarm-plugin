<?php

namespace OCA\Files_External_Ethswarm\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

abstract class BaseApp extends App implements IBootstrap {
	use Dependency;
	use Telemetry;

	public ContainerInterface $container;
	protected LoggerInterface $logger;

	public function __construct() {
		parent::__construct(Application::NAME);
		$this->container = $this->getContainer();
		$this->logger = $this->container->get(LoggerInterface::class);
	}
}
