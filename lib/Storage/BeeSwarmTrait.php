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

namespace OCA\Files_External_Ethswarm\Storage;

use CURLFile;
use OCA\Files_External_Ethswarm\Auth\License;
use OCA\Files_External_Ethswarm\Backend\BeeSwarm;
use OCA\Files_External_Ethswarm\Exception\SwarmException;
use OCA\Files_External_Ethswarm\Utils\Curl;
use OCP\Files\StorageBadConfigException;
use Safe\Exceptions\CurlException;

trait BeeSwarmTrait
{

	/** @var string */
	protected string $api_url;

	/** @var string */
	protected string $access_key;

	/**
	 * @param $params
	 * @return void
	 * @throws StorageBadConfigException
	 */
	protected function parseParams($params): void
	{
		$this->validateParams($params);

		$this->api_url = $params[BeeSwarm::OPTION_HOST_URL];
		$this->access_key = $params[License::SCHEME_ACCESS_KEY];
	}

	/**
	 * @param array $params
	 * @return void
	 * @throws StorageBadConfigException
	 */
	private function validateParams(array &$params): void
	{
		if (!$params[BeeSwarm::OPTION_HOST_URL] || !$params[License::SCHEME_ACCESS_KEY]) {
			throw new StorageBadConfigException('Creating ' . self::class . ' storage failed, required parameters not set');
		}
		if (!preg_match('/^https?:\/\//i', $params[BeeSwarm::OPTION_HOST_URL])) {
			$params[BeeSwarm::OPTION_HOST_URL] = 'https://' . $params[BeeSwarm::OPTION_HOST_URL];
		}
		if (!filter_var($params[BeeSwarm::OPTION_HOST_URL], FILTER_VALIDATE_URL)) {
			throw new StorageBadConfigException('Creating ' . self::class . ' storage failed, invalid url');
		}
	}

	/**
	 * @param string $path
	 * @param string $tempFile
	 * @param string $mimeType
	 * @return string
	 * @throws SwarmException|CurlException
	 */
	private function uploadSwarm(string $path, string $tempFile, string $mimeType): string
	{
		// prepare the endpoint
		$endpoint = $this->api_url . DIRECTORY_SEPARATOR . 'api/files';
		$params = "?file=" . urlencode(basename($path));

		// prepare the form data
		$formData = ['file' => new CURLFile($tempFile, $mimeType, basename($path))];

		// prepare the curl request
		$curl = new Curl($endpoint . $params, [
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => $formData,
		], [], $this->access_key);

		// execute the request
		$response = $curl->exec(true);

		// check the response
		if (!$curl->isResponseSuccessful() || !isset($response['reference'])) {
			throw new SwarmException('Failed to upload file to Swarm: ' . $response['message']);
		}

		// return the reference
		return $response['reference'];
	}

	/**
	 * @param string $reference
	 * @return resource
	 * @throws SwarmException|CurlException
	 */
	private function downloadSwarm(string $reference)
	{
		$endpoint = $this->api_url . DIRECTORY_SEPARATOR . 'api/files' . DIRECTORY_SEPARATOR . $reference . DIRECTORY_SEPARATOR;

		$curl = new Curl($endpoint, [
			CURLOPT_RETURNTRANSFER => true,
		], [
			'content-type: application/octet-stream',
		], $this->access_key);
		$response = $curl->exec();

		if (!$curl->isResponseSuccessful()) {
			throw new SwarmException('Failed to download file from Swarm: ' . $response['message']);
		}

		$stream = fopen('php://memory', 'r+');
		fwrite($stream, $response);
		rewind($stream);
		return $stream;
	}

	/**
	 * Returns the connection status of Swarm node
	 *
	 * @return bool
	 * @throws SwarmException|CurlException
	 */
	private function checkConnection(): bool
	{
		$endpoint = $this->api_url . DIRECTORY_SEPARATOR . 'readiness';

		$curl = new Curl($endpoint);
		$curl->setAuthorization($this->access_key);

		$response = $curl->exec(true);
		if (!$curl->isResponseSuccessful() and !isset($response['status'])) {
			throw new SwarmException('Failed to connect to Swarm: ' . $response['message']);
		}
		return $response['status'] === 'ok';
	}
}
