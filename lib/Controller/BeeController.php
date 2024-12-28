<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023, MetaProvide Holding EKF
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

namespace OCA\Files_External_Ethswarm\Controller;

use OCA\Files_External_Ethswarm\Settings\Admin;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\IRequest;

class BeeController extends Controller {
	/** @var string */
	protected $appName;

	/** @var Admin */
	private $admin;

	/** @var IConfig */
	private $config;

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
	 *
	 * @NoAdminRequired
	 * Create a new postage batch stamp
	 *
	 * @return \DataResponse
	 */
	public function createPostageBatch(): DataResponse {
		if ($this->request->getParam('postageBatch')) {
			$postageBatch = json_decode($this->request->getParam('postageBatch'), true);

			$response_data = $this->admin->buyPostageStamp($postageBatch['amount'], $postageBatch['depth'], $postageBatch['mount_urloptions']);

			if (isset($response_data['batchID'])) {
				return new DataResponse(['batchID' => $response_data['batchID']], Http::STATUS_OK);
			}
			if (isset($response_data['message'])) {
				return new DataResponse(['msg' => $response_data['message']], $response_data['code']);
			}
		}

		return new DataResponse(['msg' => 'Error in request'], Http::STATUS_CONFLICT);
	}

	/**
	 * @NoCSRFRequired
	 *
	 * @NoAdminRequired
	 * Top up an existing batch stamp based on the batchID
	 *
	 * @return \DataResponse
	 */
	public function topUpBatch(): DataResponse {
		if ($this->request->getParam('postageBatch')) {
			$postageBatch = json_decode($this->request->getParam('postageBatch'), true);

			$response_data = $this->admin->topUpPostageStamp($postageBatch['activeBatchId'], $postageBatch['topUpValue'], $postageBatch['mount_urloptions']);
			if (isset($response_data['batchID'])) {
				return new DataResponse(['batchID' => $response_data['batchID']], Http::STATUS_OK);
			}
			if (isset($response_data['message'])) {
				return new DataResponse(['msg' => $response_data['message']], $response_data['code']);
			}
		}

		return new DataResponse(['msg' => 'Error in request'], Http::STATUS_CONFLICT);
	}
}
