<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2025, MetaProvide Holding EKF
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

namespace OCA\Files_External_Ethswarm\Event;

use OCP\EventDispatcher\Event;

/**
 * Class FileUploadedEvent.
 *
 * Event dispatched when a file is successfully uploaded to Swarm
 */
class FileUploadedEvent extends Event {
	private string $filename;
	private string $mimetype;
	private string $path;

	public function __construct(string $filename, string $mimetype, string $path) {
		parent::__construct();
		$this->filename = $filename;
		$this->mimetype = $mimetype;
		$this->path = $path;
	}

	public function getFilename(): string {
		return $this->filename;
	}

	public function getMimetype(): string {
		return $this->mimetype;
	}

	public function getPath(): string {
		return $this->path;
	}
}
