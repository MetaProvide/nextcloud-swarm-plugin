<?php

declare(strict_types=1);

namespace OCA\Files_External_Ethswarm\Controller;

use Exception;
use GuzzleHttp\Client;
use OCA\Files_External_Ethswarm\AppInfo\AppConstants;
use OCA\Files_External_Ethswarm\Exception\HejBitException;
use OCA\Files_External_Ethswarm\Utils\Curl;
use OCA\Files_External_Ethswarm\Utils\Env;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

class FeedbackController extends Controller {
	/** @var string */
	protected $appName;

	/** @var Client */
	private $client;

	/** @var IUserSession */
	private $userSession;

	public function __construct(
		string $appName,
		IRequest $request,
		IUserSession $userSession
	) {
		parent::__construct($appName, $request);
		$this->client = new Client();
		$this->userSession = $userSession;
	}

	/**
	 * @NoCSRFRequired
	 *
	 * @NoAdminRequired
	 */
	public function submit(): JSONResponse {
		$feedbackData = $this->request->getParams();
		$feedbackData['type'] = $feedbackData['feedbackType'];
		$feedbackData['email'] = $this->userSession->getUser()?->getEMailAddress();
		$feedbackEndpoint = (Env::get('API_URL') ?? AppConstants::API_URL).'/api/feedback';

		try {
			$request = new Curl($feedbackEndpoint);
			$request->post($feedbackData);

			if (!$request->isResponseSuccessful()) {
				throw new HejBitException('Failed to submit feedback', $request->getStatusCode());
			}

			return new JSONResponse([
				'status' => 'success',
				'message' => 'Feedback submitted successfully',
			], 200);
		} catch (Exception $e) {
			return new JSONResponse([
				'status' => 'error',
				'message' => $e->getMessage(),
			], 500);
		}
	}
}
