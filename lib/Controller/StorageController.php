<?php

declare(strict_types=1);

namespace OCA\Files_External_Ethswarm\Controller;

use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\Service\GlobalStoragesService;
use OCA\Files_External_Ethswarm\AppInfo\Application;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class StoragesController extends OCSController {
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
     * Create a new Swarm external storage
     *
     * @param string $mountPoint The folder name/mount point (e.g., "/MySwarmStorage")
     * @param string $accessKey The Swarm access key
     * @param string $hostUrl The Access Server URL (e.g., "app.hejbit.com")
     * @return DataResponse
     */
    #[NoAdminRequired]
    public function create(
        string $mountPoint,
        string $accessKey,
        string $hostUrl
    ): DataResponse {
        // Validate required parameters
        if (empty($mountPoint)) {
            return new DataResponse([
                'ocs' => [
                    'meta' => [
                        'status' => 'failure',
                        'statuscode' => 400,
                        'message' => 'Mount point is required'
                    ]
                ]
            ], 400);
        }

        if (empty($accessKey)) {
            return new DataResponse([
                'ocs' => [
                    'meta' => [
                        'status' => 'failure',
                        'statuscode' => 400,
                        'message' => 'Access key is required'
                    ]
                ]
            ], 400);
        }

        if (empty($hostUrl)) {
            return new DataResponse([
                'ocs' => [
                    'meta' => [
                        'status' => 'failure',
                        'statuscode' => 400,
                        'message' => 'Access key is required'
                    ]
                ]
            ], 400);
        }

        // Ensure mount point starts with /
        if (strpos($mountPoint, '/') !== 0) {
            $mountPoint = '/' . $mountPoint;
        }

        try {
            // Set as personal storage (current user only)
            $user = $this->userSession->getUser();
			$user = [$user->getUID()];

			// Create StorageConfig
            $storageConfig = new StorageConfig();
			$storageConfig = $this->globalStoragesService->createStorage(
				$mountPoint,
				APPLICATION::NAME,
				'access:key',
				[
                'access_key' => $accessKey,
                'host_url' => $hostUrl ?: 'app.hejbit.com'
            	],
				null,
				$user
				);

            // Add the storage via the service
            $newStorage = $this->globalStoragesService->addStorage($storageConfig);

            $this->logger->info('Swarm storage created: ' . $mountPoint);

            return new DataResponse([
                'ocs' => [
                    'meta' => [
                        'status' => 'success',
                        'statuscode' => 201,
                        'message' => 'Storage created successfully'
                    ]
                ],
                'data' => $newStorage->jsonSerialize(true)
            ], 201);

        } catch (\Exception $e) {
            $this->logger->error('Failed to create storage: ' . $e->getMessage());

            return new DataResponse([
                'ocs' => [
                    'meta' => [
                        'status' => 'failure',
                        'statuscode' => 500,
                        'message' => $e->getMessage()
                    ]
                ]
            ], 500);
        }
    }
}
