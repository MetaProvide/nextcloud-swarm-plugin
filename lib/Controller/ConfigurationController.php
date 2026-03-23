<?php

declare(strict_types=1);

namespace OCA\Files_External_Ethswarm\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use Throwable;

class ConfigurationController extends Controller {
	private StorageController $storageController;
	private IURLGenerator $urlGenerator;

	public function __construct(
		string $appName,
		IRequest $request,
		StorageController $storageController,
		IURLGenerator $urlGenerator
	) {
		parent::__construct($appName, $request);
		$this->storageController = $storageController;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * Create a storage definition by delegating to StorageController::create().
	 */
	#[NoCSRFRequired]
	public function create(): RedirectResponse|TemplateResponse {
		$params = $this->request->getParams();
		$accessKey = trim((string) ($params['accessKey'] ?? ''));
		$folderName = trim((string) ($params['folderName'] ?? 'Hejbit-Storage'));
		$hostUrl = trim((string) ($params['hostUrl'] ?? 'app.hejbit.com'));

		try {
			$dataResponse = $this->storageController->create($folderName, $accessKey, $hostUrl);

			// Extract status and message from DataResponse
			$data = $dataResponse->getData();
			$meta = $data['ocs']['meta'] ?? [];
			$status = $dataResponse->getStatus();
			$isFailure = Http::STATUS_CREATED !== $status || 'success' !== ($meta['status'] ?? '');

			if ($isFailure) {
				return $this->buildErrorResponse((string) ($meta['message'] ?? 'Failed to create storage'), $status);
			}

			// Redirect to NC external storage mounts page after creation
			$redirectUrl = $this->urlGenerator->getAbsoluteURL('/apps/files/extstoragemounts');

			return new RedirectResponse($redirectUrl);
		} catch (Throwable $e) {
			return $this->buildErrorResponse($e->getMessage(), Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	private function buildErrorResponse(string $message, int $statusCode): TemplateResponse {
		$response = new TemplateResponse('core', 'error', [
			'errors' => [
				['error' => $message],
			],
		], 'error');

		$response->setStatus($statusCode);

		return $response;
	}
}
