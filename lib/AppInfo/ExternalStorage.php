<?php

namespace OCA\Files_External_Ethswarm\AppInfo;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\Lib\Config\IAuthMechanismProvider;
use OCA\Files_External\Lib\Config\IBackendProvider;
use OCA\Files_External\Service\BackendService;
use OCA\Files_External_Ethswarm\Auth\AccessKey;
use OCA\Files_External_Ethswarm\Backend\BeeSwarm;
use OCP\AppFramework\Bootstrap\IBootContext;
use Psr\Container\ContainerInterface;

class ExternalStorage implements IBackendProvider, IAuthMechanismProvider {
	public function __construct(
		protected ContainerInterface $container,
		protected IBootContext $context
	) {
		$context->injectFn(function (BackendService $backendService) {
			$backendService->registerBackendProvider($this);
			$backendService->registerAuthMechanismProvider($this);
		});
	}

	/**
	 * @return Backend[]
	 */
	public function getBackends(): array {
		return [$this->container->get(BeeSwarm::class)];
	}

	/**
	 * @return AuthMechanism[]
	 */
	public function getAuthMechanisms(): array {
		return [$this->container->get(AccessKey::class)];
	}
}
