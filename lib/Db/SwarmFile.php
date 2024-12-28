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
 * @method void        setFileId(int $fileId)
 * @method int         getFileId()
 * @method void        setName(string $name)
 * @method string      getName()
 * @method void        setSwarmReference(string $swarmReference)
 * @method null|string getSwarmReference()
 * @method void        setSwarmTag(string $tag)
 * @method null|string getSwarmTag()
 * @method void        setMimetype(int $mimetype)
 * @method int         getMimetype()
 * @method void        setSize(int $size)
 * @method string      getSize()
 * @method void        setStorageMtime(int $mtime)
 * @method int         getStorageMtime()
 * @method void        setEncryptionKey(string $encryptionKey)
 * @method string      getEncryptionKey()
 * @method void        setStorage(int $storage)
 * @method null|int    getStorage()
 * @method void        setVisibility(int $visibility)
 * @method int         getVisibility()
 * @method void        setToken(string $token)
 * @method int         getToken()
 */
class SwarmFile extends Entity {
	/** @var null|int */
	protected $fileId;

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
	protected $encryptionKey;

	/** @var null|int */
	protected $storage;

	/** @var int */
	protected $visibility;

	/** @var string */
	protected $token;

	public function __construct() {
		$this->addType('fileId', 'int');
		$this->addType('name', 'string');
		$this->addType('swarmReference', 'string');
		$this->addType('swarmTag', 'string');
		$this->addType('mimetype', 'int');
		$this->addType('size', 'int');
		$this->addType('storageMtime', 'int');
		$this->addType('encryptionkey', 'string');
		$this->addType('storage', 'int');
		$this->addType('visibility', 'int');
		$this->addType('token', 'string');
	}
}
