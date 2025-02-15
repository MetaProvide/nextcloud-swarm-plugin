<?php

declare(strict_types=1);

namespace OCA\Files_External_Ethswarm\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use GuzzleHttp\Client;
use OCA\Files_External_Ethswarm\Traits\BeeswarmTrait;


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
     * @NoAdminRequired
     */
    public function submit(): JSONResponse {
        $feedbackData = $this->request->getParams();

        // Get current user email
        $user = $this->userSession->getUser();
        $userEmail = $user ? $user->getEMailAddress() : '';

        // Add email to feedback data
        $feedbackData['email'] = $userEmail;

        try {
            $response = $this->client->post('https://test.hejbit.com/api/feedback', [
                'json' => $feedbackData,
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ]);

            return new JSONResponse([
                'status' => 'success',
                'message' => 'Feedback submitted successfully'
            ], 200);

        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
