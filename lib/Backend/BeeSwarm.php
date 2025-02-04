<?php

/**
 * @copyright Copyright (c) 2022, MetaProvide Holding EKF
 * @author Ron Trevor <ecoron@proton.me>
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
 */

namespace OCA\Files_External_Ethswarm\Backend;

use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\Lib\DefinitionParameter;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\Service\GlobalStoragesService;
use OCA\Files_External_Ethswarm\AppInfo\AppConstants;
use OCA\Files_External_Ethswarm\Auth\License;
use OCP\IConfig;
use OCP\IL10N;
use Psr\Log\LoggerInterface;

class BeeSwarm extends Backend {
	/** @const string */
	public const OPTION_HOST_URL = 'host_url';

	protected string $appName;
	private IL10N $l;

	private IConfig $config;

	private LoggerInterface $logger;

	private GlobalStoragesService $globalStoragesService;

	public function __construct(string $appName, IL10N $l, IConfig $config, LoggerInterface $logger, GlobalStoragesService $globalStoragesService) {
		$this->l = $l;
		$this->appName = $appName;
		$this->config = $config;
		$this->logger = $logger;
		$this->globalStoragesService = $globalStoragesService;
		$this
			->setIdentifier(AppConstants::APP_NAME)
			->addIdentifierAlias('\OC\Files\External_Storage\BeeSwarm') // legacy compat
			->setStorageClass('\OCA\Files_External_Ethswarm\Storage\BeeSwarm')
			->setText($l->t('HejBit-Swarm'))
			->addParameters([
				(new DefinitionParameter(
					self::OPTION_HOST_URL,
					$l->t('Access Key Server URL'),
					'app.hejbit.com'
				))
					->setTooltip($l->t('Access Key Server URL')),
			])->addAuthScheme(License::SCHEME_ACCESS_KEY)
		;
	}

	public function validateStorageDefinition(StorageConfig $storage): bool {
		$result = true;

		// access key
		if (!$storage->getBackendOption(License::SCHEME_ACCESS_KEY)) {
			$this->logger->warning('access key not set');
			$result = false;
		}

		// server url
		$host = $storage->getBackendOption(self::OPTION_HOST_URL);
		if (!preg_match('/^https?:\/\//i', $host)) {
			$host = 'https://'.$host;
		}
		if (!filter_var($host, FILTER_VALIDATE_URL)) {
			$this->logger->warning('invalid url');
			$result = false;
		}

		return $result;
	}
}
