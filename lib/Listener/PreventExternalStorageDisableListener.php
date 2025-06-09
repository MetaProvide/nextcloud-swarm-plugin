<?php

namespace OCA\Files_External_Ethswarm\Listener;

use Exception;
use OCP\App\Events\AppDisableEvent;
use OCP\App\IAppManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

class PreventExternalStorageDisableListener implements IEventListener {
	private IAppManager $appManager;
	private LoggerInterface $logger;

	public function __construct(
		IAppManager $appManager,
		LoggerInterface $logger
	) {
		$this->appManager = $appManager;
		$this->logger = $logger;
	}

	public function handle(Event $event): void {
		if (!$event instanceof AppDisableEvent) {
			return;
		}

		$appId = $event->getAppId();

		// Check if files_external is being disabled while our app is enabled
		if ('files_external' === $appId && $this->appManager->isEnabledForUser('files_external_ethswarm')) {
			$message = 'Cannot disable External Storage app while HejBit Swarm plugin is enabled. Please disable HejBit Swarm plugin first.';

			$this->logger->warning($message);

			// Throw an exception to prevent the disable and show error message
			throw new Exception($message);
		}
	}
}
