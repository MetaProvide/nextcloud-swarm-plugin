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
use OCA\Files_External_Ethswarm\Auth\AccessKey;
use OCA\Files_External_Ethswarm\Backend\BeeSwarm;
use OCA\Files_External_Ethswarm\Contract\Enum\ApiEndpoints;
use OCA\Files_External_Ethswarm\Dto\LinkDto;
use OCA\Files_External_Ethswarm\Exception\CurlException;
use OCA\Files_External_Ethswarm\Exception\HejBitException;
use OCA\Files_External_Ethswarm\Utils\Curl;
use OCA\Files_External_Ethswarm\Utils\HostUrl;
use OCP\Files\StorageBadConfigException;
use OCP\Files\StorageNotAvailableException;

trait BeeSwarmTrait {
	protected const DEFAULT_GATEWAY_URL_TEMPLATE = 'https://bzz.link/bzz/{reference}/';
	protected const GATEWAY_URL_TEMPLATE_CACHE_TTL = 86400;
	protected const GATEWAY_URL_TEMPLATE_FAILURE_CACHE_TTL = 300;
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
	 * @throws StorageBadConfigException
	 */
	protected function parseParams(array $params): void {
		$this->validateParams($params);

		$this->api_url = $params[BeeSwarm::OPTION_HOST_URL];
		$this->access_key = $params[AccessKey::SCHEME];
	}

	protected function createCurl(string $url, array $options = [], array $headers = [], ?string $authorization = null) {
		return new Curl($url, $options, $headers, $authorization);
	}

	/**
	 * @return array{template: string, ttl: int}
	 */
	protected function getGatewayUrlTemplate(): array {
		if ($this->isVersion()) {
			return $this->gatewayUrlTemplateFallback();
		}

		$endpoint = $this->api_url.ApiEndpoints::GATEWAY_LINK->value;
		$request = $this->createCurl($endpoint, headers: [
			'accept: application/json',
		], authorization: $this->access_key);
		$response = $request->get(true);
		$template = is_array($response) ? $response['urlTemplate'] ?? null : null;

		if (!$request->isResponseSuccessful() || !$this->isValidGatewayUrlTemplate($template)) {
			return $this->gatewayUrlTemplateFallback();
		}

		return [
			'template' => $template,
			'ttl' => min(max((int) ($response['cacheTtlSeconds'] ?? self::GATEWAY_URL_TEMPLATE_CACHE_TTL), 300), self::GATEWAY_URL_TEMPLATE_CACHE_TTL),
		];
	}

	/**
	 * @return array{template: string, ttl: int}
	 */
	protected function gatewayUrlTemplateFallback(): array {
		return [
			'template' => self::DEFAULT_GATEWAY_URL_TEMPLATE,
			'ttl' => self::GATEWAY_URL_TEMPLATE_FAILURE_CACHE_TTL,
		];
	}

	/**
	 * @throws CurlException|HejBitException
	 */
	protected function importReferenceToSwarm(string $type, string $reference): array {
		if ($this->isVersion()) {
			throw new HejBitException('Import is not supported by this HejBit infrastructure');
		}

		$link = $this->getLink(ApiEndpoints::IMPORT);
		$endpoint = rtrim($link->url, '/').'/'.$type.'/'.rawurlencode($reference);
		$request = $this->createCurl($endpoint, authorization: $link->token);
		$response = $request->exec(true, [
			CURLOPT_CUSTOMREQUEST => $link->method,
		], [
			'accept: application/json',
		]);

		if (!is_array($response)) {
			$response = [];
		}

		if (!$request->isResponseSuccessful()) {
			throw new HejBitException('Failed to import file to HejBit: '.($response['message'] ?? 'Unknown error'));
		}

		$swarmReference = $response['reference'] ?? $response['swarmReference'] ?? null;
		if (null === $swarmReference && 'swarm' === $type) {
			$swarmReference = $reference;
		}

		if (null === $swarmReference) {
			throw new HejBitException('Failed to import file to HejBit: missing Swarm reference');
		}

		return [
			'name' => $response['filename'] ?? $response['name'] ?? null,
			'reference' => $swarmReference,
			'mimetype' => $response['contentType'] ?? $response['mimetype'] ?? $response['mimeType'] ?? 'application/octet-stream',
			'size' => (int) ($response['size'] ?? 0),
		];
	}

	private function isValidGatewayUrlTemplate(mixed $template): bool {
		if (!is_string($template) || 1 !== substr_count($template, '{reference}')) {
			return false;
		}

		$parsedUrl = parse_url(str_replace('{reference}', 'reference', $template));

		return is_array($parsedUrl) && 'https' === ($parsedUrl['scheme'] ?? null) && isset($parsedUrl['host']);
	}

