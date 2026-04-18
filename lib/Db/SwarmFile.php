<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022, MetaProvide Holding EKF
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

namespace OCA\Files_External_Ethswarm\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method void        setEncryptionAuthTag(string $encryptionAuthTag)
 * @method string      getEncryptionAuthTag()
 * @method void        setStorage(int $storage)
 * @method null|int    getStorage()
 * @method void        setVisibility(int $visibility)
 * @method int         getVisibility()
 * @method void        setToken(string $token)
 * @method int         getToken()
 * @method void        setEncryptionVersion(int $encryptionVersion)
 * @method int         getEncryptionVersion()
 * @method void        setEncryptionNonce(?string $encryptionNonce)
 * @method null|string getEncryptionNonce()
 */
class SwarmFile extends Entity {
	/** @var string */
	protected $name;

	/** @var null|string */
	protected $swarmReference;

	/** @var null|string */
	protected $swarmTag;

	/** @var int */
	protected $mimetype;

	/** @var int */
	protected $size;

	/** @var int */
	protected $storageMtime;

	/** @var string */
	protected $encryptionAuthTag;

	/** @var null|int */
	protected $storage;

	/** @var int */
	protected $visibility;

	/** @var string */
	protected $token;

	/** @var int */
	protected $encryptionVersion = 0;

	/** @var null|string */
	protected $encryptionNonce;

	public function __construct() {
		$this->addType('name', 'string');
		$this->addType('swarmReference', 'string');
		$this->addType('swarmTag', 'string');
		$this->addType('mimetype', 'int');
		$this->addType('size', 'int');
		$this->addType('storageMtime', 'int');
		$this->addType('encryptionauthtag', 'string');
		$this->addType('storage', 'int');
		$this->addType('visibility', 'int');
		$this->addType('token', 'string');
		$this->addType('encryptionversion', 'integer');
		$this->addType('encryptionnonce', 'string');
	}

	/**
	 * Check if this file is encrypted.
	 */
	public function isEncrypted(): bool {
		return $this->encryptionVersion > 0;
	}
}
