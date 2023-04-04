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

class AdminController extends Controller {

	/** @var Admin */
	private $adminmapper;

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
		$this->adminmapper = $admin;
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * Create a new postage batch stamp
	 */
	public function createPostageBatch(): DataResponse  {
		if ($this->request->getParam("postageBatch")) {
			$postageBatch = json_decode($this->request->getParam("postageBatch"), true);

			$response_data = $this->adminmapper->buyPostageStamp($postageBatch["amount"],$postageBatch["depth"],$postageBatch["mount_urloptions"]);
			if (isset($response_data["batchID"])) {
				new DataResponse(array('batchID' => $response_data["batchID"]), Http::STATUS_OK);
			} else if (isset($response_data["message"])) {
				return new DataResponse(array('msg' => $response_data["message"]), $response_data["code"]);
			}
		}
		return new DataResponse(array('msg' => "Error in request", Http::STATUS_CONFLICT));
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * Top up an existing batch
	 */
	public function topUpBatch(): void {
		/** To do */
	}
}
