<?php

namespace OCA\Files_External_Ethswarm\Utils;

use CurlHandle;
use Safe\Exceptions\CurlException;

class Curl
{
	private const DEFAULT_OPTIONS = [
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_RETURNTRANSFER => true,
	];

	protected CurlHandle $handler;
	protected string $url;
	protected array $options = [];
	protected array $headers = [];
	protected ?string $authorization = null;

	/**
	 * @param string $url
	 * @param array $options
	 * @param array $headers
	 * @param string|null $authorization
	 */
	public function __construct(string $url, array $options = [], array $headers = [], ?string $authorization = null)
	{
		$this->url = $url;
		$this->options = $options + self::DEFAULT_OPTIONS;
		$this->headers = $headers;
		$this->authorization = $authorization;

		$this->init();
	}

	/**
	 * initializes a curl handler
	 * @return void
	 */
	private function init(): void
	{
		$this->handler = curl_init();
		curl_setopt($this->handler, CURLOPT_URL, $this->url);
	}

	/**
	 * set curl options
	 *
	 * @param array $options
	 * @return void
	 */
	private function setOptions(array $options = []): void
	{
		$options = self::DEFAULT_OPTIONS + $this->options + $options;
		curl_setopt_array($this->handler, $options);
	}

	/**
	 * @param array $headers
	 * @return void
	 */
	private function setHeaders(array $headers = []): void
	{
		$headers = $this->headers + $headers;
		if ($this->authorization) {
			$headers[] = 'Authorization: ' . $this->authorization;
		}
		curl_setopt($this->handler, CURLOPT_HTTPHEADER, $headers);
	}

	/**
	 * set authorization
	 *
	 * @param string $authorization
	 * @return void
	 */
	public function setAuthorization(string $authorization): void
	{
		$this->authorization = $authorization;
	}

	/**
	 * execute curl request
	 *
	 * @param bool $array
	 * @return string|array
	 * @throws CurlException
	 */
	public function exec(bool $array = false): string|array
	{
		$this->setOptions();
		$this->setHeaders();
		$response = curl_exec($this->handler);
		$this->checkResponse();
		return $array ? json_decode($response, true) : $response;
	}

	/**
	 * get curl info
	 *
	 * @param int|null $option
	 * @return mixed
	 */
	public function getInfo(?int $option = null): mixed
	{
		return curl_getinfo($this->handler, $option);
	}

	/**
	 * check response results for error
	 *
	 * @return void
	 * @throws CurlException
	 */
	private function checkResponse(): void
	{
		if (curl_errno($this->handler) !== 0) {
			curl_close($this->handler);
			throw new CurlException(curl_error($this->handler));
		}
	}
}
