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

namespace OCA\Files_External_Ethswarm\Storage;

use CURLFile;
use OCA\Files_External_Ethswarm\Auth\License;
use OCA\Files_External_Ethswarm\Backend\BeeSwarm;
use OCA\Files_External_Ethswarm\Exception\SwarmException;
use OCA\Files_External_Ethswarm\Utils\Curl;
use OCP\Files\StorageBadConfigException;
use Safe\Exceptions\CurlException;

trait BeeSwarmTrait {
	private const INFRASTRUCTURE_VERSION_GATEWAY = 1;
	private const INFRASTRUCTURE_VERSION_HEJBIT = 2;

	protected string $api_url;

	protected string $access_key;

	public function isVersion(int $version = self::INFRASTRUCTURE_VERSION_GATEWAY): bool {
		return match ($version) {
			self::INFRASTRUCTURE_VERSION_GATEWAY => 'https://license.hejbit.com' === $this->api_url,
			self::INFRASTRUCTURE_VERSION_HEJBIT => 'https://license.hejbit.com' !== $this->api_url,
			default => false,
		};
	}

	/**
	 * @param mixed $params
	 *
	 * @throws StorageBadConfigException
	 */
	protected function parseParams($params): void {
		$this->validateParams($params);

		$this->api_url = $params[BeeSwarm::OPTION_HOST_URL];
		$this->access_key = $params[License::SCHEME_ACCESS_KEY];
	}

	/**
	 * @throws StorageBadConfigException
	 */
	private function validateParams(array &$params): void {
		if (!$params[BeeSwarm::OPTION_HOST_URL] || !$params[License::SCHEME_ACCESS_KEY]) {
			throw new StorageBadConfigException('Creating '.self::class.' storage failed, required parameters not set');
		}
		if (!preg_match('/^https?:\/\//i', $params[BeeSwarm::OPTION_HOST_URL])) {
			$params[BeeSwarm::OPTION_HOST_URL] = 'https://'.$params[BeeSwarm::OPTION_HOST_URL];
		}
		if (!filter_var($params[BeeSwarm::OPTION_HOST_URL], FILTER_VALIDATE_URL)) {
			throw new StorageBadConfigException('Creating '.self::class.' storage failed, invalid url');
		}
	}

	/**
	 * @throws CurlException
	 * @throws SwarmException
	 */
	private function getUploadLink(): string {
		$endpoint = $this->api_url.'/api/upload';
		$curl = new Curl($endpoint, authorization: $this->access_key);
		$response = $curl->exec(true);

		if (!$curl->isResponseSuccessful()) {
			throw new SwarmException('Failed to connect to HejBit: '.$response['message']);
		}

		return $response['url'];
	}

	/**
	 * @throws CurlException
	 * @throws SwarmException
	 */
	private function getDownloadLink(string $reference): string {
		$endpoint = $this->api_url.'/api/download';
		$curl = new Curl($endpoint, authorization: $this->access_key);
		$response = $curl->exec(true);

		if (!$curl->isResponseSuccessful()) {
			throw new SwarmException('Failed to connect to HejBit: '.$response['message']);
		}

		return $response['url'].DIRECTORY_SEPARATOR.$reference;
	}

	/**
	 * @throws CurlException|SwarmException
	 */
	private function uploadSwarm(string $path, string $tempFile, string $mimetype): string {
		if ($this->isVersion(self::INFRASTRUCTURE_VERSION_GATEWAY)) {
			return $this->uploadSwarmV1($path, $tempFile, $mimetype);
		}

		$curl = new Curl($this->getUploadLink(), [
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => [
				'file' => new CURLFile($tempFile, $mimetype, basename($path)),
			],
		]);
		$response = $curl->exec(true);

		if (!$curl->isResponseSuccessful() || !isset($response['reference'])) {
			throw new SwarmException('Failed to upload file to Swarm: '.$response['message']);
		}

		return $response['reference'];
	}

	/**
	 * @return resource
	 *
	 * @throws CurlException|SwarmException
	 */
	private function downloadSwarm(string $reference) {
		if ($this->isVersion(self::INFRASTRUCTURE_VERSION_GATEWAY)) {
			return $this->downloadSwarmV1($reference);
		}

		$curl = new Curl($this->getDownloadLink($reference));
		$response = $curl->exec();

		if (!$curl->isResponseSuccessful()) {
			throw new SwarmException('Failed to download file from Swarm: '.$response['message']);
		}

		$stream = fopen('php://memory', 'r+');
		fwrite($stream, $response);
		rewind($stream);

		return $stream;
	}

	/**
	 * Returns the connection status of Swarm node.
	 *
	 * @throws CurlException|SwarmException
	 */
	private function checkConnection(): bool {
		if ($this->isVersion(self::INFRASTRUCTURE_VERSION_GATEWAY)) {
			return $this->checkConnectionV1();
		}

		$endpoint = $this->api_url.'/api/readiness';

		$curl = new Curl($endpoint, authorization: $this->access_key);
		$response = $curl->exec(true);

		if (!$curl->isResponseSuccessful() and !isset($response['status'])) {
			throw new SwarmException('Failed to connect to HejBit: '.$response['message']);
		}

		return 'ok' === $response['status'];
	}

	/**
	 * @throws CurlException
	 */
	private function checkConnectionV1(): bool {
		$endpoint = $this->api_url.DIRECTORY_SEPARATOR.'readiness';

		$curl = new Curl($endpoint);
		$curl->setAuthorization($this->access_key, CURLAUTH_ANY);

		$output = $curl->exec();
		$httpCode = $curl->getInfo(CURLINFO_HTTP_CODE);

		return 200 === $httpCode and 'OK' === $output;
	}

	/**
	 * @return resource
	 *
	 * @throws CurlException|SwarmException
	 */
	private function downloadSwarmV1(string $reference) {
		$endpoint = $this->api_url.DIRECTORY_SEPARATOR.'bzz'.DIRECTORY_SEPARATOR.$reference.DIRECTORY_SEPARATOR;

		$curl = new Curl($endpoint, [
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_HEADER => false,
		], [
			'content-type: application/octet-stream',
		]);
		$curl->setAuthorization($this->access_key, CURLAUTH_ANY);
		$response = $curl->exec();

		$httpCode = $curl->getInfo(CURLINFO_HTTP_CODE);
		if (200 !== $httpCode) {
			throw new SwarmException('Failed to download file from Swarm');
		}

		$stream = fopen('php://memory', 'r+');
		fwrite($stream, $response);
		rewind($stream);

		return $stream;
	}

	/**
	 * @throws CurlException|SwarmException
	 */
	private function uploadSwarmV1(string $path, string $tempFile, string $mimetype): array|string {
		$endpoint = $this->api_url.DIRECTORY_SEPARATOR.'bzz';
		$params = '?name='.urlencode(basename($path));

		$curl = new Curl($endpoint.$params, [
			CURLOPT_PUT => true,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POST => true,
			CURLOPT_INFILE => fopen($tempFile, 'r'),
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_VERBOSE => true,
		], [
			'content-type: '.$mimetype,
			'swarm-pin: true',
			'swarm-redundancy-level: 2',
		]);
		$curl->setAuthorization($this->access_key, CURLAUTH_ANY);

		$result = $curl->exec(true);
		$reference = (isset($result['reference']) ? $result['reference'] : null);

		if (!isset($reference)) {
			throw new SwarmException('Failed to upload file to '.$this->id.': '.$result['message']);
		}

		return $reference;
	}
}
