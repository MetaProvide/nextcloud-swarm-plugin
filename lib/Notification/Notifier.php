<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024, MetaProvide Holding EKF
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

namespace OCA\Files_External_Ethswarm\Notification;

use InvalidArgumentException;
use OCA\Files_External_Ethswarm\AppInfo\AppConstants;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class Notifier implements INotifier {
	protected $factory;
	protected $url;

	public function __construct(
		IFactory $factory,
		IURLGenerator $urlGenerator
	) {
		$this->factory = $factory;
		$this->url = $urlGenerator;
	}

	/**
	 * Identifier of the notifier, only use [a-z0-9_].
	 */
	public function getID(): string {
		return AppConstants::APP_NAME;
	}

	/**
	 * Human-readable name describing the notifier.
	 */
	public function getName(): string {
		return $this->factory->get(AppConstants::APP_NAME)->t('Hejbit External Storage');
	}

	/**
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 */
	public function prepare(INotification $notification, string $languageCode): INotification {
		if (AppConstants::APP_NAME !== $notification->getApp()) {
			// Not my app => throw
			throw new InvalidArgumentException();
		}

		// Read the language from the notification
		$l = $this->factory->get(AppConstants::APP_NAME, $languageCode);

		// Deal with known subjects
		switch ($notification->getSubject()) {
			// Set the parsed subject, message and action labels
			case 'swarm-fileupload':
				// Set rich subject, see https://github.com/nextcloud/server/issues/1706 for more information
				// and https://github.com/nextcloud/server/blob/master/lib/public/RichObjectStrings/Definitions.php
				// for a list of defined objects and their parameters.
				$parameters = $notification->getSubjectParameters();
				$notification->setRichSubject(
					$l->t('Your file \'{filename}\' was decentralized.'),
					[
						'filename' => [
							'type' => 'file',
							'id' => '',
							'name' => basename($parameters['path']),
							'path' => $parameters['path'],
						],
					]
				);

				// Set the plain text subject automatically
				$this->setParsedSubjectFromRichSubject($notification);

				return $notification;

			default:
				// Unknown subject => Unknown notification => throw
				throw new InvalidArgumentException();
		}
	}

	/**
	 * This is a little helper function which automatically sets the simple parsed subject
	 * based on the rich subject you set. This is also the default behaviour of the API
	 * since Nextcloud 26, but in case you would like to return simpler or other strings,
	 * this function allows you to take over.
	 */
	protected function setParsedSubjectFromRichSubject(INotification $notification): void {
		$placeholders = $replacements = [];
		foreach ($notification->getRichSubjectParameters() as $placeholder => $parameter) {
			$placeholders[] = '{'.$placeholder.'}';
			if ('file' === $parameter['type']) {
				$replacements[] = $parameter['path'];
			} else {
				$replacements[] = $parameter['name'];
			}
		}

		$notification->setParsedSubject(str_replace($placeholders, $replacements, $notification->getRichSubject()));
	}
}
