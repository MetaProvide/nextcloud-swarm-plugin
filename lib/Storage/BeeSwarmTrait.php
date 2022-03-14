<?php
/**
 * @author
 *
 * @copyright Copyright (c) 2022
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files_External_BeeSwarm\Storage;

trait BeeSwarmTrait  {
	protected $ip;
	protected $port;
	protected $api_url;
	protected $debug_api_url;

	/** @var string */
	protected $id;

	/** @var array */
	protected $params;

	/** @var SwarmClient */
	protected $connection;

	protected function parseParams($params) {

		//\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\Storage\\BeeSwarmTrait.php-parseParams: ip=" .$params["ip"] . ";id=" . $this->id);

		if (isset($params['ip']) && isset($params['port'])) {
			$this->ip = $params['ip'];
			$this->port = $params['port'] ?? 1633;
			$this->api_url = $this->ip . ':' . $this->port;
			$this->debug_api_url = $this->ip . ':1635';
		} else {
			throw new \Exception('Creating ' . self::class . ' storage failed, required parameters not set for bee swarm');
		}
		$this->params = $params;
	}
	/**
	 * Returns the connection
	 *
	 * @return Swarm connected client
	 * @throws \Exception if connection could not be made
	 */
	public function getConnection() {
		if (!is_null($this->connection)) {
			return $this->connection;
		}
		return $this->connection;
	}

	/**
	 * initializes a curl handler
	 * @return \CurlHandle
	 */
	private function setCurl($url) {
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		//curl_setopt($ch, CURLOPT_HEADER, false);		// include header in output
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

	/*
	*/
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
			($this->isEncrypted ? 'Swarm-Encrypt: true' : '')
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
		//$cfile = curl_file_create($tmppath, 'image/jpeg','mytest');
		//$cfile = new CURLFile(@$input_file_path,'image/png','testpic');	// alternative
		// Assign POST data
		$post_data = array('file=' => $cfile);
		//$post_data = array('file_contents'=>'@'.$tmppath.';type=image/jpeg;');
		if ($post_data === false)
		{
			\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\Storage\\BeeSwarmTrait.php-upload_file: postdata=" . var_export($post_data, true));
		}
		else
		{
			\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\Storage\\BeeSwarmTrait.php-upload_file: postdata ok=" . var_export($post_data, true));
		}

		curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);

		\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\Storage\\BeeSwarmTrait.php-upload_file: postdata ok exec");

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

			$output = fopen($url_endpoint, 'r');

			if (!$output)
			{
			  	throw new \Exception("Unable to get file $path from swarm");
			}
		return $output;
	}
}
