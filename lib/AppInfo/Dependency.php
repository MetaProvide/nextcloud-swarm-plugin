<?php

namespace OCA\Files_External_Ethswarm\AppInfo;

use Exception;
use OC\Installer;
use OCA\Files_External_Ethswarm\Exception\AppDependencyException;
use OCP\App\IAppManager;
use OCP\IDBConnection;

trait Dependency {
	protected function installApp(string $app, ?string $table = null): void {
		/** @var IAppManager $appManager */
		$appManager = $this->container->get(IAppManager::class);

		/** @var Installer $installer */
		$installer = $this->container->get(Installer::class);

		if (!$appManager->isInstalled($app)) {
			$this->logger->info($app.' is not installed, installing it now');

			try {
				// Installing
				if ($table) {
					if (!$this->checkTableExists($table)) {
						$installer->installApp($app);
					}
					if (!$this->checkTableExists($table)) {
						$this->logger->error($app.' table '.$table.' does not exist, '.$app.' installation failed');

						throw new AppDependencyException();
					}
				} else {
					$installer->installApp($app);
				}

				// Enabling
				if (!$appManager->isInstalled($app)) {
					$this->logger->info($app.' is not enabled, enabling it now');
					$appManager->enableApp($app);

					if (!$appManager->isInstalled($app)) {
						$this->logger->warning('Try enabling '.$app.' forcefully');
						$appManager->enableApp($app, true);

						if (!$appManager->isInstalled($app)) {
							$this->logger->error($app.' enabling failed');

							throw new AppDependencyException();
						}
					}
				}

				// Loading
				if (!$appManager->isAppLoaded($app)) {
					$this->logger->info($app.' is not loaded, loading it now');
					$appManager->loadApp($app);
				}
				if (!$appManager->isAppLoaded($app)) {
					$this->logger->error($app.' loading failed');

					throw new AppDependencyException();
				}
			} catch (Exception $e) {
				$this->logger->error($e->getMessage());

				throw new AppDependencyException($e->getMessage());
			}
		}
	}

	private function checkTableExists(string $table): bool {
		/** @var IDBConnection $connection */
		$connection = $this->container->get(IDBConnection::class);

		return $connection->tableExists($table);
	}
}
