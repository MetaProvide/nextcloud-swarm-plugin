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
use OCP\IConfig;

class BeeSwarm extends Backend {
	/**
	 * ethswarm constructor.
	 * @param IL10N $l
	 */

	 /** @var IConfig */
	 private $config;

	public function __construct(IL10N $l, IConfig $config) {
		$this->config = $config;
		$this
			->setIdentifier('files_external_ethswarm')
			->addIdentifierAlias('\OC\Files\External_Storage\BeeSwarm') // legacy compat
			->setStorageClass('\OCA\Files_External_Ethswarm\Storage\BeeSwarm')
			->setText($l->t('Swarm'))
			->addParameters([
				// (new DefinitionParameter('ip', $l->t('URL')))->setTooltip($l->t("Add http:// or https:// at the start of the parameter")),
				// (new DefinitionParameter('port', $l->t('API Port')))->setTooltip($l->t("The API-endpoint port that exposes all functionality with the Swarm network. By default, it runs on port 1633")),
				// (new DefinitionParameter('debug_api_port', $l->t('Debug API Port')))->setTooltip($l->t("The Debug API exposes functionality to inspect the state of your Bee node while it is running.  By default, it runs on port 1635")),
				(new DefinitionParameter('access_key', $l->t('Access Key')))->setTooltip($l->t("Access Key from MetaProvide")),
			])
			->addAuthScheme(AuthMechanism::SCHEME_NULL)
			->addAuthScheme(HttpBasicAuth::SCHEME_HTTP_BASIC);
		//->addCustomJs("../../../$appWebPath/js/beeswarm");
	}

	public function manipulateStorageConfig(StorageConfig &$storageConfig, IUser $user = null) {
		$storageConfig->setBackendOption('has_access', false);

		$access_key = $storageConfig->getBackendOption('access_key');

		// Check if access key is empty
		if (empty($access_key)) {
			\OC::$server->getLogger()->warning("Access Key not set");
			return;
		}

		// Verify access key
		$ch = curl_init();

		// Set the URL
		$endpoint = "https://nocodb.metaprovide.org/api/v1/db/data/v1/ethswarm-api-key-manger/access_keys/find-one";
		$query = 'where='. urlencode("(Key,eq," . $access_key . ")");
		$url = $endpoint . '?' . $query;

		// Set the necessary cURL options
		$api_token = $this->config->getSystemValue('swarm_access_api_token');

		// check if api token is empty
		if (empty($api_token)) {
			\OC::$server->getLogger()->warning("API Token not found");
			return;
		}

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			"accept: application/json",
			"xc-token: $api_token"
		]);

		// Execute the request and store the response
		$response = curl_exec($ch);
		$data = json_decode($response, true);

		// check if staus code is 400
		if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 400) {
			\OC::$server->getLogger()->warning("Access Key not found");
			return;
		}

		// check if still valid by checking if ExpiresAt is less than current time
		if (strtotime($data["ExpiresAt"]) < time()) {
			\OC::$server->getLogger()->warning("Access Key has expired");
			return;
		}

		// Success
		$storageConfig->setBackendOption('has_access', true);

		$auth = $storageConfig->getAuthMechanism();
		if ($auth->getScheme() != HttpBasicAuth::SCHEME_HTTP_BASIC) {
			$storageConfig->setBackendOption('user', '');
			$storageConfig->setBackendOption('password', '');
			$storageConfig->setBackendOption('access_key', '');
		}
	}
}
