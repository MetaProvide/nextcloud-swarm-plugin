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
use OCA\Files_External\Lib\InsufficientDataForMeaningfulAnswerException;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\MountConfig;
use OCA\Files_External\Service\GlobalStoragesService;
use OCA\Files_External_Ethswarm\AppInfo\Application;
use OCA\Files_External_Ethswarm\Auth\AccessKey;
use OCA\Files_External_Ethswarm\Backend\BeeSwarm;
use OCA\Files_External_Ethswarm\Utils\HostUrl;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\Files\StorageNotAvailableException;
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
		$validationErrors = $this->validateParameters($folderName, $accessKey, $hostUrl);
		if (!empty($validationErrors)) {
			return $this->validationErrorResponse($validationErrors);
		}

		// Validate host URL format
		$validatedHost = HostUrl::normalize($hostUrl);
		if (null === $validatedHost) {
			return $this->validationErrorResponse([
				'hostUrl' => 'Invalid host URL format',
			]);
		}

		// Ensure mount point starts with /
		$mountPoint = '/'.ltrim($folderName, '/');
		$mountPoint = $this->resolveUniqueMountPoint($mountPoint);

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

			$connectionValidationError = $this->validateStorageConnection($storageConfig);
			if (null !== $connectionValidationError) {
				return $this->errorResponse(
					'Failed to connect to external storage: '.$connectionValidationError,
					400,
					[
						'errors' => [
							'connection' => $connectionValidationError,
						],
					]
				);
			}

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
	 *
	 * @return array<string, string>
	 */
	private function validateParameters(string $folderName, string $accessKey, string $hostUrl): array {
		$errors = [];

		if (empty(trim($folderName))) {
			$errors['folderName'] = 'Folder name is required';
		}

		if (empty(trim($accessKey))) {
			$errors['accessKey'] = 'Access key is required';
		}

		if (empty(trim($hostUrl))) {
			$errors['hostUrl'] = 'Host URL is required';
		}

		return $errors;
	}

	/**
	 * Create a validation error response with an errors bag.
	 *
	 * @param array<string, string> $errors
	 */
	private function validationErrorResponse(array $errors): DataResponse {
		return $this->errorResponse(
			implode('; ', array_values($errors)),
			400,
			['errors' => $errors]
		);
	}

	/**
	 * Resolve a unique mount point by appending a numeric suffix when needed.
	 */
	private function resolveUniqueMountPoint(string $mountPoint): string {
		$existingMountPoints = [];
		foreach ($this->globalStoragesService->getAllGlobalStorages() as $storage) {
			$existingMountPoints[strtolower($storage->getMountPoint())] = true;
		}

		if (!isset($existingMountPoints[strtolower($mountPoint)])) {
			return $mountPoint;
		}

		$baseMountPoint = $mountPoint;
		$suffix = 1;
		do {
			$candidateMountPoint = $baseMountPoint.'-'.$suffix;
			++$suffix;
		} while (isset($existingMountPoints[strtolower($candidateMountPoint)]));

		return $candidateMountPoint;
	}

	/**
	 * Validate storage connectivity before persisting config.
	 */
	private function validateStorageConnection(StorageConfig $storageConfig): ?string {
		try {
			$authMechanism = $storageConfig->getAuthMechanism();
			$authMechanism->manipulateStorageConfig($storageConfig);

			$backend = $storageConfig->getBackend();
			$backend->manipulateStorageConfig($storageConfig);

			$status = MountConfig::getBackendStatus(
				$backend->getStorageClass(),
				$storageConfig->getBackendOptions(),
			);

			if (StorageNotAvailableException::STATUS_SUCCESS !== $status) {
				return StorageNotAvailableException::getStateCodeName($status);
			}
		} catch (InsufficientDataForMeaningfulAnswerException $e) {
			return 'Insufficient data: '.$e->getMessage();
		} catch (StorageNotAvailableException $e) {
			return $e->getMessage();
		} catch (Exception $e) {
			return $e->getMessage();
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
	private function errorResponse(string $message, int $statusCode, array $data = []): DataResponse {
		return new DataResponse([
			'ocs' => [
				'meta' => [
					'status' => 'failure',
					'statuscode' => $statusCode,
					'message' => $message,
				],
				'data' => $data,
			],
		], $statusCode);
	}
}
