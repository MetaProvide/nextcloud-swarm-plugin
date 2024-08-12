<?php
/**
 * @copyright Copyright (c) 2022, MetaProvide Holding EKF
 *
 * @author Ron Trevor <ecoron@proton.me>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Files_External_Ethswarm\Backend;


use OCP\IL10N;
use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\Lib\DefinitionParameter;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\Service\GlobalStoragesService;
use OCA\Files_External_Ethswarm\Auth\License;
use OCP\IUser;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

class BeeSwarm extends Backend
{
	/** @var IL10N */
	private IL10N $l;

	/** @var string */
	protected string $appName;

	/** @var IConfig */
	private IConfig $config;

	/** @var LoggerInterface */
	private LoggerInterface $logger;

	/** @var GlobalStoragesService */
	private GlobalStoragesService $globalStoragesService;

	/**
	 * @param string $appName
	 * @param IL10N $l
	 * @param IConfig $config
	 * @param LoggerInterface $logger
	 * @param GlobalStoragesService $globalStoragesService
	 */
	public function __construct(string $appName, IL10N $l, IConfig $config, LoggerInterface $logger, GlobalStoragesService $globalStoragesService)
	{
		$this->l = $l;
		$this->appName = $appName;
		$this->config = $config;
		$this->logger = $logger;
		$this->globalStoragesService = $globalStoragesService;
		$this
			->setIdentifier('files_external_ethswarm')
			->addIdentifierAlias('\OC\Files\External_Storage\BeeSwarm') // legacy compat
			->setStorageClass('\OCA\Files_External_Ethswarm\Storage\BeeSwarm')
			->setText($l->t('Swarm'))
			->addParameters([
				(new DefinitionParameter('HOST_URL', $l->t('Host')))->setTooltip($l->t("Swarm Provider URL")),
				(new DefinitionParameter('HOST_PORT', $l->t('Port')))->setTooltip($l->t("Port Number")),
			])->addAuthScheme(License::SCHEME_ACCESS_KEY);
	}

	/**
	 * @param StorageConfig $storageConfig
	 * @param IUser|null $user
	 * @return void
	 * @throws \OCA\Files_External\NotFoundException
	 */
	public function manipulateStorageConfig(StorageConfig &$storageConfig, IUser $user = null): void
	{
		// Check if access key is empty
		$accessKey = $storageConfig->getBackendOption(License::SCHEME_ACCESS_KEY);
		if (!$accessKey) {
			$this->logger->warning("Access Key not set");
			return;
		}

		// Set storage config to MetaProvide Swarm Gateway
		$storageConfig->setBackendOptions([
			'host' => $storageConfig->getBackendOption('HOST_URL'),
			'port' => $storageConfig->getBackendOption('HOST_PORT'),
			'access_key' => $accessKey
		]);

		// Update storage config
		$this->globalStoragesService->updateStorage($storageConfig);
	}
}
