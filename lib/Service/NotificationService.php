<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2024, MetaProvide Holding EKF
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

namespace OCA\Files_External_Ethswarm\Service;

use OCA\Files_External_Ethswarm\AppInfo\AppConstants;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Notification\IManager;

class NotificationService {
	private $notificationManager;
	private $userManager;
	private $userSession;

	public function __construct(IManager $notificationManager, IUserManager $userManager, IUserSession $userSession) {
		$this->notificationManager = $notificationManager;
		$this->userManager = $userManager;
		$this->userSession = $userSession;
	}

	public function sendTemporaryNotification($subject, $path) {
		// Create a notification
		$notification = $this->notificationManager->createNotification();
		$notification->setApp(AppConstants::APP_NAME);
		$userId = $this->userSession->getUser()->getUID();
		$notification->setUser($userId);
		$notification->setSubject($subject, ['path' => $path]);
		$notification->setObject('temporary', $userId); // Marks the notification as temporary
		$notification->setDateTime(new \DateTime());

		// Send the notification
		$this->notificationManager->notify($notification);
	}
}
