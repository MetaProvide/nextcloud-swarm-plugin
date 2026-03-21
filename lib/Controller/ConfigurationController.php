<?php

declare(strict_types=1);

namespace OCA\Files_External_Ethswarm\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\RedirectResponse;
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
	 *
	 * @return JSONResponse|RedirectResponse JSON response with status and message, or redirect
	 */
	#[NoCSRFRequired]
	public function create(): JSONResponse|RedirectResponse {
		$params = $this->request->getParams();
		$key = trim($params['key'] ?? '');
		$folder = trim($params['folder'] ?? 'Hejbit-Storage');
		$hosturl = trim($params['hosturl'] ?? 'app.hejbit.com');

		try {
			$dataResponse = $this->storageController->create($folder, $key, $hosturl);

			// Extract status and message from DataResponse
			$data = $dataResponse->getData();
			$meta = $data['ocs']['meta'] ?? [];
			$status = $dataResponse->getStatus();

			// Redirect to NC external storage mounts page after creation
			$redirectUrl = $this->urlGenerator->getAbsoluteURL('/apps/files/extstoragemounts');

			// TODO: Determine how to send $dataResponse parameters to the caller or the redirect URL.
			// For now, send them as querystring parameters for demonstration/debug purposes.
			$redirectUrl .= '?status='.urlencode($meta['status'] ?? 'unknown').'&message='.urlencode($meta['message'] ?? '');

			return new RedirectResponse($redirectUrl);
			// To return a JSON response to the caller (instead of redirecting):
			/*return new JSONResponse([
				'status' => $meta['status'] ?? 'unknown',
				'message' => $meta['message'] ?? ''
			], $status);*/
		} catch (Throwable $e) {
			return new JSONResponse(['status' => 'error', 'message' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}
}
