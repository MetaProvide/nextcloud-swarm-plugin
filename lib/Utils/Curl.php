<?php

namespace OCA\Files_External_Ethswarm\Utils;

use CurlHandle;
use Safe\Exceptions\CurlException;

class Curl {
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
	protected int $authorizationType = CURLAUTH_NONE;

	public function __construct(string $url, array $options = [], array $headers = [], ?string $authorization = null) {
		$this->url = $url;
		$this->options = $options + self::getDefaultOptions();
		$this->headers = $headers;
		$this->setAuthorization($authorization);

		$this->init();
	}

	/**
	 * set authorization.
	 */
	public function setAuthorization(?string $authorization, int $authorizationType = CURLAUTH_BEARER): void {
		$this->authorization = $authorization;
		if (!$authorization) {
			$this->authorizationType = CURLAUTH_NONE;
		} else {
			$this->authorizationType = $authorizationType;
		}
	}

	/**
	 * execute curl request.
	 *
	 * @throws CurlException
	 */
	public function exec(bool $array = false): array|string {
		$this->setOptions();
		$this->setHeaders();
		$response = curl_exec($this->handler);
		$this->checkResponse();

		return $array ? json_decode($response, true) : $response;
	}

	/**
	 * get curl info.
	 */
	public function getInfo(?int $option = null): mixed {
		return curl_getinfo($this->handler, $option);
	}

	/**
	 * @throws CurlException
	 */
	public function isResponseSuccessful(): bool {
		if (0 === $this->getStatusCode()) {
			throw new CurlException('Curl handler has not been executed');
		}

		return $this->getStatusCode() < 400;
	}

	private static function getDefaultOptions(): array {
		return self::checkSSLOption() + self::DEFAULT_OPTIONS;
	}

	/**
	 * @return bool[]
	 */
	private static function checkSSLOption(): array {
		return Env::isDevelopment() ? [
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false,
		] : [];
	}

	/**
	 * initializes a curl handler.
	 */
	private function init(): void {
		$this->handler = curl_init();
		curl_setopt($this->handler, CURLOPT_URL, $this->url);
	}

	/**
	 * set curl options.
	 */
	private function setOptions(array $options = []): void {
		$options = self::getDefaultOptions() + $this->options + $options;
		curl_setopt_array($this->handler, $options);
	}

	private function setHeaders(array $headers = []): void {
		$headers = $this->headers + $headers;
		if ($this->authorization) {
			$headers[] = match ($this->authorizationType) {
				CURLAUTH_BEARER => 'Authorization: Bearer '.$this->authorization,
				default => 'Authorization: '.$this->authorization
			};
		}
		curl_setopt($this->handler, CURLOPT_HTTPHEADER, $headers);
	}

	/**
	 * check response results for error.
	 *
	 * @throws CurlException
	 */
	private function checkResponse(): void {
		if (0 !== curl_errno($this->handler)) {
			curl_close($this->handler);

			throw new CurlException(curl_error($this->handler));
		}
	}

	public function getStatusCode(): int {
		return $this->getInfo(CURLINFO_HTTP_CODE);
	}
}
