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
use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External_Ethswarm\Auth\HttpBasicAuth;
use OCA\Files_External\Lib\DefinitionParameter;
use OCA\Files_External\Lib\StorageConfig;
use OCP\IUser;

class BeeSwarm extends Backend {
	/**
	 * ethswarm constructor.
	 * @param IL10N $l
	 */
	public function __construct(IL10N $l) {
		$this
			->setIdentifier('files_external_ethswarm')
			->addIdentifierAlias('\OC\Files\External_Storage\BeeSwarm') // legacy compat
			->setStorageClass('\OCA\Files_External_Ethswarm\Storage\BeeSwarm')
			->setText($l->t('Swarm'))
			->addParameters([
				(new DefinitionParameter('ip', $l->t('URL')))->setTooltip($l->t("Add http:// or https:// at the start of the parameter")),
				(new DefinitionParameter('port', $l->t('API Port')))->setTooltip($l->t("The API-endpoint port that exposes all functionality with the Swarm network. By default, it runs on port 1633")),
				(new DefinitionParameter('debug_api_port', $l->t('Debug API Port')))->setTooltip($l->t("The Debug API exposes functionality to inspect the state of your Bee node while it is running.  By default, it runs on port 1635")),
			])
			->addAuthScheme(AuthMechanism::SCHEME_NULL)
			->addAuthScheme(HttpBasicAuth::SCHEME_HTTP_BASIC);
	}

	public function manipulateStorageConfig(StorageConfig &$storage, IUser $user = null) {
		$auth = $storage->getAuthMechanism();

		if ($auth->getScheme() != HttpBasicAuth::SCHEME_HTTP_BASIC) {
			$storage->setBackendOption('user', '');
			$storage->setBackendOption('password', '');
		}
	}
}
