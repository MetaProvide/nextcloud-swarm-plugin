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
namespace OCA\Files_External_Ethswarm\Settings;

use OCA\Files_External\Service\BackendService;
use OCA\Files_External\Service\GlobalStoragesService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\Settings\ISettings;
use OCP\Util;

class Admin implements ISettings {

	/** @var string */
	protected $appName;

	/** @var IConfig */
	private $config;

	/** @var GlobalStoragesService */
	private $globalStoragesService;

	public function __construct($appName,
								IConfig $config,
								GlobalStoragesService $globalStoragesService) {
		$this->appName = $appName;
		$this->config = $config;
		$this->globalStoragesService = $globalStoragesService;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		// Get all valid Beeswarm storages
		$temp = $this->globalStoragesService->getStorages();

		$storageBackends = array_filter($this->globalStoragesService->getStorages(), function ($storageBackend) {
			return $storageBackend->getBackend()->getStorageClass() == '\OCA\Files_External_Ethswarm\Storage\BeeSwarm';
		});

		// Get Mount settings
		$mounts_json = $this->config->getAppValue($this->appName,"storageconfig","");	//default
		$mounts = json_decode($mounts_json,  true);
		if (!is_array($mounts)) {
			$mounts = [];
		}
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
			$urlOptions = $backend->getBackendOptions();
			$batchId = "";
			$encrypt = 1;

			// Is it configured?
			$key = array_search($backendId, $mountIds);
			$isConfigured = !empty($key) || $key === 0;
			if ($isConfigured) {
				$encrypt = isset($mounts[$key]['encrypt']) ? $mounts[$key]['encrypt'] : 0;
				$batchId = isset($mounts[$key]['batchid']) ? $mounts[$key]['batchid'] : "";
			}
			$batches = $this->getBatches($urlOptions);
			if ($batches === null) {
				$batches["stamps"] = [];
			}

			$batcharray = [];
			foreach ($batches["stamps"] as $batch) {
				$newbatch = [
					"batchID" => $batch["batchID"],
					"amount" => $batch["amount"],
					"batchTTL" => $batch["batchTTL"],
					"isActive" => ($batch["batchID"] === $batchId ? true : false),
					"isDisabled" => ($batch["batchID"] === $batchId ? true : false),
					"isUsable" => ($batch["usable"]),
				];
				$batcharray[] = $newbatch;
			}

			$chequeBalance = $this->getChequebookBalance($urlOptions);
			$newMounts[] = ['mount_id' => $backendId, 'mount_name' => $backendName, 'mount_urloptions' => $urlOptions, 'encrypt' => $encrypt, 'batchid' => $batchId, 'chequebalance' => $chequeBalance, 'batches' => $batcharray];

			unset($batcharray);
		}
		$stampsUsage = $this->getStampsUsage($urlOptions);
		$parameters = [
			'visibilityType' => BackendService::VISIBILITY_ADMIN,
			'mounts' => json_encode($newMounts),
			'stampsUsage' => $stampsUsage,
		];

		Util::addScript($this->appName, 'nextcloud-swarm-plugin-main');
		$response = new TemplateResponse($this->appName, 'vue-admin-settings', ['params' => $parameters], '');
		return $response;
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection(): string {
		return ""; // disable the settings page
		return $this->appName;
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

	public function getBatches($urlOptions) {
		$uri = "/stamps";
		$curl = $this->setCurl($uri, $urlOptions);

		$output = curl_exec($curl);

		$response_data = json_decode($output, true);

		curl_close($curl);

		return $response_data;
	}

	public function getBatchBalance(string $batchId, $urlOptions) {
		$uri = "/stamps/" . $batchId;
		$curl = $this->setCurl($uri, $urlOptions);

		$output = curl_exec($curl);

		$response_data = json_decode($output, true);

		curl_close($curl);
		if (isset($response_data["batchTTL"])) {
			return $response_data["batchTTL"];
		} elseif (isset($response_data["message"])) {
			return $response_data["message"];
		}
		return null;
	}

	public function getChequebookBalance($urlOptions) {
		$uri = "/chequebook/balance";
		$curl = $this->setCurl($uri, $urlOptions);

		$output = curl_exec($curl);

		$response_data = json_decode($output, true);

		curl_close($curl);
		if (isset($response_data["totalBalance"])) {
			return $response_data["totalBalance"];
		} elseif (isset($response_data["message"])) {
			return $response_data["message"];
		}
		return null;
	}

	/**
	 * Buys a new postage batch method by using the client libary handler
	 * @return \mixed
	 */
	public function buyPostageStamp($amount, $depth, $urlOptions) : mixed {
		$uri = "/stamps/" . $amount . "/" . $depth;
		$curl = $this->setCurl($uri, $urlOptions);
		curl_setopt($curl, CURLOPT_POST, 1);
		$output = curl_exec($curl);
		$response_data = json_decode($output, true);
		curl_close($curl);

		return $response_data;
	}

	/**
	 * Top up a postage batch by using the client libary handler
	 * @return \mixed
	 */
	public function topUpPostageStamp($batchid, $amount, $urlOptions) : mixed {
		$uri = "/stamps/topup/" . $batchid . "/" . $amount;
		$curl = $this->setCurl($uri, $urlOptions);
		curl_setopt($curl, CURLOPT_POST, 1);
		$output = curl_exec($curl);
		$response_data = json_decode($output, true);
		curl_close($curl);

		return $response_data;
	}

	/**
	 * initializes a curl handler
	 * @return \CurlHandle
	 */
	protected function setCurl(string $uri_params, array $urlOptions, $useDebugApi = true) : \CurlHandle {
		$url_endpoint = $urlOptions['ip'] . ":" . ($useDebugApi ? $urlOptions['debug_api_port'] : $urlOptions['port']);
		$url_endpoint .= $uri_params;

		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, $url_endpoint);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		return $curl;
	}

	public function getStampMaximumCapacityBytes(int $depth): int {
		return pow(2, $depth) * 4096;
	}
	
	private function formatBytes($bytes) {
		$units = ['B', 'KB', 'MB', 'GB', 'TB'];
		$factor = floor((strlen($bytes) - 1) / 3);
		return sprintf("%.2f", $bytes / pow(1024, $factor)) . $units[$factor];
	}
	/**
	 * Returns the usage of the stamps
	 * @return \array
	 */
	public function getStampsUsage($urlOptions) : array{
		$stamp_data = $this->getBatches($urlOptions);
		$usageResults = [];

		// Loop through each stamp and calculate the usage
		foreach ($stamp_data['stamps'] as $stamp) {
			$depth = $stamp['depth'];
			$bucketDepth = $stamp['bucketDepth'];
			$utilization = $stamp['utilization'];

			// Calculate the usage
			$usage = $utilization / pow(2, $depth - $bucketDepth);

			$usageNormal = ceil($usage * 100);
  			$usageText = $usageNormal . '%';

			// Calculate the capacity and remaining capacity in bytes
			$capacity = $this->getStampMaximumCapacityBytes($depth);
			$remainingCapacity = $capacity * (1 - $usage);

			// Convert capacity and remaining capacity to human-readable format
			$capacityFormatted = $this->formatBytes($capacity);
			$remainingCapacityFormatted = $this->formatBytes($remainingCapacity);

			// Add the result to the usageResults array
			$usageResults[] = [
				'batchID' => $stamp['batchID'],
				'usage' => $usageText,
				'capacity' => $capacityFormatted,
				'remainingCapacity' => $remainingCapacityFormatted,
			];
		}
		return $usageResults;
	}
}
