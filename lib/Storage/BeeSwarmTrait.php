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

use OCA\Files_External_Ethswarm\Utils\Curl;

trait BeeSwarmTrait
{

	/** @var string */
	protected string $api_url;

	/** @var string */
	protected string $port;

	/** @var string */
	protected string $api_base_url;

	/** @var string */
	protected string $access_key;

	/** @var bool */
	protected bool $isLicense = true;

	/** @var string */
	protected string $id;

	/**
	 * @param $params
	 * @return void
	 * @throws \Exception
	 */
	protected function parseParams($params): void
	{
		if (isset($params['host']) && isset($params['port'])) {
			$this->api_url = $params['host'];
			$this->port = $params['port'];
			$this->api_base_url = $this->api_url . ':' . $this->port;
		} else {
			throw new \Exception('Creating ' . self::class . ' storage failed, required parameters not set for bee swarm');
		}

		if ($params['access_key']) {
			$this->access_key = $params['access_key'];
			$this->isLicense = true;
		}
	}

	/**
	 * @param string $path
	 * @param string $tmpfile
	 * @param string $mimetype
	 * @param int|null $file_size
	 * @return array|string
	 * @throws \Safe\Exceptions\CurlException
	 */
	private function uploadStream(string $path, string $tmpfile, string $mimetype, int $file_size = null)
	{
		$endpoint = $this->api_base_url . DIRECTORY_SEPARATOR . 'bzz';
		$params = "?name=" . urlencode(basename($path));

		$curl = new Curl($endpoint . $params, [
			CURLOPT_PUT => true,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POST => true,
			CURLOPT_INFILE => fopen($tmpfile, 'r'),
			CURLOPT_INFILESIZE => $file_size,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_VERBOSE => true
		], [
			'content-type: ' . $mimetype,
			'swarm-pin: true',
			'swarm-redundancy-level: 2',
		], $this->access_key);

		return $curl->exec(true);
	}

	/**
	 * @param string $reference
	 * @return string|false
	 * @throws \Safe\Exceptions\CurlException
	 */
	private function downloadStream(string $reference): string|false
	{
		$endpoint = $this->api_base_url . DIRECTORY_SEPARATOR . 'bzz' . DIRECTORY_SEPARATOR . $reference;

		$curl = new Curl($endpoint, [
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_HEADER => true,
		], [
			'content-type: application/octet-stream',
		], $this->access_key);
		$response = $curl->exec();

		return substr($response, $curl->getInfo(CURLINFO_HEADER_SIZE));
	}

	/**
	 * Returns the connection status of Swarm node
	 *
	 * @return bool
	 * @throws \Exception if connection could not be made
	 */
	private function checkConnection(): bool
	{
		$endpoint = $this->api_base_url . DIRECTORY_SEPARATOR . 'readiness';

		$curl = new Curl($endpoint);
		$curl->setAuthorization($this->access_key);

		$output = $curl->exec();
		$httpCode = $curl->getInfo(CURLINFO_HTTP_CODE);

		return $httpCode === 200 and $output === 'OK';
	}
}
