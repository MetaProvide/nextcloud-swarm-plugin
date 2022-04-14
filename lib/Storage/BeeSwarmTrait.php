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
namespace OCA\Files_External_BeeSwarm\Storage;

trait BeeSwarmTrait  {

	/** @var string */
	protected $ip;

	/** @var string */
	protected $port;

	/** @var string */
	protected $api_url;

	/** @var string */
	protected $debug_api_url;

	/** @var bool */
	protected $isBasicAuth;

	/** @var string */
	protected $username;

	/** @var string */
	protected $password;

	/** @var string */
	protected $id;

	private static string $CONNECTIONSTATUS = "Ethereum Swarm Bee";

	protected function parseParams($params) {
		if (isset($params['ip']) && isset($params['port'])) {
			$this->ip = $params['ip'];
			$this->port = $params['port'];
			$this->api_url = $this->ip . ':' . $this->port;
			$this->debug_api_url = $this->ip . ':' . $params['debug_api_port'];
		} else {
			throw new \Exception('Creating ' . self::class . ' storage failed, required parameters not set for bee swarm');
		}

		if (!empty($params['user']) && !empty($params['password'])) {
			$this->isBasicAuth = true;
			$this->username = $params['user'];
			$this->password = $params['password'];
		}
	}

	/**
	 * initializes a curl handler
	 * @return \CurlHandle
	 */
	private function setCurl($url) {
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		return $ch;
	}

	/**
	 * tests whether a curl operation ran successfully. If not, an exception
	 * is thrown
	 *
	 * @param \CurlHandle $ch
	 * @param mixed $result
	 * @throws \Exception
	 */
	private function checkCurlResult($ch, $result) {
		if ($result === false) {
			$error = curl_error($ch);
			curl_close($ch);
			throw new \Exception($error);
		}
	}

	protected function addBasicAuthHeaders(string $user, string $password): string {
		$base64EncodedAuth = base64_encode($user . ':' . $password);
		$header = 'Authorization: Basic ' . $base64EncodedAuth;
		return $header;
	}

	private function upload_stream(string $path, $stream, string $tmpfile, string $mimetype, int $file_size = null) {
		$url_endpoint = $this->api_url . '/bzz';
		$url_params = "?name=" . urlencode(basename($path));

		$url_endpoint .= $url_params;
		$curl = $this->setCurl($url_endpoint);

		$fh = fopen($tmpfile, 'r');
		if ($fh === false || !is_resource($fh))
		{
			throw new \Exception("Failed to open temporary file $tmpfile");
		}

		curl_setopt($curl, CURLOPT_PUT, true);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_INFILE, $fh);
		curl_setopt($curl, CURLOPT_INFILESIZE, $file_size);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($curl, CURLOPT_VERBOSE, true);

		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'swarm-postage-batch-id: ' . $this->stampBatchId,
			'Content-Type: ' . $mimetype,
			($this->isEncrypted ? 'Swarm-Encrypt: true' : ''),
			($this->isBasicAuth ? $this->addBasicAuthHeaders($this->username, $this->password) : '')
		));

		$output = curl_exec($curl);
		$this->checkCurlResult($curl, $output);
		$response_data = json_decode($output, true);
		curl_close($curl);
		return $response_data;
	}

	private function upload_file(string $uploadpath, string $tmppath, int $file_size = null) {
		$url_endpoint = $this->api_url . '/bzz';
		$url_endpoint .= "?name=logo_meta.png"; // . basename($uploadpath);
		$curl = $this->setCurl($url_endpoint);

		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'swarm-postage-batch-id: ' . $this->stampBatchId,
			'Content-Type: application/octet-stream',	// this is necessary, otherwise produces server error 500: "could not store directory". File can then be Open or Save in browser.
			($this->isEncrypted ? 'Swarm-Encrypt: true' : '')
			 ));
		curl_setopt($curl, CURLOPT_POST, true);

		// Create a CURLFile object
		$cfile = curl_file_create($tmppath); //,'image/jpeg','mytest');
		// Assign POST data
		$post_data = array('file=' => $cfile);

		curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);

		$output = curl_exec($curl);

		$this->checkCurlResult($curl, $output);
		$response_data = json_decode($output, true);
		curl_close($curl);
		return $response_data;
	}

	private function get_stream_by_curl(string $path, string $reference) {
		$url_endpoint = $this->api_url . '/bzz/';
		$url_params = $reference;
		$url_endpoint .= $url_params;

		$curl = $this->setCurl($url_endpoint);

		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
		  	'Content-Type: application/octet-stream',
		  	 ));

		curl_setopt($curl, CURLOPT_HEADER, true);
		$output = curl_exec($curl);
		$this->checkCurlResult($curl, $output);
		curl_close($curl);
		return $output;
	}

	private function get_stream(string $path, string $reference) {
		$url_endpoint = $this->api_url . '/bzz/';
		$url_params = $reference;
		$url_endpoint .= $url_params;

		$context = null;
		if ($this->isBasicAuth) {
			$hdr = $this->addBasicAuthHeaders($this->username, $this->password);
			$opts = array(
				'http'=>array(
					'method'=>"GET",
					'header'=>$hdr
				)
				);
				$context = stream_context_create($opts);
		}
		$output = fopen($url_endpoint, 'r', false, $context);

		if (!$output)
		{
			fclose($output);
			throw new \Exception("Unable to get file $path from swarm");
		}
		return $output;
	}

	/**
	 * Returns the connection status of Swarm node
	 *
	 * @return bool
	 * @throws \Exception if connection could not be made
	 */
	private function getConnection() {
		$url_endpoint = $this->api_url;

		$curl = $this->setCurl($url_endpoint);

		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			($this->isBasicAuth ? $this->addBasicAuthHeaders($this->username, $this->password) : '')
		));
		$output = curl_exec($curl);
		$this->checkCurlResult($curl, $output);
		curl_close($curl);

		if (trim($output) === self::$CONNECTIONSTATUS)
		{
			return true;
		}
		return false;
	}
}
