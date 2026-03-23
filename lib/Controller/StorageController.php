<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022, MetaProvide Holding EKF
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

use Exception;
use OCA\Files_External\Service\GlobalStoragesService;
use OCA\Files_External_Ethswarm\AppInfo\Application;
use OCA\Files_External_Ethswarm\Auth\AccessKey;
use OCA\Files_External_Ethswarm\Backend\BeeSwarm;
use OCA\Files_External_Ethswarm\Utils\HostUrl;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class StorageController extends OCSController {
	private GlobalStoragesService $globalStoragesService;
	private IUserSession $userSession;
	private LoggerInterface $logger;

	public function __construct(
		string $appName,
		IRequest $request,
		GlobalStoragesService $globalStoragesService,
		IUserSession $userSession,
		LoggerInterface $logger
	) {
		parent::__construct($appName, $request);
		$this->globalStoragesService = $globalStoragesService;
		$this->userSession = $userSession;
		$this->logger = $logger;
	}

	/**
	 * Create a new Hejbit Swarm external storage.
	 *
	 * @param string $folderName The folder name/mount point for the storage
	 * @param string $accessKey  The Hejbit access key for authentication
	 * @param string $hostUrl    The Access Server URL (e.g., "app.hejbit.com")
	 *
	 * @return DataResponse<Http::STATUS_CREATED, array{ocs: array{meta: array{status: string, statuscode: int, message: string}, data: array{id: int, mountPoint: string, backend: string}}}, array{}>
	 * @return DataResponse<Http::STATUS_BAD_REQUEST, array{ocs: array{meta: array{status: string, statuscode: int, message: string}, data: array<string, mixed>}}, array{}>
	 * @return DataResponse<Http::STATUS_UNAUTHORIZED, array{ocs: array{meta: array{status: string, statuscode: int, message: string}, data: array<string, mixed>}}, array{}>
	 * @return DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR, array{ocs: array{meta: array{status: string, statuscode: int, message: string}, data: array<string, mixed>}}, array{}>
	 *
	 * 201: Storage created successfully
	 * 400: Bad request (missing parameters or invalid URL)
	 * 401: Unauthorized (user not authenticated)
	 * 500: Internal server error (failed to create storage)
	 */
	#[NoAdminRequired]
	public function create(
		string $folderName,
		string $accessKey,
		string $hostUrl
	): DataResponse {
		// Validate required parameters
		$validationError = $this->validateParameters($folderName, $accessKey, $hostUrl);
		if (null !== $validationError) {
			return $validationError;
		}

		// Validate host URL format
		$validatedHost = HostUrl::normalize($hostUrl);
		if (null === $validatedHost) {
			return $this->errorResponse('Invalid host URL format', 400);
		}

		// Ensure mount point starts with /
		$mountPoint = '/'.ltrim($folderName, '/');

		try {
			// Get the current user
			$user = $this->userSession->getUser();
			if (null === $user) {
				return $this->errorResponse('User not authenticated', 401);
			}

			// Create storage using GlobalStoragesService
			$storageConfig = $this->globalStoragesService->createStorage(
				$mountPoint,
				Application::NAME,
				AccessKey::IDENTIFIER,
				[
					BeeSwarm::OPTION_HOST_URL => $validatedHost,
					AccessKey::SCHEME => $accessKey,
				],
				null,
				[], // Empty array = all users
			);

			// Add the storage via the service
			$newStorage = $this->globalStoragesService->addStorage($storageConfig);

			$this->logger->info('Swarm storage created successfully: '.$mountPoint.' for user: '.$user->getUID());

			return $this->successResponse([
				'id' => $newStorage->getId(),
				'mountPoint' => $newStorage->getMountPoint(),
				'backend' => Application::NAME,
			]);
		} catch (Exception $e) {
			$this->logger->error('Failed to create Swarm storage: '.$e->getMessage(), [
				'exception' => $e,
				'folderName' => $folderName,
				'hostUrl' => $validatedHost,
			]);

			return $this->errorResponse('Failed to create storage: '.$e->getMessage(), 500);
		}
	}

	/**
	 * Validate required parameters.
	 */
	private function validateParameters(string $folderName, string $accessKey, string $hostUrl): ?DataResponse {
		if (empty($folderName)) {
			return $this->errorResponse('Folder name is required', 400);
		}
		if (empty($accessKey)) {
			return $this->errorResponse('Access key is required', 400);
		}
		if (empty($hostUrl)) {
			return $this->errorResponse('Host URL is required', 400);
		}

		return null;
	}

	/**
	 * Create a success response.
	 */
	private function successResponse(array $data): DataResponse {
		return new DataResponse([
			'ocs' => [
				'meta' => [
					'status' => 'success',
					'statuscode' => 201,
					'message' => 'Storage created successfully',
				],
				'data' => $data,
			],
		], 201);
	}

	/**
	 * Create an error response.
	 */
	private function errorResponse(string $message, int $statusCode): DataResponse {
		return new DataResponse([
			'ocs' => [
				'meta' => [
					'status' => 'failure',
					'statuscode' => $statusCode,
					'message' => $message,
				],
				'data' => [],
			],
		], $statusCode);
	}
}
