<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023, MetaProvide Holding EKF
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

namespace OCA\Files_External_Ethswarm\Controller;

use OCP\AppFramework\Controller;
use OCP\IConfig;
use OCP\IRequest;
use OCA\Files_External_Ethswarm\Settings\Admin;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http;

class BeeController extends Controller {

	/** @var Admin */
	private $admin;

	/** @var string */
	protected $appName;

	/** @var IConfig */
	private $config;

	/**
	 * @param IConfig $config
	 * @param IRequest $request
	 */
	public function __construct(
		string $appName,
		IConfig $config,
		IRequest $request,
		Admin $admin,
	) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->admin = $admin;
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * Create a new postage batch stamp
	 * @return \DataResponse
	 */
	public function createPostageBatch(): DataResponse  {
		if ($this->request->getParam("postageBatch")) {
			$postageBatch = json_decode($this->request->getParam("postageBatch"), true);

			$response_data = $this->admin->buyPostageStamp($postageBatch["amount"],$postageBatch["depth"],$postageBatch["mount_urloptions"]);

			if (isset($response_data["batchID"])) {
				return new DataResponse(array('batchID' => $response_data["batchID"]), Http::STATUS_OK);
			} else if (isset($response_data["message"])) {
				return new DataResponse(array('msg' => $response_data["message"]), $response_data["code"]);
			}
		}
		return new DataResponse(array('msg' => "Error in request"), Http::STATUS_CONFLICT);
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * Top up an existing batch stamp based on the batchID
	 * @return \DataResponse
	 */
	public function topUpBatch(): DataResponse {
		if ($this->request->getParam("postageBatch")) {
			$postageBatch = json_decode($this->request->getParam("postageBatch"), true);

			$response_data = $this->admin->topUpPostageStamp($postageBatch["activeBatchId"],$postageBatch["topUpValue"],$postageBatch["mount_urloptions"]);
			if (isset($response_data["batchID"])) {
				return new DataResponse(array('batchID' => $response_data["batchID"]), Http::STATUS_OK);
			} else if (isset($response_data["message"])) {
				return new DataResponse(array('msg' => $response_data["message"]), $response_data["code"]);
			}
		}
		return new DataResponse(array('msg' => "Error in request"), Http::STATUS_CONFLICT);
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * Verfy Bee Node Access by checking MetaProvide nocodb database
	 * @return \DataResponse
	 */
	public function verifyBeeNodeAccess(): DataResponse {

		$access_key = $this->request->getParam("access_key");

		// Check if access key is empty
		if (empty($access_key)) {
			return new DataResponse(array('msg' => "Error in request"), Http::STATUS_CONFLICT);
		}

		// Verify access key
		$ch = curl_init();

		// Set the URL
		$endpoint = "https://nocodb.metaprovide.org/api/v1/db/data/v1/ethswarm-api-key-manger/access_keys/find-one";
		$query = 'where='. urlencode("(Key,eq," . $access_key . ")");
		$url = $endpoint . '?' . $query;


		// Set the necessary cURL options
		$api_token = $this->config->getSystemValue('swarm_access_api_token');

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			"accept: application/json",
			"xc-token: $api_token"
		]);

		// Execute the request and store the response
		$response = curl_exec($ch);
		$data = json_decode($response, true);

		// check if staus code is 400
		if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 400) {
			return new DataResponse(array('msg' => "Access Key not found"), Http::STATUS_UNAUTHORIZED);
		}

		// check if still valid by checking if ExpiresAt is less than current time
		if (strtotime($data["ExpiresAt"]) < time()) {
			return new DataResponse(array('msg' => "Access Key has expired"), Http::STATUS_UNAUTHORIZED);
		}


		// handle successful response
		// add verification count
		// update the row
		$row_id = $data["Id"];
		$endpoint = "https://nocodb.metaprovide.org/api/v1/db/data/v1/ethswarm-api-key-manger/access_keys/$row_id";

		$body = json_encode([
			"VerificationCount" => (int) json_decode($response, true)["VerificationCount"] + 1
		]); // data object

		// Set the necessary cURL options
		// PATCH
		curl_setopt($ch, CURLOPT_URL, $endpoint);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			"accept: application/json",
			"xc-token: $api_token",
			"Content-Type: application/json"
		]);

		// Execute the request and store the response
		$response = curl_exec($ch);

		// handle successful response
		// Close the cURL handle
		curl_close($ch);

		return new DataResponse(array('msg' => "Access key is valid"), Http::STATUS_OK);
	}
}
