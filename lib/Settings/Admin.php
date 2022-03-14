<?php
/**
 * @copyright Copyright (c)
 *
 * @author
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Files_External_BeeSwarm\Settings;

use OCA\Files_External\Service\BackendService;
use OCA\Files_External\Service\GlobalStoragesService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Settings\ISettings;

class Admin implements ISettings {

	/** @var string */
	const APP_NAME = 'files_external_beeswarm';

	/** @var IConfig */
	private $config;
	/** @var IL10N */
	private $l;

	/** @var GlobalStoragesService */
	private $globalStoragesService;

	/** @var BackendService */
	private $backendService;

	public function __construct(IConfig $config,
								IL10N $l,
								GlobalStoragesService $globalStoragesService,
								BackendService $backendService) {
		$this->config = $config;
		$this->l = $l;
		$this->globalStoragesService = $globalStoragesService;
		$this->backendService = $backendService;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {

		// Get Mount settings
		//$mounts_json = '[{"mount_id":"1","mount_point":"\/Beeswarm-local","encrypt":"1","batchid":"112020e2e112020e2e112020e2e"},{"mount_id":"12","mount_point":"\/beeswarm2","encrypt":"0","batchid":"fsdffsdasdasdsdasdasdasdsdasdasdasd"}]';
		$mounts_json = $this->config->getAppValue(SELF::APP_NAME,"storageconfig","");	//default
		$mounts = json_decode($mounts_json,  true);

		// Get all valid Beeswarm storages
		$storageBackends = array_filter($this->globalStoragesService->getStorages(), function ($storageBackend) use ($_) {
			return $storageBackend->getBackend()->getStorageClass() == '\OCA\Files_External_BeeSwarm\Storage\BeeSwarm';
		});

		// remove configurations that are no longer in Storages
		foreach ($mounts as $key => $mount) {
			$eachmountId = $mount['mount_id'];
			if (!isset($storageBackends[$eachmountId])) {
				// remove by key index
				unset($mounts[$key]);
			}
		}

		$mountIds = array_column($mounts, 'mount_id');
		foreach ($storageBackends as $backend) {
			$backendId = $backend->getId();
			$backendName = $backend->getMountPoint();
			$url_endpoint = $backend->getBackendOptions()['ip'];

			// Is it configured?
			$key = array_search($backendId, $mountIds);
			if (!empty($key) || $key === 0) {
				$mounts[$key]['mount_name'] = $backendName;
				$batchId = isset($mounts[$key]['batchid']) ? $mounts[$key]['batchid'] : "";
				$mounts[$key]['batchbalance'] = $this->getBatchBalance($batchId, $url_endpoint);
				$mounts[$key]['chequebalance'] = $this->getChequebookBalance($url_endpoint);
			}
			else {
				// not yet configured, so add to array. Default to encryption.
				$newMounts[] = ['mount_id' => $backendId, 'mount_name' => $backendName, 'encrypt'=> '1'];
			}
		}
		if ($newMounts) {
			$mounts = array_merge($mounts, $newMounts);
		}

		$parameters = [
			'visibilityType' => BackendService::VISIBILITY_ADMIN,
			'mounts' => json_encode($mounts),
		];
		return new TemplateResponse('files_external_beeswarm', 'admin-section', $parameters, '');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection(): string {
		return 'files_external_beeswarm';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority(): int {
		return 5;
	}

	public function getName(): ?string {
		return null; // Only one setting in this section
	}

	public function getAuthorizedAppConfig(): array {
		return [
			'theming' => '/.*/',
		];
	}

	public function getBatchBalance(string $batchId, $url_endpoint) {


		$url_endpoint .= ':1635/stamps/';

		$url_endpoint .= $batchId;
		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, $url_endpoint);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		$output = curl_exec($curl);

		$response_data = json_decode($output, true);

		curl_close($curl);
		return isset($response_data["batchTTL"]) ? $response_data['batchTTL'] : null;
	}

	public function getChequebookBalance($url_endpoint) {

		$url_endpoint .= ':1635/chequebook/balance';

		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, $url_endpoint);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		$output = curl_exec($curl);

		$response_data = json_decode($output, true);

		curl_close($curl);
		return isset($response_data["totalBalance"]) ? $response_data['totalBalance'] : null;
	}
}