	/**
	 * @throws StorageBadConfigException
	 */
	private function validateParams(array &$params): void {
		$hostUrl = (string) ($params[BeeSwarm::OPTION_HOST_URL] ?? '');
		$accessKey = (string) ($params[AccessKey::SCHEME] ?? '');

		if (empty($hostUrl) || empty($accessKey)) {
			throw new StorageBadConfigException('Creating '.self::class.' storage failed, required parameters not set');
		}

		$validatedHostUrl = HostUrl::normalize($hostUrl);
		if (null === $validatedHostUrl) {
			throw new StorageBadConfigException('Creating '.self::class.' storage failed, invalid url');
		}

		$params[BeeSwarm::OPTION_HOST_URL] = $validatedHostUrl;
	}

	/**
	 * @throws CurlException|HejBitException
	 */
	private function getLink(ApiEndpoints $endpoint): LinkDto {
		$endpoint = $this->api_url.$endpoint->value;
		$request = $this->createCurl($endpoint, headers: [
			'accept: application/json',
		], authorization: $this->access_key);
		$response = $request->get(true);

		if (!$request->isResponseSuccessful()) {
			throw new HejBitException('Failed to access HejBit: '.$response['message']);
		}

		return new LinkDto($response['url'], $response['token'], $response['method']);
	}

	/**
	 * @throws CurlException|HejBitException
	 */
	private function uploadSwarm(string $path, string $tempFile, string $mimetype): string {
		if ($this->isVersion()) {
			return $this->uploadSwarmV1($path, $tempFile, $mimetype);
		}

		$link = $this->getLink(ApiEndpoints::UPLOAD);
		$request = $this->createCurl($link->url, authorization: $link->token);
		$response = $request->post([
			'file' => new CURLFile($tempFile, $mimetype, basename($path)),
			'name' => basename($path),
		], true);

		if (!$request->isResponseSuccessful() || !isset($response['reference'])) {
			throw new HejBitException('Failed to upload file to HejBit: '.$response['message']);
		}

		return $response['reference'];
	}

	/**
	 * @return resource
	 *
	 * @throws CurlException|HejBitException
	 */
	private function downloadSwarm(string $reference) {
		if ($this->isVersion()) {
			return $this->downloadSwarmV1($reference);
		}

		$link = $this->getLink(ApiEndpoints::DOWNLOAD);
		$request = $this->createCurl($link->url."/{$reference}", authorization: $link->token);
		$response = $request->get();

		if (!$request->isResponseSuccessful()) {
			throw new HejBitException('Failed to download file from HejBit: '.$response['message']);
		}

		$stream = fopen('php://memory', 'r+');
		fwrite($stream, $response);
		rewind($stream);

		return $stream;
	}

	/**
	 * Returns the connection status of Swarm node.
	 *
	 * @throws CurlException|StorageNotAvailableException
	 */
	private function checkConnection(): bool {
		if ($this->isVersion()) {
			return $this->checkConnectionV1();
		}

		$endpoint = $this->api_url.ApiEndpoints::READINESS->value;

		$request = new Curl($endpoint, authorization: $this->access_key);
		$request->get();
		$statusCode = $request->getStatusCode();

		if (!$request->isResponseSuccessful()) {
			if (401 === $statusCode) {
				throw new StorageNotAvailableException('Invalid access key');
			}

			throw new StorageNotAvailableException('Failed to connect to HejBit');
		}

		if (204 !== $statusCode) {
			throw new StorageNotAvailableException('Failed to connect to HejBit');
		}

		return true;
	}

	/**
	 * @throws CurlException
	 */
	private function checkConnectionV1(): bool {
		$endpoint = $this->api_url.DIRECTORY_SEPARATOR.'readiness';

		$request = new Curl($endpoint);
		$request->setAuthorization($this->access_key, CURLAUTH_ANY);

		$output = $request->get();
		$statusCode = $request->getStatusCode();

		return 200 === $statusCode and 'OK' === $output;
	}

	/**
	 * @return resource
	 *
	 * @throws CurlException|HejBitException
	 */
	private function downloadSwarmV1(string $reference) {
		$endpoint = $this->api_url.DIRECTORY_SEPARATOR.'bzz'.DIRECTORY_SEPARATOR.$reference.DIRECTORY_SEPARATOR;

		$request = new Curl($endpoint, [
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_HEADER => false,
		], [
			'content-type: application/octet-stream',
		]);
		$request->setAuthorization($this->access_key, CURLAUTH_ANY);
		$response = $request->get();

		$httpCode = $request->getInfo(CURLINFO_HTTP_CODE);
		if (200 !== $httpCode) {
			throw new HejBitException('Failed to download file from HejBit');
		}

		$stream = fopen('php://memory', 'r+');
		fwrite($stream, $response);
		rewind($stream);

		return $stream;
	}

	/**
	 * @throws CurlException|HejBitException
	 */
	private function uploadSwarmV1(string $path, string $tempFile, string $mimetype): array|string {
		$endpoint = $this->api_url.DIRECTORY_SEPARATOR.'bzz';
		$params = '?name='.urlencode(basename($path));

		$request = new Curl($endpoint.$params, [
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
		$request->setAuthorization($this->access_key, CURLAUTH_ANY);

		$result = $request->exec(true);
		$reference = ($result['reference'] ?? null);

		if (!isset($reference)) {
			throw new HejBitException('Failed to upload file to HejBit: '.$result['message']);
		}

		return $reference;
	}
}
